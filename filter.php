<?php

include("survey.class.php");

$survey = new Survey;

echo $survey->com_header("Filter Survey Results");

echo $survey->filter($_REQUEST['sid']);

echo $survey->com_footer();

?>
