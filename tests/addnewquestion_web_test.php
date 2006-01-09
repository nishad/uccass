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
 * Web test: add new questions to an existing survey (the test one).
 * 
 * @author Jakub Holy
 * @package uccass_tests
 */
class TestOfAddNewQuestion extends UCCASS_WebTestCase 
{
    function TestOfAddNewQuestion(&$uccassMain) 
    {
    	parent::UCCASS_WebTestCase($uccassMain);
    }
    
    /**
     * TEST: Add a new question to the test survey. 
     * Pre-condition: The survey $this->testSurveyName exists. Note: If the edit
     * survey page is laready loaded you can comment out the part marked by
     * 'GET_EDIT_SURVEY'
     */
    function test_add_new_question()
    {
    	$this->printinfo('test_add_new_question');
    	// 1. Get the page
    	// Get the 'Edit Survey' page - this stage may be omitted if we've got the page already
    	$this->goto_EditSurveyPage();
    	
    	// 2.Test we have the correct page
    	$this->assert_EditSurveyPage();
    	
    	// 3. Go to Edit questions
    	$this->assertTrue( $this->clickLink('Edit Questions') );		// L10N
    	$this->assert_EditQuestionsPage();
    	
    	// 4. Set the values; We add questions starting from the end because pagebreak
    	// cannot be inserted as the last question - it must be inserted in front of
    	// a question. Only the 1st question is inserted as first, because pagebreak
    	// cannot be inserted as First neither.
    	$first_question = array_shift($this->questions);		// get the first question
    	$questions_reversed = array_reverse($this->questions);
    	array_unshift($questions_reversed, $first_question);	// ... put it back to the front
    	$is_first = true;
    	foreach($questions_reversed as $question)
    	{
    		foreach($question as $field => $value)
    		{ $this->assertTrue( $this->setField($field,$value), "Failed to set field '$field' to '$value'" ); }
    		
    		if($is_first)
    		{ $is_first = false; }
    		else
    		{ $this->assertTrue( $this->setField('insert_after','1') ); }	// Insert After Number; L10N
    		
    		// Submit the new question
    		$success = $this->assertTrue( $this->clickSubmitByName('add_new_question'));
    		// Check result
    		$is_pagebreak = strcmp($question['question'], $this->uccassMain->CONF['page_break']) == 0;
    		//echo "Inserted question {$question['question']}; expecting: $is_pagebreak<br>";
    		$wanted_title = ($is_pagebreak)? 'PAGE BREAK inserted successfully.' : 'Question successfully added to survey.'; // L10N
    		$success = $success && $this->assertWantedText($wanted_title);//
    		$success = $success && $this->assertNoUnwantedText('Error');  
    		if(!$success)
    		{ $this->showSource(); break; }
    	} // for all questions	
    	  
    }
     
} // TestOfAddNewQuestion
?>