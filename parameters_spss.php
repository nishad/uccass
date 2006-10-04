<?php

//======================================================
// Copyright (C) 2006 Claudio Redaelli, All Rights Reserved
//
// This file is part of the Unit Command Climate
// Assessment and Survey System (UCCASS)
//
// UCCASS is free software; you can redistribute it and/or
// modify it under the terms of the Affero General Public License as
// published by Affero, Inc.; either version 1 of the License, or
// (at your option) any later version.
//
// http://www.affero.org/oagpl.html
//
// UCCASS is distributed in the hope that it will be
// useful, but WITHOUT ANY WARRANTY; without even the implied warranty
// of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// Affero General Public License for more details.
//======================================================

include('classes/main.class.php');
include('classes/special_results.class.php');
include('classes/spss_results.class.php'); /* Inherits from special_results.class.php */

$survey = new UCCASS_SPSS_Results(@$_REQUEST['sid']);
$output = $survey->com_header($survey->lang['spss_title']);
$output .= $survey->requestParameters();
$output .= $survey->com_footer();
echo $output;

?>
