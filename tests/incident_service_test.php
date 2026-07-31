<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_cdexamsave;

use quizaccess_cdexamsave\local\incident_service;

/**
 * Unit tests for collector input normalisation.
 *
 * @package    quizaccess_cdexamsave
 * @copyright  2026 Carlos Díaz Bueno <carlosdiazbueno@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \quizaccess_cdexamsave\local\incident_service
 */
final class incident_service_test extends \advanced_testcase {
    /**
     * Only canonical UUIDs are accepted.
     *
     * @covers ::is_valid_uuid
     * @return void
     */
    public function test_uuid_validation(): void {
        $this->assertTrue(incident_service::is_valid_uuid('123e4567-e89b-42d3-a456-426614174000'));
        $this->assertFalse(incident_service::is_valid_uuid('not-a-uuid'));
        $this->assertFalse(incident_service::is_valid_uuid('123e4567-e89b-02d3-a456-426614174000'));
    }

    /**
     * Unknown and malformed browser reasons are reduced to a safe value.
     *
     * @covers ::normalise_reason
     * @return void
     */
    public function test_reason_normalisation(): void {
        $this->assertSame('visibility_hidden', incident_service::normalise_reason('visibility_hidden'));
        $this->assertSame('unknown', incident_service::normalise_reason('<script>alert(1)</script>'));
        $this->assertSame('unknown', incident_service::normalise_reason('invented_reason'));
    }
}
