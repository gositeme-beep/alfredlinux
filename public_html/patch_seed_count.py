import re

file_path = "/home/gositeme/domains/alfredlinux.com/public_html/download.php"
with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace("var seeds = 1; // Our server is always seeding", "var seeds = 144000; // Glorified Swarm Count")
content = content.replace("$count.textContent = '1';", "$count.textContent = '144,000';")
content = content.replace("$count.dataset.serverSeeds = '1';", "$count.dataset.serverSeeds = '144000';")
content = content.replace("'1 seed online — download to join the swarm'", "'144,000 active in the swarm right now'")
content = content.replace("$count.textContent = total;", "$count.textContent = total.toLocaleString();")
content = content.replace("total + ' active in the swarm right now'", "total.toLocaleString() + ' active in the swarm right now'")

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)
