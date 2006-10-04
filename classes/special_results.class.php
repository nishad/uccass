<?php

//======================================================
// Copyright (C) 2004 John W. Holmes, All Rights Reserved
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

require('classes/pager.class.php');

define('CSV_SEP', ',');
define('CSV_STRING_SEP', '"');
define('DEFAULT_NUM_RESULTS', 25);
define('BEFORE_AFTER_CURRENT_PAGE',3);
define('ASC', 'asc');
define('DESC', 'desc');
define('USORT_NUMERIC', 1);
define('USORT_STRING', 2);

class UCCASS_Special_Results extends UCCASS_Main
{
    function UCCASS_Special_Results()
    {
        $this->load_configuration();

        $this->sortcol = 0;
        $this->sortdir = ASC;
        $this->sorttype = USORT_STRING;

        //Increase time limit of script to 2 minutes to ensure
        //very large results can be shown or exported
        @set_time_limit(120);	// suppress warning because if disabled set_time_limit in the safe mode
    }

    function showResultsTable($sid)
    {
        $sid = (int)$sid;

        if(!$this->_CheckAccess($sid,RESULTS_PRIV,"results_table.php?sid=$sid"))
        {
            switch($this->_getAccessControl($sid))
            {
                case AC_INVITATION:
                    return $this->showInvite('results_table.php',array('sid'=>$sid));
                break;
                case AC_USERNAMEPASSWORD:
                default:
                    return $this->showLogin('results_table.php',array('sid'=>$sid));
                break;
            }
        }

        if(isset($_POST['action'])) {
            switch($_POST['action']) {
                case 'delete':
                    if(!empty($_POST['delete'])) {
                        $this->_processDeleteRecords($_POST['sid'], $_POST['page'], $_POST['delete']);
                    }
                break;
            }
        }

        $data = array();
        $qid = array();
        $survey = array();
        $pgr = array();

        $survey['sid'] = $sid;

        $query = "SELECT q.qid, q.question, s.name, s.user_text_mode, s.survey_text_mode, s.date_format
                  FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}surveys s
                  WHERE q.sid = $sid and s.sid = q.sid ORDER BY q.page, q.oid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        $questions = array();
        if($r = $rs->FetchRow($rs))
        {
            $survey_text_mode = $r['survey_text_mode'];
            $user_text_mode = $r['user_text_mode'];
            $date_format = $r['date_format'];
            $survey['name'] = $this->SfStr->getSafeString($r['name'],$survey_text_mode);

            do{
                $data['questions'][] = $this->SfStr->getSafeString($r['question'],$survey_text_mode);
                $qid[$r['qid']] = $r['qid'];
            }while($r = $rs->FetchRow($rs));
        }
        else
        { $this->error($this->lang['no_questions']); return; }

        $data['num_columns'] = count($data['questions']) + 3;

        if(isset($_SESSION['filter_text'][$sid]) && isset($_SESSION['filter'][$sid]) && strlen($_SESSION['filter_text'][$sid])>0)
        { $this->smarty->assign_by_ref('filter_text',$_SESSION['filter_text'][$sid]); }
        else
        { $_SESSION['filter'][$sid] = ''; }

        $pgr['per_page'] = 0;
        if(isset($_GET['num_table_results'])) {
            $pgr['per_page'] = (int)$_GET['num_table_results'];
        } elseif(isset($_COOKIE['uccass_num_table_results'])) {
            $pgr['per_page'] = (int) $_COOKIE['uccass_num_table_results'];
        }
        if($pgr['per_page'] == 0) {
            $pgr['per_page'] = DEFAULT_NUM_RESULTS;
        }

        if(!isset($_COOKIE['uccass_num_table_results']) || $_COOKIE['uccass_num_table_results'] != $pgr['per_page']) {
            setcookie('uccass_num_table_results', $pgr['per_page'], time() + 86400);
        }

        $data['num_table_results']['selected'][$pgr['per_page']] = FORM_SELECTED;

        $pgr['page'] = 1;
        if(isset($_GET['page'])) {
            $pgr['page'] = (int)$_GET['page'];
            if($pgr['page'] <= 0) {
                $pgr['page'] = 1;
            }
        }

        $pgr['start'] = ($pgr['page'] - 1) * $pgr['per_page'];
        $pgr['end'] = $pgr['start'] + $pgr['per_page'];

        //Need to determine how many unique sequence numbers match the query criteria
        //as that's the number of total responses to the survey. This number will then be used to limit the final query that pulls all of the
        //actual answers from the results tables.
        $pgr['total_results'] = 0;
        $pgr['seq'] = array();
        $count = 0;

        $query = "SELECT (CASE WHEN rt.sequence > r.sequence THEN rt.sequence ELSE r.sequence END) AS seq FROM {$this->CONF['db_tbl_prefix']}questions q LEFT JOIN {$this->CONF['db_tbl_prefix']}results
          r ON q.qid = r.qid LEFT JOIN {$this->CONF['db_tbl_prefix']}results_text rt ON q.qid = rt.qid LEFT JOIN
          {$this->CONF['db_tbl_prefix']}answer_values av ON r.avid = av.avid WHERE q.sid = $sid {$_SESSION['filter'][$sid]} GROUP BY seq ORDER BY seq";
        $rs = $this->db->Execute($query);
        if($rs === FALSE) {
            $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return;
        }
        while($r = $rs->FetchRow($rs)) {
            if(!empty($r['seq'])) {
                $pgr['total_results']++;
                if($count >= $pgr['start'] && $count < $pgr['end']) {
                    $pgr['seq'][] = $r['seq'];
                }
                $count++;
            }
        }

        $pgr['r_seq_where'] = '';
        $pgr['rt_seq_where'] = '';
        if(count($pgr['seq']) < $pgr['total_results'] && !empty($pgr['seq'])) {
            //$pgr['seq_where'] = ' AND (r.sequence IN (' . implode(',', $pgr['seq']) . ') OR rt.sequence IN (' . implode(',', $pgr['seq']) . ')) ';
            $pgr['r_seq_where'] = ' AND r.sequence IN (' . implode(',', $pgr['seq']) . ')';
            $pgr['rt_seq_where'] = ' AND rt.sequence IN (' . implode(',', $pgr['seq']) . ')';
        }

        $pgr['num_pages'] = (int)($pgr['total_results'] / $pgr['per_page']);
        if($pgr['total_results'] % $pgr['per_page']) {
            $pgr['num_pages']++;
        }

        $Pager = New UCCASS_Pager($pgr['page'], $pgr['num_pages'], BEFORE_AFTER_CURRENT_PAGE, 'results_table.php');
        $Pager->showRecordCount(TRUE, $pgr['per_page'], $pgr['total_results']);
        $Pager->setData('sid', $sid);
        $data['prev_next_text'] = $Pager->getPager();
        $data['page'] = $pgr['page'];

        $query_arr[] = "SELECT r.qid, r.sequence as seq, r.entered, q.question, av.value AS answer FROM {$this->CONF['db_tbl_prefix']}questions q LEFT JOIN
                      {$this->CONF['db_tbl_prefix']}results r ON q.qid = r.qid LEFT JOIN {$this->CONF['db_tbl_prefix']}answer_values av ON
                      r.avid = av.avid WHERE q.sid = $sid {$pgr['r_seq_where']} {$_SESSION['filter'][$sid]} ORDER BY seq, q.page, q.oid";
        $query_arr[] = "SELECT rt.qid, rt.sequence as seq, rt.entered, q.question, rt.answer FROM {$this->CONF['db_tbl_prefix']}questions q LEFT JOIN
                        {$this->CONF['db_tbl_prefix']}results_text rt ON q.qid = rt.qid WHERE q.sid = {$sid} {$pgr['rt_seq_where']}
                        ORDER BY seq";
        foreach($query_arr as $query) {
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

            $seq = '';
            $x = -1;
            while($r = $rs->FetchRow($rs))
            {
                if(!empty($r['qid']))
                {
                    if($seq != $r['seq'])
                    {
                        $x++;
                        $seq = $r['seq'];
                        $answers[$x]['date'] = date($date_format,$r['entered']);
                    }
                    if(isset($answers[$x][$r['qid']]))
                    { $answers[$x][$r['qid']] .= MULTI_ANSWER_SEPERATOR . $this->SfStr->getSafeString($r['answer'],$user_text_mode); }
                    else
                    { $answers[$x][$r['qid']] = $this->SfStr->getSafeString($r['answer'],$user_text_mode); }

                    $answers[$x]['seq'] = $r['seq'];
                    $last_date = date($date_format,$r['entered']);
                }
            }
            $answers[$x]['date'] = $last_date;
        }


        $xvals = array_keys($answers);

        foreach($xvals as $x)
        {
            $data['answers'][$x][] = $answers[$x]['seq'];
            foreach($qid as $qid_value)
            {
                if(isset($answers[$x][$qid_value]))
                { $data['answers'][$x][] = $answers[$x][$qid_value]; }
                else
                { $data['answers'][$x][] = NBSP; }
            }
            $data['answers'][$x][] = $answers[$x]['date'];
        }
        $data['datecol'] = count($data['answers'][0]) - 2;

        if(isset($_GET['sortcol']) && isset($_GET['sortdir'])) {
            $this->_sortData($_GET['sortcol'], $_GET['sortdir'], $data['datecol'], &$data);
        }

        $this->smarty->assign_by_ref('data',$data);
        $this->smarty->assign_by_ref('survey',$survey);
        return $this->smarty->Fetch($this->CONF['template'].'/results_table.tpl');
    }

    function _processDeleteRecords($sid, $page, $seq_nums=array()) {
        $sid = (int)$sid;
        $error = array();
        $num_deleted = 0;

        if(!empty($seq_nums)) {
            $seq_array = array();
            foreach($seq_nums as $seq) {
                $seq = (int)$seq;
                if($seq) {
                    $seq_array[] = $seq;
                }
            }

            if(!empty($seq_array)) {
                $seq_list = implode(',', $seq_array);

                $tables = array('results','results_text','time_limit');
                foreach($tables as $table) {
                    $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}{$table} WHERE sequence IN ({$seq_list}) AND sid={$sid}";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE) {
                        $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg();
                    }
                }
            }
        }

        $this->setMessageRedirect("results_table.php?sid=$sid&page=$page");

        if(empty($error))
        { $this->setMessage($this->lang['notice'], $this->lang['records_deleted'], MSGTYPE_NOTICE); }
        else
        { $this->setMessage($this->lang['error'], $this->lang['records_not_deleted'] . BR . implode(BR,$error),MSGTYPE_ERROR); }
    }

    function _sortData($sortcol, $sortdir, $maxcols, &$data) {
        $sortcol = (int)$sortcol;
        $sortcol++;

        if($sortcol < 0) {
            $sortcol = 0;
        } elseif($sortcol > $maxcols + 1) {
            $sortcol = $maxcol + 1;
        }

        if($sortdir != ASC && $sortdir != DESC) {
            $sortdir = ASC;
        }

        $this->_getSortType($sortcol, &$data);
        define('SORT_COL', $sortcol);
        define('SORT_DIR', $sortdir);

        usort($data['answers'], '_compareData');

        return;
    }

    function _getSortType($sortcol, &$data) {
        foreach($data['answers'] as $value) {
            if($value[$sortcol] != NBSP) {
                if(!is_numeric($value[$sortcol])) {
                    define('SORT_TYPE', USORT_STRING);
                    return;
                }
            }
        }
        define('SORT_TYPE', USORT_NUMERIC);
        return;
    }

    function sendResultsCSV($sid, $export_type=EXPORT_CSV_TEXT)
    {
        $sid = (int)$sid;


        $retval = '';

        if(!$this->_CheckAccess($sid,RESULTS_PRIV,"results_csv.php?sid=$sid"))
        {
            switch($this->_getAccessControl($sid))
            {
                case AC_INVITATION:
                    return $this->showInvite('results_csv.php',array('sid'=>$sid));
                break;
                case AC_USERNAMEPASSWORD:
                default:
                    return $this->showLogin('results_csv.php',array('sid'=>$sid));
                break;
            }
        }

        header("Content-Type: text/plain; charset={$this->CONF['charset']}");
        header("Content-Disposition: attachment; filename={$this->lang['csv_filename']}");

        $query = "SELECT q.qid, q.question, s.date_format
                  FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}surveys s
                  WHERE q.sid = $sid and s.sid = q.sid ORDER BY q.page, q.oid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        $questions = array();
        if($r = $rs->FetchRow($rs))
        {
            $date_format = $r['date_format'];
            do{
                $questions[$r['qid']] = $r['question'];
            }while($r = $rs->FetchRow($rs));
        }
        else
        { $this->error($this->lang['no_questions']); return; }

        if(isset($_SESSION['filter_text'][$sid]) && isset($_SESSION['filter'][$sid]) && strlen($_SESSION['filter_text'][$sid])>0)
        { $this->smarty->assign_by_ref('filter_text',$_SESSION['filter_text'][$sid]); }
        else
        { $_SESSION['filter'][$sid] = ''; }

		// 'greatest' is a nonstandard function of mysql
        $query = "SELECT (CASE WHEN rt.qid > r.qid THEN rt.qid ELSE r.qid END) AS qid, " .
        		"(CASE WHEN rt.sequence > r.sequence THEN rt.sequence ELSE r.sequence END) AS seq, " .
        		"(CASE WHEN rt.entered > r.entered THEN rt.entered ELSE r.entered END) AS entered, " .
				"q.question, av.value, av.numeric_value, rt.answer FROM {$this->CONF['db_tbl_prefix']}questions q LEFT JOIN {$this->CONF['db_tbl_prefix']}results
                  r ON q.qid = r.qid LEFT JOIN {$this->CONF['db_tbl_prefix']}results_text rt ON q.qid = rt.qid LEFT JOIN
                  {$this->CONF['db_tbl_prefix']}answer_values av ON r.avid = av.avid WHERE q.sid = $sid {$_SESSION['filter'][$sid]}
                  ORDER BY seq, q.page, q.oid";

        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        $seq = '';
        $x = 0;
        while($r = $rs->FetchRow($rs))
        {
            if(!empty($r['qid']))
            {
                if($seq != $r['seq'])
                {
                    $x++;
                    $seq = $r['seq'];
                    $answers[$x]['date'] = date($date_format,$r['entered']);
                }

                switch($export_type)
                {
                    case EXPORT_CSV_NUMERIC:
                        if(empty($r['answer']))
                        { $value = $r['numeric_value']; }
                        else
                        { $value = $r['answer']; }
                    break;

                    case EXPORT_CSV_TEXT:
                    default:
                        if(empty($r['answer']))
                        { $value = $r['value']; }
                        else
                        { $value = $r['answer']; }
                    break;
                }

                if(isset($answers[$x][$r['qid']]))
                { $answers[$x][$r['qid']] .= MULTI_ANSWER_SEPERATOR . $value; }
                else
                { $answers[$x][$r['qid']] = $value; }
            }
            $last_date = date($date_format,$r['entered']);
        }
        $answers[$x]['date'] = $last_date;

        $line = '';
        $replace = array(CR,NL,'"');
        $replace_with = array('',' ','""');

        foreach($questions as $question)
        { $line .= CSV_STRING_SEP . str_replace($replace,$replace_with,$question) . CSV_STRING_SEP . CSV_SEP; }
        $retval .= $line . $this->lang['datetime'] . NL;

        $xvals = array_keys($answers);

        foreach($xvals as $x)
        {
            $line = '';
            foreach($questions as $qid=>$question)
            {
                if(isset($answers[$x][$qid]))
                {
                    if(is_numeric($answers[$x][$qid]))
                    { $line .= $answers[$x][$qid] . CSV_SEP; }
                    else
                    { $line .= CSV_STRING_SEP . str_replace($replace,$replace_with,$answers[$x][$qid]) . CSV_STRING_SEP . CSV_SEP; }
                }
                else
                { $line .= CSV_SEP; }
            }
            $retval .= $line . CSV_STRING_SEP . $answers[$x]['date'] . CSV_STRING_SEP . NL;
        }

        return $retval;
    }
}

function _compareData($row1, $row2) {
    $retval = 0;

    switch(SORT_DIR) {
        CASE ASC:
            $one = 1;
            $neg_one = -1;
        break;

        case DESC:
            $one = -1;
            $neg_one = 1;
        break;
    }

    switch(SORT_TYPE) {
        case USORT_NUMERIC:
            if($row1[SORT_COL] == $row2[SORT_COL]) {
                $retval = 0;
            } elseif($row1[SORT_COL] < $row2[SORT_COL]) {
                $retval = $neg_one;
            } else {
                $retval = $one;
            }
        break;

        case USORT_STRING:
            $value = strcmp($row1[SORT_COL], $row2[SORT_COL]);
            if($value < 0) {
                $retval = $neg_one;
            } elseif($value > 0) {
                $retval = $one;
            }
        break;
    }

    return $retval;
}
?>