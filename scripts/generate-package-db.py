#!/usr/bin/env python3
import subprocess
import json
import os

CONTAINER_NAME = "alfred-lb-build-1782353193"
OUTPUT_FILE = "/home/gositeme/domains/alfredlinux.com/public_html/packages.json"

print(f"Extracting package data from {CONTAINER_NAME}...")

try:
    cmd = ["docker", "exec", CONTAINER_NAME, "dpkg-query", "-W", "-f=${Package}||${Section}||${Summary}\n"]
    result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, check=True)
except subprocess.CalledProcessError as e:
    print(f"Failed to execute dpkg-query: {e.stderr}")
    exit(1)

packages = []
for line in result.stdout.strip().split('\n'):
    parts = line.split('||')
    if len(parts) == 3:
        pkg_name = parts[0].strip()
        section = parts[1].strip()
        summary = parts[2].strip()
        
        # Clean up empty sections
        if not section or section == "unknown":
            section = "misc"
        
        # Clean up categories (e.g. non-free/admin -> admin)
        if '/' in section:
            section = section.split('/')[-1]
            
        packages.append({
            "name": pkg_name,
            "category": section,
            "description": summary
        })

print(f"Extracted {len(packages)} packages. Saving to {OUTPUT_FILE}...")
os.makedirs(os.path.dirname(OUTPUT_FILE), exist_ok=True)
with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
    json.dump(packages, f)
print("Data extraction complete!")