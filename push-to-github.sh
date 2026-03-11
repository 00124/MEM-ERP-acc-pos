#!/bin/bash
# Helper script to push the code to GitHub
# Run this in the Replit Shell: bash push-to-github.sh

REPO_URL="https://github.com/00124/ma-electronics-erp.git"

echo "=============================="
echo " Push to GitHub Helper Script"
echo "=============================="
echo ""
echo "Repository: $REPO_URL"
echo ""
echo "You need a GitHub Personal Access Token (PAT) to push."
echo "Create one at: https://github.com/settings/tokens/new"
echo "Required scope: repo (Full control of private repositories)"
echo ""
echo -n "Enter your GitHub username (00124): "
read GH_USER
if [ -z "$GH_USER" ]; then
  GH_USER="00124"
fi

echo -n "Enter your GitHub Personal Access Token: "
read -s GH_TOKEN
echo ""

if [ -z "$GH_TOKEN" ]; then
  echo "Error: Token cannot be empty"
  exit 1
fi

# Set up git config
git config user.email "user@example.com" 2>/dev/null || true
git config user.name "$GH_USER" 2>/dev/null || true

# Add remote with embedded credentials
AUTH_URL="https://${GH_USER}:${GH_TOKEN}@github.com/00124/ma-electronics-erp.git"

echo ""
echo "Setting up remote..."
git remote remove github 2>/dev/null || true
git remote add github "$AUTH_URL"

echo "Pushing to main branch..."
git push github main

if [ $? -eq 0 ]; then
  echo ""
  echo "✅ Successfully pushed to GitHub!"
  echo "View your repo at: https://github.com/00124/ma-electronics-erp"
else
  echo ""
  echo "❌ Push failed. Please check your token and try again."
fi

# Remove remote with credentials for security
git remote remove github 2>/dev/null || true
echo "Cleaned up credentials."
