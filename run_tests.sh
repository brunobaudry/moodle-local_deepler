#!/bin/bash

# Always run from the script's own directory so relative paths in phpunit.xml
# and in this script resolve correctly regardless of where the caller's CWD is.
#
# SCRIPT_DIR keeps the logical (possibly symlinked) path, SCRIPT_DIR_REAL the
# physical one. Both are needed: this plugin is normally a standalone git
# checkout symlinked into a Moodle tree, so only the in-tree path has Moodle's
# lib/ two levels up and only it lives inside the DDEV project.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCRIPT_DIR_REAL="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
cd "$SCRIPT_DIR"

# Help message
show_help() {
  echo "Usage: $0 [TEST_FILTER] [--deprecations]"
  echo
  echo "Options:"
  echo "  TEST_FILTER        Optional. Run only tests matching the given filter."
  echo "  --deprecations     Optional. Show deprecation warnings during test execution."
  echo "  --help             Show this help message and exit."
  echo
  echo "Environment:"
  echo "  MOODLE_DDEV_ROOT   Optional. Pin the DDEV project root to run the tests in"
  echo "                     (skips autodetection). Useful when this plugin is"
  echo "                     symlinked into several Moodle trees."
  echo
  echo "Examples:"
  echo "  $0                          Run all tests without deprecation warnings."
  echo "  $0 SomeTest                Run tests matching 'SomeTest'."
  echo "  $0 --deprecations          Run all tests and show deprecation warnings."
  echo "  $0 SomeTest --deprecations Run filtered tests and show deprecation warnings."
}

# Check for --help flag
for arg in "$@"; do
  if [[ "$arg" == "--help" ]]; then
    show_help
    exit 0
  fi
done

# ---------------------------------------------------------------------------
# DDEV detection: if ddev is installed and a project holding this plugin is
# running, delegate test execution to that project's web container.
#
# Set MOODLE_DDEV_ROOT to pin a project root and skip autodetection.
# ---------------------------------------------------------------------------
DDEV_MOUNT="/var/www/html"   # DDEV always mounts the project root here.

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

# Moodle component of this plugin, e.g. local_deepler.
plugin_component() {
    local component
    component=$(grep -m1 -oE "component[[:space:]]*=[[:space:]]*'[^']+'" version.php 2>/dev/null \
                | grep -oE "'[^']+'" | tr -d "'")
    [[ "$component" == *_* ]] || return 1
    echo "$component"
}

# Path of this plugin relative to a Moodle docroot, e.g. local/deepler.
plugin_relpath() {
    local component
    component=$(plugin_component) || return 1
    echo "${component%%_*}/${component#*_}"
}

# Invoked through the standalone checkout, which sits outside every DDEV
# project? Then ask DDEV which registered project symlinks this plugin into
# place. Running projects win: the same checkout is often linked into several.
find_project_hosting_plugin() {
    local relpath approot docroot candidate wanted list
    relpath=$(plugin_relpath) || return 1
    command -v jq &>/dev/null || return 1
    list=$(ddev list -j 2>/dev/null) || return 1
    for wanted in running any; do
        while IFS=$'\t' read -r approot docroot; do
            [[ -n "$approot" ]] || continue
            docroot="${docroot#./}"
            docroot="${docroot%/}"
            candidate="${approot%/}${docroot:+/$docroot}/$relpath"
            [[ -d "$candidate" ]] || continue
            [[ "$(cd "$candidate" && pwd -P)" == "$SCRIPT_DIR_REAL" ]] || continue
            printf '%s\t%s\n' "${approot%/}" "$candidate"
            return 0
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

if [[ -z "$IS_DDEV_PROJECT" ]] && command -v ddev &>/dev/null; then
    ddev_root=""
    plugin_dir=""

    if [[ -n "$MOODLE_DDEV_ROOT" ]]; then
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
        ddev_status=$(ddev_project_status "$ddev_root")
        if [[ "$ddev_status" == "running" ]]; then
            wwwroot=$(cd "$ddev_root" && ddev describe -j 2>/dev/null \
                      | jq -r '.raw.primary_url // empty' 2>/dev/null)
            echo "DDEV project : $ddev_root${wwwroot:+  ($wwwroot)}"
            echo "Container dir: $container_dir"
            echo "DDEV is running — executing tests inside the web container..."
            (cd "$ddev_root" && ddev exec --dir "$container_dir" bash run_tests.sh "$@")
            exit $?
        fi
        echo "DDEV project $ddev_root is not running (status: ${ddev_status:-unknown})."
        echo "Start it with:  (cd \"$ddev_root\" && ddev start)"
        echo "Trying to run the tests on the host instead..."
    elif [[ -n "$ddev_root" ]]; then
        echo "WARNING: found DDEV project $ddev_root, but $SCRIPT_DIR_REAL is not"
        echo "         below it, so it cannot be mapped into the container."
        echo "         Set MOODLE_DDEV_ROOT to the project that hosts this plugin."
    fi
fi

# ---------------------------------------------------------------------------
# Local execution: inside the DDEV web container, or on a host Moodle install.
# ---------------------------------------------------------------------------

# Initialize variables
test_filter=""
show_deprecations=""

# Parse arguments
for arg in "$@"; do
  if [[ "$arg" == "--deprecations" ]]; then
    show_deprecations="--display-deprecations"
  else
    test_filter="$arg"
  fi
done

# Verify we can find Moodle's code root (dirroot): lib/phpunit/bootstrap.php
# must exist two levels up — i.e. the plugin sits at <dirroot>/local/deepler,
# which is <moodle>/public/local/deepler on Moodle 5.0+.
if [[ ! -f "../../lib/phpunit/bootstrap.php" ]]; then
    echo "ERROR: Cannot find Moodle's lib/phpunit/bootstrap.php relative to this script."
    echo "       Make sure you run this script from within the Moodle installation."
    echo "       In a DDEV container the correct path is:"
    echo "         $DDEV_MOUNT/moodle/public/$(plugin_relpath)/"
    echo "       Invoke with: ddev exec --dir $DDEV_MOUNT/moodle/public/$(plugin_relpath) bash run_tests.sh"
    exit 1
fi
dirroot="$(cd ../.. && pwd)"

# Moodle generates phpunit.xml next to composer.json: that is <dirroot> before
# Moodle 5.0 and the project root above public/ from 5.0 on.
#
# The tests must run from there, never from the plugin directory: PHPUnit
# implicitly loads ./phpunit.xml, and the copy of Moodle's generated config
# that lives in this plugin has paths relative to a different depth
# ("../public/lib/...") so its bootstrap cannot be resolved from here.
find_phpunit_root() {
    local parent
    if [[ -f "$dirroot/phpunit.xml" ]]; then
        echo "$dirroot"
        return 0
    fi
    parent="$(cd "$dirroot/.." && pwd)"
    if [[ -f "$parent/phpunit.xml" ]]; then
        echo "$parent"
        return 0
    fi
    return 1
}

init_phpunit=(php "$dirroot/admin/tool/phpunit/cli/init.php")
testsuite="$(plugin_component)_testsuite"

if ! phpunit_root=$(find_phpunit_root); then
    echo "Moodle PHPUnit environment is not initialised — initialising it now..."
    "${init_phpunit[@]}" || exit $?
    phpunit_root=$(find_phpunit_root) || {
        echo "ERROR: init.php did not generate a phpunit.xml for $dirroot."
        exit 1
    }
fi

# The generated config only lists components init.php has seen, so a freshly
# symlinked plugin is missing from it. PHPUnit then just reports "No tests
# executed!" instead of failing, which is easy to mistake for a passing run —
# check up front and regenerate.
if ! grep -qF "name=\"$testsuite\"" "$phpunit_root/phpunit.xml"; then
    echo "Test suite $testsuite is missing from $phpunit_root/phpunit.xml — initialising..."
    "${init_phpunit[@]}" || exit $?
fi

# Detect PHPUnit binary: vendor/ sits next to the generated config, but older
# layouts keep it in dirroot.
if [[ -x "$phpunit_root/vendor/bin/phpunit" ]]; then
    phpunit_bin="$phpunit_root/vendor/bin/phpunit"
elif [[ -x "$dirroot/vendor/bin/phpunit" ]]; then
    phpunit_bin="$dirroot/vendor/bin/phpunit"
else
    echo "ERROR: Cannot find vendor/bin/phpunit — run 'composer install' in $phpunit_root."
    exit 1
fi

# Define the PHPUnit command
phpunit_cmd=("$phpunit_bin" --colors --testsuite "$testsuite")
[[ -n "$show_deprecations" ]] && phpunit_cmd+=("$show_deprecations")
[[ -n "$test_filter" ]] && phpunit_cmd+=(--filter "$test_filter")

# Run the tests, showing output live while keeping a copy to inspect.
logfile=$(mktemp) || exit 1
trap 'rm -f "$logfile"' EXIT

(cd "$phpunit_root" && "${phpunit_cmd[@]}") 2>&1 | tee "$logfile"
status=${PIPESTATUS[0]}

# A stale environment, a missing one, or a test suite that init.php has not
# picked up yet (e.g. just after symlinking the plugin in) all need a re-init.
if grep -qF -e "Moodle PHPUnit environment was initialised for different version" \
             -e "Moodle PHPUnit environment is not initialised, please use:" \
             -e "Test suite \"$testsuite\" not found" "$logfile"; then
    echo
    echo "Re-initialising the Moodle PHPUnit environment..."
    "${init_phpunit[@]}" || exit $?
    (cd "$phpunit_root" && "${phpunit_cmd[@]}")
    status=$?
fi

exit "$status"
