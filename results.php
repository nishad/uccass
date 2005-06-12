<?php

include('classes/main.class.php');
include('classes/results.class.php');

$survey = new UCCASS_Results;

$output = $survey->com_header($survey->lang('title_survey_results'));

$output .= $survey->survey_results(@$_REQUEST['sid']);

$output .= $survey->com_footer();

echo $output;

?>