<?php namespace STPH\UniqueActionTag;

// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once __DIR__ . '/../../../redcap_connect.php';

use Exception;
use Project;
use Vanderbilt\REDCap\Classes\ProjectDesigner;

class ActionTagHelperTest extends BaseTest {

    const FIELD_PARAMS = [

        0  => [
            "field_label" => "Field 1",
            "field_name" => "field_1",
            "field_type" => "text",
            "field_note" => "Valid.",
            "field_annotation" => '@AT1'
        ],
        1 => [
            "field_label" => "Field 2",
            "field_name" => "field_2",
            "field_type" => "text",
            "field_note" => "UAT",
            "field_annotation" => '@AT1 @AT1'
        ],
        2 => [
            "field_label" => "Field 3",
            "field_name" => "field_3",
            "field_type" => "text",
            "field_note" => "UAT",
            "field_annotation" => '@AT1={"foo":"bar"}'
        ],
        3 => [
            "field_label" => "Field 4",
            "field_name" => "field_4",
            "field_type" => "text",
            "field_note" => "UAT",
            "field_annotation" => '@AT2 @AT2=Foo @AT2=Bar'
        ],
        4 => [
            "field_label" => "Field 5",
            "field_name" => "field_5",
            "field_type" => "text",
            "field_note" => "UAT",
            "field_annotation" => '@AT2={"foo":["bar_1", "bar_2"], "goo":"La la la", "hoo": true}'
        ]
    ];

    const ACTION_TAG_1 = [
        "tag" => "@AT1",
        "params" =>  [
            [
                "name" => "foo",
                "type" => "string",
                "required" => false
            ]
        ],
        "allowed_field_types" => [
            "text", 
            "notes"
        ],
        "allowFlat" => true,
        "allowMultiple" => true,
    ];

    const ACTION_TAG_2 = [
        "tag" => "@AT2",
        "params" =>  [
            [
                "name" => "foo",
                "type" => "array"
            ],
            [
                "name" => "goo",
                "type" => "string"
            ],
            [
                "name" => "hoo",
                "type" => "boolean",
            ]
        ],
        "allowed_field_types" => [
            "text", 
            "notes"
        ],
        "allowFlat" => false,
        "allowMultiple" => false,
    ];

    // /**
    //  * Helper function to generate form_name from form_label
    //  * Taken from ProjectDesigner Class (it would be good if REDCap Core offers this as a default)
    //  * 
    //  */
    // private function getFormName($form_label) {
    //     return preg_replace("/[^a-z_0-9]/", "", str_replace(" ", "_", strtolower($form_label)));
    // }

    /**
     * Test Define ActionTags
     * 
     */
    function test_define_actiontags() {

        $actionTagHelper = new ActionTagHelper();
        $actionTagHelper->define(self::ACTION_TAG_1);
        $actionTagHelper->define(self::ACTION_TAG_2);

        $this->assertCount(2, $actionTagHelper->getActionTags());
    }

    /**
     * Test GetData ActionTag
     * 
     */
    function test_getdata_actiontag() {

        $form_label = "Form " . self::getRndm();

        $project_id = self::$TEST_PID_1;
        $Proj = new Project($project_id);
        $projectDesigner = new ProjectDesigner($Proj);

        //  Create form
        $created = $projectDesigner->createForm( $form_label, "form_1");
        if(!$created) throw new Exception("Could not create form");

        //  Create fields
        $form_name = $this->getFormName($form_label);
        foreach (self::FIELD_PARAMS as $key => $fieldParam) {
            $projectDesigner->createField($form_name, $fieldParam);
        }

        $actionTagHelper = new ActionTagHelper();
        $actionTagHelper->define(self::ACTION_TAG_1);
        $actionTagHelper->define(self::ACTION_TAG_2);

        $actualData = $actionTagHelper->getData();
      
        $expectedActionTags = self::loadFixture('action-tags');

        $this->assertEquals($expectedActionTags, $actualData);
    }

}