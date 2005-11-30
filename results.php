<?php

include('classes/main.class.php');
include('classes/results.class.php');

$survey = new UCCASS_Results;

$output = $survey->com_header($survey->lang('title_survey_results'));

$output .= $survey->showSurveyResults(@$_REQUEST['sid']);

echo $output . $survey->com_footer();

?>