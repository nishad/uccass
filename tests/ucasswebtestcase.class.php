<?php
//======================================================
// Copyright (C) 2005 Jakub Holy, All Rights Reserved
//
// This file is part of the Unit Command Climate
// Assessment and Survey System (UCCASS)
//
// UCCASS is free software; you can redistribute it and/or
// modify it under the terms of the Affero General Public License as
// published by Affero, Inc.; either version 1 of the License, or
// (at your option) any later version.
//
// http://www.affero.org/oagpl.html
//
// UCCASS is distributed in the hope that it will be
// useful, but WITHOUT ANY WARRANTY; without even the implied warranty
// of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// Affero General Public License for more details.
//======================================================

require_once('simpletest/web_tester.php');

/** Add this to url parameter to disable the DBG php debuggger. */
define('NO_DEBUGGER','DBGSESSID=1');

/**
 * Abstract parent of all web test cases of uccas.
 * Defines utility methods to set up data in a database 
 * (e.g. it can create a survey to be tested).
 * 
 * @abstract
 * @author Jakub Holy
 * @package uccass_tests
 */
class UCCASS_WebTestCase extends WebTestCase 
{
	/** An opened ADOConnection to the test database. */
	var $adoConnection;
	/**@var object an instance of UCCASS_Main - I need to access its configuration. */
	var $uccassMain;
	
	/**@var string Name of the uccass user we vreate for the purpose of tests.
	 * @access private */
	var $testUserUsername = '_webtestuser';
	/**@var string Password of the uccass user we vreate for the purpose of tests.
	 * @access private */
	var $testUserPassword = '_webtestuser';
	/**@var integer Id of the uccass user we vreate for the purpose of tests.
	 * @access private */
	var $testUserUid;
	/**
	 * @var array Parameters to be appended to url (after '?' or '&') to pass
	 * user login info to uccass.
	 * @access private */
	var $userLoginParameters;
	
	/** @var string Name of the survey we create during/for our tests. */
	var $testSurveyName = '_webtest_testsurvey_';
	
	/** @var array All tables (without prefix) related to a survey and containing 
	 * the column 'sid' that references the survey by its id. Notice that 
	 * 'answer_values' is dependant on the survey indirectly through
	 * answer_types: survey-{sid}-->asnwer_types-{aid}-->answer_values.*/
	var $dependant_tables = array('answer_types', 'questions', 'results', 'results_text', 'time_limit', 'completed_surveys', 
	    		'dependencies', 'ip_track');
	    		
	/** @var string Name used for our new test answer type. */
	var $answer_type_name = '_test_answer_';
	var $answer_type_value = '_test_answer_1';
	
	/** @var array Defines the questions that we shall try to create.
     * @access protected [read only] */
    var $questions;

	/**
	 * @param object $uccassMain - an instance of UCCASS_Main - I need to access
	 * its configuration and its opened ado connection
	 * @param mixed $survey_name Name of the test survey to create, edit etc. or
	 * false to use the default name (see $this->testSurveyName)
	 */
    function UCCASS_WebTestCase(&$uccassMain, $survey_name = false) 
    {
    	// Process arguments
    	if($survey_name)
    	{ $this->testSurveyName = $survey_name; }
    	$this->uccassMain		=& $uccassMain;
    	$this->adoConnection 	=& $this->uccassMain->db;
    	if( $this->adoConnection->Time() === false )
    	{ $this->myerror("It seems that the ADOConnection isn't connected to a database as required."); }
    	$this->userLoginParameters = array( "username"=>$this->testUserUsername, 
			"password"=>$this->testUserPassword );
    	
    	// Create test user
    	if( ($this->create_test_user() === true) && (isset($this->testUserUid)) )
    	{ register_shutdown_function(array(&$this, 'delete_test_user')); }
    	
    	// Define questions
    	$pagebreak = $uccassMain->CONF['page_break'];
    	// Use all Teextarea, Sentence, SS, MS
    	// We need orientation=Dropdown for multiple choices because only this type
    	// allows us to set an answer by its label, not its value
    	$this->questions = array(	// L10N: orientation= 'Vertical'...
    		array('question'=>'question1_single select', 'answer'=>'High / Moderate / Low', 'orientation'=>'Dropdown'),
    		array('question'=>$pagebreak),
    		array('question'=>'question2_text', 'answer'=>'Sentence (255 characters)','num_answers'=>1,
    		 'num_required'=>0, 'orientation'=>'Vertical'), // dependencies: option[1]=Show, dep_qid[1]=1, dep_aid[1][]=Low,Moderate
    		array('question'=>$pagebreak),
    		array('question'=>'question3_textarea', 'answer'=>'Textbox (Large)'),
    		array('question'=>$pagebreak),
    		array('question'=>'question4_mm_2answers', 'answer'=>'Discrimination Types', 'orientation'=>'Dropdown'),	// give 2+ answers
    		array('question'=>$pagebreak),
    		array('question'=>'question5_mm_1answer', 'answer'=>"{$this->answer_type_name}", 'orientation'=>'Dropdown') // give 1 answer
    	);
    } // UCCASS_WebTestCase
    
    /** Called when a fatal error occurs which prevents us from continuing. */
    function myerror($message, $severity = E_USER_ERROR)
    {
    	// trigger_error($message, $severity); // trigger_error can't be run when the test is run
    	// otherwise the SimpleTest error handling gets into an infinite loop
    	$errortype = array (
                E_ERROR           => "Error",
                E_WARNING         => "Warning",
                E_PARSE           => "Parsing Error",
                E_NOTICE          => "Notice",
                E_CORE_ERROR      => "Core Error",
                E_CORE_WARNING    => "Core Warning",
                E_COMPILE_ERROR   => "Compile Error",
                E_COMPILE_WARNING => "Compile Warning",
                E_USER_ERROR      => "User Error",
                E_USER_WARNING    => "User Warning",
                E_USER_NOTICE     => "User Notice",
                E_STRICT          => "Runtime Notice"
                );
    	$err_msg = "ERROR[{$errortype[$severity]}]: $message";
    	echo "<p><em>$err_msg</em></p>";
    	if(!error_log("$err_msg"));
    	
    	if($severity === E_USER_ERROR)
    	{ exit("Fatal error: $message");}
    }
    
    function setUp() 
    { $this->restart(); /*'restart' the browser to be sure we're not logged in. */}
    
    /** 
     * Create an uccass user with all priviledges for our tests.
     * (Do nothing if the user exists already.)
     * @return boolean True when the user has been created or if it existed
     * already, false upon failure.
     * @access private
     */
    function create_test_user()
    {
    	$username = $this->adoConnection->Quote( $this->testUserUsername );
    	$password = $this->adoConnection->Quote( $this->testUserPassword );
    	
    	// 1. Test the user doesn't exist yet
    	if( $this->exists_in_table('users', "username=$username AND password=$password") )
    	{ unset($this->testUserUid); return true; /* user already exists */ }
    	else
    	{
	    	// 2. Create the user
	    	$this->testUserUid = $this->adoConnection->GenID($this->uccassMain->CONF['db_tbl_prefix'].'users_sequence');
	        $query = "INSERT INTO {$this->uccassMain->CONF['db_tbl_prefix']}users
	                  (create_priv, admin_priv, uid, name, username, password)
	                  VALUES (1, 1, {$this->testUserUid}, $username, $username,$password)";
	        $rs = $this->adoConnection->Execute($query);
	        if($rs === FALSE)
	        { 
	        	$this->myerror("Cannot insert test user into database; reason: " . $this->adoConnection->ErrorMsg() );
	        	return false; 
	        }
	        else
	        { return true; }
    	} // if-else test user exists already
    } // create_test_user
    
    /** 
     * Delete the uccass user we created for our tests.
     * @return mixed False if fails
     * @access private 
     */
    function delete_test_user()
    {
    	if(isset($this->testUserUid) && $this->testUserUid !== FALSE) 
    	{ return $this->adoConnection->Execute("DELETE FROM {$this->uccassMain->CONF['db_tbl_prefix']}users WHERE uid=$this->testUserUid"); }
    	else
    	{ 
    		$this->myerror("Test user not deleted because it hasn't been created.", E_USER_NOTICE);
    		return false; 
    	} 
    }
    
    /**
     * @return string Url of the uccass root directory without the trailing '/'
     * Example of usage: $this->post( $this->get_url().'/survey.php').
     */
    function get_url()
    {
    	return $this->uccassMain->CONF['html'];
    }
    
    /**
     * Wrapper for ADOConnection::Execute that calls $this->myerror when Execute
     * fails.
     * 
     * @param string $query the query string
     * @param string $error_msg Message in the case of a failure
     * @param integer $severity Severity of the failure, see $this->myerror
     * @return mixed Return value of ADOConnection::Execute
     */
    function query($query, $error_msg = '', $severity = null)
    {
    	$rs = $this->adoConnection->Execute($query);
    	if($rs === FALSE)
    	{ 
    		$error_msg = ($error_msg != '')? $error_msg : "UCCASS_WebTestCase::query failed";
    		$error_msg .= "; query: $query; reason: " . $this->adoConnection->ErrorMsg();
    		if(is_null($severity))
    		{ $this->myerror($error_msg); }
    		else
    		{ $this->myerror($error_msg, $severity); }
    	}
    	
    	return $rs;
    }
    
    /** Return the first element of $matches quoted.*/
    function quote($matches){ return $this->adoConnection->Quote($matches[1]); }
    
    /**
     * Wrapper for $this->query that performs a select.
     * 
     * @param string $table the table we select from without a prefix; e.g.
     * 'surveys'.
     * @param string $where_condition Selection condition such as sid=27; if
     * some value is surrounded by quotes (') they'll be replaced by appropriate
     * quotes (with postgres name=?prague? will become name='prague')
     * @param string $select_clause What columns to select; all by default ('*')
     * @param  string $error_msg Message in the case of a failure
     * @param integer $severity Severity of the failure, see $this->myerror
     * @return mixed Return value of ADOConnection::Execute
     */
    function select($table, $where_condition, $select_clause = '*', $error_msg = '', $severity = null)
    {
    	$adoConnection = $this->adoConnection;
    	$where_condition = preg_replace_callback("|'(\S*)'|", array(&$this, 'quote'), $where_condition);
    	$query = "SELECT $select_clause FROM {$this->uccassMain->CONF['db_tbl_prefix']}$table " .
    			"WHERE $where_condition";
    	return $this->query($query, $error_msg = '', $severity = null);
    }
    
    /** As this->select but instead of a result set it returns its 1st element or false if none. */
    function selectOne($table, $where_condition, $select_clause = '*', $error_msg = '', $severity = null)
    {
    	$ret = false;
		$rs = $this->select($table, $where_condition, $select_clause, $error_msg, $severity);
		if ($rs) {		
			if (!$rs->EOF) $ret = reset($rs->fields);
			$rs->Close();
		} 
		return $ret;
    }
    
    /**
     * Checks whether an element exists in the database.
     * 
     * @param string $table Name of the table without a prefix (uccass->CONF
     * ['db_tbl_prefix']) such as 'survey'
     * @param string $where_condition Condition for the element such as
     * 'sid=13'; instead of values you can place '?'; n-th '?' will be replaced
     * by quoted n-th element of $values
     * @param array $values Parameters of the where clause that you want to be
     * quoted for the database (useful for strings)
     * @return boolean True if at least one element satisfying the condition
     * exists in the database.
     */
    function exists_in_table($table, $where_condition, $values = array())
    {
    	// Process params - replace '?' by values
    	$replaced = ''; $i = 0; $where_suffix = $where_condition;
		while( ($pos = strpos($where_suffix,'?')) !== FALSE ) {
		   $replaced .= substr($where_suffix, 0, $pos) 
		   		. $this->adoConnection->Quote($values[$i++]);	// insert the value
		   $where_suffix = substr( $where_suffix, $pos+1);	// continue after the '?'
		}
		if(count($values) != $i)
		{ 
			$this->myerror("UCCASS_WebTestCase::exists_in_table: wrong number of elements in $values - " .
				"expected $i, got ". count($values)." for select 'from $table where $where_condition'.");
		}
		else 
		{ $where_condition = $replaced . $where_suffix;	/* append the rest behind the last '?'*/ }
    	
    	// Query the database
    	$table_name = $this->uccassMain->CONF['db_tbl_prefix']. $table;
    	$elementCount = $this->adoConnection->GetOne( "SELECT count(*) FROM $table_name " .
    			" WHERE $where_condition" );
    			
    	// Check the result
    	if($elementCount === FALSE)
    	{ $this->myerror("UCCASS_WebTestCase::exists_in_table Failed when selecting 'from $table_name where $where_condition'; reason: " . $this->adoConnection->ErrorMsg()); }
    	elseif($elementCount > 0)
    	{ return true; /* the element already exists */ }
    	else
    	{ return false; }
    } // exists_in_db
    
    /** Print info about the test beeing run; call at the beginning of each test_* method. */
    function printinfo($test_name)
    { echo "<h5>Running $test_name...</h5>"; }
    
    ////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Helper for tests: assert we're on the main page of uccass.
     */
    function assert_MainPage()
    {
    	$this->assertWantedText('Survey System', 'We should be on the start page of uccass. %s'); // L10N
    	$this->assertField('sid', true, 'Missing the select with surveys to edit. %s');		// the select tag for selection of a survey to edit
    	$this->assertField('submit', 'Edit Survey', "The button 'Edit Survey' is missing. %s");	// L10N
    }
    
    /**
     * Helper for tests: assert we're on the page that asks for username and
     * password.
     * @param mixed $do_login If true try to log in as the test user.
     * @return True if logging in has not failed
     */
    function assert_LoginPage($do_login = false)
    {
    	$logged = true;
    	$this->assertWantedText('User Login', 'We should be on the start page "User Login". %s'); // L10N
    	$this->assertField('username');
    	$this->assertField('password');
    	
    	// Log in if requested
    	if($do_login === true)
    	{
    		$this->assertTrue( $this->setField('username', $this->testUserUsername) );
    		$this->assertTrue( $this->setField('password', $this->testUserPassword) );
    		$this->assertTrue( ($logged = $this->clickSubmit('Enter')) );	// L10N
    	}
    	return $logged;
    }
    
    /**
     * Helper for tests: assert we're on the page for editting survey
     * properties.
     * @param mixed $survey_name Either a name of the survey to edit or false to
     * use the defaul $this->testSurveyName.
     */
    function assert_EditSurveyPage($survey_name = false)
    {
    	$survey_name = ($survey_name)? $survey_name : $this->testSurveyName;
    	$success = $this->assertField('name', $survey_name, "We should be editting the survey $survey_name; %s");
    	$success = $success && $this->assertWantedText('Edit Survey', 'We should be on the page "Edit Survey". %s');	// L10N
    	$success = $success && $this->assertField('edit_survey_submit', true, 'Missing the button "Save Changes"');	// 'Save Changes' button present
    	if(!$success)
    	{ $this->showSource(); }
    	return $success;
    }
    
    /**
     * Helper for tests: assert we're on the page for editting survey's
     * questions.
     * @param mixed $survey_name Either a name of the survey to edit or false to
     * use the defaul $this->testSurveyName.
     */
    function assert_EditQuestionsPage($survey_name = false)
    {
    	$this->assertWantedText('Edit Survey', 'We should be on the page "Edit Survey". %s');	// L10N
    	$this->assertField('mode', 'questions', 'The Edit Survey mode should be "questions".%s');
    	//  mode = 'questions' for a new survey, 'edit_question' for an existing one?
    	// Other fields
    	$this->assertField('question', true, 'Missing the Add a new question text area.%s');
    	$this->assertField('answer', true, 'Missing the Answer Type select.%s');
    	$this->assertField('num_answers', true, 'Missing the Number of Answer Blocks select.%s');
    	$this->assertField('num_required', true, 'Missing the Required Answers select.%s');
    	$this->assertField('insert_after', true, 'Missing the Insert After Number select.%s');
    	$this->assertField('orientation', true, 'Missing the Orientation select.%s');
    	$this->assertField('add_new_question', true, 'Missing the button "Add New Question".%s');	// 'Save Changes' button present
    	//$this->showSource();
    }
    
    /**
     * Gets the page for editting survey properties - to be used by tests.
     * Only works if the test survey exists already.
     * It gets id of the test survey from the DB, fetches the edit survey page
     * for that survey and logs in.
     */
    function goto_EditSurveyPage()
    {
    	// Get the 'Edit Survey' page - this stage may be omitted if we've got the page already
		$sid = $this->selectOne('surveys', "name='{$this->testSurveyName}'", 'sid');
		$this->assertTrue( $this->get($this->get_url()."/edit_survey.php", array('sid'=>$sid)) );
		$this->assertResponse(200);
    	// 2. Log in 
    	$this->assert_LoginPage(true);	
    }
    
    /**
     * Assert that uccass hasn't issued any error notification.
     */
    function assertNoUccassError()
    {
    	$success = $this->assertNoUnwantedPattern('/ class="error"/', 'uccass returned an error notification, it seems: %s');	// check that no error occured
    	//$success = $success && $this->assertNoUnwantedPattern('/Warning:\s.*\.php\son\sline .*/', 'It seems that php issued a warning: %s');
    	if(!$success)
    	{ $this->showSource(); }
    }
    
} // UCCASS_WebTestCase
?>