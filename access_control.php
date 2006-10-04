<?php

include('classes/main.class.php');
include('classes/accesscontrol.class.php');

$Survey = new UCCASS_AccessControl;

echo $Survey->show(@$_REQUEST['sid']);

?>
