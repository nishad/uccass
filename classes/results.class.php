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

define('ACTION_HIDE_QUESTIONS','hide_questions');
define('ACTION_SHOW_QUESTIONS','show_questions');
define('ACTION_SHOW_ALL_QUESTIONS','show_all_questions');
define('ACTION_FILTER','filter');
define('ACTION_CLEAR_FILTER','clear_filter');

class UCCASS_Results extends UCCASS_Main
{
    function UCCASS_Results()
    { $this->load_configuration(); }

    /*************************
    * VIEW RESULTS OF SURVEY *
    *************************/
    function survey_results($sid=0)
    {
        $data = array();
        $survey['sid'] = (int)$sid;
        $survey['export_csv_text'] = EXPORT_CSV_TEXT;
        $survey['export_csv_numeric'] = EXPORT_CSV_NUMERIC;

        if(!$this->_CheckAccess($sid,RESULTS_PRIV,"results.php?sid={$survey['sid']}"))
        {
            switch($this->_getAccessControl($survey['sid']))
            {
                case AC_INVITATION:
                    return $this->showInvite('results.php',array('sid'=>$survey['sid']));
                break;
                case AC_USERNAMEPASSWORD:
                default:
                    return $this->showLogin('results.php',array('sid'=>$survey['sid']));
                break;
            }
        }

        if(empty($survey['sid']))
        { $this->error($this->lang['invalid_survey']); return; }

        //Retrieve survey information
        $rs = $this->db->Execute("SELECT name, survey_text_mode, template
                                  FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid = {$survey['sid']}");
        if($rs === FALSE) { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        if($r = $rs->FetchRow($rs))
        {
            $survey['name'] = $this->SfStr->getSafeString($r['name'],$r['survey_text_mode']);
            $survey['survey_text_mode'] = $r['survey_text_mode'];
        }
        else
        { $this->error($this->lang['invalid_survey']); return; }


        //if viewing answers to single
        //question with text box
        if(isset($_REQUEST['qid']))
        { return $this->survey_results_text($survey['sid'],$_REQUEST['qid']); }
        elseif(isset($_SESSION['results']['page']))
        { unset($_SESSION['results']['page']); }

        //Set defaults for show/hide questions
        $survey['hide_show_where'] = '';
        $survey['hide_show_questions'] = TRUE;
        $survey['show_all_questions'] = FALSE;

        //Retrieve hide/show question status
        //from session if it's present
        if(isset($_SESSION['hide-show'][$sid]))
        {
            $survey['hide_show_where'] = $_SESSION['hide-show'][$sid];
            $survey['show_all_questions'] = TRUE;
            $survey['hide_show_questions'] = FALSE;
        }

        $survey['required'] = $this->smarty->fetch($this->CONF['template'].'/question_required.tpl');

        if(isset($_REQUEST['results_action']))
        {
            $retval = $this->process_results_action($survey['sid']);
            if($_REQUEST['action'] == 'filter')
            { return $retval; }
        }

        if(isset($_REQUEST['filter_submit']))
        { $this->process_filter($survey['sid'],$survey['survey_text_mode']); }
        elseif(!isset($_SESSION['filter'][$survey['sid']]))
        {
            $_SESSION['filter'][$survey['sid']] = '';
            $_SESSION['filter_total'][$survey['sid']] = '';
        }

        //Filter text has already had safe_string() applied
        if(isset($_SESSION['filter_text'][$survey['sid']]) && strlen($_SESSION['filter_text'][$survey['sid']])>0)
        { $survey['filter_text'] = $_SESSION['filter_text'][$survey['sid']]; }
        if(strlen($_SESSION['filter'][$survey['sid']])>0)
        { $survey['show']['clear_filter'] = TRUE; }

        $x = 0;

        $survey['quittime']['minutes'] = 0;
        $survey['quittime']['seconds'] = 0;
        $survey['avgtime']['minutes']  = 0;
        $survey['avgtime']['seconds']  = 0;
        $survey['mintime']['minutes']  = 0;
        $survey['mintime']['seconds']  = 0;
        $survey['maxtime']['minutes']  = 0;
        $survey['maxtime']['seconds']  = 0;

        $sql = "SELECT r.quitflag, AVG(r.elapsed_time) AS avgtime, MIN(r.elapsed_time) AS mintime, MAX(r.elapsed_time) AS maxtime
                FROM {$this->CONF['db_tbl_prefix']}time_limit r WHERE r.sid = {$survey['sid']} {$_SESSION['filter'][$survey['sid']]}
                GROUP BY r.quitflag";
        $rs = $this->db->Execute($sql);
        if($rs === FALSE) {$this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        while($r = $rs->FetchRow($rs))
        {
            if($r['quitflag'])
            {
                $survey['quittime']['minutes'] = floor($r['avgtime'] / 60);
                $survey['quittime']['seconds'] = $r['avgtime'] % 60;
            }
            else
            {
                $survey['avgtime']['minutes'] = floor($r['avgtime'] / 60);
                $survey['avgtime']['seconds'] = $r['avgtime'] % 60;
                $survey['mintime']['minutes'] = floor($r['mintime'] / 60);
                $survey['mintime']['seconds'] = $r['mintime'] % 60;
                $survey['maxtime']['minutes'] = floor($r['maxtime'] / 60);
                $survey['maxtime']['seconds'] = $r['maxtime'] % 60;
            }
        }

        $this->smarty->assign_by_ref('survey',$survey);

        if(isset($_GET['report_id']))
        { $this->_loadCustomReport($_GET['report_id'],$survey); }
        else
        { $this->_loadDefaultResults($survey); }

        $retval = $this->smarty->fetch($this->CONF['template'].'/results.tpl');

        if(empty($_SESSION['filter'][$sid]) && isset($_SESSION['filter_text'][$survey['sid']]))
        { unset($_SESSION['filter_text'][$survey['sid']]); }

        return $retval;
    }

    function _loadDefaultResults(&$survey)
    {
        $data = array();
        $this->_loadBarGraph($survey,$data);
        $this->_loadReports($survey);

        $x=0;

        $output['filter'] = $this->smarty->fetch($this->CONF['template'].'/results_filter.tpl');
        foreach($data as $qid=>$qdata)
        {
            $this->smarty->assign_by_ref('qdata',$qdata);
            $output['question'][$x] = $this->smarty->fetch($this->CONF['template'].'/results_question.tpl');
            switch($qdata['type'])
            {
                case ANSWER_TYPE_MM:
                case ANSWER_TYPE_MS:
                    $output['bar_graph'][$x] = $this->smarty->fetch($this->CONF['template'].'/results_bar_graph.tpl');
                    $output['total_ans'][$x] = $this->smarty->fetch($this->CONF['template'].'/results_total_ans.tpl');
                    $output['average'][$x] = $this->smarty->fetch($this->CONF['template'].'/results_average.tpl');
                break;

                case ANSWER_TYPE_T:
                case ANSWER_TYPE_S:
                    $output['total_ans'][$x] = $this->smarty->Fetch($this->CONF['template'].'/results_total_ans.tpl');
                    $output['text'][$x] = $this->smarty->Fetch($this->CONF['template'].'/results_text.tpl');
                break;
            }
            $x++;
        }

        $this->smarty->assign_by_ref('output',$output);
    }

    function _loadBarGraph(&$survey,&$data=array(),$qid_array=array())
    {
        $q_num = 1;
        $qid_list = '';

        if(!empty($qid_array))
        { $qid_list = ' AND q.qid IN (' . implode(',',$qid_array) . ') '; }

        //retrieve questions
        $sql = "SELECT q.qid, q.question, q.num_required, q.aid, a.type, a.label, COUNT(r.qid) AS r_total, COUNT(rt.qid) AS rt_total
                FROM {$this->CONF['db_tbl_prefix']}questions q LEFT JOIN {$this->CONF['db_tbl_prefix']}results r
                  ON q.qid = r.qid LEFT JOIN {$this->CONF['db_tbl_prefix']}results_text rt ON q.qid = rt.qid,
                  {$this->CONF['db_tbl_prefix']}answer_types a
                WHERE q.sid = {$survey['sid']} and q.aid = a.aid
                  and ((q.qid = r.qid AND NOT ".$this->db->IfNull('rt.qid',0).") OR (q.qid = rt.qid AND NOT ".$this->db->IfNull('r.qid',0).")
                  OR (NOT ".$this->db->IfNull('r.qid',0)." AND NOT ".$this->db->IfNull('rt.qid',0)."))
                  {$qid_list} {$survey['hide_show_where']} {$_SESSION['filter_total'][$survey['sid']]}
                GROUP BY q.qid
                ORDER BY q.page, q.oid";
//echo $sql . '<br /><br />';
        $rs = $this->db->Execute($sql);
        if($rs === FALSE) { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return;}

        while($r = $rs->FetchRow($rs))
        {
            $x = $r['qid'];
            $data[$x]['qid'] = $r['qid'];
            $data[$x]['question'] = nl2br($this->SfStr->getSafeString($r['question'],$survey['survey_text_mode']));
            $data[$x]['num_answers'] = max($r['r_total'],$r['rt_total']);

            if($r['num_required']>0)
            { $data[$x]['num_required'] = $r['num_required']; }

            if($r['type'] != "N")
            { $data[$x]['question_num'] = $q_num++; }
            $data[$x]['type'] = $r['type'];
            switch($r['type'])
            {
                case "MM":
                case "MS":
                    $data[$x]['answer'] = $this->get_answer_values($r['aid'],BY_AID,$survey['survey_text_mode']);
                    $data[$x]['count'] = array_fill(0,count($data[$x]['answer']['avid']),0);
                    $data[$x]['show']['numanswers'] = TRUE;
                break;

                case "T":
                case "S":
                    $data[$x]['text'] = $r['qid'];
                    $data[$x]['show']['numanswers'] = TRUE;
                break;

                case 'N':
                    $data[$x]['show']['numanswers'] = FALSE;
                break;
            }
        }

        //retrieve answers to questions
        $sql = "SELECT r.qid, r.avid, count(*) AS c FROM {$this->CONF['db_tbl_prefix']}results r,
                {$this->CONF['db_tbl_prefix']}answer_values av,
                {$this->CONF['db_tbl_prefix']}questions q
                WHERE r.qid = q.qid and r.sid = {$survey['sid']} and r.avid = av.avid {$survey['hide_show_where']}
                {$_SESSION['filter'][$survey['sid']]} {$qid_list}
                GROUP BY r.qid, r.avid
                ORDER BY r.avid ASC";
//echo $sql;
        $rs = $this->db->Execute($sql);
        if($rs === FALSE) { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return;}
        while($r = $rs->FetchRow($rs))
        {
            $k = array_search($r['avid'],$data[$r['qid']]['answer']['avid']);
            if($k !== FALSE)
            { $data[$r['qid']]['count'][$k] = $r['c']; }
        }

        foreach($data as $qid=>$q_data)
        {
            if(!empty($q_data['count']))
            {
                $data[$qid]['total'] = array_sum($data[$qid]['count']);
                $data[$qid]['average'] = 0;
                foreach($data[$qid]['count'] as $k=>$v)
                {
                    if($data[$qid]['total'] > 0)
                    { $p = 100 * $v / $data[$qid]['total']; }
                    else
                    { $p = 0; }
                    $data[$qid]['percent'][$k] = sprintf('%2.2f',$p);
                    $data[$qid]['average'] += $v * $data[$qid]['answer']['numeric_value'][$k];
                    $this->_loadImageData($data,$qid,$k,$p);
                }
                if($data[$qid]['total'] > 0 && $data[$qid]['average'] > 0)
                { $data[$qid]['average'] = sprintf('%2.2f',$data[$qid]['average'] / $data[$qid]['total']); }
            }
        }
        return $data;
    }

    function _loadImageData(&$data,$qid,$k,$p)
    {
        $data[$qid]['width'][$k] = round($this->CONF['image_width'] * $p/100);

        $img_size = getimagesize($this->CONF['images_path'] . '/' . $data[$qid]['answer']['image'][$k]);
        $data[$qid]['height'][$k] = $img_size[1];

        //Check for _left image (beginning of bar)
        $img = $data[$qid]['answer']['image'][$k];
        $last_period = strrpos($img,'.');

        $left_img = substr($img,0,$last_period) . '_left' . substr($img,$last_period);
        $right_img = substr($img,0,$last_period) . '_right' . substr($img,$last_period);

        if(file_exists($this->CONF['images_path'] . '/' . $left_img))
        { $data[$qid]['answer']['left_image'][$k] = $left_img; }

        if(file_exists($this->CONF['images_path'] . '/' . $right_img))
        { $data[$qid]['answer']['right_image'][$k] = $right_img; }

        $data[$qid]['show']['middle_image'][$k] = FALSE;
        if(isset($data[$qid]['answer']['left_image'][$k]) && isset($data[$qid]['answer']['right_image'][$k]))
        { $data[$qid]['show']['left_right_image'][$k] = TRUE; }
        else
        {
            if(isset($data[$qid]['answer']['left_image'][$k]))
            { $data[$qid]['show']['left_image'][$k] = TRUE; }
            elseif(isset($data[$qid]['answer']['right_image'][$k]))
            { $data[$qid]['show']['right_image'][$k] = TRUE; }
            else
            {
                $data[$qid]['show']['left_right_image'][$k] = FALSE;
                $data[$qid]['show']['left_image'][$k] = FALSE;
                $data[$qid]['show']['right_image'][$k] = FALSE;
                $data[$qid]['show']['middle_image'][$k] = TRUE;
            }
        }
    }

    function _loadReports(&$survey)
    {
        $query = "SELECT r.report_id, r.report_name FROM {$this->CONF['db_tbl_prefix']}reports r, {$this->CONF['db_tbl_prefix']}report_questions rq
                  WHERE sid = {$survey['sid']} AND r.report_id = rq.report_id GROUP BY r.report_id ORDER BY r.report_name ASC";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
        while($r = $rs->FetchRow($rs))
        {
            $survey['report_name'][] = $r['report_name'];
            $survey['report_id'] = $r['report_id'];
        }
    }

    function _loadCustomReport($report_id,$survey)
    {
        $report_id = (int)$report_id;

        $query = "SELECT r.report_name, rq.qid, rq.layout, rq.display, rq.crosstab_questions";
    }

    /********************
    * VIEW TEXT RESULTS *
    ********************/
    function survey_results_text($sid,$qid)
    {
        $sid = (int)$sid;
        $qid = (int)$qid;

        $answer['delete_access'] = $this->_hasPriv(EDIT_PRIV,$sid) | $this->_hasPriv(ADMIN_PRIV);

        if(!empty($_REQUEST['delete_rid']) && $answer['delete_access'])
        {
            $rid_list = '';
            foreach($_REQUEST['delete_rid'] as $rid)
            { $rid_list .= (int)$rid . ','; }
            $rid_list = substr($rid_list,0,-1);
            $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}results_text WHERE rid IN ($rid_list) AND sid = $sid AND qid = $qid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        }

        $rs = $this->db->Execute("SELECT q.question, a.type, s.survey_text_mode, s.user_text_mode
                                  FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}answer_types a,
                                  {$this->CONF['db_tbl_prefix']}surveys s
                                  WHERE q.sid = $sid AND q.qid = $qid AND q.sid = s.sid
                                  AND q.aid = a.aid AND a.type IN ('T','S')");
        if($rs === FALSE) { return $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
        if($r = $rs->FetchRow($rs))
        { $question = nl2br($this->SfStr->getSafeString($r['question'],$r['survey_text_mode'])); }
        else
        { return $this->error($this->lang['invalid_text_question']); }

        $survey_text_mode = $r['survey_text_mode'];
        $user_text_mode = $r['user_text_mode'];

        if(!isset($_SESSION['results']['page']))
        { $_SESSION['results']['page'] = 0; }

        if(isset($_REQUEST['clear']))
        {
            unset($_REQUEST['search']);
            unset($_SESSION['results']['search']);
            $_SESSION['results']['page'] = 0;
        }

        if(isset($_REQUEST['search']) && strlen($_REQUEST['search']) > 0)
        {
            $answer['search_text'] = $this->SfStr->getSafeString($_REQUEST['search'],SAFE_STRING_TEXT);

            $search = " AND answer LIKE '%{$answer['search_text']}%' ";
            $button['clear'] = TRUE;

            if(!isset($_SESSION['results']['search']) || $_REQUEST['search'] != $_SESSION['results']['search'])
            {
                $_SESSION['results']['page'] = 0;
                $_SESSION['results']['search'] = $_REQUEST['search'];
            }
        }
        else
        { $search = ''; }

        if(isset($_REQUEST['next']))
        { $_SESSION['results']['page']++; }
        elseif(isset($_REQUEST['prev']) && $_SESSION['results']['page'] > 0)
        { $_SESSION['results']['page']--; }

        if(isset($_REQUEST['per_page']))
        {
            $per_page = (int)$_REQUEST['per_page'];
            $selected[$per_page] = FORM_SELECTED;
        }
        else
        { $per_page = $this->CONF['text_results_per_page']; }

        $start = $per_page * $_SESSION['results']['page'];

        $rs = $this->db->Execute("SELECT COUNT(*) AS c FROM {$this->CONF['db_tbl_prefix']}results_text r WHERE qid = $qid
                                  $search {$_SESSION['filter'][$sid]}");
        if($rs === FALSE)
        { return $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
        $r = $rs->FetchRow($rs);
        $answer['num_answers'] = $r['c'];

        $rs = $this->db->SelectLimit("SELECT rid, answer FROM {$this->CONF['db_tbl_prefix']}results_text r WHERE qid = $qid
                                  $search {$_SESSION['filter'][$sid]} ORDER BY entered DESC",$per_page,$start);
        if($rs === FALSE)
        { return $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }

        $answer['text'] = array();
        $answer['rid'] = array();
        $answer['num'] = array();
        $answer['delete_access'] = $answer['num_answers'] && $answer['delete_access'];
        $cnt = 0;

        while($r = $rs->FetchRow($rs))
        {
            $answer['num'][] = $answer['num_answers'] - $start - $cnt++;
            $answer['text'][] = $this->SfStr->getSafeString($r['answer'],$user_text_mode);
            $answer['rid'][] = $r['rid'];
        }

        if(($start + $per_page) >= $answer['num_answers'])
        { $button['next'] = FALSE; }
        else
        { $button['next'] = TRUE; }

        if($_SESSION['results']['page'] == 0)
        { $button['previous'] = FALSE; }
        else
        { $button['previous'] = TRUE; }


        $qnum = (int)$_REQUEST['qnum'];

        $this->smarty->assign('question',$question);
        $this->smarty->assign('qnum',$qnum);

        if(isset($answer))
        { $this->smarty->assign_by_ref('answer',$answer); }

        $this->smarty->assign('sid',$sid);
        $this->smarty->assign('qid',$qid);
        $this->smarty->assign('button',$button);

        $retval = $this->smarty->fetch($this->CONF['template'].'/results_text_detail.tpl');
        return $retval;
    }

    /**********************
    * DISPLAY FILTER FORM *
    **********************/
    function filter($sid)
    {
        $x = 0;
        $qid_list = '';

        foreach($_REQUEST['select_qid'] as $qid)
        { $qid_list .= (int)$qid . ','; }
        $qid_list = substr($qid_list,0,-1);

        $query = "SELECT at.aid, q.qid, q.question, s.survey_text_mode
                  FROM {$this->CONF['db_tbl_prefix']}answer_types at,
                  {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}surveys s
                  WHERE q.aid = at.aid AND q.sid = $sid AND q.qid IN ($qid_list) AND at.type IN ('MM','MS')
                  AND q.sid = s.sid
                  ORDER BY q.page, q.oid";
        $rs = $this->db->Execute($query);

        $old_aid = '';
        if($rs === FALSE) { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
        if($r = $rs->FetchRow())
        {
            do
            {
                $question['question'][] = nl2br($this->SfStr->getSafeString($r['question'],$r['survey_text_mode']));
                $question['encquestion'][] = $this->SfStr->getSafeString($r['question'],SAFE_STRING_TEXT);
                $question['aid'][] = $r['aid'];
                $question['qid'][] = $r['qid'];
                $temp = $this->get_answer_values($r['aid'],BY_AID,$r['survey_text_mode']);
                $question['value'][] = $temp['value'];
                $question['avid'][] = $temp['avid'];
                $x++;
            }while($r = $rs->FetchRow());
            $this->smarty->assign("question",$question);
        }
        $rs = $this->db->Execute("SELECT MIN(entered) AS mindate,
                                  MAX(entered) AS maxdate FROM
                                  {$this->CONF['db_tbl_prefix']}results WHERE sid = $sid");
        if($rs === FALSE) { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
        $r = $rs->FetchRow();
        $date['min'] = date('Y-m-d',$r['mindate']);
        $date['max'] = date('Y-m-d',$r['maxdate']);

        $this->smarty->assign('date',$date);


        $this->smarty->assign('sid',$sid);

        $retval = $this->smarty->fetch($this->CONF['template'].'/filter.tpl');

        return $retval;
    }

    /**********************
    * PROCESS FILTER FORM *
    **********************/
    function process_filter($sid,$text_mode)
    {
        $sid = (int)$sid;

        //Determine sequence filter for results queries
        $_SESSION['filter'][$sid] = '';
        $_SESSION['filter_total'][$sid] = '';
        $_SESSION['filter_text'][$sid] = '';

        $where = '';
        $having = '';
        $criteria = array();
        $num_criteria = 0;
        $num_dates = 0;

        if(isset($_REQUEST['filter']) && is_array($_REQUEST['filter']))
        {
            $_SESSION['filter_text'][$sid] = '';
            $_SESSION['filter_total'][$sid] = '';
            foreach($_REQUEST['filter'] as $filter_qid=>$value)
            {
                if(is_array($value))
                {
                    $answer_values = $this->get_answer_values($filter_qid,BY_QID,$text_mode);
                    $selected_answers = '';
                    $avid_list = '';
                    foreach($value as $avid)
                    {
                        if(isset($answer_values[$avid]))
                        {
                            $selected_answers .= $answer_values[$avid] . $this->lang['filter_answer_seperator'];
                            $avid_list .= $avid . ',';
                        }
                    }
                    $selected_answers = $this->SfStr->getSafeString(substr($selected_answers,0,-2),$text_mode);
                    $avid_list = substr($avid_list,0,-1);
                    $criteria[] = "(q.qid = $filter_qid AND r.avid IN ({$avid_list}))";

                    $question_text = $this->SfStr->getSafeString($_REQUEST['name'][$filter_qid],$text_mode,1);

                    $_SESSION['filter_text'][$sid] .= $question_text . $this->lang['filter_seperator'] . $selected_answers . BR . NL;
                }
            }

            if($num_criteria = count($criteria))
            {
                $where .= ' AND (' . implode(' OR ',$criteria) . ')';
                $having = " having c = {$num_criteria}";
            }
        }

        if(isset($_REQUEST['date_filter']))
        {
            if(!empty($_REQUEST['start_date']))
            {
                if($start_date = strtotime($_REQUEST['start_date'] . ' 00:00:01'))
                {
                    $where .= " AND r.entered > $start_date ";
                    $start_date = $this->SfStr->getSafeString($_REQUEST['start_date'],SAFE_STRING_TEXT);
                    $_SESSION['filter_text'][$sid] .= $this->lang['filter_start_date'] . $start_date . BR . NL;
                    $num_dates++;
                }
            }
            if(!empty($_REQUEST['end_date']))
            {
                if($end_date = strtotime($_REQUEST['end_date'] . ' 23:59:59'))
                {
                    $where .= " AND r.entered < $end_date ";
                    $end_date = $this->SfStr->getSafeString($_REQUEST['end_date'],SAFE_STRING_TEXT);
                    $_SESSION['filter_text'][$sid] .= $this->lang['filter_end_date'] . $end_date . BR . NL;
                    $num_dates++;
                }
            }
        }

        if($num_criteria || $num_dates)
        {
            $sql = "SELECT r.sequence, count(*) as c from {$this->CONF['db_tbl_prefix']}results r,
                {$this->CONF['db_tbl_prefix']}questions q where
                r.qid = q.qid {$where} group by sequence {$having}";

            $rs = $this->db->Execute($sql);
            if($rs === FALSE) { return $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }

            $sequence = array();
            while($r = $rs->FetchRow($rs))
            { $sequence[] = $r['sequence']; }

            if($num = count($sequence))
            {
                if($num > $this->CONF['filter_limit'])
                {
                    $seq_list = implode(',',$sequence);

                    $_SESSION['filter'][$sid] = " AND r.sequence IN ($seq_list) ";
                    $_SESSION['filter_total'][$sid] = " AND (r.sequence IN ($seq_list) OR rt.sequence IN ($seq_list) OR (NOT ".$this->db->IfNull('r.sequence',0)." AND NOT ".$this->db->IfNull('rt.sequence',0).")) ";
                }
                else
                { $_SESSION['filter_text'][$sid] = $this->lang['filter_limit']; }
            }
            else
            { $_SESSION['filter_text'][$sid] = $this->lang['filter_no_match']; }
        }
        else
        {
            $_SESSION['filter'][$sid] = '';
            $_SESSION['filter_total'][$sid] = '';
        }

        //Redirect back to results page with proper filter set
        header("Location: {$this->CONF['html']}/results.php?sid=$sid");
        exit();
    }

    function process_results_action($sid)
    {
        $sid = (int)$sid;
        $redirect = TRUE;
        $retval = '';

        switch($_REQUEST['action'])
        {
            case ACTION_HIDE_QUESTIONS:
            case ACTION_SHOW_QUESTIONS:
                if(isset($_REQUEST['select_qid']) && !empty($_REQUEST['select_qid']))
                {
                    $list = '';
                    foreach($_REQUEST['select_qid'] as $select_qid)
                    { $list .= (int)$select_qid . ','; }

                    $not = '';
                    if($_REQUEST['action'] == ACTION_HIDE_QUESTIONS)
                    { $not = 'NOT'; }

                    $hide_show_where = " AND q.qid $not IN (" . substr($list,0,-1) . ') ';
                    $_SESSION['hide-show'][$sid] = $hide_show_where;
                }
            break;

            case ACTION_SHOW_ALL_QUESTIONS:
                $hide_show_where = '';
                unset($_SESSION['hide-show'][$sid]);
            break;

            case ACTION_FILTER:
                if(isset($_REQUEST['select_qid']) && !empty($_REQUEST['select_qid']))
                {
                    $retval = $this->filter($sid);
                    $redirect = FALSE;
                }
            break;

            case ACTION_CLEAR_FILTER:
                $_SESSION['filter'][$sid] = '';
                $_SESSION['filter_total'][$sid] = '';
                $_SESSION['filter_text'][$sid] = '';
            break;
        }

        if($redirect)
        {
            header("Location: {$this->CONF['html']}/results.php?sid=$sid");
            exit();
        }
        else
        { return $retval; }
    }

}
?>