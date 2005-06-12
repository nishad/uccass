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

define('QUESTION_CUTOFF_SELECT',100);
define('QUESTION_CUTOFF_LIST',50);

define('REPORT_LAYOUT_BAR_GRAPH','bar_graph');
define('REPORT_LAYOUT_NUMERIC','numeric');
define('REPORT_LAYOUT_CROSSTAB','crosstab');

class UCCASS_Reports extends UCCASS_Main
{
    //Load configuration and initialize data variable
    function UCCASS_Reports()
    {
        $this->load_configuration();
        $this->data = array();
    }

    //Show edit survey page based upon request variables
    function show($sid)
    {
        $sid = (int)$sid;
        $retval = '';

        //Ensure user is logged in with valid privileges
        //for the requested survey or is an administrator
        if(!$this->_CheckLogin($sid,EDIT_PRIV,"access_control.php?sid=$sid"))
        { return $this->showLogin('edit_survey.php',array('sid'=>$sid)); }

        //Show links at top of page
        $this->data['show']['links'] = TRUE;
        $this->data['sid'] = $sid;
        $this->data['report_id'] = 0;

        $this->smarty->assign_by_ref('data',$this->data);

        //Retrieve template that shows links for edit survey page
        $this->data['links'] = ($this->data['show']['links']) ? $this->smarty->Fetch($this->CONF['template'].'/edit_survey_links.tpl') : '';

        if(isset($_REQUEST['submit_new_report']))
        { $this->_processNewReport(); }
        elseif(isset($_POST['save_submit']))
        { $this->_processSaveReport(); }
        elseif(isset($_POST['add_questions']))
        { $this->_processAddQuestions(); }
        elseif(isset($_GET['delete']))
        { $this->_processDeleteQuestion(); }
        elseif(!empty($_REQUEST['report_id']))
        {
            $this->_loadSurveyQuestions();
            $this->_loadReportQuestions();
            $this->data['content'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_reports.tpl');
        }
        else
        {
            if(isset($_POST['report_name']))
            { $this->data['report_name'] = $this->SfStr->getSafeString($_POST['report_name'],SAFE_STRING_TEXT); }
            $this->_showChooseReport();
        }

        //Retrieve entire edit surey page based upon the content set above
        return $this->smarty->Fetch($this->CONF['template'].'/edit_survey.tpl');
    }

    function _processNewReport()
    {
        $error = '';
        $message = $this->lang['new_report'];

        if(empty($_REQUEST['report_name']))
        { $error = $this->lang['no_report_name']; }
        else
        {
            $report_name = $this->SfStr->getSafeString($_REQUEST['report_name'],SAFE_STRING_DB);

            $query = "SELECT 1 FROM {$this->CONF['db_tbl_prefix']}reports WHERE report_name = {$report_name} AND sid = {$this->data['sid']}";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }

            if($r = $rs->FetchRow($rs))
            { $error = $this->lang['report_name_used']; }
            else
            {
                $this->data['report_id'] = $this->db->GenID($this->CONF['db_tbl_prefix'].'reports_sequence');
                $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}reports (report_id, report_name, sid)
                          VALUES ({$this->data['report_id']},{$report_name},{$this->data['sid']})";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
            }
        }
        $this->_redirect($message,$error);
    }

    function _showChooseReport()
    {
        $query = "SELECT report_id, report_name FROM {$this->CONF['db_tbl_prefix']}reports WHERE sid = {$this->data['sid']} ORDER BY report_name ASC";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }

        $this->data['avail_reports'] = array('report_id' => array(), 'report_name' => array());
        while($r = $rs->FetchRow($rs))
        {
            $this->data['avail_reports']['report_id'][] = $r['report_id'];
            $this->data['avail_reports']['report_name'][] = $this->SfStr->getSafeString($r['report_name'],SAFE_STRING_TEXT);
        }

        $this->data['content'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_reports_choose.tpl');
    }

    function _loadSurveyQuestions()
    {
        $query = "SELECT q.qid, q.question, a.type FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}answer_types a
                  WHERE q.aid = a.aid AND q.sid = {$this->data['sid']} ORDER BY q.page ASC, q.oid ASC";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }

        $num = 1;
        $labelnum = 1;
        $this->data['survey_questions'] = array();
        $this->data['crosstab_questions'] = array();

        while($r = $rs->FetchRow($rs))
        {
            $question = substr($r['question'],0,QUESTION_CUTOFF_SELECT);

            $this->data['survey_questions']['question'][] = $this->SfStr->getSafeString($question,SAFE_STRING_TEXT);
            $this->data['survey_questions']['qid'][] = $r['qid'];

            if($r['type'] == ANSWER_TYPE_MM || $r['type'] == ANSWER_TYPE_MS)
            {
                $this->data['crosstab_questions']['question'][] = $this->SfStr->getSafeString($question,SAFE_STRING_TEXT);
                $this->data['crosstab_questions']['qid'][] = $r['qid'];
                $this->data['crosstab_questions']['question_num'][] = $num;
            }

            if($r['type'] == ANSWER_TYPE_N)
            { $this->data['survey_questions']['question_num'][] = LABEL_PREFIX . $labelnum++; }
            else
            { $this->data['survey_questions']['question_num'][] = $num++; }
        }
    }

    function _loadReportQuestions()
    {
        $this->data['report_id'] = (empty($_REQUEST['report_id'])) ? 0 : (int)$_REQUEST['report_id'];

        if(!empty($this->data['report_id']))
        {
            $query = "SELECT r.report_name, s.survey_text_mode FROM {$this->CONF['db_tbl_prefix']}reports r, {$this->CONF['db_tbl_prefix']}surveys s
                      WHERE r.sid = s.sid AND r.report_id = {$this->data['report_id']} AND r.sid = {$this->data['sid']}";
            $rs1 = $this->db->Execute($query);
            if($rs1 === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }

            if($r1 = $rs1->FetchRow($rs1))
            {
                $this->data['report_name'] = $this->SfStr->getSafeString($r1['report_name'],SAFE_STRING_TEXT);

                $query = "SELECT rq.rqid, rq.qid, q.question, rq.layout, rq.display, rq.crosstab_questions FROM
                          {$this->CONF['db_tbl_prefix']}report_questions rq, {$this->CONF['db_tbl_prefix']}questions q
                          WHERE rq.qid = q.qid AND rq.report_id = {$this->data['report_id']} AND q.sid = {$this->data['sid']}
                          ORDER BY rq.order_id ASC";

                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }

                $x = 0;
                $order_id = 1;
                while($r = $rs->FetchRow($rs))
                {
                    $this->data['report']['rqid'][] = $r['rqid'];
                    $this->data['report']['qid'][] = $r['qid'];
                    $this->data['report']['question'][] = $this->SfStr->getSafeString(substr($r['question'],0,QUESTION_CUTOFF_LIST),SAFE_STRING_TEXT);
                    $this->data['report']['layout'][] = $r['layout'];
                    $this->data['report']['display'][] = $r['display'];
                    $this->data['report']['crosstab_questions'][] = (empty($r['crosstab_questions'])) ? NBSP : $r['crosstab_questions'];
                    $this->data['report']['order_id'][] = $order_id++;
                    $this->data['report']['order_id_selected'][$x++] = '';
                }
                $this->data['report']['order_id_selected'][$x-1] = FORM_SELECTED;

            }
            else
            {
                $this->data['report_id'] = 0;
                $this->error($this->lang['report_invalid_id']);
            }
        }
    }

    function _processSaveReport()
    {
        $this->data['report_id'] = (empty($_REQUEST['report_id'])) ? 0 : (int)$_REQUEST['report_id'];
        $error = '';
        $message = $this->lang['report_name_saved'];

        if(empty($_POST['report_name']))
        { $error = $this->lang['no_report_name']; }
        else
        {
            $report_name = $this->SfStr->getSafeString($_POST['report_name'],SAFE_STRING_DB);

            $query = "UPDATE {$this->CONF['db_tbl_prefix']}reports SET report_name = {$report_name} WHERE report_id = {$this->data['report_id']}
                      AND sid = {$this->data['sid']}";

            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
        }

        $this->_redirect($message,$error);
    }

    function _processAddQuestions()
    {
        $this->data['report_id'] = (empty($_REQUEST['report_id'])) ? 0 : (int)$_REQUEST['report_id'];
        $error = array();
        $message = $this->lang['report_questions_added'];

        if(!empty($_POST['report_name']))
        { $input['report_name'] = $this->SfStr->getSafeString($_POST['report_name'],SAFE_STRING_DB); }
        else
        { $error[] = $this->lang['no_report_name']; }

        if(!empty($_POST['layout']))
        { $input['layout'] = $_POST['layout']; }
        else
        { $error[] = $this->lang['report_no_layout']; }

        if(!empty($_POST['display']) && is_array($_POST['display']))
        { $input['display'] = $this->SfStr->getSafeString(implode(',',$_POST['display']),SAFE_STRING_DB); }
        else
        { $error[] = $this->lang['report_no_display']; }

        if(!empty($_POST['questions']) && is_array($_POST['questions']))
        {
            $input['questions'] = array();
            foreach($_POST['questions'] as $qid)
            {
                $qid_tmp = (int)$qid;
                if($qid_tmp > 0)
                { $input['questions'][] = $qid_tmp; }
            }
        }
        else
        { $error[] = $this->lang['report_no_questions']; }

        $input['crosstab_questions'] = '';
        if(isset($input['layout']) && $input['layout'] == REPORT_LAYOUT_CROSSTAB)
        {
            if(!empty($_POST['crosstab_questions']) && is_array($_POST['crosstab_questions']))
            {
                $crosstab = array();
                foreach($_POST['crosstab_questions'] as $qid)
                {
                    $qid_tmp = (int)$qid;
                    if($qid_tmp)
                    { $crosstab[] = $qid_tmp; }
                }
                if(!empty($crosstab))
                { $input['crosstab_questions'] = implode(',',$crosstab); }
            }

            if(empty($input['crosstab_questions']))
            { $error[] = $this->lang['report_no_crosstab']; }
        }
        $input['crosstab_questions'] = $this->SfStr->getSafeString($input['crosstab_questions'],SAFE_STRING_DB);

        if(!empty($_POST['order_id']))
        {
            $increm_order_id = (int)$_POST['order_id'];
            if($increm_order_id)
            { $start_order_id = $increm_order_id + 1; }
            else
            { $start_order_id = 1; }
        }
        else
        {
            $increm_order_id = 0;
            $start_order_id = 1;
        }

        if(empty($error))
        {
            //Increment any rows that are greater than the requested
            //order_id so these questions can be inserted.
            $num_add = count($input['questions']);
            $query = "UPDATE {$this->CONF['db_tbl_prefix']}report_questions SET order_id = order_id + {$num_add} WHERE
                      order_id > {$increm_order_id} AND report_id = {$this->data['report_id']}";

            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }

            $input['layout'] = $this->SfStr->getSafeString($input['layout'],SAFE_STRING_DB);

            $sql = array();
            $input['order_id'] = $start_order_id;
            foreach($input['questions'] as $qid)
            {
                $rqid = $this->db->GenID($this->CONF['db_tbl_prefix'].'report_questions_sequence');
                $sql[] = "($rqid,{$this->data['report_id']},$qid,{$input['layout']},{$input['display']},{$input['crosstab_questions']},{$input['order_id']})";
                $input['order_id']++;
            }

            if(!empty($sql))
            {
                $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}report_questions (rqid,report_id,qid,layout,display,crosstab_questions,order_id) VALUES " . implode(',',$sql);

                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
            }

        }

        $this->_redirect($message,$error);
    }

    function _processDeleteQuestion()
    {
        $message = $this->lang['report_question_deleted'];
        $error = '';

        $rqid = (int)$_GET['delete'];

        if(!empty($rqid))
        {
            //Validate rqid passed belongs to a report that user has permission to edit
            $query = "SELECT 1 FROM {$this->CONF['db_tbl_prefix']}reports r, {$this->CONF['db_tbl_prefix']}report_questions rq
                      WHERE r.report_id = rq.report_id AND r.sid = {$this->data['sid']} AND rq.rqid = {$rqid}";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
            elseif($r = $rs->FetchRow($rs))
            {
                $this->data['report_id'] = (int)$_GET['report_id'];

                $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}report_questions WHERE rqid = {$rqid}";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
            }
            else
            { $error = $this->lang['report_invalid_delete']; }
        }

        $this->_redirect($message,$error);
    }

    function _redirect($message='',$error='')
    {
        //Set page to redirect to upon success or error
        $this->setMessageRedirect("reports.php?sid={$this->data['sid']}&report_id={$this->data['report_id']}");

        if(empty($error))
        {
            $type = MSGTYPE_NOTICE;
            $title = $this->lang['notice'];
        }
        else
        {
            $type = MSGTYPE_ERROR;
            $title = $this->lang['error'];
            $message = (is_array($error)) ? implode(BR,$error) : $error;
        }

        $this->setMessage($title,$message,$type);
    }
}
?>