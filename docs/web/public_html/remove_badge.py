import sys

target_file = '/home/gositeme/domains/alfredlinux.com/public_html/index.php'
with open(target_file, 'r') as f:
    lines = f.readlines()

with open(target_file, 'w') as f:
    for i, line in enumerate(lines):
        # Line numbers are 0-indexed in python, so line 533 is index 532
        if i == 532 and 'ENGINEERING UPDATE:' in line:
            print("Removed the ENGINEERING UPDATE badge.")
            continue
        # Fallback if line number shifted:
        if 'ENGINEERING UPDATE:' in line and 'The legendary 28GB recursive chroot anomaly' in line:
            print("Removed the ENGINEERING UPDATE badge.")
            continue
        f.write(line)
