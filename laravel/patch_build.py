#!/usr/bin/env python3
"""
Apply license/app-check patches to the Vite build.
Run after every: npm run build
Usage: python3 patch_build.py
"""
import re, glob, os, sys

assets_dir = os.path.join(os.path.dirname(__file__), "public", "build", "assets")
app_files = glob.glob(os.path.join(assets_dir, "app-*.js"))

if not app_files:
    print("ERROR: No app-*.js found in", assets_dir)
    sys.exit(1)

for fname in app_files:
    with open(fname, "r", encoding="utf-8") as f:
        content = f.read()

    original = content

    # Patch 1: verified_name → always true (!0)
    content = re.sub(
        r"(verified_name:[A-Za-z0-9_]+,value:)!1",
        r"\g<1>!0",
        content,
    )

    # Patch 2: appChecking → always false (!1)
    content = re.sub(r"appChecking:!0,", "appChecking:!1,", content)

    if content == original:
        print(f"WARNING: No changes made to {os.path.basename(fname)} — patterns may have shifted")
    else:
        with open(fname, "w", encoding="utf-8") as f:
            f.write(content)

    # Verify
    vn = re.findall(r"verified_name:[A-Za-z0-9_]+,value:!.", content)
    ac = re.findall(r"appChecking:!.", content)[:2]
    print(f"Patched {os.path.basename(fname)}:")
    print(f"  verified_name results : {vn}")
    print(f"  appChecking results   : {ac}")
