<?php namespace STPH\UniqueActionTag;

use \REDCap as REDCap;

/**
 * Class UniqueActionTag
 * Author: Ekin Tertemiz
 * 
 * This class creates custom action tags that will be parsed for in field annotations and then rendered and displayed on data entry forms.
 * The whole module architecture is based on AutofillExternalModule (https://github.com/grezniczek/redcap_autofill). 
 * Credits: Günther Rezniczek
 * 
 */
class UniqueActionTag extends \ExternalModules\AbstractExternalModule {

    private $atUnique = "@UNIQUE";
    private $atUniqueStrict = "@UNIQUE-STRICT";
    
    private $actionTags;

    function __construct() {
        $this->actionTags = array (
            $this->atUnique,
            $this->atUniqueStrict
        );
        parent::__construct();
    }

    #region Hooks

    
    /**
     * Triggers module logic on every data entry form
     */
    public function redcap_data_entry_form ($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1) {
        $this->renderActionTag($project_id, $instrument, $record, $event_id, $repeat_instance, NULL);
    }


    /**
     * Adds custom action tag explanations to REDCap Action Tags explanation
     * Taken from https://github.com/grezniczek/redcap_pdf_actiontags/blob/master/PDFActionTagsExternalModule.php
     * Credits: Günther Rezniczek
     */
    public function redcap_every_page_top($project_id = null) {

        // Do not run on non-project pages.
        $project_id = empty($project_id) ? 0 : (is_numeric($project_id) ? intval($project_id) : 0);
        if ($project_id < 1) return;


        // Insert the action tag descriptions (only on Design)
        if (strpos(PAGE_FULL, "/Design/online_designer.php") !== false) {
            $template = file_get_contents(dirname(__FILE__)."/lib/actiontag_info.html");
            $replace = array(
                "{PREFIX}" => $this->PREFIX,
                "{HELPTITLE}" => $this->tt("helptitle"),
                "{ADD}" => $this->tt("button_add"),
                "{UNIQUE_DESC}" => $this->tt("unique_desc"),
                "{UNIQUE_STRICT_DESC}" => $this->tt("unique_strict_desc")

            );
            print str_replace(array_keys($replace), array_values($replace), $template);
        }
    }

    #end

    #region Setup and Rendering 

    /**
     * Returns an array containing active fields and parameters for each field
     * @return array
     */
    private function getFieldParams() {

        if (!class_exists("ActionTagHelper")) include_once("classes/ActionTagHelper.php");

        $field_params = array();
        $action_tag_results = ActionTagHelper::getActionTags($this->actionTags);
        foreach ($action_tag_results as $tag => $tag_data) {
            foreach ($tag_data as $field => $param_array) {
                $params = array ();
                foreach ($param_array as $raw_param) {
                    $param = empty($raw_param) ? "" : json_decode($raw_param, true);
                    if ($param === NULL) {
                        $raw_param = str_replace("\r", "", $raw_param);
                        $raw_param = str_replace("\n", "\\n", $raw_param);
                        $param = json_decode($raw_param);
                    }
                    if ($param === NULL) {
                        $param = array(
                            "error" => "Invalid parameters",
                            "target" => null
                        );
                    }
                    if($param == "") {
                        switch($tag) {
                            case $this->atUnique:
                                $param = array (
                                    "targets" => array($field),
                                    "field" => $field,
                                    "error" => ""                                
                                );
                                break;
                            case $this->atUniqueStrict:
                                //  Skip this because not relevant                              
                                break;
                        }

                    }
                    else {                       
                        // Convert non-json parameters to corresponding array
                        if (!is_array($param)) {
                            switch($tag) {
                                case $this->atUniqueStrict:
                                case $this->atUnique:
                                    $error = $param;
                                    if(empty($param)){
                                        $error = "Invalid parameter string.";
                                        break;
                                    }
                                    $param_array = array_map('trim', explode(',', $param));
                                    //  Add field always to the list itself
                                    array_unshift($param_array , $field);
                                    //  Remove duplicate fields, trim spaces and explode into array
                                    $param_final = array_values(array_unique($param_array));
                                    $param = array (
                                        "targets" => $param_final
                                    );
                                    break;
                            }
                        }
                        // Complete parameters with defaults
                        $param["field"] = $field;
                        //$param["error"] = $error;

                    }
                    $params[] = $param;
                }
                $field_params[$tag][$field] = $params;

            }
        }

        return $field_params;
    }

    /**
     * Passess along details about the current record so that action tags can be rendered
     * and displayed on the data entry form.
     * @param $project_id
     * @param $instrument
     * @param $record
     * @param $event_id
     * @param $instance
     * @param @survey_hash
     */
    private function renderActionTag($project_id, $instrument, $record, $event_id, $instance, $survey_hash = null) {
        
        global $Proj;
        $is_survey = $survey_hash != NULL;

        $field_params = $this->getFieldParams();

        // Filter the tags and configured fields to only those on the current instrument
        $fields = array(); 
        $tags = array();

        $fields = REDCap::getFieldNames($instrument);
        array_push($tags, $this->atUnique, $this->atUniqueStrict);

        // Filter for active fields only and count
        $active_uniques = 0;
        $active_unique_stricts = 0;
        $active_other = 0;
        $active_fields = array();
        foreach ($tags as $tag) {
            foreach ($fields as $field_name) {
                if (isset($field_params[$tag][$field_name])) {
                    
                    $active_fields[$tag][$field_name] = $field_params[$tag][$field_name];

                    if ($tag == $this->atUnique) {
                        $active_uniques++;
                    }
                    else if($tag == $this->atUniqueStrict) {
                        $active_unique_stricts++;
                    }
                    else {
                        $active_other++;
                    }
                }
            }
        }

        // Anything to do? At least one widget and autofill must be present
        if ($active_uniques + $active_unique_stricts + $active_other == 0) {
            return;
        }

        // Assemble information needed to pass to JavaScript for rendering        
        if (!class_exists("\STPH\UniqueActionTag\Project")) include_once ("classes/Project.php");
        $project = new Project($this->framework, $project_id);
        $debug = $this->getProjectSetting("javascript-debug") == true;
        $show_errors = $this->getProjectSetting("show-errors") == true;
        $show_labels = $this->getProjectSetting("show-labels") == true;
        $enable_hard_check = $this->getProjectSetting("enable-hard-check") == true;


        //  Gather strict-unique exceptions to be passed to Javascript
        $exceptions = array();
        $exceptions_str = $this->getProjectSetting("exceptions");
        if(!empty($exceptions_str)) {
            $exceptions = array_map('trim', explode(',', $exceptions_str ));
        }

        // Augment unqiue fields with some metadata (field type, ...)
        foreach ($active_fields as $tag => &$field_info) {
            foreach ($field_info as $field_name => &$data) {
                $fmd = $project->getFieldMetadata($field_name);
                $data["tagPerFieldCount"] = count($data);
                $data["type"] = $fmd["element_type"];
                $data["tag"] = $tag;                
            }
        }

        $actionTags = [];

        // Add active fields to action tags array with tag keys in camelCase
        foreach ($this->actionTags as $actionTag) {
            
            //  Only insert active action tags
            if(isset($active_fields[$actionTag])) {
                $camelCase = str_replace('-', '', ucwords(strtolower(ltrim($actionTag, '@')), '-'));
                $camelCase[0] = strtolower($camelCase[0]);
                $actionTags[$camelCase] = $active_fields[$actionTag];
            }

        }

        // Prepare parameter array to be passed to Javascript
        $js_params = array (
            "debug" => $debug,
            "errors" => $show_errors,
            "labels" => $show_labels,
            "survey" => $is_survey,
            "exceptions" => $exceptions,
            "hard_check" => $enable_hard_check,
            "actionTags" => $actionTags ?: array(),
        );

        $this->renderJavascript($js_params);
        $this->renderStyles();
    }

    /**
     * Include JavaScript files
     */
    private function renderJavascript($params) {

        $lang = array(
            "dialog_1" => $this->tt("u_dialog_1"),
            "dialog_1_5" => $this->tt("u_dialog_1_5"),
            "dialog_2" => $this->tt("u_dialog_2"),
            "dialog_2_5" => $this->tt("u_dialog_2_5"),
            "dialog_3" => $this->tt("u_dialog_3"),
            "dialog_4" => $this->tt("w_dialog_1"),
            "dialog_5" => $this->tt("w_dialog_2"),
        );

        // Define request parameters
        $request = array(
            "url" => $this->getUrl("requestHandler.php"),
            "pid" =>  htmlentities($_GET["pid"], ENT_QUOTES),
            "record" => htmlentities($_GET['id'], ENT_QUOTES),
            "event_id" => htmlentities($_GET["event_id"], ENT_QUOTES),
            "instance" => htmlentities($_GET["instance"], ENT_QUOTES)
        );
        //  Include Babel compiled Javascript file (`npm run build`) and initialize module script with parameters
        ?>
            <script src="<?php print $this->getUrl('lib/js/unique.js'); ?>"></script>
            <script>
                STPH_UniqueAT.request = <?php print json_encode($request) ?>;
                STPH_UniqueAT.params = <?= json_encode($params) ?>;
                STPH_UniqueAT.lang = <?php print json_encode($lang) ?>;
                $(function() {
                    $(document).ready(function(){
                        STPH_UniqueAT.init();
                    })
                });
            </script>
        <?php
    }

    /**
     * Include CSS Style File
     */
    private function renderStyles() {
        ?>
            <link rel="stylesheet" href="<?= $this->getUrl('css/style.css')?>">
        <?php
    }

    #end



    #region Ajax Request Handles

    /**
     * This function handles the ajax request for each action tag and performs the necessary database operations
     * to print the needed response back to the client.
     * @param $data
     * @throws \Exception
     */
    public function handleActionTag($data) {  

        try {          

            $project_id = db_escape($data["pid"]);
            $record = db_escape($data["record"]);
            $value = db_escape($data["value"]);
            $field = db_escape($data["field"]);
            $targets = implode("','",$data["targets"]);
            $tag = db_escape($data["tag"]);
           
            if($tag == $this->atUnique) {

                // Get a count of all duplicated values for the $secondary_pk field (exclude submitted record name when counting)
                $sql = "select count(1) from redcap_data where project_id = '$project_id' and field_name IN('".$targets."')
                and value = '" . db_escape($value) . "' and record != '' and record != '" . db_escape($record) . "'";
                $q = db_query($sql);
                // Return the number of duplicates
                print db_result($q, 0);

            }

            if($tag == $this->atUniqueStrict) {

                $event_id = $data["event_id"];
                //$instance = $data["instance"];
            
                $sql = "SELECT (
                        (SELECT count(1) FROM redcap_data WHERE project_id = '$project_id' AND field_name IN('".$targets."') AND value = '" . db_escape($value) . "' AND record != '' ) - 
                        (SELECT count(1) FROM redcap_data WHERE project_id = '$project_id' AND field_name = '$field' AND value = '" . db_escape($value) . "' AND record = '$record' AND event_id = '$event_id' )
                        )";
                $q = db_query($sql);
                print db_result($q, 0);
            }

        } catch(\Exception $e) {

            http_response_code(400);

            echo json_encode(array(
                'error' => array(
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                ),
            ));
        }
    }

    #end
}
