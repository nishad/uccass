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
 * Web test: take a survey.
 * Assumption: There's only one question on one page.
 * 
 * @author Jakub Holy
 * @package uccass_tests
 */
class TestOfTakeSurvey extends UCCASS_WebTestCase 
{
	
    function TestOfTakeSurvey(&$uccassMain, $survey_name = false) 
    {
    	parent::UCCASS_WebTestCase($uccassMain, $survey_name);
    }
    
    function setUp() 
    { /* do NOT restart the browser! Every test depends on the state created by the previous one. */}
    
    function tearDown() 
    { /* do NOT restart the browser! Every test depends on the state created by the previous one. */}
    
    
    /**
     * Check that no uccass error occured.
     */
    function assert_no_error()
    {
    	$this->assertNoUnwantedText('error');		// L10N a crude test that no error occured
    }
    
    /**
     * Check that we are on one of the pages that form the survey.
     * $param int $page_number Number of the page starting with 1
     */
    function assert_TakeSurveyPage($page_number)
    {
    	$this->assertWantedText("Page $page_number of");		// L10N
    	if(! $this->assertWantedPattern("/Survey #\d+: ".preg_quote($this->testSurveyName)."/") )	// L10N
    	{ $this->showSource(); /* doesn't show anything?! */ }
    	$this->assertField('quit', true, 'Missing the button "Quit Survey - ...".%s');
    	if($page_number > 1)
    	{ $this->assertField('previous', true, 'Missing the button "Previous Page".%s'); }
    	$this->assertField('next', true, 'Missing the button "Finish/Next Page".%s');
    }
    
    /**
     * Find the name of the question field on the current survey page.
     * @return string name of the field that represents the question
     */
    function find_question_field_name()
    {
    	$browser = $this->getBrowser(); 
    	$page = $browser->getContent();
    	$matches = array();
    	// Question field is e.g. <select name="answer[286][0]" size="1">; one more [] for MM
    	preg_match('/<[^<]* name="(answer\[(\d+)\]\[\d+]\[?\]?)"[^>]*>/',$page, $matches);
    	$this->assertTrue(isset($matches[1]), 'No question field found on the page!');
    	return $matches[1];
    }
    
    /**
     * Go to the first page of the survey $this->testSurveyName.
     * Pre-condition: The survey is activated.
     */
    function test_take_survey()
    {
    	$this->printinfo('test_take_survey');

		// 1. Go to the start page
		$this->assertTrue( $this->get($this->get_url()."/index.php") );
		$this->assertResponse(200);
		$this->assertWantedText('Survey System');	// L10N
		$this->assertTrue( ($page = $this->clickLink($this->testSurveyName)), "Failed to click the link to the survey {$this->testSurveyName}." );
    	//$this->assert_LoginPage(true);	// only if not public
    	
    	$this->subtest_question1_ss();
    	$this->subtest_question2_text();
    	$this->subtest_question3_textarea();
    	$this->subtest_question4_mm_2answers();
    	$this->subtest_question5_mm_1answer();
    }
    
    /* 	questions defined in UCCASS_WebTestCase
     * $this->questions = array(
    		array('question'=>'question1_single select', 'answer'=>'High / Moderate / Low', 'orientation'=>'Dropdown'),
    		array('question'=>$pagebreak),
    		array('question'=>'question2_text', 'answer'=>'Sentence (255 characters)','num_answers'=>1,
    		 'num_required'=>0, 'orientation'=>'Vertical'),
    		array('question'=>$pagebreak),
    		array('question'=>'question3_textarea', 'answer'=>'Textbox (Large)'),
    		array('question'=>$pagebreak),
    		array('question'=>'question4_mm', 'answer'=>'Discrimination Types', 'orientation'=>'Matrix'),	// give 2+ answers
    		array('question'=>$pagebreak),
    		array('question'=>'question5_mm', 'answer'=>'Discrimination Types', 'orientation'=>'Horizontal') // give 1 answer
    	);
     */
    
    /**
     * Answer the 1st question and go to the next one.
     * see $this->questions.
     * 'question'=>'question1_single select', 'answer'=>'High / Moderate / Low'
     * Assumption: There's only one question on one page.
     */
    function subtest_question1_ss()
    {
    	$this->printinfo('test_question1_ss');
    	$this->assert_no_error();
    	$this->assert_TakeSurveyPage(1);
    	$this->assertWantedText('question1_single select');
    	$question_name = $this->find_question_field_name();
    	$this->assertTrue( $this->setField($question_name, 'Moderate') );
    	$this->assertTrue( $this->clickSubmitByName('next') );
    }
    
    /**
     * Answer the 2nd question and go to the next one. 'question2_text',
     * 'answer'=>'Sentence (255 characters)'
     */
    function subtest_question2_text()
    {
    	$this->printinfo('test_question2_text');
    	$this->assert_no_error();
    	$this->assert_TakeSurveyPage(2);
    	$this->assertWantedText('question2_text');
    	$question_name = $this->find_question_field_name();
    	$selectors = array_keys($this->dynaanswer_values);
    	$this->assertTrue( $this->setField($question_name, $selectors[0]) );
    	$this->assertTrue( $this->clickSubmitByName('next') );
    }
    
    /**
     * Answer the 3rd question and go to the next one. 'question3_textarea',
     * 'answer'=>'Textbox (Large)'
     */
    function subtest_question3_textarea()
    {
    	$this->printinfo('test_question3_textarea');
    	$this->assert_no_error();
    	$this->assert_TakeSurveyPage(3);
    	$this->assertWantedText('question3_textarea');
    	$question_name = $this->find_question_field_name();
    	$this->assertTrue( $this->setField($question_name, 'Some\nlong\ntext') );
    	$this->assertTrue( $this->clickSubmitByName('next') );
    }
    
    /**
     * Answer the 4th question and go to the next one. 'question4_mm_2answers',
     * 'answer'=>'Discrimination Types' Give more answers.
     */
    function subtest_question4_mm_2answers()
    {
    	$this->printinfo('test_question4_mm');
    	$this->assert_no_error();
    	$this->assert_TakeSurveyPage(4);
    	$this->assertWantedText('question4_mm');
    	$question_name = $this->find_question_field_name();
    	$this->assertTrue( $this->setField($question_name, array('Yes - Racial','Yes - Gender')) );
    	$this->assertTrue( $this->clickSubmitByName('next') );
    }
    
    /**
     * Answer the 5th question and go to the next one. 'question5_mm',
     * 'answer'=>$this->answer_type_name. Give one answer.
     */
    function subtest_question5_mm_1answer()
    {
    	$this->printinfo('test_question5_mm');
    	$this->assert_no_error();
    	$this->assert_TakeSurveyPage(5);
    	$this->assertWantedText('question5_mm_1answer');
    	$question_name = $this->find_question_field_name();
    	$this->assertTrue( $this->setField($question_name, array($this->answer_type_value)) );
    	$this->assertTrue( $this->clickSubmitByName('next')  );
    }
    
    /**
     * Answer the 6th question and finish. Check dependencies.
     */
    function question6_dynamic()
    {
    	$this->printinfo('question6_dynamic');
    	$this->assert_no_error();
    	$this->assert_TakeSurveyPage(6);
    	$this->assertWantedText('question6_dynamic');
    	$this->assertField('next', 'Finish', 'This should be the last page ".%s');	// L10N
    	$question_name = $this->find_question_field_name();
    	// TODO: use $this->dynaanswer_values; also in setting the selector determining text field in q2
    	$answers = array_values($this->dynaanswer_values);
    	$this->assertTrue( $this->setField($question_name, array($answers[0])) ); // answer matching the selector set in subtest_question2_text
    	$this->assertFalse( $this->setField($question_name, array($answers[1])) ); // this answer should be excluded by the selector
    	$this->assertTrue( $this->clickSubmitByName('next')  );
    }
     
} // TestOfTakeSurvey
?>