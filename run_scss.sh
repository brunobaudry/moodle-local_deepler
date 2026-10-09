#!/usr/bin/env bash
# Compile the plugin's SCSS into styles.css, fix stylelint issues using
# Moodle root's stylelint configuration, and watch for SCSS changes.
#
# Compatible with Moodle 4.5+ through 5.1+ (both classic and public layouts).
# - Moodle <= 5.0 (classic layout): target is local/deepler/styles.css
# - Moodle >= 5.1 (public layout):  target is public/local/deepler/styles.css
set -euo pipefail

# shellcheck source=run_lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/run_lib.sh"

show_help() {
    echo "Usage: $0 [--watch | --no-watch | --once]"
    echo "Compile scss/styles.scss into styles.css, run stylelint --fix, and start watching scss/styles.scss."
    echo
    echo "Options:"
    echo "  --watch, -w         Watch scss/styles.scss for changes after initial compile and lint (default)."
    echo "  --no-watch, --once  Compile and run stylelint once without entering watch mode."
    echo "  -h, --help          Show this help message and exit."
    echo
    echo "The Moodle tree is found two levels above this script (<dirroot>/local/deepler);"
    echo "as a fallback the current working directory is walked up."
}
handle_help_flag show_help "$@"

watch_mode=true
for arg in "$@"; do
    case "$arg" in
        --no-watch|--once)
            watch_mode=false
            ;;
        --watch|-w)
            watch_mode=true
            ;;
    esac
done

ORIG_DIR="$PWD"
DEEPLER_DIR="$SCRIPT_DIR"
echo "DeepLer directory: $DEEPLER_DIR"

# True when $1 is a Moodle project root (classic or public layout).
is_moodle_project_root() {
    [[ -f "$1/config.php" && ( -d "$1/admin" || -d "$1/public/admin" ) ]]
}

# Layout detection via run_lib with directory walk fallback.
if moodle_detect_layout; then
    moodle_print_layout
else
    MOODLE_ROOT=""
    dir="$ORIG_DIR"
    while [[ "$dir" != "/" ]]; do
        if is_moodle_project_root "$dir"; then
            MOODLE_ROOT="$dir"
            break
        fi
        dir=$(dirname "$dir")
    done
    if [[ -z "$MOODLE_ROOT" ]]; then
        echo "ERROR: Moodle root not found above $DEEPLER_DIR or $ORIG_DIR."
        exit 1
    fi
    echo "Moodle root   : $MOODLE_ROOT"
    if [[ -d "$MOODLE_ROOT/public" && -d "$MOODLE_ROOT/public/admin" ]]; then
        MOODLE_LAYOUT="public"
        DIRROOT="$MOODLE_ROOT/public"
    else
        MOODLE_LAYOUT="classic"
        DIRROOT="$MOODLE_ROOT"
    fi
    echo "Moodle layout : $MOODLE_LAYOUT"
fi

# 1. Compile SCSS in the plugin folder.
echo "==> Compiling SCSS in $DEEPLER_DIR..."
if ! command -v sass &>/dev/null; then
    echo "ERROR: 'sass' command not found in PATH."
    echo "Please install Sass (e.g. 'npm install -g sass' or via your package manager)."
    exit 1
fi

if [[ ! -f "$DEEPLER_DIR/scss/styles.scss" ]]; then
    echo "ERROR: $DEEPLER_DIR/scss/styles.scss not found."
    exit 1
fi

(cd "$DEEPLER_DIR" && sass scss/styles.scss:styles.css)
echo "SCSS compiled successfully to styles.css."

# 2. Ensure Node/NVM is available for npx stylelint.
export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
if [[ -s "$NVM_DIR/nvm.sh" ]]; then
    # shellcheck disable=SC1090
    . "$NVM_DIR/nvm.sh"
    if [[ -f "$MOODLE_ROOT/.nvmrc" ]]; then
        (cd "$MOODLE_ROOT" && nvm use)
    fi
fi

# 3. Determine target path relative to MOODLE_ROOT and run stylelint --fix.
relpath=$(plugin_relpath 2>/dev/null || echo "local/deepler")
if [[ "$MOODLE_LAYOUT" == "public" ]]; then
    STYLELINT_TARGET="public/$relpath/styles.css"
else
    STYLELINT_TARGET="$relpath/styles.css"
fi

echo "==> Running stylelint --fix from $MOODLE_ROOT on $STYLELINT_TARGET..."
(cd "$MOODLE_ROOT" && npx stylelint "$STYLELINT_TARGET" --fix)

echo "Initial compilation and stylelint fix completed successfully."

# 4. Start watching for SCSS changes if watch mode is enabled.
if [[ "$watch_mode" == true ]]; then
    echo "==> Starting Sass watcher on scss/styles.scss -> styles.css..."
    echo "Press Ctrl+C to stop watching."
    (cd "$DEEPLER_DIR" && exec sass --watch scss/styles.scss:styles.css)
fi
