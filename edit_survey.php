<?php

include("survey.class.php");

$survey = new Survey;

echo $survey->com_header();

echo $survey->edit_survey(@$_REQUEST['sid']);

echo $survey->com_footer();

?>
