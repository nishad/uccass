<?php
/*
 * Created on 4.12.2005
 * @author Jakub Holy
 * 
 * IMPORTANT: This file is expected to be included in a file in the root
 * directory of uccass.
 */

//error_reporting(E_ALL);

if (! defined('SIMPLE_TEST')) {
	define('SIMPLE_TEST', 'tests/simpletest/');
}
require_once(SIMPLE_TEST . 'unit_tester.php');
require_once(SIMPLE_TEST . 'reporter.php');

/**
 * Test of the class TAnswerType (dynamic vs. norm. type)
 */
class TestOfDynamicAnswerType extends UnitTestCase 
{
	/**@var object an instance of UCCASS_Main - I need to access its configuration. */
	var $uccassMain;
	
	/* Some parameters of data in the database; see test_tables_schema.xml. */
	var $surveyId					= 1;		// id of the survey the following belongs to
	var $dynaAnswerId 				= 1;		// id of a dynamic answer type 
	var $staticAnswerId 			= 2;		// id of a static answer type
	var $dynaQuestionId 			= 1;		// id of a question with a dynamic answer type 
	var $staticQuestionId			= 2;		// id of a question with a static answer type
	//var $dynaAnswerValues			= 2;		// number of its answers
	var $selectors1					= array('blondie');	// a value of the answer values' selector
	var $selectors2					= array('blondie', 'brunette', '');	// a value of the answer values' selector
	var $matchingDynaAnswerValues	= 1;		// number of its answers matching the selector
	// Copy of the dynamic answer values as stored in DB (avid => answer value details):
	var $dynaAnswers = array(
		1 => array('avid'=>1, 'value'=>'A blondie', 'numeric_value'=>1, 'image'=>'bar.gif', 'selector'=>'blondie')
		, 2 => array('avid'=>2, 'value'=>'A brunette', 'numeric_value'=>2, 'image'=>'bar.gif', 'selector'=>'brunette')
		, 3 => array('avid'=>3, 'value'=>'Empty', 'numeric_value'=>3, 'image'=>'bar.gif', 'selector'=>'')
		);
	var $staticAnswers = array(
		array('avid'=>4, 'value'=>'Static: Yes', 'numeric_value'=>1, 'image'=>'bar.gif')
		, array('avid'=>5, 'value'=>'Static: No', 'numeric_value'=>2, 'image'=>'bar.gif')
		);
	
	/**
	 * Create new TestOfAnswerType.
	 * @param object $uccassMain - an instance of UCCASS_Main - I need to access
	 * its configuration
	 */
	function TestOfDynamicAnswerType(&$uccassMain)
	{
		$this->uccassMain 			= &$uccassMain;
	}// constructor
    
    /** 
     * Test that test_get_answer_values returns what expected for a dynamic
     * answer type.
     * Note: We assume the operator used to match selectors is '='.
     * aid and different selector
     */
    function test_get_answer_values_dynamic()
    {
    	$paramsSet = array(
			array('by' => BY_AID, 'id' => $this->dynaAnswerId, 'answers'=>&$this->dynaAnswers),
			array('by' => BY_QID, 'id' => $this->dynaQuestionId, 'answers'=>&$this->dynaAnswers)
    	);
    	$_REQUEST['sid'] = $this->surveyId;
    	
    	foreach ($paramsSet as $params)
		{
			$this->sendMessage("Running test_get_answer_values_dynamic: by ".(($params['by']===BY_AID)? 'AID' : 'QID'));
			 
	    	// GET ANSWERS MATCHING A SINGLE SELECTOR ['blondie']
	    	$answer_values = $this->uccassMain->get_answer_values($params['id'], $params['by'], SAFE_STRING_TEXT, $this->selectors1);
			//$answerType = TAnswerType::load($this->dynaAnswerId, $prefix);
	    	if(!$this->assertNotEqual($answer_values, false, "No answer value found or a DB error (".$this->uccassMain->db->ErrorMsg()."). (%s)"))
	    	{ return false; }
	    	$matchingAnswers = array();	// Answer values with the given selector
	    	foreach ($params['answers'] as $avid => $answer)
			{ 
				if(strcmp($answer['selector'], current($this->selectors1)) === 0) 
				{ $matchingAnswers[] = $answer; }  
			}
	    	$this->assertNotEqual(count($answer_values), 0, "This test requires that at least 1 answer value is returned. (%s)");
	    	$this->assertEqual(count($answer_values['avid']), count($matchingAnswers), "Found different number of matching answers than expected (received:".var_export($answer_values, true)."). (%s)");
	    	$this->assertSameAnswerSets($answer_values['avid'], $matchingAnswers);
	    	
	    	//$this->assertEqual($answerType->isDynamic(), true, "Data are wrong in the database - this should be a dynamic answer type; $s");
	    	//$this->assertEqual($answerType->getType(), ANSWER_TYPE_MS, "Data are wrong in the database - this should be a dynamic answer of the type MS; $s");
	    	
	    	// GET ANSWERS MATCHING A SINGLE SELECTOR ['brunette']
	    	// - check that we do not obtain the same result as previously [it could be cached]
	    	$answer_values = $this->uccassMain->get_answer_values($params['id'], $params['by'], SAFE_STRING_TEXT, array('brunette'));
			//$answerType = TAnswerType::load($this->dynaAnswerId, $prefix);
	    	if(!$this->assertNotEqual($answer_values, false, "No answer value found or a DB error (".$this->uccassMain->db->ErrorMsg()."). (%s)"))
	    	{ return false; }
	    	$matchingAnswers = array();	// Answer values with the given selector
	    	foreach ($params['answers'] as $avid => $answer)
			{ 
				if(strcmp($answer['selector'], 'brunette') === 0) 
				{ $matchingAnswers[] = $answer; }  
			}
	    	$this->assertNotEqual(count($answer_values), 0, "This test requires that at least 1 answer value is returned. (%s)");
	    	$this->assertEqual(count($answer_values['avid']), count($matchingAnswers), "Found different number of matching answers than expected (received:".var_export($answer_values, true)."). (%s)");
	    	$this->assertSameAnswerSets($answer_values['avid'], $matchingAnswers);
	    	
	    	//$this->assertEqual($answerType->isDynamic(), true, "Data are wrong in the database - this should be a dynamic answer type; $s");
	    	//$this->assertEqual($answerType->getType(), ANSWER_TYPE_MS, "Data are wrong in the database - this should be a dynamic answer of the type MS; $s");
	    	
	    	
	    	// GET ALL ANSWERS
	    	$answer_values = $this->uccassMain->get_answer_values($params['id'], $params['by'], SAFE_STRING_TEXT);
	    	if(!$this->assertNotEqual($answer_values, false, "No answer value found or a DB error (".$this->uccassMain->db->ErrorMsg()."). (%s)"))
	    	{ return false; }
	    	$this->assertNotEqual(count($answer_values), 0, "This test requires that at least 1 answer value is returned. (%s)");
	    	$this->assertEqual(count($answer_values['avid']), count($this->dynaAnswers), "Found different number of matching answers than expected (received:".var_export($answer_values, true)."). (%s)");
	    	$this->assertSameAnswerSets($answer_values['avid'], $params['answers']);
	    	
	    	// GET ANSWERS MATCHING 1 OF MULTIPLE SELECTOR (all should be matched)
	    	// IMPORTANT: ensure that $this->selectors2 includes all selectors from the table dyna_answer_selectors
	    	$answer_values = $this->uccassMain->get_answer_values($params['id'], $params['by'], SAFE_STRING_TEXT, $this->selectors2);
	    	if(!$this->assertNotEqual($answer_values, false, "No answer value found or a DB error (".$this->uccassMain->db->ErrorMsg()."). (%s)"))
	    	{ return false; }
	    	$this->assertNotEqual(count($answer_values), 0, "This test requires that at least 1 answer value is returned. (%s)");
	    	$this->assertEqual(count($answer_values['avid']), count($this->dynaAnswers), "Found different number of matching answers than expected - all should be matched (received:".var_export($answer_values, true)."). (%s)");
	    	$this->assertSameAnswerSets($answer_values['avid'], $params['answers']);
		}
    } // test_get_answers
    
    
    /** 
     * Test that test_get_answer_values returns what expected for a static
     * answer type.
     */
    function test_get_answer_values_static()
    {
    	$paramsSet = array(
			array('by' => BY_AID, 'id' => $this->staticAnswerId, 'answers'=>&$this->staticAnswers),
			array('by' => BY_QID, 'id' => $this->staticQuestionId, 'answers'=>&$this->staticAnswers)
    	);
    	$_REQUEST['sid'] = $this->surveyId;
    	
    	foreach ($paramsSet as $params)
		{
			$this->sendMessage("Running test_get_answer_values_static: by ".(($params['by']===BY_AID)? 'AID' : 'QID'));
			//echo "<br>test_get_answer_values_static: by ".(($params['by']===BY_AID)? 'AID' : 'QID').", id=".$params['id']); 
	    	// GET ANSWERS MATCHING A SELECTOR
	    	$answer_values_all = $this->uccassMain->get_answer_values($params['id'], $params['by'], SAFE_STRING_TEXT);
	    	if(!$this->assertNotEqual($answer_values_all, false, "No answer value found or a DB error (".$this->uccassMain->db->ErrorMsg()."). (%s)"))
	    	{ return false; }	    	
	    	$this->assertSameAnswerSets($answer_values_all['avid'], $params['answers']);
		}
    } // test_get_answers
    
    /**
     * Assert that the two sets of answer values are equal.
     * @param array $receivedAnswersAvids - an array of answer value ids (of
     * answers received from the DB)
     * @param array $expectedAnswers - [a subset of] $this->dynaAnswers or
     * staticAnswers.
     */
    function assertSameAnswerSets($receivedAnswersAvids, $expectedAnswers)
    {
    	$expectedAvids = array();
    	foreach ($expectedAnswers as $answer)
		{ $expectedAvids[] = $answer['avid'] ;  }
		
		$differences = array_diff($receivedAnswersAvids, $expectedAvids); // what is in $receivedAnswers and not in $expectedAnswers?
		$diffSize = count($differences);
		if($diffSize !== 0)
		{ $differencesString = var_export($differences, true); } 
		return $this->assertEqual($diffSize, 0, "We've received more/less avids than expected (surplus: $differencesString). (%s)");
    }
   
}
?>
