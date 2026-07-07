import sys

target_file = '/home/gositeme/domains/alfredlinux.com/public_html/index.php'
with open(target_file, 'r') as f:
    lines = f.readlines()

badge_1 = '        <div class="hero-badge" style="background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(0,0,0,0.6)); border-color: rgba(239,68,68,0.5); color: #fca5a5; margin-left: 15px; box-shadow: 0 0 25px rgba(239,68,68,0.3);"><span class="pulse" style="background: #ef4444; box-shadow: 0 0 10px #ef4444;"></span> <strong>SECURITY UPDATE:</strong> The Zero-Trust Fluid God-Mode architecture has been successfully forged into the kernel. Root is locked. Execution is absolute.</div>\n'
badge_3 = '        <div class="hero-badge" style="background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(0,0,0,0.6)); border-color: rgba(139,92,246,0.5); color: #c4b5fd; margin-left: 15px; box-shadow: 0 0 25px rgba(139,92,246,0.3);"><span class="pulse" style="background: #8b5cf6; box-shadow: 0 0 10px #8b5cf6;"></span> <strong>CRYPTOGRAPHY UPDATE:</strong> Post-Quantum ML-KEM Key Encapsulation fully integrated into the LUKS2 encrypted root filesystem.</div>\n'

with open(target_file, 'w') as f:
    for line in lines:
        f.write(line)
        if 'WORLD FIRST: NEXT-GEN NVIDIA NATIVE ARCHITECTURE' in line:
            f.write(badge_1)
            f.write(badge_3)
            print("Badges injected successfully!")

