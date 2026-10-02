#!/bin/bash
# Run the plugin's PHPUnit test suite, (re)initialising Moodle's PHPUnit
# environment when needed. Works with the classic (<= 5.0) and the public/
# (>= 5.1) Moodle layouts and, on the host, delegates itself to the DDEV web
# container.

# shellcheck source=run_lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/run_lib.sh"
cd "$SCRIPT_DIR"

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
handle_help_flag show_help "$@"

# On the host: hand over to the DDEV web container when one hosts this plugin.
ddev_delegate "$@"

# ---------------------------------------------------------------------------
# Local execution: inside the DDEV web container, or on a host Moodle install.
# ---------------------------------------------------------------------------
moodle_require_layout || exit 1
moodle_print_layout

test_filter=""
show_deprecations=""
for arg in "$@"; do
  if [[ "$arg" == "--deprecations" ]]; then
    show_deprecations="--display-deprecations"
  else
    test_filter="$arg"
  fi
done

[[ -f "$DIRROOT/lib/phpunit/bootstrap.php" ]] || {
    echo "ERROR: $DIRROOT/lib/phpunit/bootstrap.php not found."
    exit 1
}

# Moodle generates phpunit.xml next to composer.json, i.e. in MOODLE_ROOT:
# <dirroot> before Moodle 5.1 and the project root above public/ from 5.1 on.
#
# The tests must run from there, never from the plugin directory: PHPUnit
# implicitly loads ./phpunit.xml, and the copy of Moodle's generated config
# that lives in this plugin has paths relative to a different depth
# ("../public/lib/...") so its bootstrap cannot be resolved from here.
find_phpunit_root() {
    local root
    for root in "$MOODLE_ROOT" "$DIRROOT"; do
        if [[ -f "$root/phpunit.xml" ]]; then
            echo "$root"
            return 0
        fi
    done
    return 1
}

init_phpunit=(php "$DIRROOT/admin/tool/phpunit/cli/init.php")
testsuite="$(plugin_component)_testsuite"

if ! phpunit_root=$(find_phpunit_root); then
    echo "Moodle PHPUnit environment is not initialised — initialising it now..."
    "${init_phpunit[@]}" || exit $?
    phpunit_root=$(find_phpunit_root) || {
        echo "ERROR: init.php did not generate a phpunit.xml in $MOODLE_ROOT."
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

phpunit_bin=$(moodle_vendor_bin phpunit) || {
    echo "ERROR: Cannot find vendor/bin/phpunit — run 'composer install' in $MOODLE_ROOT."
    exit 1
}

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
