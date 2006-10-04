<?php

define('USORT_NUMERIC', 1);
define('USORT_STRING', 2);

$arr = array(array('John','25','www.domain.com'),
                  array('Bob','30','www.something.com'),
                  array('Rich','12','www.nintendo.com'),
                  array('Mark','34','www.mark.com'),
                  array('Mike','29','www.farmer.com'));

function compare($row1, $row2) {
    $sortcol = 1;
    $sorting = USORT_NUMERIC;

    switch($sorting) {
        case USORT_NUMERIC:
            if($row1[$sortcol] == $row2[$sortcol]) {
                return 0;
            } elseif($row1[$sortcol] < $row2[$sortcol]) {
                return -1;
            } else {
                return 1;
            }
        break;

        case USORT_STRING:
            return strcmp($row1[$sortcol], $row2[$sortcol]);
        break;
    }
}

echo 'Before:<br />';
echo '<pre>' . print_r($arr,1) . '</pre>';
usort($arr, 'compare');
echo 'After:<br />';
echo '<pre>' . print_r($arr,1) . '</pre>';

?>