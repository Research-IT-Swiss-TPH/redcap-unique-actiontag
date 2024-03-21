<?php namespace STPH\UniqueActionTag;

if (file_exists("vendor/autoload.php")) {
    require 'vendor/autoload.php';
}

use \REDCap as REDCap;


class UniqueActionTag extends \ExternalModules\AbstractExternalModule {

    /**
     * ActionTags
     * 
     */
    private $actionTagUnique = [
        "tag" => "@UNIQUE",
        "params" =>  [
            [
                "name" => "strict",
                "type" => "boolean",
                "required" => false
            ],
            [
                "name" => "targets",
                "type" => "array",
                "reqired" => false
            ],
            [
                "name" => "title",
                "type" => "string",
                "requried" => false
            ],
            [
                "name" => "message",
                "type" => "string",
                "required" => false
            ]
        ],
        "allowed_field_types" => [
            "text", 
            "notes"
        ],
        "allowMultiple" => false,   //  allow to be used with multiple instances of same action tag on the same field
        //"allowStacking" => false  //  allow to be used with other action tags
    ];

    private $actionTagTest = [
        "tag" => "@TEST",
        "allowed_field_types" => []
    ];



    /**
     * REDCap Hook - Data Entry Form
     */
    public function redcap_data_entry_form ( int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1 ): void
    {

        $this->renderActionTags($project_id, $instrument, $record, $event_id, $repeat_instance, NULL);
        //$this->renderJavascript();

    }

    private function renderActionTags($project_id, $instrument, $record, $event_id, $instance, $survey_hash = null)
    {

        /**
         * 1. parse ActionTag data from within FieldMetaData
         * 2. validate ActionTags per tag (field-type, parameter-type)
         * 3. validate ActionTags per field (combinable?)
         * 4. pass ActionTags data to JavaScript
         * 5. initiate JavaScript
         * 
         */

        // $dataDictionary = REDCap::getDataDictionary($project_id, 'json', false, NULL, [$instrument]);
        // $dataDictionaryOld = REDCap::getDataDictionary('json', false, NULL, NULL);

        // if (!class_exists("ActionTagHelper_v1")) include_once("classes/ActionTagHelper_v1.php");
        // $action_tag_results_v1 = ActionTagHelper_v1::getActionTags($this->actionTags, NULL, [$instrument]);

        // if (!class_exists("ActionTagHelper_v2")) include_once("classes/ActionTagHelper_v2.php");
        // $action_tag_results_v2 = ActionTagHelper_v2::getActionTags($this->actionTags, NULL, [$instrument]);

        // if (!class_exists("ActionTagHelper_v3")) include_once("classes/ActionTagHelper_v3.php");
        // $action_tag_results_v3 = ActionTagHelper_v3::getActionTags($this->actionTags, NULL, [$instrument]);

        // if (!class_exists("ActionTagHelper_v3")) include_once("classes/ActionTagHelper_v3.php");
        // $action_tag_results_v3 = ActionTagHelper_v3::getActionTags($this->typedActionTags, NULL, [$instrument]);

        // dump($action_tag_results_v1);
        // dump($action_tag_results_v2);
        // dump($action_tag_results_v3);

        if (!class_exists("ActionTagHelper")) include_once("classes/ActionTagHelper.php");

        $actionTagHelper = new ActionTagHelper();
        $actionTagHelper->addActionTag($this->actionTagUnique);
        $actionTagHelper->addActionTag($this->actionTagTest);

        //dump($actionTagHelper->actionTags);

        $actionTagData = $actionTagHelper->getActionTagData(null, [$instrument]);

        dump($actionTagData);
    }
    
    private function renderJavascript(){
        ?>
        <script 
            type="module"  
            src="<?php print $this->getUrl('dist/unique.js'); ?>">
        </script>
        <?php
    }

}