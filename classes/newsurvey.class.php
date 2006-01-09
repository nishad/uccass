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

//sid of survey to copy default answer types and values
//from. Set to -1 to disable any copying. Zero is default.
define('DEFAULT_COPY_SID', 0);

class UCCASS_NewSurvey extends UCCASS_Main
{
    function UCCASS_NewSurvey()
    { $this->load_configuration(); }

    function createNewSurvey()
    {
        //If permission is required to create surveys, validate login or permissions
        if($this->CONF['create_access'] && !$this->_CheckLogin(0,CREATE_PRIV,'new_survey.php'))
        { return $this->showLogin('new_survey.php'); }

        //If Next button was pressed, process sent data
        if(isset($_REQUEST['next']))
        { $this->_processNewSurveyData(); }

        //If Reset button was pressed, clear saved session data
        if(isset($_POST['clear']))
        { unset($_SESSION['new_survey']); }

        //display page to create new survey
        return $this->_displayNewSurvey();
    }

    /*********************
    * PROCESS NEW SURVEY *
    *********************/
    function _processNewSurveyData()
    {
        $error = array();

        $error = $this->_validateNewSurveyData();

        if(empty($error))
        {

            //Default variables
            $sid = 0;
            $page = 1;
            $oid = 1;

            //Default values for new survey
            $input['activate'] = 0;
            $input['template'] = $this->SfStr->getSafeString($this->CONF['default_template'],SAFE_STRING_ESC);
            $input['date_format'] = $this->SfStr->getSafeString($this->CONF['date_format'],SAFE_STRING_ESC);
            $input['created'] = time();

            $input['survey_name'] = $this->SfStr->getSafeString($_REQUEST['survey_name'], SAFE_STRING_DB);
            $input['username'] = $this->SfStr->getSafeString($_REQUEST['username'], SAFE_STRING_DB);
            $input['password'] = $this->SfStr->getSafeString($_REQUEST['password'], SAFE_STRING_DB);

            //////////////////
            //CREATE SURVEY //
            //////////////////
            $sid = $this->db->GenID($this->CONF['db_tbl_prefix'].'surveys_sequence');

            $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}surveys (sid, name, active, template, date_format, created) VALUES
                      ($sid,{$input['survey_name']},{$input['activate']},{$input['template']},{$input['date_format']},{$input['created']})";
            $rs1 = $this->db->Execute($query);
            if($rs1 === FALSE)
            { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
            else
            {
                $this->_createDefaultUser($sid, $input['username'], $input['password']);

                //Make copy of "copy_survey". If "copy_survey" key is not
                //passed, then use default. If default is -1, then no copying is done
                if(isset($_REQUEST['copy_survey']) && is_numeric($_REQUEST['copy_survey']))
                { $copy_survey = $_REQUEST['copy_survey']; }
                else
                { $copy_survey = DEFAULT_COPY_SID; }

                //Only copy survey if value is over zero
                if($copy_survey >= 0)
                {
                    //Copy existing survey
                    $error = $this->_copySurvey($copy_survey, $sid);
                }
            }
        }

        if(!empty($error))
        {
            $_SESSION['new_survey'] = $_POST;
            $this->setMessageRedirect("new_survey.php");
            $this->setMessage($this->lang['error'],implode(BR,$error),MSGTYPE_ERROR);
        }
        else
        {
            unset($_SESSION['new_survey']);
            $this->setMessageRedirect("edit_survey.php?sid={$sid}");
            $this->setMessage($this->lang['notice'],$this->lang['survey_created'],MSGTYPE_NOTICE);
        }
    }

    function _createDefaultUser($sid, $username, $password)
    {
        //Create default user with edit and view results privileges
        $uid = $this->db->GenID($this->CONF['db_tbl_prefix'].'users_sequence');
        $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}users (uid, sid, username, password, edit_priv, results_priv) VALUES
                  ($uid, $sid, {$username}, {$password}, 1, 1)";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }

        //Set edit and view results priv within session
        $_SESSION['priv'][$sid][EDIT_PRIV] = 1;
        $_SESSION['priv'][$sid][RESULTS_PRIV] = 1;

        return;
    }

    function _displayNewSurvey()
    {
        //Default values
        $data = array();
        $data['copy_survey'] = 0;
        $data['available_surveys'] = array();

        //Check for existing new survey data (from failed attempt)
        //and save safely into data
        if(isset($_SESSION['new_survey']['survey_name']))
        { $data['survey_name'] = $this->SfStr->getSafeString($_SESSION['new_survey']['survey_name'], SAFE_STRING_TEXT); }
        if(isset($_SESSION['new_survey']['username']))
        { $data['username'] = $this->SfStr->getSafeString($_SESSION['new_survey']['username'], SAFE_STRING_TEXT); }
        if(isset($_SESSION['new_survey']['password']))
        { $data['password'] = $this->SfStr->getSafeString($_SESSION['new_survey']['password'], SAFE_STRING_TEXT); }
        if(isset($_SESSION['new_survey']['copy_survey']))
        { $data['copy_survey'] = (int)$_SESSION['new_survey']['copy_survey']; }

        //Gather list of surveys that are not hidden that can be copied
        $query = "SELECT sid, name FROM {$this->CONF['db_tbl_prefix']}surveys WHERE hidden = 0 order by name ASC";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        //Default value of "copy survey" dropdown
        $data['available_surveys']['sid'][] = '';
        $data['available_surveys']['name'][] = $this->lang['default_copy_name'];
        $data['available_surveys']['selected'][] = '';

        while($r = $rs->FetchRow($rs))
        {
            $data['available_surveys']['sid'][] = $r['sid'];
            $data['available_surveys']['name'][] = $this->SfStr->getSafeString($r['name'],SAFE_STRING_TEXT);
            if($r['sid'] == $data['copy_survey'])
            { $data['available_surveys']['selected'][] = FORM_SELECTED; }
            else
            { $data['available_surveys']['selected'][] = ''; }
        }


        //Assign smarty variables and return parsed template
        $this->smarty->assign('data', $data);
        return $this->smarty->Fetch($this->CONF['template'].'/add_survey.tpl');
    }

    function _validateNewSurveyData()
    {
        $error = array();

        // PROCESS NAME OF FORM
        if(!empty($_REQUEST['survey_name']))
        {
            $name = $this->SfStr->getSafeString($_REQUEST['survey_name'],SAFE_STRING_DB);
            $query = "SELECT 1 FROM {$this->CONF['db_tbl_prefix']}surveys WHERE name = $name";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }

            if($rs->FetchRow($rs))
            { $error[] = $this->lang['survey_name_used']; }
        }
        else
        { $error[] = $this->lang['name_required']; }

        if(empty($_REQUEST['username']))
        { $error[] = $this->lang['invalid_new_username']; }

        if(empty($_REQUEST['password']))
        { $error[] = $this->lang['invalid_new_password']; }

        return($error);
    }

    //Copy information FROM "$copy_survey" TO "$sid"
    function _copySurvey($copy_survey, $sid)
    {
        $new = array();

        //Copies answer types and values from existing survey
        //sets relations between old/new aid and old/new avid
        $this->_copyAnswerTypes($copy_survey, $sid, $new);
        //Copy questions from existing survey
        //Modifies $new to add relations between old/new qid
        $this->_copyQuestions($copy_survey, $sid, $new);
        //Copy dependencies from existing survey
        $this->_copyDependencies($copy_survey, $sid, $new);

        return;
    }

    function _copyAnswerTypes($copy_survey, $sid, &$new)
    {

        $query = "SELECT aid, name, type, label FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE sid = {$copy_survey}";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('1:' . $this->lang['db_query_error'] . $this->db->ErrorMsg()); }
        while($r = $rs->FetchRow($rs))
        {
            $name = $this->SfStr->getSafeString($r['name'],SAFE_STRING_ESC);
            $type = $this->SfStr->getSafeString($r['type'],SAFE_STRING_ESC);
            $label = $this->SfStr->getSafeString($r['label'],SAFE_STRING_ESC);
            $aid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_types_sequence');

            $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_types (aid, name, type, label, sid) VALUES
                      ($aid, $name,$type,$label,$sid)";
            $rs2 = $this->db->Execute($query);
            if($rs2 === FALSE)
            { $this->error('2: ' . $this->lang['db_query_error'] . $this->db->ErrorMsg()); }

            $new['new_aid'][$r['aid']] = $aid;

            $this->_copyAnswerValues($sid, $r['aid'], $aid, $new);
        }

        return;
    }

    function _copyAnswerValues($sid, $aid_old, $aid_new, &$new)
    {
        $query = "SELECT avid, value, numeric_value, image FROM {$this->CONF['db_tbl_prefix']}answer_values
                  WHERE aid = {$aid_old}";

        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('3: ' . $this->lang['db_query_error'] . $this->db->ErrorMsg()); }
        while($r = $rs->FetchRow($rs))
        {
            $value = $this->SfStr->getSafeString($r['value'],SAFE_STRING_ESC);
            $image = $this->SfStr->getSafeString($r['image'],SAFE_STRING_ESC);
            $avid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_values_sequence');

            $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_values (avid, aid, value, numeric_value, image)
                      VALUES ($avid, {$aid_new},$value,{$r['numeric_value']},$image)";
            $rs2 = $this->db->Execute($query);
            if($rs2 === FALSE)
            { $this->error('5: ' . $this->lang['db_query_error'] . $this->db->ErrorMsg()); }

            $new['new_avid'][$r['avid']] = $avid;
        }
        return;
    }

    function _copyQuestions($copy_survey, $sid, &$new)
    {
        $data = array();
        $old_page = 1;
        $page = 1;
        $oid = 1;
        $x = 0;

        $query = "SELECT qid, question, aid, num_answers, num_required, page, orientation FROM {$this->CONF['db_tbl_prefix']}questions
                  WHERE sid = {$copy_survey} ORDER BY page, oid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('6: ' . $this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }

        while($r = $rs->FetchRow($rs))
        {
            if($r['page'] != $old_page)
            {
                $data['question'][$x] = $this->CONF['page_break'];
                $data['answer'][$x] = 0;
                $data['num_answers'][$x] = 0;
                $data['num_required'][$x] = 0;
                $old_page = $r['page'];
                $x++;
            }

            $data['qid'][$x] = $r['qid'];
            $data['question'][$x] = $this->SfStr->getSafeString($r['question'],SAFE_STRING_ESC);
            $data['answer'][$x] = $r['aid'];
            $data['num_answers'][$x] = $r['num_answers'];
            $data['num_required'][$x] = $r['num_required'];
            $data['orientation'][$x] = $this->SfStr->getSafeString($r['orientation'],SAFE_STRING_ESC);

            $x++;
        }

        if(!empty($data))
        {
            //Loop through each question and create SQL
            //needed to insert them into table
            $numq = count($data['question']);
            for($x=0;$x<$numq;$x++)
            {
                //If question matches "page break" text, increment
                //the $page counter, and reset the order ID (oid) counter
                if(strcasecmp($data['question'][$x],$this->CONF['page_break']) == 0)
                {
                    $page++;
                    $oid = 1;
                }
                else
                {
                    //Use $new array to get "new aid" value that relates
                    //to the "old aid" value assigned to the question being copied
                    $aid = $new['new_aid'][$data['answer'][$x]];

                    //Change lookbacks to point to correct qid
                    $replace = '/' . preg_quote(LOOKBACK_START_DELIMITER.LOOKBACK_TEXT).'([0-9]+)'.preg_quote(LOOKBACK_END_DELIMITER) . '/ie';
                    $replace_with = 'LOOKBACK_START_DELIMITER.LOOKBACK_TEXT.$new[\'new_qid\'][$1].LOOKBACK_END_DELIMITER';
                    $data['question'][$x] = preg_replace($replace,$replace_with,$data['question'][$x]);

                    //Create SQL to insert question and increment order ID (oid)
                    $qid = $this->db->GenID($this->CONF['db_tbl_prefix'].'questions_sequence');
                    $q = "($qid,{$data['question'][$x]},$aid,{$data['num_answers'][$x]},$sid,$page,{$data['num_required'][$x]},$oid,{$data['orientation'][$x]})";
                    $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}questions (qid,question,aid,num_answers,sid,page,num_required,oid,orientation) VALUES $q";

                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $this->error('7: ' . $this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

                    $new['new_qid'][$data['qid'][$x]] = $qid;

                    $oid++;
                }
            }
        }

        return;
    }

    function _copyDependencies($copy_survey, $sid, $new)
    {
        $query = "SELECT dep_id, qid, dep_qid, dep_aid, dep_option FROM {$this->CONF['db_tbl_prefix']}dependencies
                  WHERE sid = {$copy_survey}";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('8: ' . $this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        $dep_insert = '';
        while($r = $rs->FetchRow($rs))
        {
            //Replace old question IDs with
            //new question IDs of questions just inserted above
            $qid = $new['new_qid'][$r['qid']];
            $dep_qid = $new['new_qid'][$r['dep_qid']];
            $dep_aid = $new['new_avid'][$r['dep_aid']];

            $dep_id = $this->db->GenID($this->CONF['db_tbl_prefix'].'dependencies_sequence');
            $dep_option = $this->SfStr->getSafeString($r['dep_option'], SAFE_STRING_DB);
            $dep_insert .= "($dep_id, $sid, $qid, $dep_qid, $dep_aid, $dep_option),";

            //Insert query if INSERT list gets over 500 characters
            //to prevent errors from queries that are too large
            if(strlen($dep_insert) > 500)
            {
                $this->_insertDependencies($dep_insert);
                $dep_insert = '';
            }
        }

        if(!empty($dep_insert))
        {
            $this->_insertDependencies($dep_insert);
        }

        return;
    }

    function _insertDependencies($dep_insert)
    {
        $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}dependencies (dep_id, sid, qid, dep_qid, dep_aid, dep_option)
              VALUES " . substr($dep_insert,0,-1);
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('9: ' . $this->lang['db_query_error'] . $this->db->ErrorMsg()); }

        return;
    }
}
?>
