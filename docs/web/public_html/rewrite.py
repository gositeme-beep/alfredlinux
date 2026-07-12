import re
import sys

with open("scratch/manifesto.php", "r", encoding="utf-8") as f:
    html = f.read()

# Replace main container with cinematic full width
html = html.replace('<div class="container">', '<div class="cinematic-container">')

# Add basic CSS additions into <style> if needed, but manifesto.php probably needs the cinematic CSS block from gold-master-2026.php.
# Let's read the cinematic CSS from gold-master.
with open("scratch/gold-master-2026.php", "r", encoding="utf-8") as f:
    gold_html = f.read()

css_match = re.search(r'(/\*\s*Cinematic Panels.*?\*/.*?)\s*</style>', gold_html, re.DOTALL)
if css_match:
    cinematic_css = css_match.group(1)
    html = html.replace('</style>', f'\n{cinematic_css}\n</style>')

# We want to replace each <div class="section">...</div> with a cinematic panel.
# But section XII is different.
# Let's split by '<div class="section"'

parts = html.split('<div class="section"')
new_html = parts[0]

themes = ['theme-purple', 'theme-gold', 'theme-danger', 'theme-cyan', '', 'theme-purple', 'theme-danger', 'theme-gold', 'theme-cyan', 'theme-purple', 'theme-danger', '']
pills = [
    'Paradigm Shift', 'Secure By Default', 'Zero Telemetry', 'The Linux Way', 'AI Bias', 'Sovereignty',
    'Honesty', 'Incorruptible', 'Absolute Power', 'The Burning Bush', 'Speed & Invincibility', 'The Kingdom'
]

for i in range(1, len(parts)):
    part = parts[i]
    # Re-add the split string but changed
    # part contains the rest of the section and then '<div class="divider"></div>' or something.
    # We find the end of the section by looking for <div class="divider"></div>
    
    # Actually, we can just replace `<div class="section">` with `<section class="cinematic-panel...`
    # and we have to close it. But where does the section end? It ends before `<div class="divider"></div>`
    
    if 'id="kingdom-architecture"' in part:
        # Section XII - Special formatting
        part = ' id="kingdom-architecture" class="cinematic-panel reveal" style="padding: 4rem 2rem;">' + \
               '<div class="panel-container" style="flex-direction:column; max-width:1200px;">' + \
               part.replace('<h2>', '<div class="pill">The Citadel</div><h2 style="font-size:3rem;text-align:center;">')
        
        # Change the evidence blocks inside Section 12 into a grid
        part = part.replace('<h3', '</div><div class="evidence-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:1.5rem;"><h3 style="grid-column:1/-1;"')
        # Close the last grid
        part = part.replace('<!-- TO GOD BE ALL THE GLORY -->', '</div><!-- TO GOD BE ALL THE GLORY -->')
        
        part += '</section>'
        # We need to strip the original closing </div>
        part = part.rsplit('</div>', 1)[0]
    else:
        # Regular sections (I to XI)
        reverse = ' reverse' if i % 2 == 0 else ''
        theme = themes[(i-1) % len(themes)]
        pill = pills[(i-1) % len(pills)]
        
        # Extract title
        title_match = re.search(r'<h2>(.*?)</h2>', part)
        if title_match:
            title_html = title_match.group(1)
            # Remove title from part
            part = part.replace(f'<h2>{title_html}</h2>', '')
        else:
            title_html = ""
            
        header = f' class="cinematic-panel{reverse} reveal {theme}">\n'
        header += f'    <div class="panel-container">\n'
        header += f'        <div class="panel-text">\n'
        header += f'            <div class="pill">{pill}</div>\n'
        header += f'            <h2>{title_html}</h2>\n'
        
        # The content of the section goes here
        content, remainder = part.split('<div class="divider"></div>', 1) if '<div class="divider"></div>' in part else (part, "")
        
        # Strip the trailing </div> of the original section
        content = content.strip()
        if content.endswith('</div>'):
            content = content[:-6]
            
        # We also need a visual panel
        visual = f'''
        </div>
        <div class="panel-visual">
            <div class="code-window">
                <div class="code-header"><h3>{pill} Protocol</h3></div>
                <div class="code-body">
                    <span class="dim">$</span> systemctl enable alfred-sovereignty<br>
                    > Executing {pill} parameters...<br>
                    <span style="color:var(--green);">[OK] System Secured.</span>
                </div>
            </div>
        </div>
    </div>
</section>
'''
        if remainder:
            remainder = '<div class="divider"></div>' + remainder
            
        part = header + content + visual + remainder

    new_html += '<section' + part

# Fix any stray closing tags or structure issues
new_html = new_html.replace('</section></div>\n\n    <!-- ═══════════════════════════════════════════════════\n         CLOSING', '</section>\n\n    <!-- ═══════════════════════════════════════════════════\n         CLOSING')

with open("scratch/manifesto.php", "w", encoding="utf-8") as f:
    f.write(new_html)

print("Redesign applied successfully.")
