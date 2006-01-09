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

if (!defined('TEST_DIRECTORY')) 
{ define('TEST_DIRECTORY', ''); }
require_once(TEST_DIRECTORY . 'ucasswebtestcase.class.php');

/**
 * Web test: delete the test survey.
 * 
 * @author Jakub Holy
 * @package uccass_tests
 */
class TestOfDeleteSurvey extends UCCASS_WebTestCase 
{
    function TestOfDeleteSurvey(&$uccassMain) 
    {
    	parent::UCCASS_WebTestCase($uccassMain);
    }
    
    ////////////////////////////////////////////////////////////////////////////////////////
    // END - THE FOLLOWING MUST BE RUN AS LAST
    ////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * TEST: Delete the test survey
     * Pre-condition: the survey named $this->testSurveyName exists.
     * Note: If the edit survey page is laready loaded you can comment out the
     * part marked by 'GET_EDIT_SURVEY'
     */
    function test_delete_survey()
    {
    	$this->printinfo('test_delete_survey');
    	// Get info of the survey
    	$sid = $this->selectOne('surveys', "name='{$this->testSurveyName}'", 'sid');
    	$this->assertTrue($sid, "There should be a survey named {$this->testSurveyName}; sid=$sid");
    	
    	// Get the 'Edit Survey' page - this stage may be omitted if we've got the page already
    	$this->goto_EditSurveyPage();
    	
    	// Process the form
    	$this->assert_EditSurveyPage();
    	$this->assertTrue( $this->setField('delete_survey', true) );
    	$result_page = $this->clickSubmitByName('edit_survey_submit');
    	$this->assertTrue( $result_page !== FALSE );
    	if( !$this->assertWantedText('Survey has been deleted.') )	// L10N
    	{ $this->showSource(); }
    	
    	// Check the functionality: was all really deleted? (cannot check that answer_values were deleted too)
    	if($sid)
    	{
    		// Check that no dependent element stayed in any table
    		foreach($this->dependant_tables as $dependant_table)
	    	{
	    		$this->assertFalse( $this->exists_in_table($dependant_table, "sid=$sid"), "The/a $dependant_table wasn't deleted!." );
	    	}
	    	
	    	// Check answer values - verify that no answer values without their answer types exist
	    	// (there is no 'sid' attribute so we cannot check the relation survey->answer value directly)
	    	$tbl_prefix = $this->uccassMain->CONF['db_tbl_prefix'];
	    	$query = "SELECT count(*) as count FROM {$tbl_prefix}answer_values av WHERE av.aid NOT IN " .
		    			" (SELECT aid FROM {$tbl_prefix}answer_types at WHERE at.sid=$sid)";
		    $count = $this->query($query, "", E_USER_NOTICE);
		    $this->assertTrue($count["count"] == 0, "There shouldn't stay any answer_values without the corresponding" .
		    		" answer_types; but there're $count (we expect == 0). %s");
    	}
    } // test_delete_survey
    
    /**
     * Delete the survey with the given name from the database.
     * 
     * @param string $survey_name Name of the survey to delete.
     * @return True upon success
     */
    /*function delete_survey($survey_name = false)
    {
    	if($survey_name === false)
    	{ $survey_name = $this->testSurveyName; }
    	
    	$success = true;
    	$error_msg = "TestOfCreateSurvey::delete_survey problem with ";
    	$tbl_prefix = $this->uccassMain->CONF['db_tbl_prefix'];
    	// Get the survey
    	$sid = $this->selectOne('surveys', "name='{$this->testSurveyName}'", 'sid');
    	if($sid)
    	{
    		// TODO: delete all answer_types of this query and their answer_values
    		// TODO: Delete survey contents: answer types, answer values, questions and dependencies
    		// Get all survey answer types
    		if( $this->exists_in_table('answer_types', "sid=$sid") )
    		{
			// Delete the survey answers
			$query = "DELETE FROM {$tbl_prefix}answer_values " .
		    			" WHERE aid IN (SELECT aid $answer_types_from)";
		    	$this->query($query, "$error_msg delete survey's answer values (none exist?)", E_USER_NOTICE);
    		}

		// Delete all entities depending on this survey	    	
	    	foreach($this->dependant_tables as $table2delete)
	    	{
	    		if($this->exists_in_table($table2delete, "sid=$sid"))
	    		{
	    			$success = $success && $this->query("DELETE FROM {$tbl_prefix}$table2delete WHERE sid=$sid", 
					"$error_msg delete survey's $table2delete", 
	    				E_USER_WARNING);
	    		}	
	    	} // for each dependant table
	    
	    	// Delete the survey itself
	    	$rs_del_survey = $this->query("DELETE $from WHERE sid=$sid", "$error_msg delete survey", E_USER_WARNING);
    	}
    	else
    	{ $success = false; }
    	
    	return $success;
    }
    */
    
} // TestOfDeleteSurvey
?>