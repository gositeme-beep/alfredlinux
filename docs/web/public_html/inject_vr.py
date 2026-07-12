import sys
import re

target_file = '/home/gositeme/domains/alfredlinux.com/public_html/index.php'
with open(target_file, 'r') as f:
    content = f.read()

vr_html = '''
    <div style="margin-top: 3rem; margin-bottom: 3rem; background: rgba(0,0,0,0.6); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 12px; padding: 2rem; box-shadow: 0 0 30px rgba(139, 92, 246, 0.1);">
        <img src="vr_spatial_os_matrix.png" alt="AlfredLinux 360-Degree Spatial OS Matrix" style="width: 100%; border-radius: 8px; margin-bottom: 2rem; border: 1px solid rgba(255,255,255,0.1);">
        <h3 style="color: #c4b5fd; margin-bottom: 1rem;">The 360-Degree God-Mode Matrix</h3>
        <p style="margin-bottom: 1.5rem;">Extend the native KDE Plasma Wayland compositor into a mathematically perfect 3D sphere. Pin root terminals, build logs, and global satellite feeds in infinite physical space.</p>
        
        <h3 style="color: #c4b5fd; margin-bottom: 1rem;">Zero-Friction AR Anchoring</h3>
        <p style="margin-bottom: 1.5rem;">Utilize full-color passthrough to physically anchor God-Mode Linux terminals to your living room walls, desk, and real-world environment.</p>
        
        <h3 style="color: #c4b5fd; margin-bottom: 1rem;">Zero-Trust Fluidity</h3>
        <p>Because the <code>root</code> is locked and your operator profile runs on <code>NOPASSWD: ALL</code>, you can execute omnipotent system commands floating in mid-air without ever breaking VR immersion to type a password.</p>
    </div>
'''

if 'vr_spatial_os_matrix.png' not in content:
    # Find the exact Seamless Meta Quest 3 Integration header and insert right after its parent div or the header itself.
    # We will just replace the header with the header + the new HTML
    target_header = '<h2 style="background: linear-gradient(135deg, #e9d5ff, #c084fc, #9333ea); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Seamless Meta Quest 3 Integration</h2>'
    if target_header in content:
        content = content.replace(target_header, target_header + '\n' + vr_html)
        with open(target_file, 'w') as f:
            f.write(content)
        print('VR section injected successfully!')
    else:
        print('Could not find the target header in index.php!')
else:
    print('VR section already exists!')
