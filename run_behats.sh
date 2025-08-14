#!/bin/bash

# Define the initialization script
init_behat="php ../../admin/tool/behat/cli/init.php"

# Function to display help
show_help() {
    echo "Usage: $0 [OPTION] [TAG]"
    echo "Run Behat tests with optional initialization."
    echo
    echo "Options:"
    echo "  --init       Initialize Behat before running tests."
    echo "  --help       Display this help message."
    echo "  [TAG]        Run Behat tests with the specified tag(s)."
    echo "               For more information on tags syntax, visit Gherkin Filters:"
    echo "               https://docs.behat.org/en/v2.5/guides/6.cli.html#gherkin-filters"
}

# Check if the --help argument is passed
if [[ "$1" == "--help" ]]; then
    show_help
    exit 0
fi

# Initialize variables
init_flag=false
tag=""

# Parse arguments
for arg in "$@"; do
    case $arg in
        --init)
            init_flag=true
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            tag=$arg
            ;;
    esac
done

# Define the Behat command
if [ -z "$tag" ]; then
  behat_cmd="../../vendor/bin/behat --config ../../../behat_moodle/behatrun/behat/behat.yml -vvv --tags=@local_deepler"
else
  behat_cmd="../../vendor/bin/behat --config ../../../behat_moodle/behatrun/behat/behat.yml -vvv --tags=$tag"
fi

# Run the Behat command and capture the output
output=$($behat_cmd 2>&1)
echo "$behat_cmd"

# Check if the --init argument is passed or if the output contains "No scenarios"
if $init_flag || [[ $output == *"No scenarios"* || $output == *"Your behat test site is outdated,"* ]]; then
    # Run the initialization script
    $init_behat

    # Run the Behat command again
    output=$($behat_cmd 2>&1)
    echo "$output"
else
    # Print the original output
    echo "$output"
fi
