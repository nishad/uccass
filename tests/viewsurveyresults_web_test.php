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
 * Web test: view results of a survey as text/graphical representation/export
 * into csv.
 * 
 * @author Jakub Holy
 * @package uccass_tests
 */
class TestOfViewSurveyResults extends UCCASS_WebTestCase 
{
	
    function TestOfViewSurveyResults(&$uccassMain, $survey_name = false) 
    {
    	parent::UCCASS_WebTestCase($uccassMain, $survey_name);
    }
    
    /**
     * Go to the page for viewing survey results.
     */
    function goto_ViewResultsPage()
    {
    	$sid = $this->selectOne('surveys', "name='{$this->testSurveyName}'", 'sid');
		$this->assertTrue( $this->get($this->get_url()."/results.php", array('sid'=>$sid)) );
		$this->assertResponse(200);
    	$this->assert_LoginPage(true);
    }
    
    /**
     * Check we are on the page 'View Results'.
     */
    function assert_ViewResultsPage()
    {
    	$this->assertWantedText('Survey Results');	// L10N
    	$this->assertWantedPattern("/Results for Survey #\d+: ".preg_quote($this->testSurveyName)."/");	// L10N
    }
    
    /**
     * TEST: View survey - graphical view [the default].  
     * Pre-condition: The survey $this- >testSurveyName exists.
     */
    function test_viewsurveyresults()
    {
    	$this->printinfo('test_viewsurveyresults');

		// 1. Go to the page
		$this->goto_ViewResultsPage();
    	
    	// Check the page
    	$this->assert_ViewResultsPage();
    	$this->assertLink('Results as Table');		// L10N
    	$this->assertNoUnwantedText('error');		// L10N a crude test that no error occured
    	
    	// 2. TODO: Check for the presence of survey questions ???
    	
    }
    
    /**
     * TEST: View survey - graphical view [the default].  
     * Pre-condition: The survey $this- >testSurveyName exists.
     */
    function test_resultsastable()
    {
    	$this->printinfo('test_resultsastable');

		// 1. Go to the page
		$this->goto_ViewResultsPage();
    	$this->assertTrue( $this->clickLink('Results as Table') );
    	$this->assert_ViewResultsPage();
    	$this->assertLink('Graphic Results');
    	
    	// 2. TODO: Check for the presence of survey questions
    	foreach( $this->questions as $question )
    	{ 
    		if( strcmp($question['question'], $this->uccassMain->CONF['page_break']) != 0 )
    		{ $this->assertWantedText($question['question']); }
    	}
    }
    
    function test_exporttocvs_as_text()
    {
    	$this->printinfo('test_exporttocvs_as_text');

		// 1. Go to the page
		$this->goto_ViewResultsPage();
    	$this->assertWantedText('Export Results to CSV as Text or Numeric Values');	// L10N
    	$this->assertTrue( $this->clickLink('Text') );	// the link part of 'Export Results to CSV as Text or Numeric Values'
    	if(!$this->assertHeader("Content-Disposition", "attachment; filename=Export.csv", 'Assert header Content-Disposition:attachment; filename=Export.csv; %s'))
    	{ $this->showHeaders(); }
    }
     
} // TestOfViewSurveyResults
?>