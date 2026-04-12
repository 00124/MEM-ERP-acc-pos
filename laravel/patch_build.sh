#!/bin/bash
# Run after every: npm run build
# Usage: bash patch_build.sh

APP=$(ls public/build/assets/app-*.js 2>/dev/null | head -1)
if [ -z "$APP" ]; then
    echo "ERROR: No app-*.js found in public/build/assets/"
    exit 1
fi

echo "Patching: $APP"
sed -E -i 's/(verified_name:[A-Za-z0-9_]+,value:)!1/\1!0/g' "$APP"
sed -i 's/appChecking:!0,/appChecking:!1,/g' "$APP"

echo "Results:"
grep -oE "verified_name:[A-Za-z0-9_]+,value:!." "$APP"
grep -oE "appChecking:!.,." "$APP" | head -2
