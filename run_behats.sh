#!/bin/bash

# Detect if running inside a container (ddev web container sets DDEV_SITENAME)
if [[ -n "$DDEV_SITENAME" || -f "/.dockerenv" ]]; then
    php_bin="php"
else
    php_bin="ddev exec php"
fi

# Detect BEHAT binary and paths
# moodle_root  = composer root (where vendor/ lives)
# moodle_public = web root (where admin/ and lib/ live)
if [[ -f "../../vendor/bin/behat" ]]; then
    # Plugin is directly under moodle root (moodle_root/local/deepler)
    behat_bin="../../vendor/bin/behat"
    moodle_root="../../"
    moodle_public="../../"
else
    # Plugin is under a public subdir (moodle_root/public/local/deepler)
    behat_bin="../../../vendor/bin/behat"
    moodle_root="../../../"
    moodle_public="../../"
fi

behat_config="${moodle_root}../behat_moodle/behatrun/behat/behat.yml"
init_behat="${moodle_public}admin/tool/behat/cli/init.php"

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
    behat_cmd="$behat_bin --config $behat_config -vvv --tags=@local_deepler"
else
    behat_cmd="$behat_bin --config $behat_config -vvv --tags=$tag"
fi

echo "$behat_cmd"

run_init() {
    echo "Initializing Behat..."
    $php_bin "$init_behat" || { echo "ERROR: Behat init failed (init.php not found or errored)"; exit 1; }
}

# Auto-init if config file is missing or --init was passed
if $init_flag || [[ ! -f "$behat_config" ]]; then
    run_init
    output=$($behat_cmd 2>&1)
    echo "$output"
    exit 0
fi

# Run the Behat command and capture the output
output=$($behat_cmd 2>&1)

# Re-init if Behat reports stale/missing state
if [[ $output == *"No scenarios"* || $output == *"Your behat test site is outdated,"* || $output == *"does not exist"* ]]; then
    run_init
    output=$($behat_cmd 2>&1)
fi

echo "$output"
