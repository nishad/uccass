<?php

include("survey.class.php");

$survey = new Survey;

echo $survey->com_header();

echo $survey->edit_survey();

echo $survey->com_footer();

?>
