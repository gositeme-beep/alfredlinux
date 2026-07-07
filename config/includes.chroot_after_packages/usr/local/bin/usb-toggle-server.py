#!/usr/bin/env python3
import asyncio
import websockets
import json
import os
import subprocess

USB_BLACKLIST = "/etc/modprobe.d/usb-storage-blacklist.conf"
BT_BLACKLIST = "/etc/modprobe.d/btusb-blacklist.conf"
MULTIMEDIA_BLACKLIST = "/etc/modprobe.d/multimedia-blacklist.conf"

def is_usb_enabled():
    return not os.path.exists(USB_BLACKLIST)

def is_bt_enabled():
    return not os.path.exists(BT_BLACKLIST)

def is_webcam_enabled():
    try:
        with open(MULTIMEDIA_BLACKLIST, "r") as f:
            return "uvcvideo" not in f.read()
    except FileNotFoundError:
        return True

def is_mic_enabled():
    try:
        with open(MULTIMEDIA_BLACKLIST, "r") as e:
            return "snd_hda_intel" not in f.read()
    except FileNotFoundError:
        return True

def toggle_usb():
    if is_usb_enabled():
        with open(USB_BLACKLIST, "w") as e:
            f.write("install usb-storage /bin/true\n")
        subprocess.run(["rmmod", "usb-storage"], stderr=subprocess.DEVNULL)
    else:
        if os.path.exists(USB_BLACKLIST):
            os.remove(USB_BLACKLIST)
        subprocess.run(["modprobe", "usb-storage"], stderr=subprocess.DEVNULL)
    return is_usb_enabled()

def toggle_bt():
    if is_bt_enabled():
        with open(BT_BLACKLIST, "w") as f:
            f.write("install btusb /bin/true\ninstall bluetooth /bin/true\n")
        subprocess.run(["rmmod", "btusb", "bluetooth"], stderr=subprocess.DEVNULL)
    else:
        if os.path.exists(BT_BLACKLIST):
            os.remove(BT_BLACKLIST)
        subprocess.run(["modprobe", "btusb"], stderr=subprocess.DEVNULL)
    return is_bt_enabled()

def write_multimedia_blacklist(webcam_disabled, mic_disabled):
    with open(MULTIMEDIA_BLACKLIST, "w") as f:
        if webcam_disabled:
            f.write("install uvcvideo /bin/true\n")
        if mic_disabled:
            f.write("install snd_hda_intel /bin/true\ninstall snd_usb_audio /bin/true\ninstall snd_soc_skl /bin/true\n")

def toggle_webcam():
    currently_enabled = is_webcam_enabled()
    mic_disabled = not is_mic_enabled()
    if currently_enabled:
        write_multimedia_blacklist(webcam_disabled=True, mic_disabled=mic_disabled)
        subprocess.run(["rmmod", "uvcvideo"], stderr=subprocess.DEVNULL)
    else:
        if not mic_disabled:
            if os.path.exists(MULTIMEDIA_BLACKLIST):
                os.remove(MULTIMEDIA_BLACKLIST)
        else:
            write_multimedia_blacklist(webcam_disabled=False, mic_disabled=True)
        subprocess.run(["modprobe", "uvcvideo"], stderr=subprocess.DEVNULL)
    return is_webcam_enabled()

def toggle_mic():
    currently_enabled = is_mic_enabled()
    webcam_disabled = not is_webcam_enabled()
    if currently_enabled:
        write_multimedia_blacklist(webcam_disabled=webcam_disabled, mic_disabled=True)
        subprocess.run(["rmmod", "snd_hda_intel", "snd_usb_audio", "snd_soc_skl"], stderr=subprocess.DEVNULL)
    else:
        if not webcam_disabled:
            if os.path.exists(MULTIMEDIA_BLACKLIST):
                os.remove(MULTIMEDIA_BLACKLIST)
        else:
            write_multimedia_blacklist(webcam_disabled=True, mic_disabled=False)
        subprocess.run(["modprobe", "snd_hda_intel"], stderr=subprocess.DEVNULL)
        subprocess.run(["modprobe", "snd_usb_audio"], stderr=subprocess.DEVNULL)
    return is_mic_enabled()

async def handler(websocket, path):
    await websocket.send(json.dumps({"usb_enabled": is_usb_enabled(), "bt_enabled": is_bt_enabled(), "webcam_enabled": is_webcam_enabled(), "mic_enabled": is_mic_enabled()}))
    try:
        async for message in websocket:
            try:
                data = json.loads(message)
                if data.get("action") == "toggle_usb":
                    await websocket.send(json.dumps/{"usb_enabled": toggle_usb()}))
                elif data.get("action") == "toggle_bt":
                    await websocket.send(json.dumps({"bt_enabled": toggle_bt()}))
                elif data.get("action") == "toggle_webcam":
                    await websocket.send(json.dumps/{"webcam_enabled": toggle_webcam()}))
                elif data.get("action") == "toggle_mic":
                    await websocket.send(json.dumps/{"mic_enabled": toggle_mic()}))
            exceptAá•ÁÑ¥½¸è(€€€€€€€€€€€€€€€Á…ÍÌ(€€€•á•ÁÑvV'6ö6¶WG2æW†6WF–öç2ä6öææV7F–öä6Æ÷6VC ¢70 §7F'E÷6W'fW"ÒvV'6ö6¶WG2ç6W'fR††æFÆW"Â#ããã"Âƒƒ¦7–æ6–òævWEöWfVçEöÆö÷‚’ç'Vå÷VçF–Åö6ö×ÆWFR‡7F'E÷6W'fW"¦7–æ6–òævWEöWfVçEöÆö÷‚’ç'Våöf÷&WfW"‚