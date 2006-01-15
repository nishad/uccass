<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"      "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <title>UCCASS Unit Tests</title>
</head>
<body>

<?php 
/*
 * Created on 4.12.2005
 * @author Jakub Holy
 * 
 * Run unit tests. 
 * This file must be in the main uccass directory so that uccass
 * classes can find their files (such as survey.ini.php and ADOdb/).
 * 
 */
define('TEST_DIRECTORY', 'tests/');

if (! defined('SIMPLE_TEST')) {
	define('SIMPLE_TEST', TEST_DIRECTORY . 'simpletest/');
}

define('TEST_SCHEMA_FILE', 'tests/test_tables_schema.xml');

require_once(SIMPLE_TEST . 'unit_tester.php');
require_once(TEST_DIRECTORY . 'body_html_reporter.php');
require_once(SIMPLE_TEST . 'mock_objects.php');

require_once('classes/main.class.php');

// The test cases -----------------------------------
require_once('tests/answertypes_test.php');
require_once('tests/createsurvey_web_test.php');

require_once('tests/editsurveyproperties_web_test.php');
require_once('tests/newanswertype_web_test.php');
require_once('tests/addnewquestion_web_test.php');
require_once('tests/activatesurvey_web_test.php');
require_once('tests/takesurvey_web_test.php');
require_once('tests/viewsurveyresults_web_test.php');

require_once('tests/deletesurvey_web_test.php');

require_once('tests/tanswertype_test.php');


// Parameters for test cases -------------------------
// Mock objects
// Make UCCASS configuration easily accessible:
$uccassMain = new UCCASS_Main();
// 	-> side effect: ADOdb files are loaded as well.
Mock::generate('ADOConnection', 'MockADOConnection'); // def. the class MockADOConnection
//////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Prepares and runs uccass unit tests using special test tables that it
 * creates.
 * 
 */
class TestDatabaseTests extends GroupTest
{
	var $uccassMain;
	
	function TestDatabaseTests($name = 'Test DB unit tests')
	{ 
		global $uccassMain;
		parent::GroupTest($name);
		 $this->uccassMain = $uccassMain;
	}
	
	function run()
	{
		// Set the prefix of test tables
		// Note: We cannot reset the prefix back unless the tests are run.
		$this->changeTablePrefix();		// this will apply to all test cases added above
		// create the test tables + data if they don't exist
		$this->generateTestTablesIfNone();
		
		// ADD UNIT TESTS TO PERFORM
		// Note: we must add them after we've changed the prefix of $this->uccassMain
		$this->addTestCase( new TestOfTAnswerType($this->uccassMain) );
		
		// run the tests -----------------------------------------------------------
		parent::run(new BodyHtmlReporter());
		
		$this->resetTablePrefix();
	}
	
	/** Stores the old prefix of uccass tables when we change it. */
	var $oldPrefix = '';
	
	/**
	 * Change the table prefix defined in uccass init file.
	 */
	function changeTablePrefix($prefix = 'test_7_')
	{
		$this->oldPrefix = $this->uccassMain->CONF['db_tbl_prefix'];
		$this->uccassMain->CONF['db_tbl_prefix'] = $prefix;
		
		// Replace the prefix string
		$file = 'survey.ini.php';
		if(file_exists($file))
        {
            $fp = fopen($file,"r");
            $ini_file = fread($fp,filesize($file));
            fclose($fp);

            if($fp = fopen($file,"w"))
            {
                
               $ini_file = preg_replace("/^db_tbl_prefix\s?=.*$/m","db_tbl_prefix = \"$prefix\"",$ini_file);

                if(!fwrite($fp,$ini_file))
                { $this->uccassMain->error($this->uccassMain->lang['config_not_write']); return false; }

                fclose($fp);
                echo "<p>Notice: The prefix of uccass tables changed to $prefix.</p>";
            }
            else
            { $this->uccassMain->error($this->uccassMain->lang['config_not_write']); return false; }
        }
        else
        { $this->uccassMain->error($this->uccassMain->lang['config_not_found']); return false; }
	}
	
	/** Reset the uccass table prefix to the original one.*/
	function resetTablePrefix()
	{
		if(!empty($this->oldPrefix))
		{
			$this->changeTablePrefix($this->oldPrefix);
			$this->oldPrefix = '';
		}
	}
	
	/**
	 * Create and fill with data tables for test if they don't exist.
	 */
	function generateTestTablesIfNone()
	{
		// Test that the tables don't exist
		$query = "SELECT count(*) FROM {$this->uccassMain->CONF['db_tbl_prefix']}dyna_answer_type_details";
        $rs = $this->uccassMain->db->GetOne($query);
        if($rs === FALSE)
        { 
        	// Table doesn't exist
        	echo '<p>Creating the test tables...</p>';
        	require_once('classes/databasecreator.class.php');
	        $dbCreator = Uccass_DbCreator::createInstance();
	        if($dbCreator)
	        {
	        	$dbCreator->SetUpgradeMethod('REPLACE');
	        	$success = $dbCreator->createDatabase($this->uccassMain->CONF['db_tbl_prefix'], TEST_SCHEMA_FILE);
	        	if(!$success)
	        	{ 
	        		echo '<p style="color:red">Table creation failed!'.$this->uccassMain->db->ErrorMsg().'</p>';
	        		return false; 
	        	} // else { echo "<hr><h2>The Executed SQL:</h2><pre>$success</pre><hr>";}
	        } // if $dbCreator instantiated without an error
        } // if the table doesn't exist (select failed)
        	
        return true;
	}
	
} // TestDatabaseTests

/**
 * Prepares and runs uccass unit tests on the normal database tables used by
 * uccass. For the purpose of tests a test uccass user and a test survey are
 * created. Both are deleted unless a grave php error occures. Note: The same
 * user is used to create a test survey and as a default user of the survey,
 * which will strip her privileges - this could cause problems if the user is
 * not deleted before the next run of the tests [incorrect login/password].
 */
class NormalDatabaseTests extends GroupTest
{
	var $uccassMain;
	
	function NormalDatabaseTests($name = 'Normal DB/Web tests')
	{ 
		global $uccassMain;
		parent::GroupTest($name);
		 $this->uccassMain = $uccassMain;
		 
		 // ADD UNIT TESTS TO PERFORM
		// FUNCTIONAL TESTS:
		$this->addTestCase( new TestOfAnswerTypes($this->uccassMain, 'MockADOConnection') );
		//*/ WEB TESTS:
		$this->addTestCase( new TestOfCreateSurvey($this->uccassMain) );
		
		$this->addTestCase( new TestOfEditSurveyProperties($this->uccassMain) );
		$this->addTestCase( new TestOfNewAnswerType($this->uccassMain) );
		$this->addTestCase( new TestOfActivateSurvey($this->uccassMain, false) );	// may be ommited
		$this->addTestCase( new TestOfAddNewQuestion($this->uccassMain) );
		$this->addTestCase( new TestOfActivateSurvey($this->uccassMain, true) );
		// todo: ? Edit Answer Type, ?Preview Survey, ? Access Control
		$this->addTestCase( new TestOfTakeSurvey($this->uccassMain) );	// may be ommited
		$this->addTestCase( new TestOfViewSurveyResults($this->uccassMain) );	// may be ommited
		
		$this->addTestCase( new TestOfDeleteSurvey($this->uccassMain) );
		//*/
	}
	
	function run()
	{ parent::run(new BodyHtmlReporter()); }
} // NormalDatabaseTests
/*
// ADOdb schema files -------------------------------
$adodb_dir = $uccassMain->CONF['adodb_path'];	
$adodb_files = array('adodb.inc.php', 'adodb-datadict.inc.php', 'adodb-xmlschema.inc.php');
foreach($adodb_files as $key => $fileName) 
{ 
	$fileName = $adodb_dir . '/' . $fileName;
	require_once($fileName);
}


//////////////////////////////////////////////////////////////////////////////////////////////
define('SCHEMA_DEFINITION_FILE', 'survey-adodb_schema.xml');
define('SCHEMA_DELETION_FILE', 'survey-delete_schema.xml');

/**
 * Create/Delete uccass DB schema.
 * Deletion will also drop all data.
 * @param string $schema_file Either SCHEMA_DEFINITION_FILE (create) or
 * SCHEMA_DELETION_FILE (delete/drop).
 * /
function process_schema($schema_file)
{
	global $uccassMain;
	// 1. CREATE TABLES + INDICES via adodb-xmlschema, insert data
	if( $uccassMain->db->Time() === false ){ trigger_error('Not adoConn. connected!', E_USER_ERROR); }
	$schema = new adoSchema( $uccassMain->db );
	$schema->ParseSchema( $schema_file );
	$schema->ContinueOnError(true);
	$result_nr = $schema->ExecuteSchema(); // 0 failed, 1
	$resultMsg = array(0 => 'failed', 1 => 'errors', 2 => 'success' );
	if($result_nr != 2) {
		echo "<h5 style='color:red'>Schema execution hasn't succeded. Reason: {$resultMsg[$result_nr]}; " .
				$uccassMain->db->ErrorMsg()."<h5>";
		exit('Schema execution failed');
	} 
	// echo $schema->PrintSQL( 'HTML' );
}
*/

/* *
 * Drop & create the uccass db schema.
 * /
function db_recreate_schema()
{
	process_schema( SCHEMA_DELETION_FILE );		// Drop all tables etc. and data from the database
	process_schema( SCHEMA_DEFINITION_FILE );	// Create all tables etc. and data 
}*/
////////////////////////////////////////////////////////////////////////////////////////////
/*
$test = &new GroupTest('UCCASS Unit tests');

// ADD UNIT TESTS TO PERFORM
// FUNCTIONAL TESTS:
$test->addTestCase( new TestOfAnswerTypes($uccassMain, 'MockADOConnection') );
// WEB TESTS:
$test->addTestCase( new TestOfCreateSurvey($uccassMain) );

$test->addTestCase( new TestOfEditSurveyProperties($uccassMain) );
$test->addTestCase( new TestOfNewAnswerType($uccassMain) );
$test->addTestCase( new TestOfActivateSurvey($uccassMain, false) );	// may be ommited
$test->addTestCase( new TestOfAddNewQuestion($uccassMain) );
$test->addTestCase( new TestOfActivateSurvey($uccassMain, true) );
// todo: ? Edit Answer Type, ?Preview Survey, ? Access Control
$test->addTestCase( new TestOfTakeSurvey($uccassMain) );	// may be ommited
$test->addTestCase( new TestOfViewSurveyResults($uccassMain) );	// may be ommited

$test->addTestCase( new TestOfDeleteSurvey($uccassMain) );

// run the tests
error_reporting(E_ALL ^ E_NOTICE);	// ignore notices
//db_recreate_schema();
*/
?>
<h1>UCCASS Unit Tests</h1>
<p>INFO: database type is 
<?php 
	echo "<var>{$uccassMain->CONF['db_type']}</var>, database is <var>{$uccassMain->CONF['db_database']}</var> at 
		<var>{$uccassMain->CONF['db_host']}</var>, user <var>{$uccassMain->CONF['db_user']}</var>.";
?>
</p>
<hr>

<h4>NOTICE: Don't forget to check the server log to discover any PHP errors that have occured.</h4>
<p>The tests make take up to 5-10 minutes. The default memory limit 8MB doesn't need to be 
sufficient but 16MB should be ok (I'll try to increase it by ini_set("memory_limit","16M")).</p>
<h4>Some things need to be checked manually:</h4>
<ul>
 <li>Dependencies: add a dependency that's satisfied for 2 or more values to a question</li>
</ul>
<h4>If there are problems with SimpleTest in WebTests:</h4>
<p>If it seems that the SimpleTest's internal browser doesn't set a field correctly:
1.Check that the field is set in the page representation (debug the method setValue of the 
appropriate tag class in tests/simpletest/tag.php) 2.Check whether the value is encoded and 
sent correctly upon submition: see getValue of the same tag and SimpleForm::_getEncoding in 
form.php (and SimpleFormEncoding::add(key,value) and its asString in encoding.php). The encoding
 is sent in SimpleHttpRequest::_dispatchRequest (http.php).</p>

<form action="<?php echo($_SERVER['PHP_SELF']); ?>" method="post" style="text-align:center">
	<input type="submit" name="run_tests" value="Run Tests">
</form>
<hr>

<?php
if( isset($_REQUEST['run_tests']) )
{
	flush();
/*
ini_set("memory_limit","16M");
$test->run(new HtmlReporter());
//db_recreate_schema();
*/
	$bodyHtmlReporter = new BodyHtmlReporter();
	$bodyHtmlReporter->paintPageHeader('UCCASS Unit tests');
	
	$normalDbTtests = new NormalDatabaseTests();
	$testDatabaseTests = new TestDatabaseTests();
	
	error_reporting(E_ALL ^ E_NOTICE);	// ignore notices
	ini_set("memory_limit","16M");
	
	$normalDbTtests->run();
	$testDatabaseTests->run();
	
	$bodyHtmlReporter->paintPageFooter();
	
}
?>

</body>
</html>