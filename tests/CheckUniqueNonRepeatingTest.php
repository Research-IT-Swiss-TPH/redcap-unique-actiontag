<?php namespace STPH\UniqueActionTag;

// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once __DIR__ . '/../../../redcap_connect.php';

use REDCap;
use Project;
use Vanderbilt\REDCap\Classes\ProjectDesigner;
use Exception;

class CheckUniqueNonRepeatingTest extends BaseTest {

    const FIELD_PARAMS = [
        0  => [
            "field_label" => "Field 1 NR",
            "field_name" => "field_1_nr",
            "field_type" => "text",
            "field_note" => ""
        ],
        1 => [
            "field_label" => "Field 2 NR",
            "field_name" => "field_2_nr",
            "field_type" => "text",
            "field_note" => ""
        ]
    ];

    const RECORD_DATA = [

    ];

    private static $project_id;
    private static $formName;
   
    static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        //  Use Test Project 1
        self::$project_id = self::$TEST_PID_1;

        //  Fixtures
        $Proj = new Project(self::$project_id);
        $projectDesigner = new ProjectDesigner($Proj);

        $form_label = "Form " . self::getRndm();

        //  Create form
        $created = $projectDesigner->createForm( $form_label, "form_1");
        if(!$created) throw new Exception("Could not create form");

        //  Create fields
        $form_name = self::getFormName($form_label);

        self::$formName = $form_name;

        foreach (self::FIELD_PARAMS as $key => $fieldParam) {
            $created = $projectDesigner->createField($form_name, $fieldParam);
        }
    }

    function test_mock_ajax_hook() {

        
        $event_id = $this->getFirstEventId(self::$project_id);

        $data = [
            "1" => [
                $event_id => [
                    "field_1_nr" => "value"
              ]
            ]
          ];
          
        $params = array( 'project_id' => self::$project_id, 'data' => $data );


        REDCap::saveData($params);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Payload must be not empty.");

        $record = 1;
        $repeat_instance = 1;
        $payload = [];

        $this->mock_module_ajax(self::$project_id, $record, $repeat_instance, $payload);
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