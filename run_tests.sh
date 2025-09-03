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
    show_deprecations="--display-deprecations"
  else
    test_filter="$arg"
  fi
done

# Define the PHPUnit command
phpunit_cmd="../../vendor/bin/phpunit --display-deprecated --colors --testsuite local_deepler_testsuite $show_deprecations"

# Add filter if provided
if [ -n "$test_filter" ]; then
  phpunit_cmd="$phpunit_cmd --filter $test_filter"
fi

# Define the initialization script
init_phpunit="php ../../admin/tool/phpunit/cli/init.php"

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
