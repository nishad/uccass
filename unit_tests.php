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
//define('TEST_DIRECTORY', 'tests/');

if (! defined('SIMPLE_TEST')) {
	define('SIMPLE_TEST', 'tests/simpletest/');
}
require_once(SIMPLE_TEST . 'unit_tester.php');
require_once(SIMPLE_TEST . 'reporter.php');
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


// Parameters for test cases -------------------------
// Mock objects
// Make UCCASS configuration easily accessible:
$uccassMain = new UCCASS_Main();
// 	-> side effect: ADOdb files are loaded as well.
Mock::generate('ADOConnection', 'MockADOConnection'); // def. the class MockADOConnection
//////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Prepares and runs uccass unit tests.
 * For the purpose of tests a test uccass user and a test survey are created.
 * Both are deleted unless a grave php error occures.
 * Note: The same user is used to create a test survey and as a default user
 * of the survey, which will strip her privileges - this could cause problems if
 * the user is not deleted before the next run of the tests [incorrect
 * login/password].
 */
class UCCASS_UnitTestsGroup extends GroupTest
{
	function UCCASS_UnitTestsGroup($name = 'UCCASS Unit tests')
	{ parent::GroupTest($name); }
	
	function run()
	{
		global $uccassMain;
		// ADD UNIT TESTS TO PERFORM
		// FUNCTIONAL TESTS:
		$this->addTestCase( new TestOfAnswerTypes($uccassMain, 'MockADOConnection') );
		//*/ WEB TESTS:
		$this->addTestCase( new TestOfCreateSurvey($uccassMain) );
		
		$this->addTestCase( new TestOfEditSurveyProperties($uccassMain) );
		$this->addTestCase( new TestOfNewAnswerType($uccassMain) );
		$this->addTestCase( new TestOfActivateSurvey($uccassMain, false) );	// may be ommited
		$this->addTestCase( new TestOfAddNewQuestion($uccassMain) );
		$this->addTestCase( new TestOfActivateSurvey($uccassMain, true) );
		// todo: ? Edit Answer Type, ?Preview Survey, ? Access Control
		$this->addTestCase( new TestOfTakeSurvey($uccassMain) );	// may be ommited
		$this->addTestCase( new TestOfViewSurveyResults($uccassMain) );	// may be ommited
		
		$this->addTestCase( new TestOfDeleteSurvey($uccassMain) );
		//*/
		
		// run the tests
		error_reporting(E_ALL ^ E_NOTICE);	// ignore notices
		ini_set("memory_limit","16M");
		parent::run(new HtmlReporter());
	}
	
} // UCCASS_UnitTestsGroup
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
	$test = new UCCASS_UnitTestsGroup();
	$test->run();
	echo '<hr>';
}
?>

</body>
</html>