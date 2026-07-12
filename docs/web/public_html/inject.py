file_path = "world-firsts.php"

with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

old_title = "<h2><i class=\"fa-solid fa-shield-halved\"></i> FIRST Native Post-Quantum LUKS2 Full Disk Encryption</h2>"
new_title = "<h2><i class=\"fa-solid fa-shield-halved\"></i> First Native Post-Quantum LUKS2 Full Disk Encryption</h2>"

if old_title in content:
    content = content.replace(old_title, new_title)
    with open(file_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Fixed.")
else:
    print("Not found.")
