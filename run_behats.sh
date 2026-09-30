#!/bin/bash
# Run the plugin's Behat features, (re)initialising Moodle's Behat environment
# when needed. Works with the classic (<= 5.0) and the public/ (>= 5.1) Moodle
# layouts and, on the host, delegates itself to the DDEV web container.

# shellcheck source=run_lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/run_lib.sh"
cd "$SCRIPT_DIR"

show_help() {
    echo "Usage: $0 [--init] [TAG]"
    echo "Run Behat tests with optional initialization."
    echo
    echo "Options:"
    echo "  --init       Initialize Behat before running tests."
    echo "  --help       Display this help message."
    echo "  [TAG]        Run Behat tests with the specified tag(s), default: @$(plugin_component 2>/dev/null || echo local_deepler)."
    echo "               For more information on tags syntax, visit Gherkin Filters:"
    echo "               https://docs.behat.org/en/v2.5/guides/6.cli.html#gherkin-filters"
    echo
    echo "Environment:"
    echo "  BEHAT_PROFILE      Behat profile to use (default: chrome)."
    echo "  XDEBUG_MODE        Forced to 'off' for the Behat CLI unless set (e.g. XDEBUG_MODE=debug)."
    echo "  MOODLE_DDEV_ROOT   Pin the DDEV project root to run the tests in (skips autodetection)."
}
handle_help_flag show_help "$@"

# On the host: hand over to the DDEV web container when one hosts this plugin.
# Behat's generated config only holds container paths, so it can't run on the host.
ddev_delegate "$@"

# ---------------------------------------------------------------------------
# Local execution: inside the DDEV web container, or on a host Moodle install.
# ---------------------------------------------------------------------------
moodle_require_layout || exit 1
moodle_print_layout

init_flag=false
tag="@$(plugin_component)"
for arg in "$@"; do
    case $arg in
        --init) init_flag=true ;;
        *) tag=$arg ;;
    esac
done

behat_bin=$(moodle_vendor_bin behat) || {
    echo "ERROR: Cannot find vendor/bin/behat — run 'composer install' in $MOODLE_ROOT."
    exit 1
}
init_behat="$DIRROOT/admin/tool/behat/cli/init.php"
[[ -f "$init_behat" ]] || { echo "ERROR: $init_behat not found."; exit 1; }

# behat.yml is generated in $CFG->behat_dataroot/behatrun/behat/. Read the
# dataroot from config.php, fall back to the conventional sibling directory.
behat_dataroot=$(moodle_cfg behat_dataroot)
if [[ -z "$behat_dataroot" || ! -d "$(dirname "$behat_dataroot")" ]]; then
    behat_dataroot="$MOODLE_ROOT/../behat_moodle"
fi
behat_config="$behat_dataroot/behatrun/behat/behat.yml"

behat_profile="${BEHAT_PROFILE:-chrome}"
behat_cmd=("$behat_bin" --config "$behat_config" --profile "$behat_profile" -vvv "--tags=$tag")

# Behat is a CLI process: unless the caller explicitly wants to step-debug it,
# turn Xdebug off so it does not spam "Could not connect to debugging client".
export XDEBUG_MODE="${XDEBUG_MODE:-off}"

# Preflight: Behat aborts (exit 251) when $CFG->behat_wwwroot is not reachable
# from *this* process, and Selenium must reach it too. Fail early with a hint.
preflight() {
    local wwwroot code
    wwwroot=$(moodle_cfg behat_wwwroot)
    if [[ -z "$wwwroot" ]]; then
        echo "ERROR: \$CFG->behat_wwwroot is not set in $MOODLE_ROOT/config.php."
        return 1
    fi
    echo "Behat wwwroot : $wwwroot"
    if command -v curl &>/dev/null; then
        code=$(curl -sS -o /dev/null -m 10 -w '%{http_code}' "$wwwroot/" 2>/dev/null) || code=""
        if [[ -z "$code" || "$code" == "000" ]]; then
            echo "ERROR: $wwwroot is not reachable from $(hostname)."
            if in_container; then
                echo "       In DDEV use the web service name, which is resolvable from the web"
                echo "       and selenium containers alike:"
                echo "         \$CFG->behat_wwwroot = 'http://web';"
            else
                echo "       Make sure the URL resolves here and in the Selenium container."
            fi
            echo "       Then re-run with --init so behat.yml picks the new URL up."
            return 1
        fi
    fi
    return 0
}

run_init() {
    echo "Initializing Behat..."
    php "$init_behat" || { echo "ERROR: Behat init failed (see output above)."; exit 1; }
}

# Run Behat showing the output live while keeping a copy to inspect.
logfile=$(mktemp) || exit 1
trap 'rm -f "$logfile"' EXIT
run_behat() {
    echo "${behat_cmd[*]}"
    "${behat_cmd[@]}" 2>&1 | tee "$logfile"
    return "${PIPESTATUS[0]}"
}

preflight || exit 1

# Init first when asked to, when the environment has never been initialised, or
# when behat.yml was generated for another wwwroot (config.php changed since).
if [[ -f "$behat_config" ]] && ! grep -qF "$(moodle_cfg behat_wwwroot)" "$behat_config"; then
    echo "behat.yml was generated for a different behat_wwwroot — re-initialising."
    init_flag=true
fi
if $init_flag || [[ ! -f "$behat_config" ]]; then
    run_init
    run_behat
    exit $?
fi

run_behat
status=$?

# Re-init once if Behat reports stale/missing state, then run again.
if grep -qF -e "No scenarios" \
             -e "Your behat test site is outdated," \
             -e "does not exist" "$logfile"; then
    echo
    run_init
    run_behat
    status=$?
fi

exit "$status"
