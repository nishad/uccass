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
 * Web test: edit properties of an existing survey (of the test survey).
 * 
 * @author Jakub Holy
 * @package uccass_tests
 */
class TestOfEditSurveyProperties extends UCCASS_WebTestCase 
{
    function TestOfEditSurveyProperties(&$uccassMain) 
    {
    	parent::UCCASS_WebTestCase($uccassMain);
    }
    
    /**
     * Select on the main page of uccass the survey 'Example Survey' for
     * editting.
     * Pre-condition: The survey $this->testSurveyName exists. Note: If the edit
     * survey page is already loaded you can comment out the part marked by
     * 'GET_EDIT_SURVEY'
     */
    function test_edit_survey()
    {
    	$this->printinfo('test_edit_survey');
    	$survey_name = 'Example Survey';
    	
    	// 1. Get & check the Main page
    	$this->assertTrue( $this->get($this->get_url()."/index.php") );
    	$this->assertResponse(200);
    	$this->assert_MainPage();
    	// Select a survey
		$this->assertTrue( $this->setField('sid', $survey_name), "Failed to select the survey $survey_name for editing." );		// what survey to edit
    	// Submit to edit the selected survey
    	$this->assertTrue( $this->clickSubmitByName('submit') );
    	
    	// 2. Log in if not logged
    	$this->assert_LoginPage(true);
    	
    	// Verify the result
    	$this->assert_EditSurveyPage($survey_name);
    }
    
    /**
     * Test: Fill-in and submit new survey properties (start date...). 
     * This actually creates a new empty survey stored in the database.
     * //Pre-condition: We've already created a new survey (submitted it) and
     * //got the page for editing of survey properties. [CANCELED]
     */
    function test_set_survey_properties()
    {
    	$this->printinfo('test_set_survey_properties');
    	
    	// 1. Get the page
    	$this->goto_EditSurveyPage();
    	
    	// 3.Test we have the correct page
    	$this->assert_EditSurveyPage();
    	$this->assertField('name', $this->testSurveyName, "We should be editting the survey {$this->testSurveyName}; %s");
    	$this->assertField('active', '0', "A new survey should be inactive [it has no questions yet]%s");  // L10N
    	$this->assertField('start');				// start date
    	$this->assertField('end');
    	$this->assertField('template');
    	$this->assertField('redirect_page');
    	$this->assertField('redirect_page_text');	// if redirect = custom url
    	$this->assertField('date_format');
    	$this->assertField('time_limit');			// max. number of minutes for taking the survey
    	$this->assertField('clear_answers');
    	$this->assertField('delete_survey');
    	$this->assertField('edit_survey_submit');	// 'Save Changes' button
    	//DebugBreak();
    	// 4. Set some properties of the survey
    	$this->assertTrue( $this->setField('active','0'), 'set active failed' );
    	$this->assertTrue( $this->setField('start',date(UCCASS_DATE_FORMAT)), 'set start failed' );
    	$this->assertTrue( $this->setField('end',date(UCCASS_DATE_FORMAT, time() + 7*24*60*60)), 'set end failed' ); // next week
    	$template = 'Default';
    	$this->assertTrue( $this->setField('template', $template), "Failed to set the survey template to $template.");
    	$this->assertTrue( $this->setField('redirect_page', 'index') ); // value of the input tag
    	// $this->setField('redirect_page_text', 'http://example.url.eu/');
    	$this->assertTrue( $this->setField('time_limit', 30) );
    	
    	// 5. Submit the changes
    	$this->assertTrue( $this->clickSubmitByName('edit_survey_submit') );
    	// $this->showRequest();
    	// 6. Check the result of the submition
    	$this->assert_EditSurveyPage();
    	$this->assertNoUnwantedPattern('/ class="error"/', 'uccass returned an error notification, it seems: %s');	// check that no error occured
    	if( !$this->assertWantedText($this->uccassMain->lang['properties_updated']) )
    	{ $this->showSource(); }
    	$this->assertField('template', $template, "It seems that we failed to set the template to $template.(%s)");
    }

} // TestOfEditSurveyProperties
?>