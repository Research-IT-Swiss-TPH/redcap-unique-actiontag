<?php namespace STPH\UniqueActionTag;

require_once __DIR__ . '/../../../redcap_connect.php';
if (!class_exists("ActionTagHelper")) include_once("classes/ActionTagHelper.php");

use Exception;
use \ExternalModules\ExternalModules;
use \ExternalModules\ModuleBaseTest;
use Project;
use Vanderbilt\REDCap\Classes\ProjectDesigner;

abstract class BaseTest extends ModuleBaseTest {

    const AJAX_ACTION = "check-unique";
    const FORM_1_NAME = "form_1";
    const FORM_2_NAME = "form_2";

    public static $rndm;
    public static $testPIDs;

    public static $TEST_PID_1;
    public static $TEST_PID_2;
    public static $TEST_PID_3;

    public function __construct() {        
        
        parent::__construct();
        $this->rndm = (string) substr(hash('sha256', rand()), 0, 10);

    }

    /**
     * This method is called before the first test of this test class is run.
     * 
     */
    static function setUpBeforeClass(): void
    {
        self::resetTestProjects();
        self::$testPIDs = ExternalModules::getTestPIDs();
        self::$TEST_PID_1 = self::$testPIDs[0];
        self::$TEST_PID_2 = self::$testPIDs[1];
        self::$TEST_PID_3 = self::$testPIDs[2];
    }

    /**
     * This method is called before each test.
     * 
     */
    public function setUp():void {
        parent::setUp();

    }
    
    /**
     * This method is called after the last test of this test class is run.
     * 
     */
    static function tearDownAfterClass():void{

        // self::echo("\nFinishing tests.\n");
        // self::echo("---\n\n");
    }


    static function resetTestProjects() {
        if(self::hasTestProjects()) {
            self::echo("\n---");
            self::cleanupTestProjects();
            self::preserveProjectsTable();
            self::echo("---\n\n");
        }
    }

    //  Check if system has test projects
    private static function hasTestProjects(){
        $sql = "SELECT *  FROM redcap_config WHERE field_name = 'external_modules_test_pids'";
        $result  = ExternalModules::query($sql, []);

        return ($result->num_rows) > 0 ? true : false;

    }

    //  Cleanup test projects
    static function cleanupTestProjects() {
        
        //  Delete projects
        $sql = 'DELETE FROM redcap_projects WHERE `app_title` LIKE "External Module Unit Test Project%" ';
        ExternalModules::query($sql, []);

        //  Reset config
        ExternalModules::query(
            "DELETE FROM `redcap_config` WHERE  `field_name`='external_modules_test_pids'", []
        );

        //  Unset global
        unset($GLOBALS['external_modules_test_pids']);
        self::echo("\nTest Projects have been deleted.\n");
    }

    /**
     * Preserve projects table
     * 
     * Sets redcap_project AUTO_INCREMENT to MAX(project_id)
     * https://stackoverflow.com/a/41466825/3127170
     * 
     */
    static function preserveProjectsTable() {

        ExternalModules::query("SET @m = (SELECT MAX(project_id) + 1 FROM redcap_projects)", []);
        ExternalModules::query("SET @s = CONCAT('ALTER TABLE redcap_projects AUTO_INCREMENT=', @m)", []);
        ExternalModules::query("PREPARE stmt1 FROM @s", []);
        ExternalModules::query("EXECUTE stmt1", []);
        ExternalModules::query("DEALLOCATE PREPARE stmt1", []);

        self::echo("Projects table has been preserved.\n");

    }

    protected static function getRndm() {
        return (string) substr(hash('sha256', rand()), 0, 10);
    }

    /**
     * Writes into console
     * 
     */
    protected static function echo($message)
    {
        // if output buffer has not started yet
        if (ob_get_level() == 0) {
            // current buffer existence
            $hasBuffer = false;
            // start the buffer
            ob_start();
        } else {
            // current buffer existence
            $hasBuffer = true;
        }

        // echo to output
        echo "\033[01;33m" . $message . "\033[0m";

        // flush current buffer to output stream
        ob_flush();
        flush();
        ob_end_flush();

        // if there were a buffer before this method was called
        //      in my version of PHPUNIT it has its own buffer running
        if ($hasBuffer) {
            // start the output buffer again
            ob_start();
        }
    }

    /**
     * Loads a test fixture from json into an associative array
     */
    protected static function loadFixture($file)
    {
        $fixture_path = __DIR__."/fixtures/{$file}.json";

        if(!file_exists($fixture_path)) {
            throw new Exception("Fixture at path $fixture_path does not exist.");
        }

        return json_decode(file_get_contents($fixture_path), true);
    }

    /**
     * REDCap Module Ajax Hook Call
     * 
     */
    function mock_module_ajax($project_id, $record, $repeat_instance, $payload) {

        $action = self::AJAX_ACTION;
        $event_id = $this->getFirstEventId($project_id);
        $instrument = self::FORM_1_NAME;

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

        $this->redcap_module_ajax(...$args);

    }

    /**
     * Fixture Helpers
     */
    protected static function addField($projectId, $formName, $fieldLabel, $fieldName, $fieldType) {

        $fieldParams = array(
            "field_label"   => $fieldLabel,
            "field_name"    => $fieldName,
            "field_type"    => $fieldType
        );

        $project = new Project($projectId);
        $projectDesigner = new ProjectDesigner($project);

        $projectDesigner->createField($formName, $fieldParams);
    }

    protected static function makeFormRepeatable($projectId, $formName, $eventId) {
        
    }

    /**
     * Helper function to generate form_name from form_label
     * Taken from ProjectDesigner Class (it would be good if REDCap Core offers this as a default)
     * 
     */
    protected static function getFormName($form_label) {
        return preg_replace("/[^a-z_0-9]/", "", str_replace(" ", "_", strtolower($form_label)));
    }


}