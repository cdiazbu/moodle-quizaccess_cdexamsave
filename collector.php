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

/**
 * Lightweight beacon-compatible collector for student monitoring signals.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__ . '/../../../../config.php');

require_login();
require_sesskey();

$rawbody = file_get_contents('php://input');
$payload = json_decode($rawbody, true);
if (!is_array($payload)) {
    $payload = [];
}

try {
    $result = \quizaccess_cdexamsave\local\incident_service::record($payload);
    \core\session\manager::write_close();
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($result);
} catch (Throwable $exception) {
    \core\session\manager::write_close();
    http_response_code($exception instanceof moodle_exception ? 400 : 500);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode([
        'accepted' => false,
        'error' => get_string('invalidrequest', 'quizaccess_cdexamsave'),
    ]);
}
