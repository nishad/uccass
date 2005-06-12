<?php
// Set to the number of characters you'd like to show for each question in
// the select box. Leave set to zero to show all questions.
$question_length = 150;

// Function to loop through array and convert elements to integers
function my_intval(&$item, $key)
{ $item = intval($item); }

// Include main class that connects to database and sets all of the
// configuration settings
include('classes/main.class.php');

// Create new survey object
$survey = new UCCASS_Main;

echo '<html><head><title>Example Page</title></head><body>';

// If survey id and question id are not sent in the URL, then
// show a drop down to select a survey
if(empty($_GET['sid']) && empty($_GET['qid']))
{
    // Get list of surveys from database and create drop down box
    $query = "SELECT sid, name FROM {$survey->CONF['db_tbl_prefix']}surveys ORDER BY name ASC";
    $rs = $survey->db->Execute($query) or die('Error: ' . $survey->db->ErrorMsg());
    echo '<form method="get" action="test.php">Choose survey: <select name="sid" size="1">';
    while($r = $rs->FetchRow($rs))
    { echo "<option value=\"{$r['sid']}\">" . $survey->SfStr->getSafeString($r['name']) . "</option>\n"; }
    echo '</select><input type="submit" value="Go"></form>';
}
// If survey id is sent in URL, but question id is not, then display a
// list of questions within the chosen survey in a select box
elseif(isset($_GET['sid']) && empty($_GET['qid']))
{
    // Ensure "sid" is an integer
    $sid = (int)$_GET['sid'];
    if($sid > 0)
    {
        // Get all questions for selected survey and create select box
        $query = "SELECT qid, question FROM {$survey->CONF['db_tbl_prefix']}questions WHERE sid = {$sid} ORDER BY page, oid";
        $rs = $survey->db->Execute($query) or die('Error: ' . $survey->db->ErrorMsg());
        echo '<form method="get" action="test.php">
              <input type="hidden" name="sid" value="'.$sid.'">
              Choose Questions (use Cntrl-click to select more than one):<br /><select name="qid[]" size="5" multiple>';
        while($r = $rs->FetchRow($rs))
        {
            if($question_length)
            { $r['question'] = substr($r['question'],0,$question_length); }
            echo "<option value=\"{$r['qid']}\">" . $survey->SfStr->getSafeString($r['question']) . "</option>\n";
        }
        echo '</select><input type="submit" value="Generate Crosstab"></form>';
    }
    else
    { echo 'Invalid survey chosen'; }
}
// If survey id and question id are sent, generate cross tab based on selected questions
elseif(isset($_GET['sid']) && !empty($_GET['qid']))
{
    // Ensure question ids were passed in an array
    if(is_array($_GET['qid']))
    {
        // Convert all question ids passed to integers within the array
        $sid = (int)$_GET['sid'];
        array_walk($_GET['qid'],'my_intval');
        $qid = $_GET['qid'];

        // Get list of questions and possible answer values for
        // selected questions. This gives us the answer values
        // we need to look for in the cross tab
        $query = "select q.qid, q.question, av.avid, av.value, r.sequence
        from {$survey->CONF['db_tbl_prefix']}questions q, {$survey->CONF['db_tbl_prefix']}answer_values av LEFT JOIN {$survey->CONF['db_tbl_prefix']}results r ON av.avid = r.avid AND q.qid = r.qid
        where q.aid = av.aid AND q.qid in (" . implode(',',$qid) . ") AND q.sid = {$sid}
        order by q.page, q.oid, av.avid";

        $rs = $survey->db->Execute($query) or die('Error: ' . $survey->db->ErrorMsg());

        $data = array();
        while($r = $rs->FetchRow($rs))
        {
            $data[$r['qid']]['question'] = $survey->SfStr->getSafeString($r['question']);
            $data[$r['qid']]['answer'][$r['avid']] = $survey->SfStr->getSafeString($r['value']);
            if(!empty($r['sequence']))
            { $data[$r['qid']]['sequence'][$r['avid']][] = $r['sequence']; }
        }

        // Create crosstab query from answer values for each question selected
        // Each answer value will have it's own column in the result
        if(!empty($data))
        {
            $crosstab = '';
            $title = array();
            $length = array();
            $answer = array();
            $row = array();
            foreach($data as $qid => $ar)
            {
                $title[] = $ar['question'];
                $length[] = count($ar['answer']);
                $answer[] = $ar['answer'];
                foreach($ar['answer'] as $avid => $answer_value)
                {
                    if(empty($ar['sequence'][$avid]))
                    { $crosstab .= "0"; }
                    else
                    { $crosstab .= "SUM(IF(r.sequence IN (" . implode(',',$ar['sequence'][$avid]) . "),1,0))"; }
                    $crosstab .= " AS " . $survey->SfStr->getSafeString("{$qid}-{$avid} " . $answer_value,SAFE_STRING_DB) . ",\n";
                    $row[] = $survey->SfStr->getSafeString("{$qid}-{$avid} {$answer_value}");
                }
            }
        }

        // Create HTML needed for displaying headers of tables
        // so it's only created once from values already retrieved
        $l = 0;
        $question_table_row = "<tr>\n<td>&nbsp;</td>\n";
        foreach($title as $t)
        { $question_table_row .= "<td colspan=\"".$length[$l++]."\">" . $survey->SfStr->getSafeString($t) . "</td>\n"; }
        $question_table_row .= "</tr>\n";

        $answer_table_row = '';
        foreach($answer as $ar)
        {
            foreach($ar as $a)
            { $answer_table_row .= "<td width=\"50\">" . $survey->SfStr->getSafeString($a) . "</td>\n"; }
        }
        $answer_table_row .= "</tr>\n";

        // Run second query that returns crosstab results for
        // every question in the selected survey
        $query = "SELECT {$crosstab}q.qid, q.question, av.value
        FROM {$survey->CONF['db_tbl_prefix']}questions q, {$survey->CONF['db_tbl_prefix']}answer_values av LEFT JOIN {$survey->CONF['db_tbl_prefix']}results r ON av.avid = r.avid AND q.qid = r.qid
        WHERE q.aid = av.aid AND q.sid = {$sid}
        GROUP BY q.qid, av.avid
        ORDER BY q.page, q.oid, av.avid";

        // Display HTML for each question and crosstab result, surrounded
        // by HTML created earlier
        $last_qid = '';
        $rs = $survey->db->Execute($query) or die('Error: ' . $survey->db->ErrorMsg());
        while($r = $rs->FetchRow($rs))
        {
            if($r['qid'] != $last_qid)
            {
                if(!empty($last_qid))
                { echo "</table>\n"; }
                echo "<table border=\"1\" cellpadding=\"3\">\n{$question_table_row}<tr>\n<td width=\"300\">" . $survey->SfStr->getSafeString($r['question']) . "</td>\n{$answer_table_row}";
            }
            $last_qid = $r['qid'];
            echo "<tr>\n<td align=\"right\">{$r['value']}</td>\n";
            foreach($row as $rowname)
            { echo "<td align=\"center\">{$r[$rowname]}</td>\n"; }
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
}

echo '</body></html>';

?>