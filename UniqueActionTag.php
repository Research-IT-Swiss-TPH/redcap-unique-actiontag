<?php namespace STPH\UniqueActionTag;

use Exception;

if (file_exists("vendor/autoload.php")) {
    require 'vendor/autoload.php';
}

class UniqueActionTag extends \ExternalModules\AbstractExternalModule {

    private Array $data;
    private Array $errors;
    private Array $params;
    private Array $DTO;

    private Object $request;

    /**
     * ActionTag Definitions
     * 
     */
    private $actionTagUnique = [
        "tag" => "@UNIQUE",
        "params" =>  [
            [
                "name" => "with_all_records",
                "type" => "boolean",
                "default" => false,
                "required" => false
            ],
            [
                "name" => "with_all_instances",
                "type" => "boolean",
                "default" => false,
                "required" => false
            ],
            [
                "name" => "with_all_events",
                "type" => "boolean",
                "default" => false,
                "required" => false
            ],
            [
                "name" => "targets",
                "type" => "array",
                "required" => false
            ],
            [
                "name" => "title",
                "type" => "number",
                "required" => false
            ],
            [
                "name" => "message",
                "type" => "string",
                "required" => false
            ]
        ],
        "allowed_field_types" => [
            "text"
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
     * REDCap Hook - AJAX
     */
    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id){

        //  Set query parameters per ajax request
        $this->request = (object)[
            "project_id" => $project_id,
            "record" => $record,
            "instrument" => $instrument,
            "event_id" => $event_id,
            "repeat_instance" => $repeat_instance,
            "survey_hash" => $survey_hash
        ];

        //  Switch action
        switch ($action) {
            case 'check-unique':
                $response = $this->ajax_check_unique($payload);
                break;
            
            default:               
                break;
        }

        return $response;
    }

    /**
     * REDCap Hook - Data Entry Form
     */
    public function redcap_data_entry_form ( int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1 ): void
    {
        $this->getModuleParams();
        $this->getModuleData($project_id, $instrument, $record, $event_id, $repeat_instance, NULL);
        $this->getDataTransferObject();
        $this->renderStyles();
        $this->renderStaticHTML();
        $this->initializeJavascriptModuleObject();
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
            "enable_hard_check"  => (bool) $this->getProjectSetting("enable-hard-check") === true,
            "disable_summary"  => (bool) $this->getProjectSetting("disable-summary") === true
        ];

    }

    private function getModuleData($project_id, $instrument, $record, $event_id, $instance, $survey_hash = null) 
    {

        if (!class_exists("ActionTagHelper")) include_once("classes/ActionTagHelper.php");
        $actionTagHelper = new ActionTagHelper();
        $actionTagHelper->define($this->actionTagUnique);
        //$actionTagHelper->define($this->actionTagTest);

        list($this->data, $this->errors) = $actionTagHelper->getData(null, [$instrument]);

    }

    private function getDataTransferObject() {
        $queue = [];
        $data = [];

        foreach ($this->data as $field => $tags) {
            foreach ($tags as $tag => $detail) {

                if(!(array)$detail["errors"]) {
                    $queue[] = array(
                        "field"=> $field,
                        "tag" => $tag,
                        "checked" => false
                    );
                }

                $data[] = $detail;
            }
        }

        $this->DTO = array(
            "data" => $data,
            "errors" => $this->errors,
            "params" => $this->params  ,
            "summary" => array(
                "queue" => $queue,
                "duplicates" => []
            )
        );

    }
    private function renderStaticHTML() {        
        $path = $this->getUrl('src/unique_modal.html');
        $html = file_get_contents($path);
        echo $html;
    }

    private function renderStyles() {
        ?>
            <link rel="stylesheet" href="<?= $this->getUrl('css/style.css')?>">
        <?php
    }

    private function renderJavascript($record){
        ?>
        <script>
            /**
             * Passthrough:
             * JavascriptModuleObject JS(M)O
             * Data Transfer Object DTO
             * 
             */
            const JSO_STPH_UAT = <?=$this->getJavascriptModuleObjectName()?>;
            const DTO_STPH_UAT = <?= json_encode($this->DTO) ?>;
        </script>
        <script 
            type="module"  
            src="<?php print $this->getUrl('dist/unique.js'); ?>">
        </script>
        <?php
    }

    private function ajax_check_unique($payload) {

        if(empty($payload)) {
            throw new Exception("Payload must be not empty.");
        }
        
        list($data, $value) = $payload;

        return $this->query_unique($data, $value);

    }

    /**
     * Query method to check for uniqueness with different parameters
     * Returns array with duplicate entries: 
     * $duplicate = array('event_id' => , 'record' => , 'field_name' => , 'instance' => )
     * 
     */
    private function query_unique($data, $value) {

        $field = $data["field"];
        $params = (object) $data["params"];
        $tag = $data["tag"];

        $duplicates = [];

        # Request parameters from ajax hook
        $request = $this->request;

        # Support multiple redcap_data tables
        $data_table = method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($request->project_id) : "redcap_data";

        # Prepare query
        $query = $this->createQuery();
      
        # base query
        # ignores empty records
        $sql = "SELECT * FROM ".$data_table." WHERE project_id = ? AND value = ? AND record != ''";
        $prepared = [$request->project_id, $value];

        # defaults to check all records but current
        $sql .= " AND record != ?";
        $prepared[] = $request->record;

        # defaults to check only current event
        if ($params->with_all_events !== true) {
            $sql .= " AND event_id = ?";
            $prepared[] = $request->event_id;
        }

        # defaults to check only current instance
        if ($params->with_all_instances !== true) {
            $sql .= " AND IFNULL(INSTANCE,1)= ?";
            $prepared[] = $request->repeat_instance;
        } else {
            $sql .= " AND IFNULL(instance, 1) != ?";
            $prepared[] = $request->repeat_instance;
        }

        $query->add($sql, $prepared);

        # Specify all fields to be checked including current field and targets
        if(isset($params->targets) && is_array($params->targets) && count($params->targets) > 0) {
            $fields = array_merge( [$field], $params->targets);
        } else {
            $fields = [$field];
        }

        $query->add("AND")->addInClause('field_name', $fields);

        $result = $query->execute();        
        while($row = $result->fetch_assoc()) {
            $row["trigger"] = array(
                "field" => $field,
                "tag" => $tag
            );
            $duplicates[]= $row;
        }

        //  Additional query within record if we have all records and params set
        if($params->with_all_records == true && isset($params->targets) && is_array($params->targets) && count($params->targets) > 0) {
            $duplicates_war= [];

            $sql_war = "SELECT * FROM ".$data_table." WHERE project_id = ? AND value = ? AND record = ?";
            $prepared_war = [$request->project_id, $value, $request->record];
            
            $query_war = $this->createQuery();
            $query_war->add($sql_war, $prepared_war);
            $query_war->add("AND")->addInClause("field_name", $params->targets);


            $result_war = $query_war->execute();  
            while($row = $result_war->fetch_assoc()) {
                $row["trigger"] = array(
                    "field" => $field,
                    "tag" => $tag
                );
                $duplicates_war[]= $row;
            }

            $duplicates = array_merge($duplicates, $duplicates_war);
        }

        return $this->escape($duplicates);
    }

}
