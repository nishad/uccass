<?php

include("survey.class.php");

$survey = new Survey;

echo $survey->com_header();

echo $survey->available_surveys();

echo $survey->com_footer();

?>    