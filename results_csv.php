<?php

include('special_results.class.php');

$survey = new Special_Results;

header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=Export.csv");

echo $survey->results_csv(@$_REQUEST['sid']);

?>