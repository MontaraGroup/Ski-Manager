#!/usr/bin/env python3
import os
import datetime
import re
import subprocess

def update_updates_view():
    # Fetch commits merged in this release
    cmd = ["git", "log", "origin/main..HEAD", "--pretty=format:%s (%h)"]
    res = subprocess.run(cmd, capture_output=True, text=True)
    commits = [l.strip() for l in res.stdout.strip().split("\n") if l.strip()]

    if not commits:
        commits = ["Routine maintenance and system updates"]

    date_str = datetime.datetime.now().strftime("%B %d, %Y")
    time_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M")

    feats, fixes, improvements = [], [], []
    for c in commits:
        c_clean = c.replace('<', '&lt;').replace('>', '&gt;')
        if re.match(r'^(feat|feature)', c, re.I):
            feats.append(c_clean)
        elif re.match(r'^(fix|bug)', c, re.I):
            fixes.append(c_clean)
        else:
            improvements.append(c_clean)

    sections_html = ""
    if feats:
        items = "".join([f'<li class="flex items-start gap-2 text-sm text-base-content/80"><span class="w-1.5 h-1.5 rounded-full bg-success mt-1.5 shrink-0"></span><span>{f}</span></li>' for f in feats])
        sections_html += f'<div><div class="flex items-center gap-2 mb-2"><span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-plus text-[10px]"></i>New Features</span><span class="text-xs text-base-content/40">{len(feats)}</span></div><ul class="space-y-1.5 ml-1">{items}</ul></div>'

    if improvements:
        items = "".join([f'<li class="flex items-start gap-2 text-sm text-base-content/80"><span class="w-1.5 h-1.5 rounded-full bg-info mt-1.5 shrink-0"></span><span>{i}</span></li>' for i in improvements])
        sections_html += f'<div><div class="flex items-center gap-2 mb-2"><span class="badge badge-info badge-sm gap-1"><i class="fa-solid fa-arrow-up-long text-[10px]"></i>Improvements</span><span class="text-xs text-base-content/40">{len(improvements)}</span></div><ul class="space-y-1.5 ml-1">{items}</ul></div>'

    if fixes:
        items = "".join([f'<li class="flex items-start gap-2 text-sm text-base-content/80"><span class="w-1.5 h-1.5 rounded-full bg-error mt-1.5 shrink-0"></span><span>{fx}</span></li>' for fx in fixes])
        sections_html += f'<div><div class="flex items-center gap-2 mb-2"><span class="badge badge-error badge-sm gap-1"><i class="fa-solid fa-bug text-[10px]"></i>Bug Fixes</span><span class="text-xs text-base-content/40">{len(fixes)}</span></div><ul class="space-y-1.5 ml-1">{items}</ul></div>'

    new_card = f'''        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-primary"></span>
            <div class="card bg-base-100 shadow-sm border border-primary">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-primary font-mono">Release</span>
                        <span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-star text-[10px]"></i>Latest</span>
                        <span class="badge badge-outline badge-sm">Patch</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>{date_str}</span>
                    </div>
                    <h2 class="text-lg font-bold">Release Update ({time_str})</h2>
                    <p class="text-sm text-base-content/70 mt-1">Automated production release containing recent system enhancements and bug fixes.</p>
                    <div class="mt-4 space-y-4">{sections_html}</div>
                </div>
            </div>
        </div>'''

    view_path = "app/Views/updates/index.php"
    if os.path.exists(view_path):
        with open(view_path, "r", encoding="utf-8") as f:
            content = f.read()

        # Demote previous top entry 'Latest' badge and styling
        content = content.replace('bg-primary"></span>', 'bg-base-300"></span>', 1)
        content = content.replace('border border-primary', 'border border-base-300', 1)
        content = content.replace('badge badge-primary font-mono', 'badge badge-ghost font-mono', 1)
        content = content.replace('<span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-star text-[10px]"></i>Latest</span>', '', 1)

        marker = '<div class="relative border-l-2 border-base-300 ml-3 space-y-8">'
        if marker in content:
            content = content.replace(marker, marker + "\n" + new_card, 1)
            with open(view_path, "w", encoding="utf-8") as f:
                f.write(content)
            print("Successfully updated app/Views/updates/index.php")
        else:
            print("Warning: Could not locate timeline marker in app/Views/updates/index.php")

if __name__ == "__main__":
    update_updates_view()
