import re

file_path = "world-firsts.php"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Remove the 00 entry entirely
# The 00 entry ends with "</div>\n        </div>" or similar? Let's just use regex to remove it from <div class="first-entry declassified" data-number="00"> up to the NEXT <div class="first-entry
pattern_00 = re.compile(r'<div class="first-entry declassified" data-number="00">.*?(?=<div class="first-entry declassified" data-number="01">)', re.DOTALL)
content = re.sub(pattern_00, '', content)

# 2. Update Entry #41
old_entry_41 = """    <div class="first-entry declassified" data-number="41">
        <span class="first-badge badge-gositeme"><i class="fas fa-lock"></i> Security</span>
        <h2><i class="fas fa-shield-alt"></i> First Omni-Quantum OS Hardening (Hybrid LUKS)</h2>
        <p class="claim">An impenetrable shield against the encroaching quantum apocalypse. The Master Volume Keys are wrapped in CRYSTALS-Kyber encapsulation, blinding the quantum network surveillance of Babylon and ensuring the Kingdom's data remains eternally sealed.</p>
        <div class="proof">
            <h4><i class="fas fa-check-circle"></i> Proof Points</h4>
            <ul>
                <li><i class="fas fa-check"></i> Hybrid Post-Quantum LUKS Architecture using ML-KEM</li>
                <li><i class="fas fa-check"></i> Native mandate for post-quantum OpenSSH (`sntrup761x25519`)</li>
                <li><i class="fas fa-check"></i> The highest cryptographic standard ever shipped by default</li>
            </ul>
        </div>
        <span class="nobody"><i class="fas fa-ban"></i> Nobody else has this</span>
    </div>"""

new_entry_41 = """    <div class="first-entry declassified" data-number="41">
        <span class="first-badge badge-gositeme"><i class="fas fa-lock"></i> Security</span>
        <h2><i class="fas fa-shield-alt"></i> First Native Post-Quantum LUKS2 Full Disk Encryption</h2>
        <p class="claim">Alfred Linux is the first operating system on Earth to mathematically bind ML-KEM-1024 (Kyber) directly into the kernel's cryptsetup binary during ISO compilation, eliminating offline Python wrappers. An impenetrable shield against the encroaching quantum apocalypse.</p>
        <div class="proof">
            <h4><i class="fas fa-check-circle"></i> Proof Points</h4>
            <ul>
                <li><i class="fas fa-check"></i> Dynamic C injection of liboqs into the LUKS2 token handler</li>
                <li><i class="fas fa-check"></i> Native mandate for post-quantum OpenSSH (`sntrup761x25519`)</li>
                <li><i class="fas fa-check"></i> AES-256 Master Key decapsulation natively inside the initramfs</li>
            </ul>
        </div>
        <span class="nobody"><i class="fas fa-ban"></i> Nobody else has this</span>
    </div>"""

if old_entry_41 in content:
    content = content.replace(old_entry_41, new_entry_41)
    with open(file_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Cleanup successful.")
else:
    print("Error: Could not find exact string for Entry #41 to replace.")
    # Attempt a fallback regex if exact match fails due to whitespace
    fallback_pattern = re.compile(r'<div class="first-entry declassified" data-number="41">.*?Nobody else has this</span>\s*</div>', re.DOTALL)
    if re.search(fallback_pattern, content):
        content = re.sub(fallback_pattern, new_entry_41, content)
        with open(file_path, "w", encoding="utf-8") as f:
            f.write(content)
        print("Cleanup successful using fallback regex.")
    else:
        print("Fallback regex also failed.")
