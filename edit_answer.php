<?php

include("survey.class.php");

$survey = new Survey;

echo $survey->com_header();

echo $survey->edit_answer();

echo $survey->com_footer();

?>