#!/usr/bin/env bash
set -euo pipefail

ORIG_DIR="$PWD"
DEEPLER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "DeepLer directory: $DEEPLER_DIR"

FOUND_DIR=''
find_parent_with_items() {
    local dir="$DEEPLER_DIR"

    while [[ "$dir" != "/" ]]; do
        if [[ -f "$dir/config.php" && ( -f "$dir/Gruntfile.js" || -f "$dir/gruntfile.js" ) && -d "$dir/admin" ]]; then
            FOUND_DIR="$dir"
            return 0
        fi

        dir=$(dirname "$dir")
    done

    # If not found through symlink target hierarchy, check current working directory
    dir="$ORIG_DIR"
    while [[ "$dir" != "/" ]]; do
        if [[ -f "$dir/config.php" && ( -f "$dir/Gruntfile.js" || -f "$dir/gruntfile.js" ) && -d "$dir/admin" ]]; then
            FOUND_DIR="$dir"
            return 0
        fi

        dir=$(dirname "$dir")
    done

    return 1
}

if find_parent_with_items; then
    echo "Moodle parent found at: $FOUND_DIR"
    cd "$FOUND_DIR"
else
    echo "Error: Moodle parent directory not found."
    exit 1
fi

if [[ -d "$PWD/public" ]]; then
    echo "Moodle 5.1+ directory structure detected."
fi

echo "Working directory: $PWD"

# Ensure NVM is available in non-interactive shells
export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
if [ -s "$NVM_DIR/nvm.sh" ]; then
    # Load nvm
    # shellcheck disable=SC1090
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

# Print versions for debugging
node -v
npm -v
npm install
npx update-browserslist-db@latest --yes

# Ensure the symlink resolution patch is in place
PATCH_FILE="$DEEPLER_DIR/.grunt_symlink_patch.js"
if [ ! -f "$PATCH_FILE" ]; then
    cat << 'EOF' > "$PATCH_FILE"
const fs = require('fs');
const path = require('path');

const moodleRoot = process.cwd();
const origRealpathSync = fs.realpathSync;

function safeRealpath(targetPath, options) {
    const absPath = path.isAbsolute(targetPath) ? path.normalize(targetPath) : path.normalize(path.resolve(moodleRoot, targetPath));
    const isRelative = !path.isAbsolute(targetPath);

    if (absPath.startsWith(moodleRoot)) {
        try {
            const resolved = origRealpathSync(targetPath, options);
            if (!resolved.startsWith(moodleRoot)) {
                return isRelative ? path.relative(moodleRoot, absPath) || '.' : absPath;
            }
            return resolved;
        } catch (e) {
            return isRelative ? path.relative(moodleRoot, absPath) || '.' : absPath;
        }
    }

    return origRealpathSync(targetPath, options);
}

fs.realpathSync = function(targetPath, options) {
    return safeRealpath(targetPath, options);
};

if (fs.realpathSync.native) {
    fs.realpathSync.native = function(targetPath, options) {
        return safeRealpath(targetPath, options);
    };
}
EOF
fi

export NODE_OPTIONS="${NODE_OPTIONS:-} -r $PATCH_FILE"

# Run grunt AMD compilation task
npx grunt amd --files="$DEEPLER_DIR/amd/src/*.js,$DEEPLER_DIR/amd/src/**/*.js" --force

# Return to original directory
cd "$ORIG_DIR"
echo "AMD compilation completed successfully."
