#!/bin/bash

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
# Find Moodle's root dir and phpunit DIR
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
MOODLE_ROOT=""
# Candidate roots to try (5.1+: ../../../ from public/local/plugin; pre-5.1: ../../ from local/plugin)
CANDIDATES=(
  "$SCRIPT_DIR/../../.."
  "$SCRIPT_DIR/../.."
  "$SCRIPT_DIR/../../../.."
)
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

# Define the initialization and util scripts (support both 5.1+ and older paths)
init_behat=""
util_behat=""
if [ -f "$MOODLE_ROOT/public/admin/tool/behat/cli/init.php" ]; then
  init_behat="php $MOODLE_ROOT/public/admin/tool/behat/cli/init.php"
  util_behat="php $MOODLE_ROOT/public/admin/tool/behat/cli/util.php"
elif [ -f "$MOODLE_ROOT/admin/tool/behat/cli/init.php" ]; then
  init_behat="php $MOODLE_ROOT/admin/tool/behat/cli/init.php"
  util_behat="php $MOODLE_ROOT/admin/tool/behat/cli/util.php"
else
  echo "Error: Behat init script not found under $MOODLE_ROOT (looked in public/admin/tool/behat/cli/init.php and admin/tool/behat/cli/init.php)." >&2
  exit 1
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
  behat_cmd="$MOODLE_ROOT/vendor/bin/behat --config $MOODLE_ROOT/../behat_moodle/behatrun/behat/behat.yml -vvv --tags=@local_deepler"
else
  behat_cmd="$MOODLE_ROOT/vendor/bin/behat --config $MOODLE_ROOT/../behat_moodle/behatrun/behat/behat.yml -vvv --tags=$tag"
fi

# Optionally initialize first if requested
if $init_flag; then
    echo "[deepler] Running Behat init..."
    $init_behat
fi

# Run the Behat command and capture the output
echo "$behat_cmd"
output=$($behat_cmd 2>&1)

# Check if we need to reset+initialize due to errors or outdated site
if [[ $output == *"No scenarios"* || $output == *"Your behat test site is outdated,"* || $output == *"Moodle behat context"* || $output == *"Error reading from database"* ]]; then
    echo "[deepler] Detected Behat environment problem. Resetting test site and re-initializing..."

    # Attempt to disable Behat test mode by removing marker files before running util/init.
    BEHAT_PARENT_DIR="$MOODLE_ROOT/../behat_moodle"
    TEST_MODE_FILE="$BEHAT_PARENT_DIR/test_environment_enabled.txt"
    TEST_MODE_FILE_ALT="$BEHAT_PARENT_DIR/behat/test_environment_enabled.txt"
    RUN_ENV_FILE="$BEHAT_PARENT_DIR/run_environment.json"
    if [ -f "$TEST_MODE_FILE" ]; then
        echo "[deepler] Removing test mode marker: $TEST_MODE_FILE"
        rm -f "$TEST_MODE_FILE"
    fi
    if [ -f "$TEST_MODE_FILE_ALT" ]; then
        echo "[deepler] Removing test mode marker: $TEST_MODE_FILE_ALT"
        rm -f "$TEST_MODE_FILE_ALT"
    fi
    if [ -f "$RUN_ENV_FILE" ]; then
        echo "[deepler] Removing run environment file: $RUN_ENV_FILE"
        rm -f "$RUN_ENV_FILE"
    fi

    # Best-effort: remove main site bootstrap cache so $CFG->version is not preloaded during util/init bootstrap.
    MAIN_DATAROOT="$MOODLE_ROOT/../moodledata"
    if [ -d "$MAIN_DATAROOT" ]; then
        rm -f "$MAIN_DATAROOT/localcache/bootstrap.php" "$MAIN_DATAROOT/cache/bootstrap.php"
    fi

    if [ -n "$util_behat" ]; then
        (cd "$(dirname "${init_behat#php }")" && BEHAT_CLI=0 php util.php --drop)
    fi
    BEHAT_CLI=0 $init_behat
    output=$($behat_cmd 2>&1)
fi

# Print the final output
echo "$output"
