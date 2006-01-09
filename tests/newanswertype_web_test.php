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
class TestOfNewAnswerType extends UCCASS_WebTestCase 
{
	
    function TestOfNewAnswerType(&$uccassMain, $survey_name = false) 
    {
    	parent::UCCASS_WebTestCase($uccassMain, $survey_name);
    }
    
    /**
     * Go to the page for viewing survey results.
     */
    function goto_NewAnswerTypePage()
    {
    	$this->goto_EditSurveyPage();
    	$this->assertTrue( $this->clickLink('New Answer Type') );	// L10N
    	$this->assert_NewAnswerTypePage();
    }
    
    /**
     * Assert we are on the new answers type page.
     */
    function assert_NewAnswerTypePage()
    {
    	$this->assertWantedText('Edit Survey');	// L10N
    	$this->assertWantedText(' Answer Name');	// L10N
    	$this->assertWantedText(' Answer Values (MS and MM Answer Types Only)');	// L10N
    	$this->assertField('name');
    	$this->assertField('type');
    	$this->assertField('label');
    	$this->assertField('submit');	// button
    	$this->assertField('add_answer_num');
    	$this->assertField('add_answers_submit');	// button
    	$this->assertField('value[]');
    	$this->assertField('numeric_value[]');
    	$this->assertField('image[]');
    }
    
    /** @return string Name of the new question - used in add question. */
    function get_name()
    { return $this->answer_type_name; }
    
    /** @return string Label of the new question - displayed to the user. */
    function get_label()
    { return $this->answer_type_name; }
    
    /**
     * TEST: Try to add a new multiple choices - multiple answers question.
     * Its name is determined by $this->get_name().
     * Unfortunatelly we can only add 1 answer due to uccass implementation and
     * missing simpletest support. 
     * Pre-condition: The survey $this->testSurveyName exists.
     */
    function test_newanswertype()
    {
    	$this->printinfo('test_newanswertype');

		// 1. Go to the page
		$this->goto_NewAnswerTypePage();
    	
    	// Set fields
    	$this->assertTrue( $this->setField('name', $this->get_name()) );
    	$this->assertTrue( $this->setField('label', $this->get_label()) );
    	$this->assertTrue( $this->setField('type', 'MM - Multiple Choice, Multiple Answers') );
    	// Answers; 
    	// Note setting a field to array(value1,value2) produces 2 parameters per text field 
    	// (value[]=value1 and value[]=value2 per one field named 'value[]' => for 6 fields we get
    	// 12 elements); it wouldn't mind if we could do the same for image[] but we can't - it's a
    	// select, which is set by label, not value, and the value being set is compared to labels
    	// - but array('aquabar.gif','aquabar.gif') != 'aquabar.gif'
    	$this->assertTrue( $this->setField('value[]', $this->answer_type_value) );	// _test_answer_1
    	$this->assertTrue( $this->setField('numeric_value[]', 1) );
    	
    	// Submit
    	$this->assertTrue( $this->clickSubmitByName('submit')/*, 'Submit the new answer type failed. %s'*/ );
    	
    	// Check result
    	$this->assert_NewAnswerTypePage();
    	$this->assertWantedText('New answer type successfully added.');		// L10N
    	$this->assertNoUnwantedText('Error');	// L10N
    }
    
} // TestOfNewAnswerType
?>