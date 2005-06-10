<?php

include("survey.class.php");

$survey = new Survey;

$output = $survey->com_header();

$output .= $survey->new_survey();

$output .= $survey->com_footer();

echo $output;

?>
