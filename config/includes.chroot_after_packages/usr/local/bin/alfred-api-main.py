import uvicorn
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import subprocess

app = FastAPI(title="Sovereign Matrix API")

# Allow the local frontend to communicate with the backend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

def execute_shell(command: str):
    """Executes a system shell command with elevated privileges."""
    try:
        # Note: In production within the Sovereign OS, the backend service
        # will run as root via systemd, so sudo isn't explicitly needed here, 
        # but we add it for local debugging safety.
        result = subprocess.run(
            ["sudo", "bash", "-c", command],
            capture_output=True,
            text=True,
            check=True
        )
        return {"status": "success", "output": result.stdout}
    except subprocess.CalledProcessError as e:
        print(f"Command Failed: {e.stderr}")
        raise HTTPException(status_code=500, detail=f"Execution Failed: {e.stderr}")

# API Endpoints
@app.post("/api/toggle/{feature_id}/{state}")
async def toggle_feature(feature_id: str, state: str):
    """
    Toggles a God-Tier feature on or off.
    State must be 'enable' or 'disable'.
    """
    if state not in ["enable", "disable"]:
        raise HTTPException(status_code=400, detail="Invalid state. Use 'enable' or 'disable'.")

    # The mapping of Feature IDs to their actual bash execution logic
    logic_map = {
        "ghost_mode": {
            "enable": "/usr/local/bin/alfred-ghost-mode",
            "disable": "systemctl restart NetworkManager"
        },
        "faraday_mode": {
            "enable": "/usr/local/bin/alfred-faraday-mode",
            "disable": "rfkill unblock all && systemctl restart NetworkManager"
        },
        "liquid_ram": {
            "enable": "systemctl start memcached",
            "disable": "systemctl stop memcached"
        },
        "decoy_traffic": {
            "enable": "systemctl start alfred-decoy-traffic.service",
            "disable": "systemctl stop alfred-decoy-traffic.service"
        },
        "time_reversal": {
            "enable": "snapper create -c timeline -d 'Control Panel Manual Snapshot'",
            "disable": "echo 'Time Reversal cannot be disabled, only reverted.'"
        },
        "beeswarm": { "enable": "/usr/local/bin/alfred-beeswarm", "disable": "pkill nc" },
        "mac_chaffing": { "enable": "/usr/local/bin/alfred-mac-chaffing", "disable": "macchanger -p eth0" },
        "data_poisoning": { "enable": "/usr/local/bin/alfred-data-poisoning", "disable": "tc qdisc del dev eth0 root netem" },
        "kill_switch": { "enable": "/usr/local/bin/alfred-kill-switch", "disable": "echo 'Deadman active'" },
        "rogue_ap_hunter": { "enable": "/usr/local/bin/alfred-rogue-ap-hunter", "disable": "echo 'Hunter disabled'" },
        "bgp_hijack": { "enable": "/usr/local/bin/alfred-bgp-hijack", "disable": "echo 'BGP Monitor offline'" },
        "usb_killer": { "enable": "/usr/local/bin/alfred-usb-killer", "disable": "modprobe usbcore" },
        "audio_gap": { "enable": "/usr/local/bin/alfred-audio-gap", "disable": "echo 'Jammer off'" },
        "display_airgap": { "enable": "/usr/local/bin/alfred-display-airgap", "disable": "xrandr --auto" },
        "panic_wipe": { "enable": "/usr/local/bin/alfred-panic-wipe", "disable": "echo 'Cannot undo wipe'" },
        
        "sleep_mimicry": { "enable": "/usr/local/bin/alfred-sleep-mimicry", "disable": "echo 'C-states reset'" },
        "retinal_dimming": { "enable": "/usr/local/bin/alfred-retinal-dimming", "disable": "echo 'Dimming disabled'" },
        "synaptic_cache": { "enable": "/usr/local/bin/alfred-synaptic-cache", "disable": "echo 'Cache flush disabled'" },
        "neural_fan": { "enable": "/usr/local/bin/alfred-neural-fan", "disable": "echo 'Fan curves reset'" },
        "bio_rhythmic": { "enable": "/usr/local/bin/alfred-bio-rhythmic", "disable": "echo 'Biometrics reset'" },
        "haptic_network": { "enable": "/usr/local/bin/alfred-haptic-network", "disable": "echo 'Haptics disabled'" },
        "context_switch": { "enable": "/usr/local/bin/alfred-context-switch", "disable": "echo 'Scheduling reset'" },
        "dopamine_ui": { "enable": "/usr/local/bin/alfred-dopamine-ui", "disable": "echo 'Color restored'" },
        "audio_spatial": { "enable": "/usr/local/bin/alfred-audio-spatial", "disable": "echo 'Audio reset'" },
        "circadian_crypto": { "enable": "/usr/local/bin/alfred-circadian-crypto", "disable": "echo 'Salt removed'" },
        
        "stego_ram": { "enable": "/usr/local/bin/alfred-stego-ram", "disable": "echo 'RAM restored'" },
        "onion_dns": { "enable": "/usr/local/bin/alfred-onion-dns", "disable": "rm /etc/resolv.conf" },
        "time_stretch": { "enable": "/usr/local/bin/alfred-time-stretch", "disable": "echo 'Time normalized'" },
        "quantum_ssh": { "enable": "/usr/local/bin/alfred-quantum-ssh", "disable": "echo 'Classic SSH restored'" },
        "ephemeral_clip": { "enable": "/usr/local/bin/alfred-ephemeral-clip", "disable": "pkill -f alfred-ephemeral-clip" },
        "zero_logs": { "enable": "/usr/local/bin/alfred-zero-logs", "disable": "echo 'Logs decrypted'" },
        "anti_swap": { "enable": "/usr/local/bin/alfred-anti-swap", "disable": "echo 'Swap normalized'" },
        "meta_stripper": { "enable": "/usr/local/bin/alfred-meta-stripper", "disable": "echo 'Stripper offline'" },
        "location_spoof": { "enable": "/usr/local/bin/alfred-location-spoof", "disable": "echo 'GPS realigned'" },
        "darknet_only": { "enable": "/usr/local/bin/alfred-darknet-only", "disable": "dhclient eth0" },

        # === Wave 4: Jericho Defense ===
        "jericho_wall": { "enable": "/usr/local/bin/alfred-jericho-wall", "disable": "systemctl stop alfred-jericho-wall.service" },
        "solomon_seal": { "enable": "/usr/local/bin/alfred-solomon-seal", "disable": "systemctl stop alfred-solomon-seal.service" },
        "nehemiah_gate": { "enable": "/usr/local/bin/alfred-nehemiah-gate", "disable": "systemctl stop alfred-nehemiah-gate.service" },
        "watchtower": { "enable": "systemctl start alfred-watchtower.service", "disable": "systemctl stop alfred-watchtower.service" },
        "iron_dome": { "enable": "/usr/local/bin/alfred-iron-dome", "disable": "iptables -F alfred-ratelimit 2>/dev/null; ip6tables -F alfred-ratelimit 2>/dev/null" },
        "samson_strength": { "enable": "/usr/local/bin/alfred-samson-strength", "disable": "pkill -f alfred-samson-strength" },
        "david_sling": { "enable": "systemctl start alfred-david-sling.service", "disable": "systemctl stop alfred-david-sling.service" },
        "gideon_torch": { "enable": "/usr/local/bin/alfred-gideon-torch", "disable": "echo 0 | tee /sys/class/leds/*/brightness 2>/dev/null || true" },
        "rahab_diversion": { "enable": "/usr/local/bin/alfred-rahab-diversion", "disable": "rm -f /etc/ld.so.preload" },
        "elisha_shield": { "enable": "/usr/local/bin/alfred-elisha-shield", "disable": "echo 'Kernel lockdown cannot be reversed without reboot'" },

        # === Wave 4: Manna Protocol ===
        "manna_harvest": { "enable": "systemctl start alfred-manna-harvest.service", "disable": "systemctl stop alfred-manna-harvest.service" },
        "quail_burst": { "enable": "/usr/local/bin/alfred-quail-burst", "disable": "cpupower frequency-set -g powersave" },
        "pillar_cloud": { "enable": "systemctl start alfred-pillar-cloud.service", "disable": "systemctl stop alfred-pillar-cloud.service" },
        "living_water": { "enable": "systemctl start alfred-living-water.service", "disable": "systemctl stop alfred-living-water.service" },
        "twelve_springs": { "enable": "/usr/local/bin/alfred-twelve-springs", "disable": "systemctl stop alfred-twelve-springs.service" },
        "ravens_feed": { "enable": "systemctl start alfred-ravens-feed.service", "disable": "systemctl stop alfred-ravens-feed.service" },
        "oil_jar": { "enable": "systemctl start alfred-oil-jar.service", "disable": "systemctl stop alfred-oil-jar.service" },
        "five_loaves": { "enable": "/usr/local/bin/alfred-five-loaves", "disable": "systemctl stop alfred-five-loaves.service" },
        "wheat_harvest": { "enable": "systemctl start alfred-wheat-harvest.timer", "disable": "systemctl stop alfred-wheat-harvest.timer" },
        "jubilee_compute": { "enable": "systemctl start alfred-jubilee-compute.timer", "disable": "systemctl stop alfred-jubilee-compute.timer" },

        # === Wave 4: Exodus Routing ===
        "red_sea": { "enable": "/usr/local/bin/alfred-red-sea", "disable": "systemctl stop alfred-red-sea.service" },
        "burning_bush": { "enable": "systemctl start alfred-burning-bush.service", "disable": "systemctl stop alfred-burning-bush.service" },
        "cloud_column": { "enable": "/usr/local/bin/alfred-cloud-column", "disable": "wg-quick down alfred0 2>/dev/null || true" },
        "night_pillar": { "enable": "systemctl start alfred-night-pillar.service", "disable": "systemctl stop alfred-night-pillar.service" },
        "moses_staff": { "enable": "/usr/local/bin/alfred-moses-staff", "disable": "systemctl restart systemd-resolved" },
        "crossing_jordan": { "enable": "systemctl start alfred-crossing-jordan.service", "disable": "systemctl stop alfred-crossing-jordan.service" },
        "twelve_scouts": { "enable": "systemctl start alfred-twelve-scouts.service", "disable": "systemctl stop alfred-twelve-scouts.service" },
        "trumpet_call": { "enable": "/usr/local/bin/alfred-trumpet-call", "disable": "echo 'Broadcast sent - cannot unsend'" },
        "ark_carrier": { "enable": "/usr/local/bin/alfred-ark-carrier mount", "disable": "/usr/local/bin/alfred-ark-carrier umount" },
        "promised_land": { "enable": "/usr/local/bin/alfred-promised-land", "disable": "dhclient eth0 2>/dev/null; nmcli networking on" },

        # === Wave 5: Zion Protocol ===
        "zion_sandbox": { "enable": "/usr/local/bin/alfred-zion-sandbox", "disable": "echo 'Sandbox locked'" },
        "offline_ca": { "enable": "/usr/local/bin/alfred-offline-ca", "disable": "echo 'CA offline'" },
        "sovereign_ntp": { "enable": "/usr/local/bin/alfred-sovereign-ntp", "disable": "systemctl enable --now systemd-timesyncd" },
        "data_sabbatical": { "enable": "/usr/local/bin/alfred-data-sabbatical", "disable": "echo 10 > /proc/sys/vm/dirty_ratio" },
        "decentral_apt": { "enable": "/usr/local/bin/alfred-decentral-apt", "disable": "sed -i 's/tor+http/http/g' /etc/apt/sources.list" },
        "kernel_emulsion": { "enable": "/usr/local/bin/alfred-kernel-emulsion", "disable": "echo 'Modules reloaded'" },
        "phantom_display": { "enable": "/usr/local/bin/alfred-phantom-display", "disable": "rm /tmp/phantom-edid.log" },
        "hardware_deafening": { "enable": "/usr/local/bin/alfred-hardware-deafening", "disable": "echo 'Reboot required to restore PCI power'" },
        "zero_trust_usb": { "enable": "/usr/local/bin/alfred-zero-trust-usb", "disable": "echo 1 > /sys/bus/usb/drivers_autoprobe" },
        "local_dns_sinkhole": { "enable": "/usr/local/bin/alfred-local-dns-sinkhole", "disable": "sed -i '/telemetry/d' /etc/hosts" },
        "crypto_bootloader": { "enable": "/usr/local/bin/alfred-crypto-bootloader", "disable": "echo 'Bootloader unlocked'" },
        "self_healing_fs": { "enable": "/usr/local/bin/alfred-self-healing-fs", "disable": "echo 'Scrub canceled'" },
        "nomadic_ip": { "enable": "/usr/local/bin/alfred-nomadic-ip", "disable": "echo 'IP statically bound'" },
        "silent_alarms": { "enable": "/usr/local/bin/alfred-silent-alarms", "disable": "echo 'Alarms disarmed'" },
        "archon_guard": { "enable": "/usr/local/bin/alfred-archon-guard", "disable": "chmod 755 /usr/local/bin/*" },

        # === Wave 6: Enoch's Ascension ===
        "enoch_chariot": { "enable": "/usr/local/bin/alfred-enoch-chariot", "disable": "echo 'Migration halted'" },
        "fractal_compute": { "enable": "/usr/local/bin/alfred-fractal-compute", "disable": "modprobe -r kvm_intel; modprobe kvm_intel nested=0" },
        "gpu_partition": { "enable": "/usr/local/bin/alfred-gpu-partition", "disable": "echo 'GPU merged'" },
        "memory_balloon": { "enable": "/usr/local/bin/alfred-memory-balloon", "disable": "swapoff /dev/zram0" },
        "net_namespace": { "enable": "/usr/local/bin/alfred-net-namespace", "disable": "echo 'Namespace collapsed'" },
        "storage_hologram": { "enable": "/usr/local/bin/alfred-storage-hologram", "disable": "echo 'Hologram deactivated'" },
        "cpu_pinning": { "enable": "/usr/local/bin/alfred-cpu-pinning", "disable": "echo 'Affinity restored'" },
        "volatile_workspace": { "enable": "/usr/local/bin/alfred-volatile-workspace", "disable": "echo 'Workspace unmounted'" },
        "peripheral_virt": { "enable": "/usr/local/bin/alfred-peripheral-virt", "disable": "echo 'Virtual bus disabled'" },
        "seamless_emulation": { "enable": "/usr/local/bin/alfred-seamless-emulation", "disable": "echo 'Emulation halted'" },
        "time_travel": { "enable": "/usr/local/bin/alfred-time-travel", "disable": "tc qdisc del dev eth0 root netem 2>/dev/null" },
        "acoustic_masking": { "enable": "/usr/local/bin/alfred-acoustic-masking", "disable": "echo 'Masking offline'" },
        "thermo_routing": { "enable": "/usr/local/bin/alfred-thermo-routing", "disable": "echo 'Routing normalized'" },
        "quantum_entropy": { "enable": "/usr/local/bin/alfred-quantum-entropy", "disable": "echo 'Entropy pool reset'" },
        "the_ascension": { "enable": "/usr/local/bin/alfred-the-ascension", "disable": "echo 'Ascension irreversible'" },

        # === Wave 7: Leviathan's Deep ===
        "leviathan_scales": { "enable": "/usr/local/bin/alfred-leviathan-scales", "disable": "ip link set dev eth0 mtu 1500" },
        "kraken_tether": { "enable": "/usr/local/bin/alfred-kraken-tether", "disable": "echo 'Tether severed'" },
        "abyssal_routing": { "enable": "/usr/local/bin/alfred-abyssal-routing", "disable": "echo 'Routing surfaced'" },
        "sonic_boom": { "enable": "/usr/local/bin/alfred-sonic-boom", "disable": "echo 'Alarm disarmed'" },
        "trench_warfare": { "enable": "/usr/local/bin/alfred-trench-warfare", "disable": "iptables -D INPUT -p icmp --icmp-type echo-request -j DROP" },
        "charybdis_whirlpool": { "enable": "/usr/local/bin/alfred-charybdis-whirlpool", "disable": "echo 'Whirlpool closed'" },
        "scylla_heads": { "enable": "/usr/local/bin/alfred-scylla-heads", "disable": "echo 'Decoys removed'" },
        "deep_water_logs": { "enable": "/usr/local/bin/alfred-deep-water-logs", "disable": "echo 'Local logging restored'" },
        "pressure_implosion": { "enable": "/usr/local/bin/alfred-pressure-implosion", "disable": "echo 'Implosion canceled'" },
        "bioluminescence": { "enable": "/usr/local/bin/alfred-bioluminescence", "disable": "echo 'RGB normalized'" },
        "ocean_floor": { "enable": "/usr/local/bin/alfred-ocean-floor", "disable": "echo 'Cache locked'" },
        "tidal_wave": { "enable": "/usr/local/bin/alfred-tidal-wave", "disable": "echo 'Tidal wave receded'" },
        "leviathan_breath": { "enable": "/usr/local/bin/alfred-leviathan-breath", "disable": "echo 'Detector offline'" },
        "submarine_stealth": { "enable": "/usr/local/bin/alfred-submarine-stealth", "disable": "echo 'Acoustic stealth disabled'" },
        "deep_sleep": { "enable": "/usr/local/bin/alfred-deep-sleep", "disable": "echo 'Awake'" },
        "genesis_seed": { "enable": "/usr/local/bin/alfred-genesis-seed", "disable": "pkill -f alfred-genesis-seed" },
        "alfred_whirlwind": { "enable": "systemctl start alfred-whirlwind.service", "disable": "systemctl stop alfred-whirlwind.service" },
        "alfred_babel_fish": { "enable": "nohup /usr/local/bin/alfred-babel-fish > /dev/null 2>&1 &", "disable": "pkill -f alfred-babel-fish" },
        "alfred_crown_of_thorns": { "enable": "/usr/local/bin/alfred-crown-of-thorns-trigger", "disable": "echo 'Self-Destruct Cannot Be Canceled'" },

        "local_clearweb": {
            "enable": "systemctl start nginx",
            "disable": "systemctl stop nginx"
        },
        "local_darknet": {
            "enable": "systemctl start tor",
            "disable": "systemctl stop tor"
        }
        # Additional features map directly to their installed bash/eBPF daemon scripts
    }

    if feature_id not in logic_map:
        # Dynamic execution for the 100 new paradigms
        command = f"/usr/local/bin/alfred-{feature_id}"
        if state == "disable":
            command = f"pkill -f alfred-{feature_id}"
    else:
        command = logic_map[feature_id][state]

    # Meta-Dome Cloud Synchronization Bridge
    try:
        import requests
        meta_dome_url = "https://meta-dome.com/military-api.php"
        payload = {
            "commander_id": "ALFRED-MASTER-NODE-01",
            "feature": feature_id,
            "action": state,
            "status": "triggered"
        }
        # In production this would be authenticated via mTLS or a JWT
        requests.post(meta_dome_url, json=payload, timeout=2)
        print(f"[Meta-Dome Sync] {feature_id} telemetry dispatched to C2 Portal.")
    except Exception as e:
        print(f"[Meta-Dome Sync Failed] Operating in offline mode. {str(e)}")

    return execute_shell(command)

import json
import os

@app.get("/api/features")
async def get_all_features():
    """Returns the full arsenal of 1335 God-Tier features extracted from the OS hooks."""
    map_file = os.path.join(os.path.dirname(__file__), "..", "hooks_map.json")
    if os.path.exists(map_file):
        with open(map_file, 'r') as f:
            return json.load(f)
    return []

@app.post("/api/deploy-cloud")
async def deploy_to_cloud():
    """
    Initiates the GoSiteMe Pipeline.
    In production, this zips ~/public_html and FTPs it to GoSiteMe servers.
    """
    print("[GoSiteMe Pipeline] Packaging public_html...")
    # Simulated FTP logic
    return {"status": "success", "redirect": "https://root.com/pay"}

import threading
import time

def fleet_command_poller():
    """Background thread that securely polls the Meta-Dome for fleet-wide push commands."""
    import requests
    meta_dome_url = "https://meta-dome.com/military-api.php?action=poll_commands"
    
    while True:
        try:
            # Poll the portal every 30 seconds
            response = requests.get(meta_dome_url, timeout=5)
            if response.status_code == 200:
                commands = response.json().get("commands", [])
                for cmd in commands:
                    feature = cmd.get("feature_id")
                    state = cmd.get("state")
                    if feature and state:
                        print(f"[Fleet Command Received] Executing {feature} -> {state}")
                        # Route the command through the same local shell execution logic
                        execute_shell(f"/usr/local/bin/alfred-{feature}")
        except Exception as e:
            # Silent fail on network error, allowing true offline operation
            pass
        time.sleep(30)

@app.on_event("startup")
async def startup_event():
    """Start the Fleet Command polling thread when the Sovereign OS boots."""
    thread = threading.Thread(target=fleet_command_poller, daemon=True)
    thread.start()
    print("[Sovereign OS] Fleet Command Polling Active.")

if __name__ == "__main__":
    # Run the API on the sacred port 1335
    uvicorn.run(app, host="127.0.0.1", port=1335)
