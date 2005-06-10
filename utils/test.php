<?php
header('Content-Type: text/plain');

echo "Rand max: " . mt_getrandmax() . "\n";

$start1 = microtime(true);
$words1 = file('words.txt');
$keys1 = array_rand($words1,2);
$result1 = trim($words1[$keys1[0]]) . '-' . trim($words1[$keys1[1]]);
$end1 = microtime(true);
$time1 = $end1 - $start1;

unset($words1);

$start2 = microtime(true);
$words2 = file('words.txt');
$numwords2 = count($words2);
$result2 = trim($words2[mt_rand(0,$numwords2)]) . '-' . trim($words2[mt_rand(0,$numwords2)]);
$end2 = microtime(true);
$time2 = $end2 - $start2;

$start3 = microtime(true);
$result3 = '';
$fp = fopen('words.txt','r');
$filesize = filesize('words.txt');
for($x=0;$x<2;$x++)
{
    $pos = mt_rand(0,$filesize);
    fseek($fp,$pos);
    while(fgetc($fp)!="\n")
    {
        fseek($fp,--$pos);
    }
    $result3 .= trim(fgets($fp)) . '-';
}
$result3 = substr($result3,0,-1);
$end3 = microtime(true);
$time3 = $end3 - $start3;

echo "Time1 (array_rand): $time1, $result1\nTime2 (rand): $time2, $result2\nTime3 (seek): $time3,$result3";

?>