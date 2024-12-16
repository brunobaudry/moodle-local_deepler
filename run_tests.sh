#!/bin/bash


# Check if an argument is provided
if [ -z "$1" ]; then
  # Define the PHPUnit command as a variable
  phpunit_cmd="../../vendor/bin/phpunit --colors --testsuite local_deepler_testsuite"
else
  # Define the PHPUnit command with the filter argument
  phpunit_cmd="../../vendor/bin/phpunit --colors --testsuite local_deepler_testsuite --filter $1"
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
