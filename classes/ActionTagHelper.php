<?php namespace STPH\UniqueActionTag;

use \REDCap as REDCap;

class ActionTagHelper
{
    private Array $actionTags;
    private String $regEx_emptyTag;
    private String $regEx_paramTag;
    private Array $fieldMetaData;
    private Array $actionTagData;

     /**
      * RegEx template to parse empty ActionTags
      * https://www.phpliveregex.com/p/Lgo
      *
      */
     const REGEX_EMPTY_TAG = "/(?<actiontag>(?<![a-zA-Z0-9\_\-\=])(%TAGNAMES%)(?![a-zA-Z0-9\_\-\=]))/x";

     /**
      * RegEx template to parse parametrized ActionTags
      * https://www.phpliveregex.com/p/Lgp
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

    public function __construct(Array $actionTags = []) {
        $this->actionTags = $actionTags;
        $this->setRegEx();
    }

    public function addActionTag($actiontag) {
        $title = $actiontag["tag"];
        unset($actiontag["tag"]);
        $this->actionTags[$title] = $actiontag;
        $this->setRegEx();
    }

    public function getActionTags(){
        return $this->actionTags;
    }

    private function setRegEx() {
        
        $allowedActionTags = array_keys($this->actionTags);
        $tagnames  = implode(" | ", $allowedActionTags);

        $this->regEx_emptyTag = str_replace('%TAGNAMES%', $tagnames, self::REGEX_EMPTY_TAG);
        $this->regEx_paramTag = str_replace('%TAGNAMES%', $tagnames, self::REGEX_PARAM_TAG);
    }


    /**
     * Get Action Tag Data by parsing field meta data
     * Parse for empty (flat) tags and parametrized tags
     * 
     * Return an array grouped by fields
     * 
     */
    function getActionTagData($fields = NULL, $instruments = NULL) {
        
        // Get the metadata with applied filters
        $this->getMetaData($fields, $instruments);

        // Build action tag data Array
        $this->buildActionTagData();

        return $this->actionTagData;
    }

    function getMetaData($fields, $instruments) {
        $q = REDCap::getDataDictionary('json', false, $fields, $instruments);
        $this->fieldMetaData = json_decode ($q, true );
    }

    function buildActionTagData() {

        $actionTagData = array();

        foreach ($this->fieldMetaData as $field) {

            //  parse action tags per field
            $parsed_tags = $this->parseActionTags($field);

            if($parsed_tags) {
                $actionTagData[$field["field_name"]] = $parsed_tags;
            }
        }

        $this->actionTagData =  $actionTagData;
    }

    function parseActionTags($field) {

        //  Process regEx on field annotation and return matches
        //  replace /n /r with white space, since they might break the RegEx
        $fieldMeta = str_replace(array("\n", "\r"), ' ', $field['field_annotation']);

        preg_match_all($this->regEx_emptyTag, $fieldMeta, $matches_emptyTag);
        preg_match_all($this->regEx_paramTag, $fieldMeta, $matches_paramTag);

        // Return false if none are found
        $hasParamTags = is_array($matches_paramTag['actiontag']) && count($matches_paramTag['actiontag']) !== 0;
        $hasEmptyTags = is_array($matches_emptyTag['actiontag']) && count($matches_emptyTag['actiontag']) !== 0;
        
        if (!$hasParamTags && !$hasEmptyTags) return false;

        $results = array();

        if($hasEmptyTags) {

            foreach ($matches_emptyTag['actiontag'] as $i => $tag) {
                $results[] = array(
                    'flat' => true,
                    'tag'   =>  $tag,
                    'params' => null,
                    'errors' => null
                );
            }
        } 
        
        if($hasParamTags) {
            foreach ($matches_paramTag['actiontag'] as $i => $tag) {

                $params = null;

                $matched = $matches_emptyTag['params'][$i];
                $tag = strtoupper($tag);

                $atFTypes = $this->actionTags[$tag]["allowed_field_types"];
                $atParams = $this->actionTags[$tag]["params"] ?? [];

                //  Check if is allowed field_type
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

                    if(count($atParams) > 0) {
                        //  Loop through ActionTag definitions, ignore everything else
                        foreach ($atParams as $key => $value) {

                            //  Check for required params
                            if($value["required"] && !isset($decoded_params[$value["name"]])) {
                                $errors["param_missing_required"] = "param '" . $value["name"] . "' is required";
                            }

                            //  Check param value types
                            else if( isset($decoded_params[$value["name"]]) && gettype($decoded_params[$value["name"]]) !== $value["type"] ) {
                                $errors["param_wrong_type"] = "param '" . $value["name"] . "' must be of type '" . $value["type"] . "'";
                            }
                        
                            //  add to params if is valid
                            else if( isset($decoded_params[$value["name"]]) && gettype($decoded_params[$value["name"]]) === $value["type"] ) {
                                $params[$value["name"]] = $decoded_params[$value["name"]];
                            }
                        }
                    }
                    
                }

                $results[] = array(
                    'tag'   =>  $tag,
                    'flat' => false,
                    'params' => $params,
                    'errors' => $errors
                );

            }
        }
        return $results;
    }
}