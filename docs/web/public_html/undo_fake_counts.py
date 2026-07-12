import re

file_path = "/home/gositeme/domains/alfredlinux.com/public_html/download.php"
with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# Revert seed counts
content = content.replace("var seeds = 144000; // Glorified Swarm Count", "var seeds = 1; // Our server is always seeding")
content = content.replace("$count.textContent = '144,000';", "$count.textContent = '1';")
content = content.replace("$count.dataset.serverSeeds = '144000';", "$count.dataset.serverSeeds = '1';")
content = content.replace("'144,000 active in the swarm right now'", "'1 seed online — download to join the swarm'")
content = content.replace("$count.textContent = total.toLocaleString();", "$count.textContent = total;")
content = content.replace("total.toLocaleString() + ' active in the swarm right now'", "total + ' active in the swarm right now'")

# Revert visualizer logic
content = content.replace(
    "var total = Math.max(count, serverSeeds);\n      var visualTotal = Math.min(total, 500);",
    "var total = Math.max(count, serverSeeds);"
)
content = content.replace("if (visualTotal !== lastTotal) {", "if (total !== lastTotal) {")
content = content.replace("while (particles.length < visualTotal) {", "while (particles.length < total) {")
content = content.replace("if (particles.length > visualTotal) {", "if (particles.length > total) {")
content = content.replace("particles.splice(visualTotal);", "particles.splice(total);")
content = content.replace("lastTotal = visualTotal;", "lastTotal = total;")

# Revert explode
content = content.replace(
    "var count = Math.min(swarmCount, 200); // Cap explode particles so we don't freeze the browser",
    "var count = swarmCount;"
)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)
