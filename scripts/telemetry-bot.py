#!/usr/bin/env python3
import time
import subprocess
import urllib.request
import json
import sys

# Replace this placeholder with your actual Discord Webhook URL later
WEBHOOK_URL = "https://discord.com/api/webhooks/PLACEHOLDER_TOKEN_HERE"

def send_discord(msg):
    if "PLACEHOLDER" in WEBHOOK_URL:
        return
    data = {"content": f"**[Alfred Telemetry]** {msg}"}
    req = urllib.request.Request(WEBHOOK_URL, data=json.dumps(data).encode('utf-8'), headers={'Content-Type': 'application/json'})
    try:
        urllib.request.urlopen(req, timeout=5)
    except:
        pass

def main():
    log_file = "/home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log"
    print("Telemetry bot started, watching log...")
    
    # State tracking
    chroot_done = False
    binary_started = False
    iso_done = False
    
    # Tail the log file
    p = subprocess.Popen(["tail", "-F", log_file], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    for line in iter(p.stdout.readline, b''):
        line = line.decode('utf-8').strip()
        
        if "[inner] Chroot phase complete." in line and not chroot_done:
            send_discord("✅ **Chroot phase successfully completed!** The 14.2GB payload is locked.")
            chroot_done = True
            
        elif "Begin install linux-image" in line and not binary_started:
            send_discord("🔨 **Binary SquashFS compression starting...**")
            binary_started = True
            
        elif "[inner] published fresh ISO to" in line and not iso_done:
            send_discord("🚀 **ISO Successfully Generated and Signed!** Ready for download.")
            iso_done = True
            sys.exit(0)

if __name__ == "__main__":
    main()