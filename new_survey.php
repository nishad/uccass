<?php

include('classes/main.class.php');
include('classes/newsurvey.class.php');

$survey = new UCCASS_NewSurvey;

$output = '';
$output .= $survey->com_header();
$output .= $survey->createNewSurvey();
echo $output . $survey->com_footer();

?>
