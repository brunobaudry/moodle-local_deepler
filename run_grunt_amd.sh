#!/usr/bin/env bash
# Compile the plugin's AMD modules with Moodle's Gruntfile. Runs on the host
# (node via nvm) and works with the classic (<= 5.0) and the public/ (>= 5.1)
# Moodle layouts: Gruntfile.js always lives in the project root (MOODLE_ROOT).
set -euo pipefail

# shellcheck source=run_lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/run_lib.sh"

show_help() {
    echo "Usage: $0"
    echo "Compile amd/src/**/*.js of this plugin into amd/build/ using Moodle's grunt setup."
    echo
    echo "The Moodle tree is found two levels above this script (<dirroot>/local/deepler);"
    echo "as a fallback the current working directory is walked up."
}
handle_help_flag show_help "$@"

ORIG_DIR="$PWD"
DEEPLER_DIR="$SCRIPT_DIR"
echo "DeepLer directory: $DEEPLER_DIR"

# True when $1 is a Moodle project root holding a Gruntfile (classic or public layout).
is_grunt_root() {
    [[ -f "$1/config.php" && ( -f "$1/Gruntfile.js" || -f "$1/gruntfile.js" ) \
       && ( -d "$1/admin" || -d "$1/public/admin" ) ]]
}

# Prefer the regular layout detection; when the script is invoked from a
# checkout that is not inside a Moodle tree, walk up from the caller's CWD.
if moodle_detect_layout && is_grunt_root "$MOODLE_ROOT"; then
    moodle_print_layout
else
    MOODLE_ROOT=""
    dir="$ORIG_DIR"
    while [[ "$dir" != "/" ]]; do
        if is_grunt_root "$dir"; then
            MOODLE_ROOT="$dir"
            break
        fi
        dir=$(dirname "$dir")
    done
    if [[ -z "$MOODLE_ROOT" ]]; then
        echo "Error: Moodle root (config.php + Gruntfile.js) not found above $DEEPLER_DIR or $ORIG_DIR."
        exit 1
    fi
    echo "Moodle root   : $MOODLE_ROOT"
    [[ -d "$MOODLE_ROOT/public" ]] && echo "Moodle layout : public (Moodle 5.1+ public/ directory)"
fi

cd "$MOODLE_ROOT"
echo "Working directory: $PWD"

# Ensure NVM is available in non-interactive shells
export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
if [[ -s "$NVM_DIR/nvm.sh" ]]; then
    # Load nvm
    # shellcheck disable=SC1090
    . "$NVM_DIR/nvm.sh"
else
    echo "ERROR: NVM not found at '$NVM_DIR/nvm.sh'."
    echo "Install NVM or set NVM_DIR correctly. See: https://github.com/nvm-sh/nvm#install--update-script"
    exit 1
fi

# Use node version (from .nvmrc if present, otherwise pick one)
if [[ -f ".nvmrc" ]]; then
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
npm install --ignore-scripts
# npx update-browserslist-db@latest --yes

# Ensure the symlink resolution patch is in place
PATCH_FILE="$DEEPLER_DIR/.grunt_symlink_patch.js"
if [[ ! -f "$PATCH_FILE" ]]; then
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
