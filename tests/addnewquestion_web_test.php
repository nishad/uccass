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
    	
    	//////////////////////////////////////////////////
    	// 		Question with 2 dependencies			//
    	//////////////////////////////////////////////////
    	$this->printinfo("test_add_new_question - question with dependencies...");
    	// Note: the test may fail e.g. if the labels  'Get selector' etc change
 		$question = array('question'=>'question6_dynamic', 'answer'=>$this->dynaanswer_type_name,
			'option[1]'=>'Get selector', 'dep_qid[1]' => '2',	// dependency on a sentence-type question
			'option[2]'=>'Require', 'dep_qid[2]' => '4' // dependency on  question4_mm_2answers
			);
 		foreach($question as $field => $value)
    	{ $this->assertTrue( $this->setField($field,$value), "Failed to set field '$field' to '$value'; does the field/the value exist?" ); }
    		
    	// Submit the new question
    	// These fields cannot be set directly beacuse they're constructed dynamicly by JavaScript
    	$answers = array('No', 'Yes - Gender');
    	$q4_avids = $this->_get_avids('question4_mm_2answers', $answers);
    	$this->assertTrue( $q4_avids, 'Failed to get avids for question4_mm_2answers.' );
    	$dep_avids = array('dep_aid[1][]' => 0, 'dep_aid[2][]' => array($q4_avids[0], $q4_avids[1]));
		$success = $this->assertTrue($this->clickSubmitByName('add_new_question', $dep_avids), 'Failed to submit the new question [with dependencies].');
		
		// Check result
		//echo "Inserted question {$question['question']}; expecting: $is_pagebreak<br>";
		$success = $success && $this->assertWantedText('Question successfully added to survey.');	// L10N
		$success = $success && $this->assertWantedText('Require if question 4 is: ');
		$success = $success && $this->assertNoUnwantedText('Error');  
		if(!$success)
		{ $this->showSource(); }
		
		// Insert a pagebreak before the last (6th) question
		$this->assertTrue( $this->setField('question',$this->uccassMain->CONF['page_break']), "Failed to set field 'question' to a pagebreak." );
		$this->assertTrue( $this->setField('insert_after','5'), "Failed to set field 'insert_after' to 5." );
		$success = $this->assertTrue($this->clickSubmitByName('add_new_question', $dep_avids), 'Failed to submit the pagebreak.');
		$success = $success && $this->assertWantedText('PAGE BREAK inserted successfully.');
		$success = $success && $this->assertNoUnwantedText('Error');
    } // test_add_new_question
    
    /**
     * Return an array of ids of answers (avids) to the given question of false
     * on a failure.
     * @param string $question The field 'question' of the questions table
     * @param array $desired_answers answers whose avids we want (field value
     * of answer_values).
     */
    function _get_avids($question, $desired_answers)
    {
    	$prefix = $this->uccassMain->CONF['db_tbl_prefix'];
    	$question = $this->uccassMain->SfStr->getSafeString($question,SAFE_STRING_DB);
    	$answers_cond = '(1=2 OR ';
    	foreach ($desired_answers as $value)
		{
			$value = $this->uccassMain->SfStr->getSafeString($value,SAFE_STRING_DB);
			$answers_cond .= "av.value = $value OR " ;  
		}
		$answers_cond .= '1=2)';
    	$query = "SELECT q.question, av.avid FROM {$prefix}questions q JOIN {$prefix}answer_values av ON (q.aid=av.aid) 
    			  WHERE q.question = $question AND $answers_cond";
    	$rs = $this->uccassMain->db->Execute($query);
        if($rs === FALSE)
        { 
        	$this->sendMessage('ERROR in _get_avids: ' . $this->db->ErrorMsg());
        	return false; 
        }
        
        $avids = array();
        while($row = $rs->FetchRow($rs))
        { $avids[] = (int)$row['avid'] ;}
        
        if(!empty($desired_answers) && (count($desired_answers) !== count($avids)))
        { 
        	$this->sendMessage('ERROR in _get_avids: the number of avids and of desied answers differ:'
        		. count($desired_answers) . '(desired)!==(avids)' . count($avids)
        	); 
        	return false; 
        }
    	
    	return 	$avids;
    }
     
} // TestOfAddNewQuestion
?>