<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

throw new Exception("behat_hooks.php was loaded!");

// This file is automatically loaded by Behat before any scenario runs.
require_once(__DIR__ . '/../env_loader.php');

// Load environment variables from the .env.
env_loader::load(__DIR__ . '/../../../.env');

// Debug output.
echo "Loaded .env: DEEPL_API_TOKEN = " . getenv('DEEPL_API_TOKEN') . "\n";
