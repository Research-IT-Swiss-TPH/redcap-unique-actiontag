<?php namespace STPH\UniqueActionTag;

if (file_exists("vendor/autoload.php")) {
    require 'vendor/autoload.php';
}

class UniqueActionTag extends \ExternalModules\AbstractExternalModule {

    private Array $data;
    private Array $params;
    private Array $DTO;

    /**
     * ActionTag Definitions
     * 
     */
    private $actionTagUnique = [
        "tag" => "@UNIQUE",
        "params" =>  [
            [
                "name" => "strict",
                "type" => "boolean",
                "required" => true
            ],
            [
                "name" => "targets",
                "type" => "array",
                "reqired" => false
            ],
            [
                "name" => "title",
                "type" => "number",
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
        // allow to be used without parameters, default=true
        "allowFlat" => true,
        // allow to be used with multiple instances of same action tag on the same field
        "allowMultiple" => false,
        // allow to be used with other action tags, default=true
        //"allowStacking" => []  
    ];

    private $actionTagTest = [
        "tag" => "@TEST",
        "allowed_field_types" => [],
        "allowFlat" => true,
        "allowMultiple" => true,
        "allowStacking" => ["@UNIQUE"]
    ];

    /**
     * REDCap Hook - Data Entry Form
     */
    public function redcap_data_entry_form ( int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1 ): void
    {
        $this->getModuleParams();
        $this->getModuleData($project_id, $instrument, $record, $event_id, $repeat_instance, NULL);
        $this->getDataTransferObject();
        $this->renderJavascript($record);

    }

    private function getModuleParams() {
        
        //  Gather strict-unique exceptions
        $exceptions = array();
        $exceptions_str = $this->getProjectSetting("exceptions");
        if(!empty($exceptions_str)) {
            $exceptions = array_map('trim', explode(',', $exceptions_str ));
        }

        $this->params = [
            "exceptions"  => (Array) $exceptions,
            "show_debug"  => (bool) $this->getProjectSetting("javascript-debug") === true,
            "show_errors" => (bool) $this->getProjectSetting("show-errors") === true,
            "show_labels" => (bool) $this->getProjectSetting("show-labels") === true,
            "hard_check"  => (bool) $this->getProjectSetting("enable-hard-check") === true
        ];

    }

    private function getModuleData($project_id, $instrument, $record, $event_id, $instance, $survey_hash = null) 
    {

        if (!class_exists("ActionTagHelper")) include_once("classes/ActionTagHelper.php");
        $actionTagHelper = new ActionTagHelper();
        $actionTagHelper->define($this->actionTagUnique);
        $actionTagHelper->define($this->actionTagTest);

        $this->data = $actionTagHelper->getData(null, [$instrument]);

    }

    private function getDataTransferObject() {
        $this->DTO = array(
            "data" => $this->data,
            "params" => $this->params
        );
    }

    private function renderJavascript($record){
        ?>
        <script>
            /**
             * Store module data into global context so that accessing through modern JavaScript (TypeScript) is possible
             * 
             * In a tabbed browser, each tab is represented by its own Window object; 
             * https://developer.mozilla.org/en-US/docs/Web/API/Window
             * 
             */
            window.STPH_UAT_DTO = <?= json_encode($this->DTO) ?>

        </script>
        <script 
            type="module"  
            src="<?php print $this->getUrl('dist/unique.js'); ?>">
        </script>
        <?php
    }

}