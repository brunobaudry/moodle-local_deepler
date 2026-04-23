#!/bin/bash

# Always run from the script's own directory so relative paths in phpunit.xml
# and in this script resolve correctly regardless of where the caller's CWD is.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
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
# DDEV detection: if ddev is installed and the project is running, delegate
# test execution to the web container.
# ---------------------------------------------------------------------------
DDEV_CONTAINER_SCRIPT_DIR="/var/www/html/moodle/public/local/deepler"

# Walk up from SCRIPT_DIR to find the directory containing .ddev/
find_ddev_root() {
    local dir="$SCRIPT_DIR"
    while [[ "$dir" != "/" ]]; do
        if [[ -d "$dir/.ddev" ]]; then
            echo "$dir"
            return 0
        fi
        dir="$(dirname "$dir")"
    done
    return 1
}

if [[ -z "$IS_DDEV_PROJECT" ]] && command -v ddev &>/dev/null; then
    DDEV_ROOT=$(find_ddev_root)
#    echo $DDEV_ROOT
    if [[ -n "$DDEV_ROOT" ]]; then
        ddev_status=$(cd "$DDEV_ROOT" && ddev status 2>/dev/null)
        echo "RUNNING IN DDEV $DDEV_ROOT $DDEV_CONTAINER_SCRIPT_DIR"
        # echo "$ddev_status" | grep -qiE "running|[[:space:]]OK[[:space:]]|[[:space:]]OK$"
        DDEV_DESCRIBE=$(ddev describe -j)
        wwwroot=$(echo "$DDEV_DESCRIBE" | jq -r '.raw.primary_url')
        echo $wwwroot
        if [[ -n "$wwwroot" ]]; then
          echo "DDEV is running — executing tests inside the web container..."
                     ddev exec --dir "$DDEV_CONTAINER_SCRIPT_DIR" bash run_tests.sh "$@"
                     exit $?
        else
          echo 'no go'
          exit 1;
        fi
    fi
fi

# ---------------------------------------------------------------------------
# Local execution (DDEV not running or not available)
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

# Verify we can find the Moodle root (lib/phpunit/bootstrap.php must exist
# two levels up — i.e. the plugin lives under <moodle>/local/deepler).
if [[ ! -f "../../lib/phpunit/bootstrap.php" ]]; then
    echo "ERROR: Cannot find Moodle's lib/phpunit/bootstrap.php relative to this script."
    echo "       Make sure you run this script from within the Moodle installation."
    echo "       In a DDEV container the correct path is:"
    echo "         /var/www/html/moodle/public/local/deepler/"
    echo "       Invoke with: ddev exec --dir /var/www/html/moodle/public/local/deepler bash run_tests.sh"
    exit 1
fi

# Detect PHPUnit binary
if [[ -f "../../vendor/bin/phpunit" ]]; then
    phpunit_bin="../../vendor/bin/phpunit"
else
    phpunit_bin="../../../vendor/bin/phpunit"
fi

# Define the PHPUnit command
phpunit_cmd="$phpunit_bin --colors --testsuite local_deepler_testsuite $show_deprecations"

# Add filter if provided
if [ -n "$test_filter" ]; then
  phpunit_cmd="$phpunit_cmd --filter $test_filter"
fi

# Detect Moodle version and set paths
if [[ -f "../../admin/tool/phpunit/cli/init.php" ]]; then
    # Pre-5.1 structure
    init_phpunit="php ../../admin/tool/phpunit/cli/init.php"
else
    # Moodle 5.1+ structure
    init_phpunit="php ../../../admin/tool/phpunit/cli/init.php"
fi

# Run the PHPUnit command and capture the output
output=$($phpunit_cmd 2>&1)

# Check if the output contains the specific message
if [[ $output == *"Moodle PHPUnit environment was initialised for different version"* || $output == *"Moodle PHPUnit environment is not initialised, please use:"* ]]; then
    # Run the initialization script
    $init_phpunit

    # Run the PHPUnit command again
    $phpunit_cmd
else
    # Print the original output
    echo "$output"
fi
