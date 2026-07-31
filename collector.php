<?php
// This file is part of Moodle - http://moodle.org/

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
