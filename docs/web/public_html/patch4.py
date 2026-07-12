import re

file_path = "/home/gositeme/domains/alfredlinux.com/public_html/download.php"
with open(file_path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Hide the Operator Truth Box
content = content.replace(
    '<div style="margin:1rem 0 0.5rem;padding:1.25rem 1.5rem;border-radius:14px;border:1px solid rgba(0,206,201,0.35);background:rgba(0,206,201,0.06);text-align:left;max-width:40rem;margin-left:auto;margin-right:auto;">',
    '<div style="display:none;margin:1rem 0 0.5rem;padding:1.25rem 1.5rem;border-radius:14px;border:1px solid rgba(0,206,201,0.35);background:rgba(0,206,201,0.06);text-align:left;max-width:40rem;margin-left:auto;margin-right:auto;">'
)

# 2. Fix the Launch Week Dates
content = content.replace(
    '<span class="launch-day past" data-day="2026-06-15">Mon 15</span>',
    '<span class="launch-day past" data-day="2026-06-29">Mon 29</span>'
)
content = content.replace(
    '<span class="launch-day past" data-day="2026-06-16">Tue 16</span>',
    '<span class="launch-day today" data-day="2026-06-30">Tue 30 🚀🥇</span>'
)
content = content.replace(
    '<span class="launch-day past" data-day="2026-06-17">Wed 17</span>',
    '<span class="launch-day future" data-day="2026-07-01">Wed 01</span>'
)
content = content.replace(
    '<span class="launch-day past" data-day="2026-06-18">Thu 18</span>',
    '<span class="launch-day future" data-day="2026-07-02">Thu 02</span>'
)
content = content.replace(
    '<span class="launch-day past" data-day="2026-06-19">Fri 19</span>',
    '<span class="launch-day future" data-day="2026-07-03">Fri 03</span>'
)
content = content.replace(
    '<span class="launch-day today" data-day="2026-06-20">Sat 20 🚀⚱️</span>',
    '<span class="launch-day future" data-day="2026-07-04">Sat 04</span>'
)
content = content.replace(
    '<span class="launch-day" data-day="2026-06-21">Sun 21</span>',
    '<span class="launch-day future" data-day="2026-07-05">Sun 05</span>'
)
content = content.replace("<strong>Sat Jun 20, 6:00 PM Eastern</strong>", "<strong>Tue Jun 30, 6:00 PM Eastern</strong>")
content = content.replace('<span style="color:var(--gold);font-size:1.15rem;font-weight:800;">Saturday, June 20th, 2026 · 6:00 PM Eastern</span>', '<span style="color:var(--gold);font-size:1.15rem;font-weight:800;">Tuesday, June 30th, 2026 · 6:00 PM Eastern</span>')

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content)
