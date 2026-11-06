#!/usr/bin/env python3
import os
import datetime
import re
import subprocess
import json

def get_next_version(content):
    # Search for existing version badges in JSON or HTML
    json_path = "app/Views/updates/updates.json"
    if os.path.exists(json_path):
        try:
            with open(json_path, "r", encoding="utf-8") as f:
                data = json.load(f)
            if data and isinstance(data, list) and "version" in data[0]:
                top_v = str(data[0]["version"]).lstrip("v")
                parts = [int(p) for p in top_v.split(".") if p.isdigit()]
                if len(parts) >= 2:
                    patch = parts[2] if len(parts) > 2 else 0
                    return f"v{parts[0]}.{parts[1]}.{patch + 1}"
        except Exception:
            pass

    matches = re.findall(r'font-mono">\s*v?(\d+)\.(\d+)(?:\.(\d+))?', content)
    if not matches:
        matches = re.findall(r'\bv?(\d+)\.(\d+)(?:\.(\d+))?\b', content)

    versions = []
    for m in matches:
        major = int(m[0])
        minor = int(m[1])
        patch = int(m[2]) if m[2] else 0
        versions.append((major, minor, patch))

    if versions:
        latest = max(versions)
        next_ver = (latest[0], latest[1], latest[2] + 1)
    else:
        next_ver = (1, 4, 32)

    return f"v{next_ver[0]}.{next_ver[1]}.{next_ver[2]}"

def update_updates_view():
    view_path = "app/Views/updates/index.php"
    content = ""
    if os.path.exists(view_path):
        with open(view_path, "r", encoding="utf-8") as f:
            content = f.read()

    version_str = get_next_version(content)

    # Fetch commits merged in this release
    cmd = ["git", "log", "origin/main..HEAD", "--pretty=format:%s (%h)"]
    res = subprocess.run(cmd, capture_output=True, text=True)
    commits = [l.strip() for l in res.stdout.strip().split("\n") if l.strip()]

    if not commits:
        commits = ["Routine maintenance and system updates"]

    date_str = datetime.datetime.now().strftime("%B %d, %Y")

    feats, fixes, improvements = [], [], []
    for c in commits:
        c_clean = c.replace('<', '&lt;').replace('>', '&gt;')
        if re.match(r'^(feat|feature)', c, re.I):
            feats.append(c_clean)
        elif re.match(r'^(fix|bug)', c, re.I):
            fixes.append(c_clean)
        else:
            improvements.append(c_clean)

    # Update app/Views/updates/updates.json if present
    json_path = "app/Views/updates/updates.json"
    if os.path.exists(json_path):
        try:
            with open(json_path, "r", encoding="utf-8") as f:
                updates_data = json.load(f)

            for item in updates_data:
                item["isLatest"] = False

            cats = []
            if feats:
                cats.append({
                    "type": "new",
                    "label": "New Features",
                    "icon": "sparkles",
                    "badge": "success",
                    "items": [{"title": f, "description": ""} for f in feats]
                })
            if improvements:
                cats.append({
                    "type": "improved",
                    "label": "Improvements",
                    "icon": "arrow-up-long",
                    "badge": "info",
                    "items": [{"title": i, "description": ""} for i in improvements]
                })
            if fixes:
                cats.append({
                    "type": "fixed",
                    "label": "Bug Fixes",
                    "icon": "wrench",
                    "badge": "warning",
                    "items": [{"title": fx, "description": ""} for fx in fixes]
                })

            new_release = {
                "id": len(updates_data) + 1,
                "version": version_str,
                "title": f"Release {version_str} Update",
                "description": "Automated production release containing recent system enhancements and bug fixes.",
                "release_type": "patch",
                "released_at": date_str,
                "releasedAtFormatted": date_str,
                "isLatest": True,
                "categories": cats
            }
            updates_data.insert(0, new_release)
            with open(json_path, "w", encoding="utf-8") as f:
                json.dump(updates_data, f, indent=2)
            print(f"Successfully updated {json_path} to {version_str}")
        except Exception as e:
            print(f"Warning: could not update {json_path}: {e}")

    # Fallback / legacy index.php HTML marker update if present
    marker = '<div class="relative border-l-2 border-base-300 ml-3 space-y-8">'
    if marker in content:
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
                        <span class="badge badge-primary font-mono">{version_str}</span>
                        <span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-star text-[10px]"></i>Latest</span>
                        <span class="badge badge-outline badge-sm">Patch</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>{date_str}</span>
                    </div>
                    <h2 class="text-lg font-bold">Release {version_str} Update</h2>
                    <p class="text-sm text-base-content/70 mt-1">Automated production release containing recent system enhancements and bug fixes.</p>
                    <div class="mt-4 space-y-4">{sections_html}</div>
                </div>
            </div>
        </div>'''

        content = content.replace('bg-primary"></span>', 'bg-base-300"></span>', 1)
        content = content.replace('border border-primary', 'border border-base-300', 1)
        content = content.replace('badge badge-primary font-mono', 'badge badge-ghost font-mono', 1)
        content = content.replace('<span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-star text-[10px]"></i>Latest</span>', '', 1)
        content = content.replace(marker, marker + "\n" + new_card, 1)

        with open(view_path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"Successfully updated {view_path} to {version_str}")

if __name__ == "__main__":
    update_updates_view()
