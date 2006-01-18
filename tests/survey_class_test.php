<?php
/*
 * Created on 16.1.2006
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

require_once('classes/survey.class.php');

/**
 * Test of the class TAnswerType (dynamic vs. norm. type)
 */
class TestOfSurveyClass extends UnitTestCase 
{
	var $uccassSurvey;
	
	function TestOfSurveyClass()
	{
		$this->uccassSurvey =& new UCCASS_Survey();
	}
	
	/**
	 * Test that dependencies are handeled correctly.
	 * To test: dependency that is satisfied for a single answer x multiple
	 * answers, dependency that is not satisfied.
	 */
	function test_check_dependencies()
	{
		$this->sendMessage("Running test_check_dependencies");
		
		// Define the dependencies
		$dep_qid	= 1;	// the question the processed one depends upon	
		$depend = array(
			// dependency satisfied for 1 answer
			2 =>array(
				'dep_qid'=>array($dep_qid),
     			'dep_aid'=>array(101),
     			'dep_option'=>array(DEPEND_MODE_REQUIRE)
			),
			// dependency satisfied for 2 answers
			3 =>array(
				'dep_qid'=>array($dep_qid, $dep_qid),
     			'dep_aid'=>array(101, 102),
     			'dep_option'=>array(DEPEND_MODE_HIDE, DEPEND_MODE_HIDE)
			),
			// dependency that isn't satisfied
			4 =>array(
				'dep_qid'=>array($dep_qid),
     			'dep_aid'=>array(-1),
     			'dep_option'=>array(DEPEND_MODE_REQUIRE)
			),
			// Dynamic answer type question
			5 =>array(
				'dep_qid'=>array($dep_qid),
     			'dep_aid'=>array(),	// it needs to extract the answer's value 
     			'dep_option'=>array(DEPEND_MODE_SELECTOR)
     		)
		);
		$depend_keys = array_keys($depend);
		
		// 1. nothing
		
		// 2. Dependency satisfied for 1 value only & single valued answer
		// Define answers to previous questions
		$_SESSION['take_survey']['answer'][$dep_qid] = array(101);
		
		$dependencyActions = $this->uccassSurvey->check_dependencies(2, $depend_keys, $depend);
		$this->assertIdentical($dependencyActions['Require'], 1, 'The single-valued dependency should be ' .
				'satisfied and thus the question should be Required which it is not! (%s)');
		$this->assertIdentical($dependencyActions['Hide'], 0, "The question's option==Require so Hide cannot be 1! (%s)");
		$this->assertIdentical($dependencyActions['Show'], 0, "The question's option==Require so Show cannot be 1! (%s)");
		
		// 3. Dependency satisfied for 2 values & multiple-valued answer
		$_SESSION['take_survey']['answer'][$dep_qid] = array(102, 103, 104);
		
		$dependencyActions = $this->uccassSurvey->check_dependencies(3, $depend_keys, $depend);
		$this->assertIdentical($dependencyActions['Hide'], 1, 'The single-valued dependency should be ' .
				'satisfied and thus the question should be Hidden which it is not! (%s)');
		$this->assertIdentical($dependencyActions['Require'], 0, "The question's option==Hide so require cannot be 1! (%s)");
		$this->assertIdentical($dependencyActions['Show'], 0, "The question's option==Hide so show cannot be 1! (%s)");
		
		// 4. Dependency that is not satisfied
		$_SESSION['take_survey']['answer'][$dep_qid] = array();
		
		$dependencyActions = $this->uccassSurvey->check_dependencies(4, $depend_keys, $depend);
		$this->assertIdentical($dependencyActions['Require'], 0, 'The single-valued dependency should not be ' .
				'satisfied and thus the question should not be Required but it is! (%s)');
		$this->assertIdentical($dependencyActions['Hide'], 0, "The question's option==Require so Hide cannot be 1! (%s)");
		$this->assertIdentical($dependencyActions['Show'], 0, "The question's option==Require so Show cannot be 1! (%s)");
		
		// 5. Dynamic answer type question
		$selectors = array('a selectors value');
		$_SESSION['take_survey']['answer'][$dep_qid] = $selectors;
		
		$dependencyActions = $this->uccassSurvey->check_dependencies(5, $depend_keys, $depend);
		$this->assertTrue(isset($dependencyActions['Selector']), 'The method should have returned ' .
				'an array of answers but it returned nothing (the index "Selector" is undefined).');
		$selectorArray =  $dependencyActions['Selector'];
		$this->assertTrue(is_array($selectorArray), "The method should have returned a non-empty array " .
				"but it returned >$selectorArray<.");
		$this->assertTrue(count($selectorArray) == count($selectors), "The method should have returned ".
				count($selectors). "elements but it returned ".count($selectorArray));
		$difference = array_diff($selectorArray, $selectors);
		$differenceStr = var_export($difference, true);
		$this->assertTrue(empty($difference), "The array of answers and the array of returned selectors " .
				"should contain the same elements; selectors - answers = $differenceStr.(%s)");
	}	
	
}
?>
