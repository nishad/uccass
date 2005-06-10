<?php

include("survey.class.php");

$survey = new Survey;

$body = $survey->take_survey($_REQUEST['sid']);

$header = $survey->com_header("Survey #{$_REQUEST['sid']}: {$survey->survey_name}");

echo $header;
echo $body;
echo $survey->com_footer();

?>
