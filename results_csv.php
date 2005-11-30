<?php

include('classes/main.class.php');
include('classes/special_results.class.php');

$survey = new UCCASS_Special_Results;

echo $survey->sendResultsCSV(@$_REQUEST['sid'],$_REQUEST['export_type']);

?>