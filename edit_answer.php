<?php

include("survey.class.php");

$survey = new Survey;

echo $survey->com_header();

echo $survey->edit_answer(@$_REQUEST['sid'],@$_REQUEST['aid']);

echo $survey->com_footer();

?>