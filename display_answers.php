<?php

include('survey.class.php');

$survey = new Survey;

echo $survey->display_answers();

?>