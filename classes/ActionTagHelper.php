<?php namespace STPH\UniqueActionTag;

use \REDCap as REDCap;

/**
 * Class ActionTagHelper
 * 
 * Author: Ekin Tertemiz
 * Based on ActionTagHelper by Andy Martin:
 * https://gist.github.com/123andy/dd262439c0478ffbd37e4685bc8017ac
 * Modified to accept explicit parsing for ActionTags by tagnames and
 * differentiate between empty (flat) Action Tags and those with parameters.
 * 
 * Adds parameter validation, in case Action Tags were defined with specific syntax:
 * 
 * $actionTagName = [
 *      "tag" => "@MY-ACTION-TAG",              //  defines tag name for use
 *      "params" =>  [                          //  defines array of tag parameters
 *          [
 *              "name" => "param-name-1",       //  defines parameter name for tag use
 *              "type" => "boolean",            //  defines parameter type as boolean, to be validated when tag used
 *              "required" => true              //  defines if parameter must be explicitly listed when tag used
 *          ],
 *          [
 *              "name" => "param-name-2",
 *              "type" => "array",
 *              "reqired" => false
 *          ],
 *          [
 *              "name" => "param-name-3",
 *              "type" => "string",
 *              "requried" => false
 *          ]
 *      ],
 *      "allowed_field_types" => [              //  defines allowed field types where the tag can be used
 *          "text", 
 *          "notes"
 *      ],
 *      "allowFlat" => true,                    //  enable/disable use of tag without parameters
 *      "allowMultiple" => true,                //  enable/disable use of tag multiple times per field
 *      "allowStacking" => []                   //  enable/disable use of tag stacked with other tags
 *  ] 
 * 
 */
class ActionTagHelper
{
    private Array $actionTags;
    private Array $fieldMetaData;
    private Array $parsedData;
    private Array $actionTagData;
    private String $regEx_emptyTag;
    private String $regEx_paramTag;

     /**
      * RegEx template to parse empty ActionTags
      * https://www.phpliveregex.com/p/Lgo
      * 
      * typed class constants are supported in PHP > 8.3
      * https://php.watch/versions/8.3/typed-constants
      *
      */
     const REGEX_EMPTY_TAG = "/(?<actiontag>(?<![a-zA-Z0-9\_\-\=])(%TAGNAMES%)(?![a-zA-Z0-9\_\-\=]))/x";

     /**
      * RegEx template to parse parametrized ActionTags
      * https://www.phpliveregex.com/p/Lgp
      *
      * typed class constants are supported in PHP > 8.3
      * https://php.watch/versions/8.3/typed-constants
      *
      */
     const REGEX_PARAM_TAG ="/
     (?(DEFINE)
        (?<tagnames> %TAGNAMES%) 
        (?<number> -? (?= [1-9]|0(?!\d) ) \d+ (\.\d+)? ([eE] [+-]? \d+)? ) 
        (?<boolean> true | false | null ) 
        (?<string> \" ([^\"\\\\]* | \\\\ [\"\\\\bfnrt\/] | \\\\ u [0-9a-f]{4} )* \" ) 
        (?<array> \[ (?: (?&json) (?: , (?&json) )* )? \s* \] ) (?<pair> \s* (?&string) \s* : (?&json) ) 
        (?<object> \{ (?: (?&pair) (?: , (?&pair) )* )? \s* \} ) 
        (?<fieldname> [a-zA-Z0-9\_\-]+ ) 
        (?<json> \s* (?: (?&number) | (?&boolean) | (?&string) | (?&array) | (?&object) ) ) 
        (?<fieldlist> (?: (?&fieldname) (?: , (?&fieldname) )+ )+ ) ) 
        (?<actiontag> (?&tagnames) )
        (?:\= (?'params' (?: (?'match_list'(?&fieldlist)) | (?'match_json'(?&json)) | (?'match_string'(?:[[:alnum:]\_\-]+))))
     )/x";


    public function __construct() {
        $this->actionTagData = [];
        //$this->actionTags = $actionTags;
        //$this->setRegEx();
    }

    public function define(Array $actiontag): void {
        $title = $actiontag["tag"];
        unset($actiontag["tag"]);
        $this->actionTags[$title] = $actiontag;
        $this->setRegEx();
    }

    public function getActionTags() {
        return $this->actionTags;
    }

    private function setRegEx(): void {
        $allowedActionTags = array_keys($this->actionTags);
        $tagnames  = implode(" | ", $allowedActionTags);

        $this->regEx_emptyTag = str_replace('%TAGNAMES%', $tagnames, self::REGEX_EMPTY_TAG);
        $this->regEx_paramTag = str_replace('%TAGNAMES%', $tagnames, self::REGEX_PARAM_TAG);
    }


    /**
     * Get Action Tag Data
     * 
     * 
     */
    public function getData(Array|Null $fields = NULL, Array|Null $instruments = NULL): Array {
        
        // Get the metadata with applied filters fields, instruments
        $this->getMetaData($fields, $instruments);
        $this->parseData();
        $this->validate();

        return $this->actionTagData;
    }

    /**
     * Get Meta Data
     * 
     */
    private function getMetaData(Array|Null $fields, Array|Null $instruments) {
        $q = REDCap::getDataDictionary('json', false, $fields, $instruments);
        $this->fieldMetaData = json_decode ($q, true );
    }

    private function parseData(): void {
        //  parse action tags per field
        foreach ($this->fieldMetaData as $field) {
            $this->parseActionTags($field);
        }
    }

    private function validate(): void {

        //dump($this->parsedData);

        $validated = [];
        $errors = [
            "not_allowed_flat" => [],
            "not_allowed_multiple" => []
            //,"not_allowed_stacking"=> []
        ];
        foreach ($this->parsedData as $key => $data) {

            $hasErrors = false;

            $tag    = $data["tag"];
            $flat   = $data["flat"];
            $field  = $data["field"];

            //  check allowFlat per field
            if($flat && !$this->actionTags[$tag]["allowFlat"] && !in_array($field, $errors["not_allowed_flat"][$tag] ?? [])) {
                $errors["not_allowed_flat"][$tag][] = $field;
                $hasErrors = true;
            }

            //  check allowMultiple per field
            if(!$this->actionTags[$tag]["allow_multiple"] && isset($validated[$field][$tag]) && !in_array($field, $errors["not_allowed_multiple"][$tag] ?? [])) {
                $errors["not_allowed_multiple"][$tag][] = $field;

                unset($validated[$field][$tag]);
                if(count($validated[$field]) === 0) unset($validated[$field]);

                $hasErrors = true;
            }

            /**
             * Only add to validated if we have no errors
             * 
             */
            if($hasErrors === false) {
                $validated[$field][$tag] = $data;
            }

        }

        $this->actionTagData = array(
            $validated,
            $errors 
        );

    }

    /**
     * Parse field meta data with RegEx patterns, 
     * for empty and parametrized Action Tags.
     * 
     */
    private function parseActionTags(Array $field): void {

        //  replace /n /r with white space, since they might break the RegEx patterns
        $fieldMeta = str_replace(array("\n", "\r"), ' ', $field['field_annotation']);

        preg_match_all($this->regEx_emptyTag, $fieldMeta, $matches_emptyTag);
        preg_match_all($this->regEx_paramTag, $fieldMeta, $matches_paramTag);

        // Return false if none are found
        $hasParamTags = is_array($matches_paramTag['actiontag']) && count($matches_paramTag['actiontag']) !== 0;
        $hasEmptyTags = is_array($matches_emptyTag['actiontag']) && count($matches_emptyTag['actiontag']) !== 0;        
        if (!$hasParamTags && !$hasEmptyTags) return;

        if($hasEmptyTags) {
            foreach ($matches_emptyTag['actiontag'] as $i => $tag) {

                $atFTypes = $this->actionTags[$tag]["allowed_field_types"];
                if( isset($atFTypes) && !empty($atFTypes) && !in_array($field['field_type'], $atFTypes)) continue;

                $this->parsedData[] = array(
                    'tag'   =>  $tag,
                    'field' => $field["field_name"],
                    'field_type' => $field["field_type"],
                    'flat' => true,
                    'params' => [],
                    'errors' => []
                );
            }
        } 
        
        if($hasParamTags) {
            foreach ($matches_paramTag['actiontag'] as $i => $tag) {

                $params = [];
                $errors = [];

                $matched = $matches_paramTag['params'][$i];
                $tag = strtoupper($tag);

                $atFTypes = $this->actionTags[$tag]["allowed_field_types"];
                $atParams = $this->actionTags[$tag]["params"] ?? [];

                //Check if is allowed field_type
                if( isset($atFTypes) && !empty($atFTypes) && !in_array($field['field_type'], $atFTypes)) continue;

                $isList = $matches_paramTag["match_list"][$i] !== "";
                $isJSON = $matches_paramTag["match_json"][$i] !== "";
                $isString = $matches_paramTag["match_string"][$i] !== "";

                if($isString && !$isJSON && !$isList) {
                    $params = (String) $matched;
                }

                else if($isList && !$isJSON && !$isString) {
                    $params = explode(",", $matched);
                }

                else if($isJSON && !$isString && !$isString) {
                    
                    $decoded_params = json_decode($matched, true);
                    
                    //  Validate parameters
                    if(count($atParams) > 0) {
                        //  Loop through ActionTag definitions, ignore everything else
                        foreach ($atParams as $key => $value) {

                            //  Check for required params
                            if(isset($value["required"]) && $value["required"] && !isset($decoded_params[$value["name"]])) {
                                $errors["param_missing_required"][] = "param '" . $value["name"] . "' is required";
                            }

                            //  Check param value types
                            else if( isset($decoded_params[$value["name"]]) && gettype($decoded_params[$value["name"]]) !== $value["type"] ) {
                                $errors["param_wrong_type"][] = "param '" . $value["name"] . "' must be of type '" . $value["type"] . "'";
                            }
                        
                            //  add to params if is valid
                            else if( isset($decoded_params[$value["name"]]) && gettype($decoded_params[$value["name"]]) === $value["type"] ) {
                                $params[$value["name"]] = $decoded_params[$value["name"]];
                            }

                            //  use default value if decoded value is not set and is not required and has a default value
                            else if( isset($value["required"]) && !$value["required"] && isset($value["default"]) && !isset($decoded_params[$value["name"]])) {
                                $params[$value["name"]] = $value["default"];
                            }

                        }
                    }
                    
                }

                $this->parsedData[] = array(
                    'tag'   =>  $tag,
                    'field' => $field["field_name"],
                    'field_type' => $field["field_type"],
                    'flat' => false,
                    'params' => $params,
                    'errors' => $errors
                );
            }
        }
    }
}