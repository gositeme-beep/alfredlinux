#!/usr/bin/env python3
import asyncio
import websockets
import os
import subprocess

LOG_FILE = '/home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log'
PORT = 8080

async def tail_log(websocket):
    print(f"Client connected to stream: {websocket.remote_address}")
    # Run tail -f on the log file
    process = subprocess.Popen(['tail', '-f', LOG_FILE], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    try:
        while True:
            line = process.stdout.readline()
            if not line:
                await asyncio.sleep(0.1)
                continue
            await websocket.send(line.decode('utf-8'))
    except websockets.exceptions.ConnectionClosed:
        print(f"Client disconnected: {websocket.remote_address}")
    finally:
        process.kill()

async def main():
    if not os.path.exists(LOG_FILE):
        open(LOG_FILE, 'a').close()
    print(f"Starting Alfred Linux Zero-Latency WebSocket Telemetry on port {PORT}...")
    async with websockets.serve(tail_log, "0.0.0.0", PORT):
        await asyncio.Future()  # run forever

if __name__ == "__main__":
    asyncio.run(main())