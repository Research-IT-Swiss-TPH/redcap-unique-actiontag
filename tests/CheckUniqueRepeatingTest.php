<?php namespace STPH\UniqueActionTag;

// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once __DIR__ . '/../../../redcap_connect.php';

use Project;

class CheckUniqueRepeatingTest extends BaseTest {
   
    static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        //  Fixtures

        //  repeating project
        //  makeFormRepeatable: test_project_1, form_1
        //  createField: test_project_1, form_1, field_1, text
        //  createField: test_project_1, form_1, field_2, text
        //  createField: test_project_1, form_1, field_3, text
    }


    function test_mock_ajax_hook() {

        $project_id = self::$TEST_PID_2;
        $record = 1;
        $repeat_instance = 1;

        $payload = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Payload must be not empty.");

        $this->mock_module_ajax($project_id, $record, $repeat_instance, $payload);
    }

    function test_repeating() {

    }

    function test_repeating_with_current() {

    }

    function test_repeating_with_targets() {

    }

    function test_repeating_with_targets_current() {

    }

    function test_repeating_with_targets_events() {

    }

    function test_repeating_with_targets_events_current() {

    }

}