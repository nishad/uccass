<?php

include("survey.class.php");

$survey = new Survey;

$output = $survey->com_header();

$output .= $survey->admin();

$output .= $survey->com_footer();

echo $output;

?>