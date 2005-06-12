<?php

include('classes/main.class.php');
include('classes/reports.class.php');

$Survey = new UCCASS_Reports;

$body = $Survey->show(@$_REQUEST['sid']);

echo $Survey->com_header();
echo $body;
echo $Survey->com_footer();

?>
