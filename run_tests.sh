#!/bin/bash

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
# Initialize variables
test_filter=""
show_deprecations=""

# Parse arguments
for arg in "$@"; do
  if [[ "$arg" == "--deprecations" ]]; then
    echo "SHOULD DISPLAY DEPREC"
    show_deprecations="--display-deprecations"
  else
    test_filter="$arg"
  fi
done
# Find Moodle's root dir and phpunit DIR
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
MOODLE_ROOT=""
# Candidate roots to try (5.1+: ../../../ from public/local/plugin; pre-5.1: ../../ from local/plugin)
CANDIDATES=(
  "$SCRIPT_DIR/../../.."
  "$SCRIPT_DIR/../.."
  "$SCRIPT_DIR/../../../.."
)
#echo $SCRIPT_DIR

for candidate in "${CANDIDATES[@]}"; do
 # echo $candidate
  if [ -f "$candidate/public/admin/tool/phpunit/cli/init.php" ] && [ -x "$candidate/vendor/bin/phpunit" ]; then
    # Moodle 5.1+
    MOODLE_ROOT="$candidate"
    break
  elif [ -f "$candidate/admin/tool/phpunit/cli/init.php" ] && [ -x "$candidate/vendor/bin/phpunit" ]; then
      # Moodle < 5.1
      MOODLE_ROOT="$candidate"
      break
  fi
done
if [ -z "$MOODLE_ROOT" ]; then
  up="$SCRIPT_DIR"
  for i in 1 2 3 4 5 6; do
    up="$up/.."
    if [ -f "$up/admin/tool/phpunit/cli/init.php" ] && [ -x "$up/vendor/bin/phpunit" ]; then
      MOODLE_ROOT="$(cd "$up" && pwd)"
      break
    fi
  done
fi

if [ -z "$MOODLE_ROOT" ]; then
  echo "Error: Could not locate Moodle root. Looked for admin/tool/phpunit/cli/init.php and vendor/bin/phpunit above $SCRIPT_DIR." >&2
  exit 1
fi
echo $MOODLE_ROOT

# Define paths based on detected root
PHPUNIT_BIN="$MOODLE_ROOT/vendor/bin/phpunit"

# Prefer plugin's phpunit.xml if present; otherwise fall back to root config(s)
if [ -f "$SCRIPT_DIR/phpunit.xml" ]; then
  PHPUNIT_CONFIG="$SCRIPT_DIR/phpunit.xml"
elif [ -f "$MOODLE_ROOT/phpunit.xml" ]; then
  PHPUNIT_CONFIG="$MOODLE_ROOT/phpunit.xml"
else
  PHPUNIT_CONFIG="$MOODLE_ROOT/phpunit.xml.dist"
fi

# Pick the correct init.php path depending on Moodle version/layout
if [ -f "$MOODLE_ROOT/public/admin/tool/phpunit/cli/init.php" ]; then
  INIT_PHPUNIT="php $MOODLE_ROOT/public/admin/tool/phpunit/cli/init.php"
else
  INIT_PHPUNIT="php $MOODLE_ROOT/admin/tool/phpunit/cli/init.php"
fi

# Build the PHPUnit command (let the selected configuration control testsuites)
phpunit_cmd="$PHPUNIT_BIN --configuration $PHPUNIT_CONFIG --colors $show_deprecations"


# Add filter if provided
if [ -n "$test_filter" ]; then
  phpunit_cmd="$phpunit_cmd --filter $test_filter"
fi

# Define the initialization script
#init_phpunit="php ../../admin/tool/phpunit/cli/init.php"

# Run the PHPUnit command and capture the output
output=$($phpunit_cmd 2>&1)

# Check if the output contains messages indicating (re)initialisation is required
if [[ $output == *"Moodle PHPUnit environment was initialised for different version"* || $output == *"Moodle PHPUnit environment is not initialised, please use:"* ]]; then
    # Run the initialization script
    $INIT_PHPUNIT

    # Run the PHPUnit command again
    $phpunit_cmd
else
    # Print the original output
    echo "$output"
fi
