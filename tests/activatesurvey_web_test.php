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

$ucasswebtestcase_file = 'ucasswebtestcase.class.php';
if (!defined('TEST_DIRECTORY')) {
	$ucasswebtestcase_file = TEST_DIRECTORY . $ucasswebtestcase_file;
}
require_once($ucasswebtestcase_file);

/**
 * Web test: try to make an existing survey active.
 * 
 * @author Jakub Holy
 * @package uccass_tests
 */
class TestOfActivateSurvey extends UCCASS_WebTestCase 
{
	var $has_questions;
	
    /**
     * @param bool $has_questions True - the survey has some question and
     * activation should succeed; oherwise it's expected to fail 
     */
    function TestOfActivateSurvey(&$uccassMain, $has_questions = true, $survey_name = false) 
    {
    	parent::UCCASS_WebTestCase($uccassMain, $survey_name);
    	$this->has_questions = $has_questions;
    }
    
    /**
     * TEST: Add a new question to the test survey. 
     * Pre-condition: The survey $this->testSurveyName exists and it has at
     * least one question. 
     * Note: If the edit survey page is laready loaded you
     * can comment out the part marked by 'GET_EDIT_SURVEY'
     */
    function test_activate_survey()
    {
    	$this->printinfo('test_activate_survey[has '.(($this->has_questions)?'':'no').' questions]');
    	$this->goto_EditSurveyPage();
    	
    	// Set active and commit
    	$this->assertTrue( $this->setField('active', '1', 'Set the field "active" to true failed' ) );
    	// Submit the changes
    	$this->assertTrue( $this->clickSubmitByName('edit_survey_submit') );
    	
    	// Check the outcome
    	$this->assert_EditSurveyPage();
    	if($this->has_questions)	// should succeed (if true) or fail (if false)?
    	{	
    		// The survey has some questions => activation should succeed
    		$this->assertField('active', '1', "The 'active' field should be true because activation should have succeeded.");
    		$this->assertNoUnwantedPattern('/ class="error"/', 'uccass returned an error notification, it seems: %s');	// check that no error occured
    		if( !($this->assertWantedText($this->uccassMain->lang['properties_updated']) && $this->assertWantedText('Survey properties updated') ))	// L10N
    		{ $this->showSource(); }
    	}
    	else
    	{
    		// The survey has no questions => activation should fail
    		$this->assertField('active', '0', "The 'active' field should be still false because activation should have failed.");
    		if( !($this->assertWantedText('Error') && $this->assertWantedText('Cannot activate a survey with no questions.') ))	// L10N
    		{ $this->showSource(); }
    	} // if-else should succeed/fail	
    }
     
} // TestOfActivateSurvey
?>