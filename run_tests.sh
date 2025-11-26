
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
show_deprecations="0"

# Parse arguments
for arg in "$@"; do
  if [[ "$arg" == "--deprecations" ]]; then
    show_deprecations="1"
  else
    test_filter="$arg"
  fi
done

# Check if PHPUnit exists
if [[ ! -f "../../vendor/bin/phpunit" ]]; then
  echo "Error: PHPUnit not found at ../../vendor/bin/phpunit"
  exit 1
fi

# Define the PHPUnit command
phpunit_cmd="../../vendor/bin/phpunit --colors --testsuite local_deepler_testsuite"

# Add filter if provided
if [[ -n "$test_filter" ]]; then
  phpunit_cmd="$phpunit_cmd --filter $test_filter"
fi

# Set PHP options based on deprecation flag
if [[ "$show_deprecations" == "1" ]]; then
  php_options="-d error_reporting=E_ALL"
else
  php_options="-d error_reporting=E_ALL\&~E_DEPRECATED"
fi

# Define the initialization script
init_phpunit="php ../../admin/tool/phpunit/cli/init.php"

# Run PHPUnit and capture output
output=$(php $php_options $phpunit_cmd 2>&1)

# Check if PHPUnit environment needs initialization
if [[ $output == *"Moodle PHPUnit environment was initialised for different version"* || $output == *"Moodle PHPUnit environment is not initialised, please use:"* ]]; then
    echo "Initializing Moodle PHPUnit environment..."
    php $init_phpunit
    php $php_options $phpunit_cmd
else
  echo "$output"
fi
