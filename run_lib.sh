#!/bin/bash
# Shared helpers for run_tests.sh, run_behats.sh, run_grunt_amd.sh and run_scss.sh.
#
# Source it, do not execute it:
#     source "$(dirname "${BASH_SOURCE[0]}")/run_lib.sh"
#
# Everything here is compatible with `set -euo pipefail`.
#
# Supported Moodle layouts (the plugin always sits at <dirroot>/local/deepler):
#   classic  (<= 5.0): <root>/{config.php,vendor,Gruntfile.js,admin,lib,local/deepler}
#   public   (>= 5.1): <root>/{config.php,composer.json,vendor,Gruntfile.js}
#                      <root>/public/{admin,lib,local/deepler}
#                      (<root>/public/config.php is a stub loading ../config.php)
#
# After `moodle_detect_layout` the following variables are set:
#   DIRROOT       Moodle code root ($CFG->dirroot): where admin/ and lib/ live.
#   MOODLE_ROOT   Project root: where config.php, composer.json/vendor/ and
#                 Gruntfile.js live. Same as DIRROOT on classic layouts.
#   MOODLE_LAYOUT "classic" or "public".

# ---------------------------------------------------------------------------
# Script location
# ---------------------------------------------------------------------------
# SCRIPT_DIR keeps the logical (possibly symlinked) path, SCRIPT_DIR_REAL the
# physical one. Both are needed: this plugin is normally a standalone git
# checkout symlinked into a Moodle tree, so only the in-tree path has Moodle's
# lib/ two levels up and only it lives inside the DDEV project.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[1]:-${BASH_SOURCE[0]}}")" && pwd)"
SCRIPT_DIR_REAL="$(cd "$(dirname "${BASH_SOURCE[1]:-${BASH_SOURCE[0]}}")" && pwd -P)"
SCRIPT_NAME="$(basename "${BASH_SOURCE[1]:-${BASH_SOURCE[0]}}")"

DDEV_MOUNT="/var/www/html"   # DDEV always mounts the project root here.

# ---------------------------------------------------------------------------
# Plugin identity
# ---------------------------------------------------------------------------
# Moodle component of this plugin, e.g. local_deepler.
plugin_component() {
    local component
    component=$(grep -m1 -oE "component[[:space:]]*=[[:space:]]*'[^']+'" "$SCRIPT_DIR/version.php" 2>/dev/null \
                | grep -oE "'[^']+'" | tr -d "'")
    [[ "$component" == *_* ]] || return 1
    echo "$component"
}

# Path of this plugin relative to a Moodle dirroot, e.g. local/deepler.
plugin_relpath() {
    local component
    component=$(plugin_component) || return 1
    echo "${component%%_*}/${component#*_}"
}

# ---------------------------------------------------------------------------
# Moodle layout detection
# ---------------------------------------------------------------------------
# True when $1 looks like a Moodle dirroot (admin/ and lib/ live there).
is_moodle_dirroot() {
    [[ -d "$1/admin" && -f "$1/lib/setup.php" ]]
}

# Sets DIRROOT, MOODLE_ROOT and MOODLE_LAYOUT (see header). Returns 1 when the
# plugin does not sit inside a Moodle tree.
moodle_detect_layout() {
    local candidate cand_logical cand_real
    DIRROOT=""
    MOODLE_ROOT=""
    MOODLE_LAYOUT=""
    # The plugin is expected at <dirroot>/local/deepler: try the logical path
    # first (symlinked checkout inside a Moodle tree), then the physical one.
    cand_logical="$(dirname "$(dirname "$SCRIPT_DIR")")"
    cand_real="$(dirname "$(dirname "$SCRIPT_DIR_REAL")")"
    for candidate in "$cand_logical" "$cand_real" "$SCRIPT_DIR/../.." "$SCRIPT_DIR_REAL/../.."; do
        if is_moodle_dirroot "$candidate"; then
            DIRROOT="$(cd "$candidate" && pwd)"
            break
        fi
    done
    [[ -n "$DIRROOT" ]] || return 1

    # Moodle 5.1+ keeps composer.json, vendor/ and the real config.php one
    # level above public/. Do not rely on public/config.php being absent: since
    # 5.1 Moodle ships a stub public/config.php that merely loads ../config.php.
    if [[ "$(basename "$DIRROOT")" == "public" && ! -f "$DIRROOT/composer.json" \
          && ( -f "$DIRROOT/../composer.json" || -f "$DIRROOT/../config.php" ) ]]; then
        MOODLE_ROOT="$(cd "$DIRROOT/.." && pwd)"
        MOODLE_LAYOUT="public"
    else
        MOODLE_ROOT="$DIRROOT"
        MOODLE_LAYOUT="classic"
    fi
    export DIRROOT MOODLE_ROOT MOODLE_LAYOUT
}

# Print a human readable summary of the detected layout.
moodle_print_layout() {
    echo "Moodle layout : $MOODLE_LAYOUT$([[ "$MOODLE_LAYOUT" == "public" ]] && echo ' (Moodle 5.1+ public/ directory)')"
    echo "Moodle root   : $MOODLE_ROOT"
    [[ "$MOODLE_ROOT" != "$DIRROOT" ]] && echo "Moodle dirroot: $DIRROOT"
    return 0
}

# Fail loudly when the plugin is not inside a Moodle tree.
moodle_require_layout() {
    moodle_detect_layout && return 0
    echo "ERROR: Cannot find Moodle's admin/ and lib/ two levels above this script."
    echo "       The plugin must be installed at <dirroot>/$(plugin_relpath 2>/dev/null || echo local/deepler)"
    echo "       (<moodle>/public/$(plugin_relpath 2>/dev/null || echo local/deepler) on Moodle 5.1+)."
    echo "       In a DDEV container, invoke with:"
    echo "         ddev exec --dir $DDEV_MOUNT/<moodle>[/public]/$(plugin_relpath 2>/dev/null || echo local/deepler) bash $SCRIPT_NAME"
    return 1
}

# Read a scalar $CFG->name value from Moodle's config.php (quotes stripped).
# Usage: moodle_cfg behat_dataroot
moodle_cfg() {
    local file="$MOODLE_ROOT/config.php"
    [[ -f "$file" ]] || file="$DIRROOT/config.php"
    [[ -f "$file" ]] || return 1
    sed -nE "s/^[[:space:]]*\\\$CFG->$1[[:space:]]*=[[:space:]]*['\"]([^'\"]*)['\"].*/\\1/p" "$file" | head -n1
}

# Composer binary lookup: vendor/ sits in the project root, but be lenient and
# fall back to dirroot for exotic setups. Usage: moodle_vendor_bin behat
moodle_vendor_bin() {
    local root
    for root in "$MOODLE_ROOT" "$DIRROOT"; do
        if [[ -x "$root/vendor/bin/$1" ]]; then
            echo "$root/vendor/bin/$1"
            return 0
        fi
    done
    return 1
}

# ---------------------------------------------------------------------------
# DDEV detection: if ddev is installed and a project holding this plugin is
# running, re-run the calling script inside that project's web container.
#
# Set MOODLE_DDEV_ROOT to pin a project root and skip autodetection.
# ---------------------------------------------------------------------------

# True when we already run inside a container (DDEV web container or docker).
in_container() {
    [[ -n "${IS_DDEV_PROJECT:-}" || -n "${DDEV_SITENAME:-}" || -f "/.dockerenv" ]]
}

# Walk up from $1 looking for a DDEV *project* root. The marker is
# .ddev/config.yaml, not .ddev/: ~/.ddev is DDEV's global config directory and
# would otherwise match for any plugin checkout living under $HOME.
find_ddev_root() {
    local dir="$1"
    while [[ -n "$dir" && "$dir" != "/" ]]; do
        if [[ -f "$dir/.ddev/config.yaml" ]]; then
            echo "${dir%/}"
            return 0
        fi
        dir="$(dirname "$dir")"
    done
    return 1
}

# Invoked through the standalone checkout, which sits outside every DDEV
# project? Then ask DDEV which registered project symlinks this plugin into
# place. Running projects win: the same checkout is often linked into several.
# Both classic (<docroot>/local/deepler) and public (<docroot>/public/local/deepler)
# layouts are probed.
find_project_hosting_plugin() {
    local relpath approot docroot candidate sub wanted list
    relpath=$(plugin_relpath) || return 1
    command -v jq &>/dev/null || return 1
    list=$(ddev list -j 2>/dev/null) || return 1
    for wanted in running any; do
        while IFS=$'\t' read -r approot docroot; do
            [[ -n "$approot" ]] || continue
            docroot="${docroot#./}"
            docroot="${docroot%/}"
            for sub in "" "public"; do
                candidate="${approot%/}${docroot:+/$docroot}${sub:+/$sub}/$relpath"
                [[ -d "$candidate" ]] || continue
                [[ "$(cd "$candidate" && pwd -P)" == "$SCRIPT_DIR_REAL" ]] || continue
                printf '%s\t%s\n' "${approot%/}" "$candidate"
                return 0
            done
        done < <(jq -r --arg w "$wanted" \
            '.raw[] | select($w == "any" or .status == $w) | [.approot, (.docroot // "")] | @tsv' <<<"$list")
    done
    return 1
}

# Read-only status probe. Do not use `ddev exec` for this: it auto-starts a
# stopped project as a side effect.
ddev_project_status() {
    local json
    json=$(cd "$1" && ddev describe -j 2>/dev/null) || return 1
    if command -v jq &>/dev/null; then
        jq -r '.raw.status // empty' <<<"$json"
    elif [[ "$json" == *'"status":"running"'* ]]; then
        echo "running"
    fi
}

# Re-run the calling script inside the DDEV web container when a running
# project hosts this plugin. Exits with the container run's status when the
# delegation happened; returns 0 (and prints a hint) otherwise so the caller
# can continue locally.
# Usage: ddev_delegate "$@"
ddev_delegate() {
    local ddev_root="" plugin_dir="" hosting candidate container_dir ddev_status wwwroot
    in_container && return 0
    command -v ddev &>/dev/null || return 0

    if [[ -n "${MOODLE_DDEV_ROOT:-}" ]]; then
        ddev_root="${MOODLE_DDEV_ROOT%/}"
    elif ddev_root=$(find_ddev_root "$SCRIPT_DIR"); then
        :
    elif ddev_root=$(find_ddev_root "$SCRIPT_DIR_REAL"); then
        :
    elif hosting=$(find_project_hosting_plugin); then
        ddev_root="${hosting%%$'\t'*}"
        plugin_dir="${hosting#*$'\t'}"
    else
        ddev_root=""
    fi

    # Which host path represents this plugin *inside* that project? Only a path
    # below the project root can be mapped into the container.
    if [[ -n "$ddev_root" && -z "$plugin_dir" ]]; then
        for candidate in "$SCRIPT_DIR" "$SCRIPT_DIR_REAL"; do
            if [[ "$candidate" == "$ddev_root/"* ]]; then
                plugin_dir="$candidate"
                break
            fi
        done
    fi

    if [[ -n "$ddev_root" && -n "$plugin_dir" ]]; then
        container_dir="$DDEV_MOUNT/${plugin_dir#"$ddev_root"/}"
        ddev_status=$(ddev_project_status "$ddev_root") || ddev_status=""
        if [[ "$ddev_status" == "running" ]]; then
            wwwroot=$(cd "$ddev_root" && ddev describe -j 2>/dev/null \
                      | jq -r '.raw.primary_url // empty' 2>/dev/null) || wwwroot=""
            echo "DDEV project : $ddev_root${wwwroot:+  ($wwwroot)}"
            echo "Container dir: $container_dir"
            echo "DDEV is running — executing $SCRIPT_NAME inside the web container..."
            (cd "$ddev_root" && ddev exec --dir "$container_dir" bash "$SCRIPT_NAME" "$@")
            exit $?
        fi
        echo "DDEV project $ddev_root is not running (status: ${ddev_status:-unknown})."
        echo "Start it with:  (cd \"$ddev_root\" && ddev start)"
        echo "Trying to run on the host instead..."
    elif [[ -n "$ddev_root" ]]; then
        echo "WARNING: found DDEV project $ddev_root, but $SCRIPT_DIR_REAL is not"
        echo "         below it, so it cannot be mapped into the container."
        echo "         Set MOODLE_DDEV_ROOT to the project that hosts this plugin."
    fi
    return 0
}

# ---------------------------------------------------------------------------
# Misc
# ---------------------------------------------------------------------------
# Print "--help" style usage and exit 0 if --help/-h is among the arguments.
# Usage: handle_help_flag show_help "$@"
handle_help_flag() {
    local fn="$1" arg
    shift
    for arg in "$@"; do
        if [[ "$arg" == "--help" || "$arg" == "-h" ]]; then
            "$fn"
            exit 0
        fi
    done
}
