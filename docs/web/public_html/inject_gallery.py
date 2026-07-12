import sys
import re

target_file = '/home/gositeme/domains/alfredlinux.com/public_html/index.php'
with open(target_file, 'r') as f:
    content = f.read()

gallery_html = '''
        <h3 style="color: #c4b5fd; margin-bottom: 1rem; margin-top: 3rem; text-align: center;">Gallery of the Future</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
            <div style="border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; overflow: hidden; box-shadow: 0 0 15px rgba(0,0,0,0.5);">
                <img src="vr_passthrough_kitchen.png" alt="AR Passthrough Kitchen Terminal" style="width: 100%; height: auto; display: block;">
                <div style="padding: 1rem; background: rgba(0,0,0,0.8);"><p style="margin:0; color: #a78bfa; font-size: 0.9rem;">Zero-Friction AR Anchoring</p></div>
            </div>
            <div style="border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; overflow: hidden; box-shadow: 0 0 15px rgba(0,0,0,0.5);">
                <img src="vr_orbital_command_center.png" alt="360-Degree Orbital Command Center" style="width: 100%; height: auto; display: block;">
                <div style="padding: 1rem; background: rgba(0,0,0,0.8);"><p style="margin:0; color: #a78bfa; font-size: 0.9rem;">Orbital Command Matrix</p></div>
            </div>
            <div style="border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; overflow: hidden; box-shadow: 0 0 15px rgba(0,0,0,0.5);">
                <img src="vr_hacker_ide.png" alt="Cyberpunk VR IDE" style="width: 100%; height: auto; display: block;">
                <div style="padding: 1rem; background: rgba(0,0,0,0.8);"><p style="margin:0; color: #a78bfa; font-size: 0.9rem;">Immersive God-Mode Execution</p></div>
            </div>
        </div>
'''

if 'Gallery of the Future' not in content:
    # Inject before the closing div of the VR section
    target_string = 'without ever breaking VR immersion to type a password.</p>\n    </div>'
    if target_string in content:
        content = content.replace(target_string, 'without ever breaking VR immersion to type a password.</p>\n' + gallery_html + '\n    </div>')
        with open(target_file, 'w') as f:
            f.write(content)
        print('Gallery injected successfully!')
    else:
        print('Could not find target string for injection!')
else:
    print('Gallery already exists!')
