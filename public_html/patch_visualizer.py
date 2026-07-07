import re

file_path = "/home/gositeme/domains/alfredlinux.com/public_html/download.php"
with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# Cap the visualizer particles
content = content.replace(
    "var total = Math.max(count, serverSeeds);",
    "var total = Math.max(count, serverSeeds);\n      var visualTotal = Math.min(total, 500);"
)
content = content.replace("if (total !== lastTotal) {", "if (visualTotal !== lastTotal) {")
content = content.replace("while (particles.length < total) {", "while (particles.length < visualTotal) {")
content = content.replace("if (particles.length > total) {", "if (particles.length > visualTotal) {")
content = content.replace("particles.splice(total);", "particles.splice(visualTotal);")
content = content.replace("lastTotal = total;", "lastTotal = visualTotal;")

# Cap the explode particle count
content = content.replace(
    "var count = swarmCount;",
    "var count = Math.min(swarmCount, 200); // Cap explode particles so we don't freeze the browser"
)

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)
