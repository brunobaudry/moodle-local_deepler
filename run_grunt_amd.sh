
#!/usr/bin/env bash
set -euo pipefail

# Go to project root
cd ../../../

# Ensure NVM is available in non-interactive shells
export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
if [ -s "$NVM_DIR/nvm.sh" ]; then
  # Load nvm
  . "$NVM_DIR/nvm.sh"
else
  echo "ERROR: NVM not found at '$NVM_DIR/nvm.sh'."
  echo "Install NVM or set NVM_DIR correctly. See: https://github.com/nvm-sh/nvm#install--update-script"
  exit 1
fi

# Use node version (from .nvmrc if present, otherwise pick one)
if [ -f ".nvmrc" ]; then
  echo "Using Node version from .nvmrc:"
  nvm use
else
  echo "No .nvmrc found; using latest LTS Node."
  nvm install --lts --no-progress
  nvm use --lts
fi

# Optional: print versions for debugging
node -v
npm -v

# Run grunt task
npx grunt amd --files="public/local/deepler/amd/src/*.js,public/local/deepler/amd/src/local/*.js" --force

# Optional CSS task
# npx grunt css --files="public/local/deepler/scss/*" --force

# Return to previous directory
cd
