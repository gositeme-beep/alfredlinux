import sys
import os

target_file = '/home/gositeme/domains/alfredlinux.com/public_html/index.php'
if not os.path.exists(target_file):
    print("Target file not found!")
    sys.exit(1)

with open(target_file, 'r') as f:
    content = f.read()

zero_trust_html = '''
<!-- ═══ ZERO-TRUST GOD-MODE ═══ -->
<section class="section" id="zero-trust" style="background: rgba(0,0,0,0.7); border-top: 1px solid rgba(255,255,255,0.05); margin-top: 5rem; padding-top: 6rem; padding-bottom: 6rem;">
    <div class="section-header">
        <span class="section-label" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5;">🛡️ IMPENETRABLE DEFENSE MEETS FLUIDITY</span>
        <h2 style="background: linear-gradient(135deg, #fca5a5, #ef4444, #991b1b); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">The Zero-Trust Fluid God-Mode</h2>
        <p>AlfredLinux fundamentally redefines operating system security by merging the impenetrable defensive posture of a locked-down production server with the offensive agility of a penetration testing distribution.</p>
    </div>
    
    <div class="feature-grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">
        <div class="feature-card" style="border-color: #ef4444; box-shadow: 0 0 20px rgba(239,68,68,0.1);">
            <div class="feature-icon" style="background:rgba(239,68,68,0.2);">🔒</div>
            <h3 style="color: #fca5a5;">The Zero-Trust Anchor</h3>
            <p>By default, AlfredLinux ships with a mathematically locked <code>root</code> account. There is no valid password hash. It cannot be brute-forced. When automated SSH scanners or malicious eBPF kernel rootkits attempt to pivot into root, they hit an unbreakable cryptographic wall.</p>
        </div>
        
        <div class="feature-card" style="border-color: #60a5fa; box-shadow: 0 0 20px rgba(96,165,250,0.1);">
            <div class="feature-icon" style="background:rgba(96,165,250,0.2);">⚡</div>
            <h3 style="color: #93c5fd;">The God-Mode Sudoer</h3>
            <p>While the OS is locked from the outside, the true Owner (<code>alfred</code>) operates with <strong>Absolute Fluidity</strong>. Granted <code>NOPASSWD: ALL</code> in the sudoers matrix, the owner never has to break their train of thought to type a password. You operate at the speed of thought.</p>
        </div>
    </div>
</section>
'''

if 'ZERO-TRUST GOD-MODE' not in content:
    content = content.replace('<!-- ═══ BARE-METAL HARDWARE MASTERY ═══ -->', zero_trust_html + '\n<!-- ═══ BARE-METAL HARDWARE MASTERY ═══ -->')
    with open(target_file, 'w') as f:
        f.write(content)
    print('Zero-Trust section injected successfully!')
else:
    print('Zero-Trust section already exists!')
