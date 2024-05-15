<?php namespace STPH\UniqueActionTag;

// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once __DIR__ . '/../../../redcap_connect.php';


class CheckUniqueTest extends BaseTest {

    
    static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        //  Fixtures

        //  nonrepeating project
        //  createField: test_project_1, form_1, field_1, text
        //  createField: test_project_1, form_1, field_2, text
        //  createField: test_project_1, form_1, field_3, text

        //  repeating project
        //  makeFormRepeatable: test_project_1, form_1
        //  createField: test_project_1, form_1, field_1, text
        //  createField: test_project_1, form_1, field_2, text
        //  createField: test_project_1, form_1, field_3, text
    }

    function test_simulate_ajax_hook() {

        $action = "check-unique";
        $payload = [];
        $project_id = self::$TEST_PID_1;
        $record = 1;
        $instrument = "form_1";
        $event_id = 73;
        $repeat_instance = 1;
        $survey_hash = null;
        $response_id = null;
        $survey_queue_hash = null;
        $page = null;
        $page_full =  null;
        $user_id = null;
        $group_id = null;

        $args = [
            $action, 
            $payload, 
            $project_id, 
            $record, 
            $instrument, 
            $event_id, 
            $repeat_instance, 
            $survey_hash, 
            $response_id, 
            $survey_queue_hash, 
            $page, 
            $page_full, 
            $user_id, 
            $group_id
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Payload must be not empty.");

        $this->redcap_module_ajax(...$args);
    }

    function test_nonrepeating() {

    }

    function test_nonrepeating_with_current() {

    }

    function test_nonrepeating_with_targets() {

    }

    function test_nonrepeating_with_targets_current() {

    }

    function test_nonrepeating_with_targets_events() {

    }

    function test_nonrepeating_with_targets_events_current() {

    }

}