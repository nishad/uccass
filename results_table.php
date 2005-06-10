<?php

include('special_results.class.php');

$survey = new Special_Results;

echo $survey->com_header("Survey Results");

echo $survey->results_table(@$_REQUEST['sid']);

echo $survey->com_footer();

?>