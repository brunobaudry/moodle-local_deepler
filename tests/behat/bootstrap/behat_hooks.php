<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Hook for behat.
 *
 * @package    local_deepler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Only do anything when the Behat testing site is running.
if (!defined('BEHAT_SITE_RUNNING')) {
    return;
}

// Attempt to load the env loader quietly.
$envloader = dirname(__DIR__, 2) . '/env_loader.php'; // From tests/behat to plugin root.
if (is_readable($envloader)) {
    require_once($envloader);

    // Load environment variables from the repository .env if present.
    $dotenv = dirname(__DIR__, 3) . '/.env'; // Adjust this path to where your .env actually is.
    if (class_exists('env_loader') && is_readable($dotenv)) {
        // Do not echo/print anything here; it can corrupt Behat output.
        env_loader::load($dotenv);
    }
}

// Never echo/print from hooks; use error_log() if you absolutely need debugging.
