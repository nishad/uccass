<?php

include('classes/main.class.php');
include('classes/accesscontrol.class.php');

$Survey = new UCCASS_AccessControl;

$body = $Survey->show(@$_REQUEST['sid']);

echo $Survey->com_header();
echo $body;
echo $Survey->com_footer();

?>
