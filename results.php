<?php

include('survey.class.php');

$survey = new Survey;

$output = $survey->com_header("Survey Results");

$output .= $survey->survey_results(@$_REQUEST['sid']);

$output .= $survey->com_footer();

echo $output;

?>