#!/usr/bin/env python3
"""
alfred-pulse-music-cache — P2P Music Cache Daemon for Alfred Pulse Mesh Network

Caches audio tracks played through SoundStudioPro and advertises them
to neighboring Yggdrasil mesh peers. When the internet is down, serves
cached tracks to other Alfred Linux nodes on the mesh.

Listens on port 7710 for:
  - POST /cache   — cache a new track (from browser hook)
  - GET  /track/<hash>  — stream a cached track
  - GET  /catalog  — list all cached track hashes
  - GET  /health   — health check

Mesh discovery:
  - Broadcasts catalog to Yggdrasil multicast group every 60s
  - Listens for peer catalogs and maintains a peer registry
  - When a track is requested but not cached locally, queries nearest peer
"""

import os
import sys
import json
import hashlib
import time
import threading
import socket
import struct
import signal
from http.server import HTTPServer, BaseHTTPRequestHandler
from pathlib import Path
from datetime import datetime, timezone
from urllib.request import urlopen, Request
from urllib.error import URLError

# === Configuration ===
CACHE_DIR = Path("/var/cache/alfred-pulse/music")
CATALOG_FILE = CACHE_DIR / "catalog.json"
PEER_REGISTRY = CACHE_DIR / "peers.json"
LOG_FILE = Path("/var/log/alfred-pulse-music.log")
LISTEN_PORT = 7710
MESH_MULTICAST_GROUP = "ff02::1"  # link-local all-nodes for Yggdrasil
MESH_MULTICAST_PORT = 7711
BROADCAST_INTERVAL = 60  # seconds
MAX_CACHE_SIZE_GB = 10  # max cache size in GB

# === Logging ===
def log(msg: str):
    ts = datetime.now(timezone.utc).isoformat()
    line = f"[{ts}] {msg}"
    print(line, flush=True)
    try:
        with open(LOG_FILE, "a") as f:
            f.write(line + "\n")
    except Exception:
        pass

# === Catalog Management ===
class MusicCatalog:
    def __init__(self):
        self.catalog = {}  # hash -> {title, artist, size, cached_at, file_path}
        self.peers = {}    # peer_ip -> {catalog: [...], last_seen: ...}
        self._load()

    def _load(self):
        if CATALOG_FILE.exists():
            try:
                self.catalog = json.loads(CATALOG_FILE.read_text())
            except Exception:
                self.catalog = {}
        if PEER_REGISTRY.exists():
            try:
                self.peers = json.loads(PEER_REGISTRY.read_text())
            except Exception:
                self.peers = {}

    def _save(self):
        CATALOG_FILE.write_text(json.dumps(self.catalog, indent=2))
        PEER_REGISTRY.write_text(json.dumps(self.peers, indent=2))

    def add_track(self, audio_data: bytes, title: str = "", artist: str = "", track_id: str = "") -> str:
        """Cache a track and return its content hash."""
        content_hash = hashlib.sha256(audio_data).hexdigest()[:16]
        file_path = CACHE_DIR / f"{content_hash}.opus"

        # Don't re-cache if we already have it
        if content_hash in self.catalog and file_path.exists():
            log(f"Track {content_hash} already cached, skipping")
            return content_hash

        # Enforce cache size limit
        self._enforce_size_limit()

        file_path.write_bytes(audio_data)
        self.catalog[content_hash] = {
            "title": title,
            "artist": artist,
            "track_id": track_id,
            "size": len(audio_data),
            "cached_at": datetime.now(timezone.utc).isoformat(),
            "file_path": str(file_path),
        }
        self._save()
        log(f"Cached track: {title} by {artist} [{content_hash}] ({len(audio_data)} bytes)")
        return content_hash

    def get_track(self, content_hash: str) -> bytes | None:
        """Retrieve a cached track by hash."""
        if content_hash in self.catalog:
            fp = Path(self.catalog[content_hash]["file_path"])
            if fp.exists():
                return fp.read_bytes()
        return None

    def get_catalog_hashes(self) -> list:
        return list(self.catalog.keys())

    def get_catalog_full(self) -> dict:
        return self.catalog

    def update_peer(self, peer_ip: str, peer_catalog: list):
        self.peers[peer_ip] = {
            "catalog": peer_catalog,
            "last_seen": datetime.now(timezone.utc).isoformat(),
        }
        self._save()

    def find_peer_with_track(self, content_hash: str) -> str | None:
        """Find the nearest peer that has a given track."""
        for peer_ip, info in self.peers.items():
            if content_hash in info.get("catalog", []):
                return peer_ip
        return None

    def _enforce_size_limit(self):
        """Evict oldest tracks if cache exceeds size limit."""
        total = sum(v["size"] for v in self.catalog.values())
        limit = MAX_CACHE_SIZE_GB * 1024 * 1024 * 1024
        if total < limit:
            return

        # Sort by cached_at ascending (oldest first)
        sorted_tracks = sorted(self.catalog.items(), key=lambda x: x[1]["cached_at"])
        while total > limit * 0.8 and sorted_tracks:  # evict down to 80%
            h, info = sorted_tracks.pop(0)
            fp = Path(info["file_path"])
            if fp.exists():
                fp.unlink()
            total -= info["size"]
            del self.catalog[h]
            log(f"Evicted track {h} ({info['title']}) to stay under cache limit")
        self._save()


# === HTTP Server ===
catalog = MusicCatalog()

class CacheHandler(BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        pass  # Suppress default logging

    def do_GET(self):
        if self.path == "/health":
            self._json_response({"status": "ok", "cached_tracks": len(catalog.catalog)})

        elif self.path == "/catalog":
            self._json_response(catalog.get_catalog_full())

        elif self.path.startswith("/track/"):
            content_hash = self.path.split("/track/")[1]
            data = catalog.get_track(content_hash)
            if data:
                self.send_response(200)
                self.send_header("Content-Type", "audio/ogg")
                self.send_header("Content-Length", str(len(data)))
                self.send_header("X-Pulse-Source", "local-cache")
                self.end_headers()
                self.wfile.write(data)
            else:
                # Try to fetch from a mesh peer
                peer_ip = catalog.find_peer_with_track(content_hash)
                if peer_ip:
                    try:
                        peer_url = f"http://[{peer_ip}]:{LISTEN_PORT}/track/{content_hash}"
                        req = Request(peer_url)
                        with urlopen(req, timeout=10) as resp:
                            peer_data = resp.read()
                            # Cache it locally for next time
                            catalog.add_track(peer_data, track_id=content_hash)
                            self.send_response(200)
                            self.send_header("Content-Type", "audio/ogg")
                            self.send_header("Content-Length", str(len(peer_data)))
                            self.send_header("X-Pulse-Source", f"mesh-peer:{peer_ip}")
                            self.end_headers()
                            self.wfile.write(peer_data)
                            log(f"Served track {content_hash} from mesh peer {peer_ip}")
                            return
                    except Exception as e:
                        log(f"Failed to fetch from peer {peer_ip}: {e}")

                self.send_response(404)
                self.end_headers()
                self.wfile.write(b'{"error": "track not found in local cache or mesh"}')
        else:
            self.send_response(404)
            self.end_headers()

    def do_POST(self):
        if self.path == "/cache":
            content_length = int(self.headers.get("Content-Length", 0))
            if content_length == 0:
                self._json_response({"error": "no data"}, 400)
                return

            title = self.headers.get("X-Track-Title", "Unknown")
            artist = self.headers.get("X-Track-Artist", "Unknown")
            track_id = self.headers.get("X-Track-Id", "")

            audio_data = self.rfile.read(content_length)
            content_hash = catalog.add_track(audio_data, title=title, artist=artist, track_id=track_id)
            self._json_response({"cached": True, "hash": content_hash, "size": len(audio_data)})
        else:
            self.send_response(404)
            self.end_headers()

    def _json_response(self, data: dict, code: int = 200):
        body = json.dumps(data).encode()
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)


# === Mesh Multicast Broadcaster ===
def mesh_broadcaster():
    """Periodically broadcast our catalog hashes to the mesh."""
    while True:
        try:
            hashes = catalog.get_catalog_hashes()
            payload = json.dumps({
                "type": "pulse-music-catalog",
                "hashes": hashes,
                "port": LISTEN_PORT,
                "tracks": len(hashes),
            }).encode()

            sock = socket.socket(socket.AF_INET6, socket.SOCK_DGRAM)
            sock.setsockopt(socket.IPPROTO_IPV6, socket.IPV6_MULTICAST_HOPS, 2)
            sock.sendto(payload, (MESH_MULTICAST_GROUP, MESH_MULTICAST_PORT))
            sock.close()
            log(f"Broadcast catalog ({len(hashes)} tracks) to mesh")
        except Exception as e:
            log(f"Broadcast error: {e}")

        time.sleep(BROADCAST_INTERVAL)


def mesh_listener():
    """Listen for catalog broadcasts from other mesh peers."""
    try:
        sock = socket.socket(socket.AF_INET6, socket.SOCK_DGRAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        sock.bind(("", MESH_MULTICAST_PORT))

        # Join multicast group
        group = socket.inet_pton(socket.AF_INET6, MESH_MULTICAST_GROUP)
        mreq = group + struct.pack("@I", 0)
        sock.setsockopt(socket.IPPROTO_IPV6, socket.IPV6_JOIN_GROUP, mreq)

        log(f"Mesh listener started on port {MESH_MULTICAST_PORT}")

        while True:
            data, addr = sock.recvfrom(65536)
            try:
                msg = json.loads(data.decode())
                if msg.get("type") == "pulse-music-catalog":
                    peer_ip = addr[0]
                    peer_hashes = msg.get("hashes", [])
                    catalog.update_peer(peer_ip, peer_hashes)
                    log(f"Received catalog from peer {peer_ip}: {len(peer_hashes)} tracks")
            except Exception as e:
                log(f"Mesh listener parse error: {e}")
    except Exception as e:
        log(f"Mesh listener failed to start: {e}")


# === Main ===
def main():
    # Ensure cache directory exists
    CACHE_DIR.mkdir(parents=True, exist_ok=True)
    LOG_FILE.parent.mkdir(parents=True, exist_ok=True)

    log("=" * 60)
    log("Alfred Pulse Music Cache Daemon starting")
    log(f"Cache directory: {CACHE_DIR}")
    log(f"HTTP port: {LISTEN_PORT}")
    log(f"Max cache size: {MAX_CACHE_SIZE_GB} GB")
    log(f"Cached tracks: {len(catalog.catalog)}")
    log("=" * 60)

    # Start mesh threads
    broadcaster_thread = threading.Thread(target=mesh_broadcaster, daemon=True)
    broadcaster_thread.start()

    listener_thread = threading.Thread(target=mesh_listener, daemon=True)
    listener_thread.start()

    # Start HTTP server
    server = HTTPServer(("0.0.0.0", LISTEN_PORT), CacheHandler)
    log(f"HTTP server listening on 0.0.0.0:{LISTEN_PORT}")

    def shutdown(sig, frame):
        log("Shutting down...")
        server.shutdown()
        sys.exit(0)

    signal.signal(signal.SIGTERM, shutdown)
    signal.signal(signal.SIGINT, shutdown)

    server.serve_forever()


if __name__ == "__main__":
    main()
