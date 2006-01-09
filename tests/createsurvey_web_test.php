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

define('UCCASS_DATE_FORMAT', 'Y-m-d');	// date format used by uccass for survey start/end dates; 'yyyy-mm-dd'

/**
 * Web test of create new survey.
 * 
 * @author Jakub Holy
 * @package uccass_tests
 * 
 * TODO: 1. Test 'copy survey' when creating new
 */
class TestOfCreateSurvey extends UCCASS_WebTestCase 
{
    function TestOfCreateSurvey(&$uccassMain) 
    {
    	parent::UCCASS_WebTestCase($uccassMain);
    }
    
    function setUp() 
    { parent::setUp(); }
    
    function tearDown() 
    { parent::tearDown(); }
    
    /**
     * Create a survey named $this->testSurveyName.
     */
    function test_create_survey()
    {
    	$this->printinfo('test_create_survey');
    	// I. Create survey
    	$this->create_newsurvey();
    	// $this->assertTrue( $this->delete_survey(), "delete_survey() failed" );
    }
    
    /**
     * Test: Creating a survey with a name that's already used should fail.
     */
    function test_create_existing_survey() 
    { 
    	$this->printinfo('test_create_existing_survey');
    	$this->myerror('TestOfCreateSurvey::test_create_existing_survey not yet implemented', E_USER_NOTICE); 
    }
    
    /**
     * Create a new test survey named $this->testSurveyName.
     * It only succeedes if the survey doesn't exist yet.
     * 
     * @param string $copy_survey_name Name of the survey to copy into the new
     * one; default: none.
     * @return string/boolean Content of the page resulting from the submition
     * (should be the page where you edit survey properties) or false.
     * 
     * @acces public
     */
    function create_newsurvey($copy_survey_name = false)
    {
    	$this->subtest1_login2newsurvey();
    	if($copy_survey_name)
    	{ return $this->subtest2_create_newsurvey($copy_survey_name); }
    	else
    	{ return $this->subtest2_create_newsurvey(); }
    }
    
    /**
     * Test: Login into uccass and get the start page of 'Create New Survey'.
     */
    function subtest1_login2newsurvey()
    {
    	$url = $this->get_url()."/new_survey.php";
    	$this->assertTrue( $this->get("$url"), "Failed to get the url $url; %s" );
    	$this->assertResponse(200);
    	$this->assert_LoginPage(true);	// we should be asked to log in at this point
    	// Elements of the form that shall be present when logged in:
    	$success = $this->assertField('survey_name');	// check we got to the page asking for survey name etc.
    	$success = $success && $this->assertField('username');		// default user - username
    	$success = $success && $this->assertField('password');		// default user - password
    	$success = $success && $this->assertField('copy_survey');	// select what survey to copy
    	$success = $success && $this->assertField('next');			// 'Next' button
    	if(!$success)
    	{ $this->showSource(); }
    }
    
    /**
     * Test: Create a new survey (submit its name, default user, copy?).
     * Pre-condition: We're logged in and got the new survey page already.
     * Important: Don't forget to delete the survey afterwards. 
     * 
     * TODO: Should we copy a survey to test that functionality too?
     * Note: The comment 'L10n' means that a string needs localization
     * 
     * @param string $copy_survey_name Name of the survey to copy into the new
     * one; default: none.
     * @return string/boolean Content of the page resulting from the submition
     * (should be the page where you edit survey properties)
     */
    function subtest2_create_newsurvey($copy_survey_name = 'None - Start with blank survey') // L10N
    {
    	// 1. Verify that such a survey doesn't exist yet
    	$this->assertFalse( $this->exists_in_table('surveys', "name=?", array($this->testSurveyName)), 'Test survey exists already.' );
    	
    	// 2.Fill-in and submit the new survey form
    	$this->assertTrue( $this->setField('survey_name', $this->testSurveyName), 'Failed to Set the survey_name' );
    	$this->assertTrue( $this->setField('username', $this->testUserUsername), 'Failed to Set the username' );
    	$this->assertTrue( $this->setField('password', $this->testUserPassword), 'Failed to Set the password' );
    	{ $this->assertTrue( $this->setField('copy_survey', $copy_survey_name), 'Failed to Set the copy_survey' ); }
    	$this->assertField('next');			// 'Next' button
    	//$this->showRequest();
    	$edit_survey_page = $this->clickSubmitByName('next');
    	//$this->showRequest();
    	
    	// Check the result of the submition
    	$this->assertTrue( $edit_survey_page !== FALSE, 'Submitting the survey name and user failed.' );
    	$this->assertWantedText($this->uccassMain->lang['survey_created']);
    	$this->assertNoUnwantedPattern('/ class="error"/', 'uccass returned an error notification, it seems: %s');	// check that no error occured
    	// echo '<pre>'.htmlentities($edit_survey_page).'</pre>';
    	return $edit_survey_page;
    }
    
} // TestOfCreateSurvey
?>