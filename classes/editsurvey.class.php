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

//Constants for different edit survey sections
define('MODE_PROPERTIES','properties');
define('MODE_EDITQUESTION','edit_question');
define('MODE_QUESTIONS','questions');
define('MODE_NEWQUESTION','new_question');

//Constant for moving questions up or down
//in question list
define('MOVE_UP',1);
define('MOVE_DOWN',2);

//Number of possible answer
//blocks to show
define('NUM_ANSWERS',5);

class UCCASS_EditSurvey extends UCCASS_Main
{
    //Load configuration and initialize data variable
    function UCCASS_EditSurvey()
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
        if(!$this->_CheckLogin($sid,EDIT_PRIV,"edit_survey.php?sid=$sid"))
        { return $this->showLogin('edit_survey.php',array('sid'=>$sid)); }

        //Show links at top of page
        $this->data['show']['links'] = TRUE;
        $this->data['content'] = MODE_PROPERTIES;
        $this->data['mode'] = MODE_PROPERTIES;
        $this->data['sid'] = $sid;

        //Set default mode if not present
        if(!isset($_REQUEST['mode']))
        { $_REQUEST['mode'] = MODE_PROPERTIES; }

        $qid = (int)@$_REQUEST['qid'];

        switch($_REQUEST['mode'])
        {
            //Methods that handle the display and processing
            //of the question list
            // - Add new question
            // - Processing editing of question
            // - Showing question list
            case MODE_QUESTIONS:
                if(isset($_REQUEST['add_new_question']))
                { $this->_processAddQuestion($sid); }
                elseif(isset($_REQUEST['edit_question_submit']))
                { $this->_processEditQuestion($sid,$qid); }
                else
                {
                    $this->data['content'] = MODE_QUESTIONS;
                    $this->_loadQuestions($sid);
                }
            break;

            //Methods for handling editing questions
            // - Deleting questions
            // - Deleting page breaks
            // - Moving questions up or down
            // - Loading edit single question form
            case MODE_EDITQUESTION:

                if(isset($_REQUEST['delete_question']))
                {
                    if(isset($_REQUEST['page_break']))
                    { $this->_processDeletePageBreak($sid,$qid); }
                    elseif(isset($_REQUEST['del_qid']))
                    { $this->_processDeleteQuestion($sid, $qid); }
                }
                elseif(isset($_REQUEST['move_up']))
                { $this->_processMoveQuestion($sid,$qid,MOVE_UP); }
                elseif(isset($_REQUEST['move_down']))
                { $this->_processMoveQuestion($sid,$qid,MOVE_DOWN); }
                elseif(isset($_REQUEST['edit_question']))
                {
                    $this->data['content'] = MODE_EDITQUESTION;
                    $this->data['mode'] = MODE_QUESTIONS;
                    $this->_loadEditQuestion($sid,$qid);
                }
            break;

            //Default mode for displaying and processing survey properties
            // - Processing delete survey request
            // - Process removing all answers from survey request
            // - Process update of properties
            // - Showing properties page/form
            case MODE_PROPERTIES:
            default:
                if(isset($_REQUEST['edit_survey_submit']))
                {
                    //Process data and redirect back to page
                    if(isset($_REQUEST['delete_survey']))
                    { $this->_processDeleteSurvey($sid); }
                    elseif(isset($_REQUEST['clear_answers']))
                    { $this->_processDeleteAnswers($sid); }
                    else
                    { $this->_processProperties($sid); }
                }
                else
                {
                    $this->data['content'] = MODE_PROPERTIES;
                    $this->_loadProperties($sid);
                }
            break;
        }

        $this->smarty->assign_by_ref('data',$this->data);

        //Retrieve template that shows links for edit survey page
        $this->data['links'] = ($this->data['show']['links']) ? $this->smarty->Fetch($this->CONF['template'].'/edit_survey_links.tpl') : '';

        if(isset($this->data['content']))
        { $this->data['content'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_' . $this->data['content'] . '.tpl'); }

        //Retrieve entire edit surey page based upon the content set above
        return $this->smarty->Fetch($this->CONF['template'].'/edit_survey.tpl');

    }

    // DELETE SURVEY //
    function _processDeleteSurvey($sid)
    {
        $error = array();

        //Loop through answer types assigned to survey and delete answer_values
        //assigned to each answer type. Then delete all answer types assigned to survey
        $query1 = "SELECT aid FROM {$this->CONF['db_tbl_prefix']}answer_types at WHERE at.sid = $sid";
        $rs = $this->db->Execute($query1);
        if($rs === FALSE)
        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        else
        {
            $aid_array = array();
            while($r = $rs->FetchRow($rs))
            { $aid_array[] = $r['aid']; }
            $this->delete_answer_values(implode(',', $aid_array));
        }

        $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE sid = $sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }

        //Delete all references to this survey in database
        $tables = array('questions','results','results_text','ip_track','surveys','dependencies','time_limit','users','completed_surveys');
        foreach($tables as $tbl)
        {
            $rs = $this->db->Execute("DELETE FROM {$this->CONF['db_tbl_prefix']}$tbl WHERE sid = $sid");
            if($rs === FALSE)
            { $error[] = $this->lang['db_table_error'] .  $this->CONF['db_tbl_prefix'] . $tbl; }
        }

        //If no errors, redirect back to index or admin page
        //based upon whether the user is logged in as an admin or not
        if(empty($error))
        {
            //Set notice and redirect to main page
            if($this->_hasPriv(ADMIN_PRIV))
            { $this->setMessageRedirect('admin.php'); }
            else
            { $this->setMessageRedirect('index.php'); }

            $this->setMessage($this->lang['notice'],$this->lang['survey_deleted'],MSGTYPE_NOTICE);
        }
        //otherwise...
        else
        {
            //Set error message and redirect back
            //to edit survey properties page
            $this->setMessageRedirect("edit_survey.php?sid=$sid");
            $this->setMessage($this->lang['error_deleting'],implode(BR,$error),MSGTYPE_ERROR);
        }
    }

    // DELETE PAGE BREAK //
    function _processDeletePageBreak($sid,$qid)
    {
        $sid = (int)$sid;
        $page = (int)$qid;
        $prev_page = $page - 1;

        //Set page to redirect to upon success or fail of deleting pagebreak
        $this->setMessageRedirect("edit_survey.php?sid=$sid&mode=questions");

        //Ensure no questions on page after break have dependencies based upon questions
        //on page before break. If dependencies exist, do not delete the question.
        $query = "SELECT COUNT(*) AS c FROM {$this->CONF['db_tbl_prefix']}dependencies d, {$this->CONF['db_tbl_prefix']}questions q1,
                  {$this->CONF['db_tbl_prefix']}questions q2 WHERE q1.page = $prev_page AND d.dep_qid = q1.qid AND q2.page = $page
                  AND d.qid = q2.qid AND d.sid = $sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        {
            //Set message and redirect back to questions page
            $this->setMessage($this->lang['error'],$this->lang['db_query_error'] . $this->db->ErrorMsg(),MSGTYPE_ERROR);
        }
        $r = $rs->FetchRow($rs);

        if($r['c'] == 0)
        {
            //Find the max oid for the questions on page before break and start assigning oid values
            //from there for questions on next page and set page values equal to each other.
            $query = "SELECT MAX(oid) as max_oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE sid=$sid and page = " . ($page-1);
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            {
                //Set message and redirect back to questions page
                $this->setMessage($this->lang['error'],$this->lang['db_query_error'] . $this->db->ErrorMsg(),MSGTYPE_ERROR);
            }
            $r = $rs->FetchRow($rs);

            if($r['max_oid'] > 0)
            {
                $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET oid = oid + {$r['max_oid']} WHERE sid=$sid and page=$page";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                {
                    //set message and redirect
                    $this->setMessage($this->lang['error'],$this->lang['db_query_error'] . $this->db->ErrorMsg(),MSGTYPE_ERROR);
                }
            }

            $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page - 1 WHERE page >= $page and sid = $sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            {
                //set message and redirect
                $this->setMessage($this->lang['error'],$this->lang['db_query_error'] . $this->db->ErrorMsg(),MSGTYPE_ERROR);
            }
            else
            {
                //set success message and redirect
                $this->setMessage($this->lang['notice'],$this->lang['delete_page_break'],MSGTYPE_NOTICE);
            }
        }
        else
        {
            //set message and redirect
            $this->setMessage($this->lang['error'],$this->lang['error_del_page_break'],MSGTYPE_ERROR);
        }
    }

    // DELETE QUESTION //
    function _processDeleteQuestion($sid,$qid)
    {
        $error = array();
        $tables = array('questions','results','results_text','dependencies');
        $error='';
        $sid = (int)$sid;
        $qid = (int)$qid;
        //Delete all references to this question in tables listed above
        foreach($tables as $tbl)
        {
            $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}$tbl WHERE qid = $qid and sid=$sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $error[] = $this->lang['db_table_error'] . $this->db->ErrorMsg(); }
        }

        //Delete any dependencies that rely upon an answer to this question
        $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}dependencies WHERE dep_qid = $qid AND sid=$sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }

        if(!empty($error))
        {
            //Set error message and redirect back to questions page
            $this->setMessageRedirect("edit_survey.php?sid=$sid&mode=questions");
            $this->setMessage($this->lang['error'],implode(BR,$error),MSGTYPE_ERROR);
        }
        else
        {
            $this->setMessageRedirect("edit_survey.php?sid=$sid&mode=questions");
            $this->setMessage($this->lang['notice'],$this->lang['question_deleted'],MSGTYPE_NOTICE);
        }
    }

    // DELETE ANSWERS/RESULTS FROM SURVEY //
    function _processDeleteAnswers($sid)
    {
        $sid = (int)$sid;
        $error = array();

        //set tables to delete any results from to clear all answers from this survey
        $tables = array('results','results_text','ip_track','time_limit','completed_surveys');
        foreach($tables as $tbl)
        {
            $rs = $this->db->Execute("DELETE FROM {$this->CONF['db_tbl_prefix']}$tbl WHERE sid = $sid");
            if($rs === FALSE)
            { $error[] = $this->lang['db_table_error'] . $this->db->ErrorMsg(); }
        }

        $this->setMessageRedirect("edit_survey.php?sid=$sid&mode=properties");

        if(empty($error))
        {
            //Set error message and redirect back to properties page
            $this->setMessage($this->lang['error'],implode(BR,$error),MSGTYPE_ERROR);
        }
        else
        {
            //Set success message and redirect back to properties page
            $this->setMessage($this->lang['notice'],$this->lang['answers_cleared'],MSGTYPE_NOTICE);
        }
    }

    // PROCESS SUBMISSION OF NEW PROPERTIES //
    function _processProperties($sid)
    {
        //validate submitted data
        $pr = $this->_validateProperties($sid);

        //if the validation did not
        //set an error, proceed with update
        if(empty($pr['error']))
        {
            $query = "UPDATE {$this->CONF['db_tbl_prefix']}surveys SET name={$pr['input']['name']}, start_date={$pr['input']['start']},
                      end_date={$pr['input']['end']}, active={$pr['input']['active']},
                      template = {$pr['input']['template']}, redirect_page = {$pr['input']['redirect_page']},
                      survey_text_mode = {$pr['input']['survey_text_mode']}, user_text_mode = {$pr['input']['user_text_mode']},
                      date_format = {$pr['input']['date_format']}, time_limit = {$pr['input']['time_limit']}
                      WHERE sid = $sid";

            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $pr['error'][] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        }

        $this->setMessageRedirect("edit_survey.php?sid=$sid&mode=properties");

        //Show success or failure message and redirect back to properties page.
        if(empty($pr['error']))
        { $this->setMessage($this->lang['notice'],$this->lang['properties_updated'],MSGTYPE_NOTICE); }
        else
        { $this->setMessage($this->lang['error'],implode(BR,$pr['error']),MSGTYPE_ERROR); }
    }

    // VALIDATE NEW PROPERTY DATA SUBMITTED BY USER //
    function _validateProperties($sid)
    {
        $input = array();
        $error = array();

        //Ensure survey name is supplied
        if(strlen($_REQUEST['name']) > 0)
        { $input['name'] = $this->SfStr->getSafeString($_REQUEST['name'],SAFE_STRING_DB); }
        else
        { $error[] = $this->lang['name_required']; }

        //Ensure valid template was chosen
        if(!empty($_REQUEST['template']))
        { $input['template'] = $this->SfStr->getSafeString(str_replace(array('\\','/'),'',$_REQUEST['template']),SAFE_STRING_DB); }
        else
        { $error[] = $this->lang['invalid_template']; }

        $today = mktime(0,0,0,date('m'),date('d'),date('Y'));

        //Ensure start and end dates are valid
        if(!empty($_REQUEST['start']))
        {
            $s = strtotime($_REQUEST['start'] . ' 00:00:01');
            if($s >= 0)
            { $input['start'] = $s; }
            else
            { $error[] = $this->lang['invalid_start_date']; }
        }
        else {$input['start'] = 0; }

        if(!empty($_REQUEST['end']))
        {
            $e = strtotime($_REQUEST['end'] . ' 23:59:59');
            if($e >= 0)
            { $input['end'] = $e; }
            else
            { $error[] = $this->lang['invalid_end_date']; }
        }
        else
        {$input['end'] = 0; }

        if($input['end'] < $input['start'])
        { $error[] = $this->lang['end_before_start']; }

        //Activate survey only if the survey has any questions. You can
        //no longer activate empty surveys.
        if($_REQUEST['active'] == 1)
        {
            $query = "SELECT COUNT(qid) AS c FROM {$this->CONF['db_tbl_prefix']}questions WHERE sid = $sid GROUP BY sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
            elseif($r = $rs->FetchRow($rs))
            { $input['active'] = 1; }
            else
            {
                $error[] = $this->lang['cannot_activate'];
                $input['active'] = 0;
            }
        }
        else
        { $input['active'] = 0; }

        //Validate survey and user text modes
        $input['survey_text_mode'] = (int)$_REQUEST['survey_text_mode'];
        if($input['survey_text_mode'] < 0 || $input['survey_text_mode'] > 2)
        { $error[] = $this->lang['invalid_survey_text_mode']; }

        $input['user_text_mode'] = (int)$_REQUEST['user_text_mode'];
        if($input['user_text_mode'] < 0 || $input['user_text_mode'] > 2)
        { $error[] = $this->lang['invalid_user_text_mode']; }

        //Validate date format
        if(!empty($_REQUEST['date_format']))
        { $input['date_format'] = $this->SfStr->getSafeString($_REQUEST['date_format'],SAFE_STRING_DB); }
        else
        { $input['date_format'] = $this->SfStr->getSafeString($this->CONF['date_format'],SAFE_STRING_ESC); }

        //Validate time limit for survey
        if(!empty($_REQUEST['time_limit']))
        { $input['time_limit'] = (int)$_REQUEST['time_limit']; }
        else
        { $input['time_limit'] = 0; }

        //validate redirection page to use after survey is completed
        if(!isset($_REQUEST['redirect_page']))
        { $error[] = $this->lang['invalid_complete_page']; }
        else
        {
            switch($_REQUEST['redirect_page'])
            {
                case 'index':
                case 'results':
                    $input['redirect_page'] = $this->SfStr->getSafeString($_REQUEST['redirect_page'],SAFE_STRING_DB);
                break;

                case 'custom':
                    if(empty($_REQUEST['redirect_page_text']))
                    { $error[] = $this->lang['invalid_custom_redirect']; }
                    else
                    { $input['redirect_page'] = $this->SfStr->getSafeString($_REQUEST['redirect_page_text'],SAFE_STRING_DB); }
                break;

                default:
                    $error[] = $this->lang['invalid_complete_page'];
                break;
            }
        }

        $retval = array('input'=>$input, 'error' => $error);

        return $retval;
    }

    // PROCESS EDIT QUESTION DATA //
    function _processEditQuestion($sid,$qid)
    {
        $sid = (int)$sid;
        $qid = (int)$qid;
        $error = array();

        //Validate new question data
        $this->data = $this->_validateEditQuestion();

        if(empty($this->data['error']))
        {
            //update question with new values
            $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET question = {$this->data['input']['question']}, aid = {$this->data['input']['aid']},
                      num_answers = {$this->data['input']['num_answers']}, num_required = {$this->data['input']['num_required']},
                      orientation = {$this->data['input']['orientation']} WHERE sid = $sid and qid = $qid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->data['error'][] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        }

        //Delete any checked dependencies
        if(isset($_REQUEST['edep_id']))
        {
            $er = $this->_processDeleteDependency($sid,$_REQUEST['edep_id']);
            if(!empty($er))
            { $this->data['error'] = array_merge($this->data['error'],$er); }
        }

        //check for and add new dependencies
        $dependencies = array();
        if(isset($_REQUEST['option']))
        { $dependencies = $this->_validateDependencyForExisting($_REQUEST,$qid); }

        // A dynamic question must have exactly 1 selector dependency
    	if(empty($this->data['error']))
        { $this->data['error'] = array_merge($this->data['error'], $this->_validateQuestionIfDynamic($dependencies, $qid, BY_QID)); }

        if(isset($_REQUEST['option']) && empty($this->data['error']))
        {
            $er = $this->_processAddDependency($sid,$qid, $dependencies);
            $this->data['error'] = array_merge($this->data['error'],$er);
        }

        //Set success or failure message and redirect to appropriate page
        if(empty($this->data['error']))
        {
            $this->setMessageRedirect("edit_survey.php?sid=$sid&mode=questions");
            $this->SetMessage($this->lang['notice'],$this->lang['question_edited'],MSGTYPE_NOTICE);
        }
        else
        {
            $this->setMessageRedirect("edit_survey.php?sid=$sid&qid=$qid&mode=edit_question&edit_question=1");
            $this->setMessage($this->lang['error'],implode(BR,$this->data['error']),MSGTYPE_ERROR);
        }
    }

    // VALIDATE DATA SUPPLIED TO EDITING QUESTION //
    function _validateEditQuestion()
    {
        $input = array();
        $error = array();

        //Validate text of question
        if(!empty($_REQUEST['question']))
        { $input['question'] = $this->SfStr->getSafeString($_REQUEST['question'],SAFE_STRING_DB); }
        else
        { $error[] = $this->lang['empty_question']; }

        //Ensure valid question ID was passed with form data
        if(empty($_REQUEST['qid']))
        { $error[] = $this->lang['no_choose_question']; }
        else
        { $input['qid'] = (int)$_REQUEST['qid']; }

        //Validate selected answer type
        if(empty($_REQUEST['answer']))
        { $error[] = $this->lang['no_answer_type']; }
        else
        { $input['aid'] = (int)$_REQUEST['answer']; }

        //Validate number of answers and number of answers required
        $input['num_answers'] = max(1,(int)@$_REQUEST['num_answers']);
        $input['num_required'] = max(0,(int)@$_REQUEST['num_required']);

        if($input['num_required'] > $input['num_answers'])
        { $error[] = $this->lang['to_many_required']; }

        //Validate orientation of question
        if(in_array($_REQUEST['orientation'],$this->CONF['orientation']))
        { $input['orientation'] = $this->SfStr->getSafeString($_REQUEST['orientation'],SAFE_STRING_DB); }
        else
        { $input['orientation'] = $this->lang['vertical']; }

        return(array('input'=>$input, 'error'=>$error));
    }

    // REMOVE DEPENDENCIES //
    function _processDeleteDependency($sid,$dep_id)
    {
        $error = array();

        //Loop through and delete any dependency IDs that are passed
        if(is_array($dep_id) && !empty($dep_id))
        {
            $id_list = '';
            foreach($dep_id as $id)
            { $id_list .= 'dep_id = ' . (int)$id . ' OR '; }
            $id_list = substr($id_list,0,-3);

            $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}dependencies WHERE sid = $sid AND ($id_list)";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $rror[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        }

        return $error;
    }

    // ADD DEPENDENCY TO QUESTION //
    /**
     * Add dependecies to a question.
     * @param int $sid Survey id of the survey the question belongs to.
     * @param mixed $qid int/bool Id of the edited question to which we add
     * the dependency(ies).
     * @param array $validated_dep The output of _validateDependency (it won't
     * be called again) or null if we should call it ourselves.
     *
     * @return array array of errors that occured or an empty array if none
     */
    function _processAddDependency($sid, $qid, $validated_dep = null)
    {
        $error = array();

		// Validate dependencies and dynamic question:
        if(is_null($validated_dep))
        { $validated_dep = $this->_validateDependencyForExisting($_REQUEST,$qid); }

        //Loop through any new dependencies passed from form. If dependency is based upon
        //a question on the same page, force the creation of a page break.
        if(empty($validated_dep['error']) && !empty($validated_dep['input']))
        {
            foreach($validated_dep['input']['dep_aid'] as $num=>$dep_aid_array)
            {
                foreach($dep_aid_array as $dep_aid)
                {
                    $dep_insert = '';
                    $dep_id = $this->db->GenID($this->CONF['db_tbl_prefix'].'dependencies_sequence');
                    $dep_insert = "($dep_id,$sid,$qid,{$validated_dep['input']['dep_qid'][$num]},{$dep_aid},{$validated_dep['input']['option'][$num]})";

                    $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}dependencies (dep_id, sid, qid, dep_qid, dep_aid, dep_option)
                            VALUES " . $dep_insert;
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                }
            }

            if(!empty($validated_dep['input']['dep_require_pagebreak']))
            {
                $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page + 1 WHERE sid = $sid AND
                        (page > {$validated_dep['input']['page']} OR (page = {$validated_dep['input']['page']} AND oid > {$validated_dep['input']['oid']}) OR qid = $qid)";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
            }
        }
        return array_merge($error,$validated_dep['error']);
    }

	/*
    // VALIDATE NEW DEPENDENCIES //
    function _validateDependency($sid,$qid)
    {
        $input = array();
        $error = array();

        if(isset($_REQUEST['option']) && is_array($_REQUEST['option']) && !empty($_REQUEST['option']))
        {
            foreach($_REQUEST['option'] as $num=>$option)
            {
                if(!empty($option))
                {
                    //Valide dependency option chosen (hide, require or show)
                    if(empty($option) || !in_array($option,array_keys($this->CONF['dependency_modes'])))
                    { $error[] = $this->lang['choose_dep_type']; }
                    else
                    { $input['option'][$num] = $this->SfStr->getSafeString($option,SAFE_STRING_DB); }

                    //Validate question ID to add depenency to
                    if(empty($_REQUEST['dep_qid'][$num]))
                    { $error[] = $this->lang['choose_dep_question']; }
                    else
                    { $input['dep_qid'][$num] = (int)$_REQUEST['dep_qid'][$num]; }

                    //Validate question ID to base dependency on
                    if(empty($_REQUEST['dep_aid'][$num]))
                    { $error[] = $this->lang['choose_dep_question2']; }
                    else
                    {
                        foreach($_REQUEST['dep_aid'][$num] as $dep_aid)
                        { $input['dep_aid'][$num][] = (int)$dep_aid; }
                    }

                    $input['dep_qid'][$num] = (int)$_REQUEST['dep_qid'][$num];

                    //Ensure question chosen to base new dependency on is before the question the dependency
                    //is being added to. If both are on the same page, set a flag to require a page break
                    //be added before the selected question.
                    $check_query = "SELECT q1.page, q1.oid, q2.page AS dep_page, q2.oid AS dep_oid
                                    FROM {$this->CONF['db_tbl_prefix']}questions q1, {$this->CONF['db_tbl_prefix']}questions q2
                                    WHERE q1.qid = $qid AND q2.qid = {$input['dep_qid'][$num]}";

                    $rs = $this->db->Execute($check_query);
                    if($rs === FALSE)
                    { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }

                    while($r = $rs->FetchRow($rs))
                    {
                        if($r['dep_page'] > $r['page'] || ($r['dep_page'] == $r['page'] && $r['dep_oid'] > $r['oid']))
                        { $error[] = $this->lang['dep_order_error']; }
                        elseif($r['page'] == $r['dep_page'])
                        {
                            $input['dep_require_pagebreak'] = 1;
                            $input['page'] = $r['page'];
                            $input['oid'] = $r['oid'];
                        }
                    }
                }
            }
        }
        return(array('input'=>$input, 'error'=>$error));
    }
    */


    // VALIDATE NEW DEPENDENCIES //
    /** See _validateDependency; for an existing question. */
    function _validateDependencyForExisting(&$request, $qid)
    { return $this->_validateDependency($request, $qid); }

    /** See _validateDependency; for a new question. */
    function _validateDependencyForNew(&$request, $question_page, $question_oid, $aid)
    { return $this->_validateDependency($request, null,$question_page,  $question_oid, $aid); }
    /**
     * Validate new dependencies and prepare the data for insertion.
     * Any errors are put to the array function's result['error'].
     * If the question is dynamic, check that it has exactly one selector
     * dependency.
     * @param array $request either _REQUEST or _POST - contains the data of the
     * new submitted dependency.
     * @param mixed $qid int/bool Id of the edited question to which we add
     * the dependency(ies) or NULL if qid is not available (e.g. when adding a
     * new question).
     * @param int $question_page Page of the edited question to which we add
     * the dependency(ies) or NULL if qid is given (we can find page having
     * qid).
     * @param int $question_oid Oid of the edited question to which we add the
     * dependency(ies) or NULL if qid is given (we can find page having qid).
     *
     * @param boolean $aid id of the question's answer type.
     *
     * @return array array('input'=>(copy of data from the request[or empty]),
     * 'error'=>(array of errors that occured[or empty]))
     */
    function _validateDependency(&$request, $qid, $question_page = null, $question_oid = null, $aid = null)
    {
        $input = array();
        $error = array();

        // FIXME: 1. check that a selector dependency is only added to a dynamic ans.type question
        //		2. check that a dependency on a text question (S, T) is only used as a selector depend.
        $selector_deps_count = 0;	// number of selector dependencies of the question

        // Check parameters
        if(is_null($qid) && (is_null($question_page) || is_null($question_oid) || is_null($aid)))
        {
        	$error[] = 'PHP programming error - _validateDependency: either $qid or ($question_page and $question_oid and $aid) must be non null.';
        	return(array('input'=>$input, 'error'=>$error));
        }

		// Validate dependency
        if(isset($request['option']) && is_array($request['option']) && !empty($request['option']))
        {
            foreach($request['option'] as $num=>$option)
            {
                if(!empty($option))
                {
                    //Valide dependency option chosen (hide, require or show)
                    if(empty($option) || !in_array($option,array_keys($this->CONF['dependency_modes'])))
                    { $error[] = $this->lang('choose_dep_type'); }
                    else
                    { $input['option'][$num] = $this->SfStr->getSafeString($option,SAFE_STRING_DB); }

                    //Validate question ID to add depenency to
                    if(empty($request['dep_qid'][$num]))
                    { $error[] = $this->lang('choose_dep_question'); }
                    else
                    { $input['dep_qid'][$num] = (int)$request['dep_qid'][$num]; }

                    //Validate question ID to base dependency on
                    if(empty($request['dep_aid'][$num]))
                    { $error[] = $this->lang('choose_dep_question2'); }
                    else
                    {
                        foreach($request['dep_aid'][$num] as $dep_aid)
                        { $input['dep_aid'][$num][] = (int)$dep_aid; }
                    }

                    $input['dep_qid'][$num] = (int)$request['dep_qid'][$num];

                    //Ensure question chosen to base new dependency on is before the question the dependency
                    //is being added to. If both are on the same page, set a flag to require a page break
                    //be added before the selected question.
                    $prefix = $this->CONF['db_tbl_prefix'];
                    if(!is_null($qid))
                    {
	                    $check_query = "SELECT q1.page, q1.oid, at1.is_dynamic, q2.page AS dep_page, q2.oid AS dep_oid, at2.type AS dep_type
	                                    FROM {$prefix}questions q1 JOIN {$prefix}answer_types at1 ON (at1.aid = q1.aid),
	                                    {$prefix}questions q2 JOIN {$prefix}answer_types at2 ON (at2.aid = q2.aid)
	                                    WHERE q1.qid = $qid AND q2.qid = {$input['dep_qid'][$num]}";
                    } else {
                    	$check_query = "SELECT $question_page AS page, $question_oid AS oid, q2.page AS dep_page, q2.oid AS dep_oid
                    					FROM {$this->CONF['db_tbl_prefix']}questions q2
                    					WHERE q2.qid = {$input['dep_qid'][$num]}";
                    }

                    $rs = $this->db->Execute($check_query);
                    if($rs === FALSE)
                    { $error[] = $this->lang('db_query_error') . $this->db->ErrorMsg(); }

                    while($r = $rs->FetchRow($rs))
                    {
                    	// Check order
                        if($r['dep_page'] > $r['page'] || ($r['dep_page'] == $r['page'] && $r['dep_oid'] > $r['oid']))
                        { $error[] = $this->lang('dep_order_error'); }
                        elseif($r['page'] == $r['dep_page'])
                        {
                            $input['dep_require_pagebreak'] = 1;
                        }
                        else
                        { $input['dep_require_pagebreak'] = 0; }

                        $input['page'] = $r['page'];
                        $input['oid'] = $r['oid'];

                        // Check correct combinations of dependency mode - question type:
                        // Only a dynamic question may have a selector dependency
                        if( ($option == DEPEND_MODE_SELECTOR))
                        {
                        	if(!$r['is_dynamic'])
                        	{ $error[] = $this->lang('err.nondynamic_selector_dep'); }
                        	else
                        	{
                        		if(!isset($input['count_selector_deps']))
                        		{ $input['count_selector_deps'] = 1; }
                        		else
                        		{ $input['count_selector_deps'] = 1 + $input['count_selector_deps']; };
                        	}

                        }
                        // Only selector dependency may be bound to a textual answer (S, T)
                        if(($option != DEPEND_MODE_SELECTOR) && ($r['dep_type'] == ANSWER_TYPE_S))
                        { $error[] = $this->lang('err.dep_on_textual_answer') . $option; }
                    }
                }
            }
        }

        return(array('input'=>$input, 'error'=>$error));
    }

    // LOAD EXISTING PROPERTIES FOR A SURVEY //
    function _loadProperties($sid)
    {
        $sid = (int)$sid;

        //load survey properties and set default values
        $query = "SELECT sid, name, start_date, end_date, active,
                  template, redirect_page, survey_text_mode, user_text_mode, created, date_format, time_limit FROM
                  {$this->CONF['db_tbl_prefix']}surveys WHERE sid = $sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        elseif($r = $rs->FetchRow($rs))
        {
            $this->data['name'] = $this->SfStr->getSafeString($r['name'],SAFE_STRING_TEXT);

            $this->data['date_format'] = $this->SfStr->getSafeString($r['date_format'],SAFE_STRING_TEXT);
            $this->data['created'] = $this->SfStr->getSafeString(date($this->CONF['date_format'],$r['created']),SAFE_STRING_TEXT);
            $this->data['time_limit'] = $this->SfStr->getSafeString($r['time_limit'],SAFE_STRING_TEXT);

            if($r['active'] == 1)
            { $this->data['active_selected'] = FORM_CHECKED; }
            else
            { $this->data['inactive_selected'] = FORM_CHECKED; }

            if($r['start_date'] == 0)
            { $this->data['start_date'] = ''; }
            else
            { $this->data['start_date'] = strtoupper(date('Y-m-d',$r['start_date'])); }

            if($r['end_date'] == 0)
            { $this->data['end_date'] = ''; }
            else
            { $this->data['end_date'] = strtoupper(date('Y-m-d',$r['end_date'])); }

            switch($r['redirect_page'])
            {
                case 'index':
                case '':
                    $this->data['redirect_index'] = FORM_CHECKED;
                break;
                case 'results':
                    $this->data['redirect_results'] = FORM_CHECKED;
                break;
                default:
                    $this->data['redirect_custom'] = FORM_CHECKED;
                    $this->data['redirect_page_text'] = $this->SfStr->getSafeString($r['redirect_page'],SAFE_STRING_TEXT);
                break;
            }

            //Set arrays for holding text mode values, options, and selected element to
            //create drop down boxes
            $survey_text_mode = array_slice($this->CONF['text_modes'],0,$this->CONF['survey_text_mode']+1);
            $this->data['survey_text_mode_values'] = array_values($survey_text_mode);
            $this->data['survey_text_mode_options'] = array_keys($survey_text_mode);
            $this->data['survey_text_mode_selected'][$r['survey_text_mode']] = FORM_SELECTED;

            $user_text_mode = array_slice($this->CONF['text_modes'],0,$this->CONF['user_text_mode']+1);
            $this->data['user_text_mode_values'] = array_values($user_text_mode);
            $this->data['user_text_mode_options'] = array_keys($user_text_mode);
            $this->data['user_text_mode_selected'][$r['user_text_mode']] = FORM_SELECTED;

            if(in_array(2,$this->data['survey_text_mode_options']) || in_array(2,$this->data['user_text_mode_options']))
            { $this->data['show']['fullhtmlwarning'] = TRUE; }

            $dh = opendir($this->CONF['path'] . '/templates');
            while($file = readdir($dh))
            {
                if($file != '.' && $file != '..')
                {
                    $this->data['templates'][] = $this->SfStr->getSafeString($file,SAFE_STRING_TEXT);
                    if($r['template'] == $file)
                    { $this->data['selected_template'][] = FORM_SELECTED; }
                    else
                    { $this->data['selected_template'][] = ''; }
                }
            }

            asort($this->data['templates']);	// preserve the correspondence of keys to values!
        }
        else
        { $this->error($this->lang['survey_not_exist']); return; }
    }

    // LOAD EXISTING QUESTIONS FOR SURVEY //
    function _loadQuestions($sid)
    {
        $sid = (int)$sid;
        $this->data['mode_edit_question'] = MODE_EDITQUESTION;
        $this->data['mode_new_question'] = MODE_QUESTIONS;

        $this->data['sid'] = $sid;

        if(!empty($_SESSION['add_question']))
        {
            $_POST = $_SESSION['add_question'];
            // $this->print_array($_POST); // debug only?
            unset($_SESSION['add_question']);
        }

        //load all questions for this survey
        $query = "SELECT q.qid, q.aid, q.question, q.page, a.type, q.oid, s.survey_text_mode
                  FROM {$this->CONF['db_tbl_prefix']}questions q,
                  {$this->CONF['db_tbl_prefix']}answer_types a, {$this->CONF['db_tbl_prefix']}surveys s
                  WHERE q.aid = a.aid and q.sid = $sid AND q.sid = s.sid order by q.page, q.oid, a.aid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        $page = 1;
        $x = 0;
        $q_num = 1;
        $label_num = 1;
        $num_demographics = 0;
        $this->data['answer'] = array();
        $this->data['show']['dep'] = TRUE;

        if($r = $rs->FetchRow($rs))
        {
            $survey_text_mode = $r['survey_text_mode'];
            do
            {
                //Load data for each question into the $data array
                while($page != $r['page'])
                {
                    $this->data['qid'][$x] = $r['page'];
                    $this->data['question'][$x] = $this->CONF['page_break'];
                    $this->data['qnum'][$x] = NBSP;
                    $this->data['page_break'][$x] = TRUE;
                    $this->data['show_dep'][$x] = FALSE;
                    $x++;
                    $page += 1;
                }
                $this->data['qid'][$x] = $r['qid'];
                $this->data['question'][$x] = $this->SfStr->getSafeString($r['question'],$survey_text_mode);

                if($r['type'] != ANSWER_TYPE_N)
                { $this->data['qnum'][$x] = $q_num++; }
                else
                { $this->data['qnum'][$x] = 'L'.$label_num++; }

                // Prepare possible answers to question we depend upon
				$this->_prepare_data4dependencies($r, $this->data['qnum'][$x]);


                $this->data['page_oid'][] = $r['page'] . '-' . $r['oid'];
                $this->data['qnum2'][] = $this->data['qnum'][$x];
                $this->data['qnum2_selected'][] = '';

                $this->data['show_edep'][$x] = FALSE;

                $x++;

            }while($r = $rs->FetchRow($rs));

            //load dependencies for current survey (note: selector dependencies haven't a valid avid)
            $query = "SELECT d.qid, d.dep_qid, av.value, d.dep_option FROM {$this->CONF['db_tbl_prefix']}dependencies d
                      LEFT JOIN {$this->CONF['db_tbl_prefix']}answer_values av ON (d.dep_aid = av.avid) WHERE d.sid = $sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

            while($r = $rs->FetchRow($rs))
            {
                // __hide__ if question __xx__ is __a,b,c__
                $x = array_search($r['qid'],$this->data['qid']);		// index of the qid in data[]
                $key = array_search($r['dep_qid'],$this->data['qid']);	// index of the dep_qid in data[]
                $qnum = $this->data['qnum'][$key];
                $answer_value = isset($r['value'])? $this->SfStr->getSafeString($r['value'],$survey_text_mode) : "";

	            // A selector dependency is satisfied for any answer:
	            if($r['dep_option'] === DEPEND_MODE_SELECTOR)
	            { $answer_value = $this->lang('whatever'); }

                $this->data['show_edep'][$x] = TRUE;
                $option = isset($this->CONF['dependency_modes'][$r['dep_option']])?
            		$this->CONF['dependency_modes'][$r['dep_option']] : $r['dep_option'];
            	$option = $this->SfStr->getSafeString($option,$survey_text_mode);
                if(isset($this->data['edep_value'][$x]) && in_array($qnum,$this->data['edep_qnum'][$x]))
                {
                    $key2 = array_search($qnum,$this->data['edep_qnum'][$x]);

                    if($this->data['edep_option'][$x][$key2] == $option)
                    { $this->data['edep_value'][$x][$key2] .= ', ' . $answer_value; }
                    else
                    {
                        $this->data['edep_option'][$x][] = $option;
                        $this->data['edep_value'][$x][] = $answer_value;
                        $this->data['edep_qnum'][$x][] = $qnum;
                    }
                }
                else
                {
                    $this->data['edep_option'][$x][] = $option;
                    $this->data['edep_value'][$x][] = $answer_value;
                    $this->data['edep_qnum'][$x][] = $qnum;
                }
            }
        }
        else
        { $this->data['show']['dep'] = FALSE; }

        //Create javascript to fill <select> boxes when creating dependencies
        $this->_prepare_js4dependencies();

        //Set "insert question after..." select box to selected element or last element
        if(isset($_POST['insert_after']))
        {
            $key = array_search($_POST['insert_after'], $this->data['page_oid']);
            if($key !== FALSE)
            {
                $this->data['qnum2_selected'][$key] = FORM_SELECTED;
            }
        }
        elseif(isset($this->data['qnum2_selected']))
        { $this->data['qnum2_selected'][count($this->data['qnum2_selected'])-1] = FORM_SELECTED; }

        $this->data['num_answers'] = array();
        for($i=1;$i<=NUM_ANSWERS;$i++)
        { $this->data['num_answers'][] = $i; }
        $this->data['num_answers_selected'] = array_fill(0,NUM_ANSWERS,'');
        if(isset($_POST['num_answers']))
        { $this->data['num_answers_selected'][(int)$_POST['num_answers']-1] = FORM_SELECTED; }

        $this->data['num_required'] = array();
        for($i=0;$i<NUM_ANSWERS+1;$i++)
        { $this->data['num_required'][] = $i; }
        $this->data['num_required_selected'] = array_fill(0,NUM_ANSWERS+1,"");
        if(isset($_POST['num_required']))
        { $this->data['num_required_selected'][(int)$_POST['num_required']] = FORM_SELECTED; }

        if(!empty($_POST['question']))
        { $this->data['new_question'] = $this->SfStr->getSafeString($_POST['question'],SAFE_STRING_TEXTAREA); }

        //retrieve answer types from database
        $rs = $this->db->Execute("SELECT aid, name FROM {$this->CONF['db_tbl_prefix']}answer_types
                                  WHERE sid = $sid ORDER BY name ASC");
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        while ($r = $rs->FetchRow($rs))
        {
            $r['name'] = $this->SfStr->getSafeString($r['name'],SAFE_STRING_TEXT);
            if(isset($_POST['answer']) && $_POST['answer'] == $r['aid'])
            { $r['selected'] = FORM_SELECTED; }
            $this->data['answer'][] = $r;
        }

        if(isset($_SESSION['answer_orientation']))
        {
            $key = array_search($_SESSION['answer_orientation'],$this->CONF['orientation']);
            $this->data['orientation']['selected'][$key] = FORM_SELECTED;
        }

		// Dependencies: prepare the flag 'selected' for the selected option/qid
        for($x=1;$x<=3;$x++)
        {
            if(isset($_POST['option'][$x]) && in_array($_POST['option'][$x], array_keys($this->CONF['dependency_modes'])))
            {
                $key = $_POST['option'][$x]; //$this->CONF['dependency_modes'] contains labels indexed by keys like 'Hide'
                $this->data['option_selected'][$x-1][$key] = FORM_SELECTED; // FIXME: key: not num but DEPEND_MODE_*
            }
            if(isset($_POST['dep_qid'][$x]))
            {
            	// FIXME: this marks the qid as selected but won' trigger populate() and so
            	// the select with possible answers will be empty !!!
                $key = array_search($_POST['dep_qid'][$x], $this->data['dep_qid']);
                if($key !== FALSE)
                { $this->data['dep_qid_selected'][$x-1][$key] = FORM_SELECTED; }
            }
        }

        // $this->print_array($this->data['option_selected']); // debug only
        // $this->print_array($this->data['dep_qid_selected']); // debug only
    }

    // PROCESS MOVING A QUESTION  UP OR DOWN IN THE LIST //
    function _processMoveQuestion($sid,$qid,$move)
    {
        $sid = (int)$sid;
        $qid = (int)$qid;
        $error = array();

        //Get page and oid for requested question
        $query = "SELECT page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE qid = $qid AND sid = $sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        elseif($r = $rs->FetchRow($rs))
        {
            switch($move)
            {
                //Move question and redirect back to questions page
                case MOVE_UP:
                    $error = $this->_processMoveQuestionUp($sid,$qid,$r['page'],$r['oid']);
                break;
                case MOVE_DOWN:
                    $error = $this->_processMoveQuestionDown($sid,$qid,$r['page'],$r['oid']);
                break;
            }
        }
        else
        { $error[] = $this->lang['invalid_move']; }

        $this->setMessageRedirect("edit_survey.php?sid=$sid&mode=questions");

        if(empty($error))
        { $this->setMessage($this->lang['notice'],$this->lang['question_moved'],MSGTYPE_NOTICE); }
        else
        { $this->setMessage($this->lang['error'],implode(BR,$error),MSGTYPE_ERROR); }
    }

    // MOVE QUESTION UP IN LIST //
    function _processMoveQuestionUp($sid,$qid,$page,$oid)
    {
        $error = array();

        //Get question, page, and oid of question directly "above"
        //the question being moved up.
        $query = "SELECT qid, page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE sid = $sid AND
                  ((page = {$page} AND oid < {$oid}) OR page < {$page}) AND page > 0
                  ORDER BY page DESC, oid DESC";
        $rs2 = $this->db->SelectLimit($query,1);
        if($rs2 === FALSE)
        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        elseif($row2 = $rs2->FetchRow($rs2))
        {
            //If question being moved up is passing page boundary, just
            //reduce the page number by one and set oid to one more than
            //oid of previous question retrieved
            if($page != $row2['page'])
            {
                //Check to see if there are any questions on the previous
                //page that the question being moved is dependant upon
                $query = "SELECT COUNT(*) AS c FROM {$this->CONF['db_tbl_prefix']}dependencies d, {$this->CONF['db_tbl_prefix']}questions q
                          WHERE q.page = {$row2['page']} AND d.qid = $qid AND d.dep_qid = q.qid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                $r = $rs->FetchRow($rs);

                if($r['c'] == 0)
                {

                    $oid2 = $row2['oid'] + 1;
                    $swap_query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page - 1, oid = $oid2 WHERE qid = $qid";
                    $swap_result = $this->db->Execute($swap_query);
                    if($swap_result === FALSE)
                    { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                }
                else
                { $error[] = $this->lang['move_question_dep']; }
            }
            else
            {
                //Otherwise just swap page and oids of the two questions
                $swap_query1 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = {$row2['page']}, oid = {$row2['oid']} WHERE qid = $qid";
                $swap_query2 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = {$row2['page']}, oid = {$oid} WHERE qid = {$row2['qid']}";
                $swap_result1 = $this->db->Execute($swap_query1);
                $swap_result2 = $this->db->Execute($swap_query2);
                if($swap_result1 === FALSE || $swap_result2 === FALSE)
                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
            }
        }
        else
        { $error[] = $this->lang['move_question_begin']; }

        return $error;
    }

    // MOVE QUESTION DOWN IN LIST //
    function _processMoveQuestionDown($sid,$qid,$page,$oid)
    {
        $error = array();

        //Get data for question "below" question being moved
        $query = "SELECT qid, page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE sid = $sid AND
                  ((page = {$page} AND oid > {$oid}) OR page > {$page})
                  ORDER BY page ASC, oid ASC";
        $rs2 = $this->db->SelectLimit($query,1);
        if($rs2 === FALSE)
        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        elseif($row2 = $rs2->FetchRow($rs2))
        {
            if($page != $row2['page'])
            {
                //Check to see if there are questions on the next page
                //that have dependencies based upon the question being moved
                $query = "SELECT COUNT(*) AS c FROM {$this->CONF['db_tbl_prefix']}dependencies d, {$this->CONF['db_tbl_prefix']}questions q
                          WHERE q.page = {$row2['page']} AND q.qid = d.qid AND d.dep_qid = $qid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                $r = $rs->FetchRow($rs);

                if($r['c'] == 0)
                {
                    $page2 = $page + 1;
                    $swap_query1 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET oid = oid + 1 WHERE page = $page2 AND sid = $sid";
                    $swap_query2 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = $page2, oid = 1 WHERE qid = $qid";
                    $swap_result1 = $this->db->Execute($swap_query1);
                    $swap_result2 = $this->db->Execute($swap_query2);
                    if($swap_result1 === FALSE || $swap_result2 === FALSE)
                    { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                }
                else
                { $error[] = $this->lang['move_question_dep2']; }
            }
            else
            {
                $swap_query1 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = {$row2['page']}, oid = {$row2['oid']} WHERE qid = $qid";
                $swap_query2 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = {$row2['page']}, oid = {$oid} WHERE qid = {$row2['qid']}";
                $swap_result1 = $this->db->Execute($swap_query1);
                $swap_result2 = $this->db->Execute($swap_query2);
                if($swap_result1 === FALSE || $swap_result2 === FALSE)
                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
            }
        }
        else
        { $error[] = $this->lang['move_question_end']; }

        return $error;
    }

    // PROCESSING ADDITION OF NEW QUESTION //
    function _processAddQuestion($sid)
    {
        $sid = (int)$sid;
        $error = array();
        $notice = '';

        //Ensure new question is not blank
        if(strlen($_POST['question']) == 0)
        { $error[] = $this->lang['empty_question']; }
        else
        {
            //Determine what question to insert new question after
            $x = explode('-',$_POST['insert_after']);
            $page = (int)$x[0];
            $oid = (int)$x[1];

            if(strcasecmp($_POST['question'],$this->CONF['page_break'])==0)
            {
                //Set error if there is an attempt to make a page break the first question in the survey
                if($page == 0 && $oid == 0)
                { $error[] = $this->lang['page_break_first']; }
                else
                {
                    $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page + 1 WHERE sid = $sid AND
                              ((page > $page) OR (page = $page AND oid > $oid))";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                    elseif($this->db->Affected_Rows() > 0)
                    { $notice = $this->lang['page_break_inserted']; }
                    else
                    { $error[] = $this->lang['page_break_end']; }
                }
            }
            else
            {
                //Make sure "first" question is page 1, oid 1,
                //not page 0, oid 0.
                if($page == 0) { $page=1; }

                $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET oid = oid + 1 WHERE sid = $sid AND page = $page AND
                          oid > $oid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }

                //Increment oid, since new question is
                //inserted "after" what was chosen
                //Validate number of answers, number required and orientation for new question
                $oid++;
                $question = $this->SfStr->getSafeString($_POST['question'],SAFE_STRING_DB);
                $num_answers = (int)$_POST['num_answers'];
                $num_required = (int)$_POST['num_required'];
                $aid = (int)$_REQUEST['answer'];

                if($num_required > $num_answers)
                { $error[] = $this->lang('to_many_required'); }

                if(in_array($_POST['orientation'],$this->CONF['orientation']))
                { $orientation = $this->SfStr->getSafeString($_POST['orientation'],SAFE_STRING_DB); }
                else
                { $orientation = $this->SfStr->getSafeString($this->lang['vertical'],SAFE_STRING_DB); }

                $_SESSION['answer_orientation'] = $_POST['orientation'];

                //If there is no error so far, attempt to process the requested dependencies
                if(empty($error))
                {
                	// Validate dependencies and dynamic question
                	$dependencies = $this->_validateDependencyForNew($_POST, $page, $oid, $aid);
                	$error = array_merge($error, $dependencies['error']);
	                // $dep_insert = array();

	                /*
                    $dep_insert = array();
                    $dep_require_pagebreak = 0;

                    //check for dependencies
                    if(isset($_POST['option']))
                    {
                        foreach($_POST['option'] as $num=>$option)
                        {
                            if(!empty($option) && !empty($_REQUEST['dep_qid'][$num]) && !empty($_POST['dep_aid'][$num])
                               && in_array($option,array_keys($this->CONF['dependency_modes'])))
                            {
                                $dep_qid = (int)$_POST['dep_qid'][$num];

                                //Ensure dependencies are based on questions before the question being added
                                $check_query = "SELECT page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE qid = $dep_qid";

                                $rs = $this->db->Execute($check_query);
                                if($rs === FALSE)
                                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }

                                while($r = $rs->FetchRow($rs))
                                {
                                    if($r['page'] > $page || ($r['page'] == $page && $r['oid'] > $oid))
                                    { $error[] = $this->lang['dep_order_error']; }
                                    elseif($r['page'] == $page)
                                    { $dep_require_pagebreak = 1; }
                                }

                                $option = $this->SfStr->getSafeString($option,SAFE_STRING_DB);

                                foreach($_POST['dep_aid'][$num] as $dep_aid)
                                {
                                    $dep_id = $this->db->GenID($this->CONF['db_tbl_prefix'].'dependencies_sequence');
                                    // %% will be later replaced by qid
                                    $dep_insert[] = "($dep_id,$sid,%%,$dep_qid," . (int)$dep_aid . ",$option) ";
                                }
                            }
                        }
                    }
                    //*/

                    //If no error has occurred, attempt to create new question in database
                    if(empty($error))
                    {
                        //Insert question data into database
                        $qid = $this->db->GenID($this->CONF['db_tbl_prefix'].'questions_sequence');
                        $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}questions (qid, sid, question, aid, num_answers, num_required, page, oid, orientation)
                                  VALUES ($qid, $sid, $question, $aid, $num_answers, $num_required, $page, $oid, $orientation)";
                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                        else
                        {
                            //Create dependencies in database and create page break, if required
                            $dep_error = $this->_processAddDependency($sid, $qid, $dependencies);
                            $error = array_merge($error, $dep_error);

                            /*if(!empty($dep_insert))
                            {
                                $dep_query_start = "INSERT INTO {$this->CONF['db_tbl_prefix']}dependencies (dep_id,sid,qid,dep_qid,dep_aid,dep_option) VALUES ";
                                // Insert each dependency; if one fails try the others anyway
                                foreach($dep_insert as $single_dependency)
                                {
                                	$single_dependency = str_replace('%%',$qid,$single_dependency);
                                	$dep_query = $dep_query_start . $single_dependency;
                                	$rs = $this->db->Execute($dep_query);

                                if($rs === FALSE)
                                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                                } // insert each dependency

                                if($dep_require_pagebreak)
                                {
                                    $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page + 1 WHERE sid = $sid AND
                                              (page > $page OR (page = $page AND oid > $oid) OR qid = $qid)";
                                    $rs = $this->db->Execute($query);
                                    if($rs === FALSE)
                                    { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                                }
                            }*/

                            $notice = $this->lang['question_added'];
                        }
                    }
                }
            }
        }

        //Set error or success message and redirect to appropriate page
        $this->setMessageRedirect("edit_survey.php?sid=$sid&mode=questions");

        if(empty($error))
        {
            if(empty($notice))
            { $notice = $this->lang['question_added']; }
            $this->setMessage($this->lang['notice'],$notice,MSGTYPE_NOTICE);
        }
        else
        {
            $_SESSION['add_question'] = $_POST;
            $this->setMessage($this->lang['error'],implode(BR,$error),MSGTYPE_ERROR);
        }
    }

    // LOAD EXISTING DATA FOR QUESTION BEING EDITED //
    function _loadEditQuestion($sid,$qid)
    {
        $sid = (int)$sid;
        $qid = (int)$qid;

        $error = array();

        $this->data['qid'] = $qid;
        $this->data['sid'] = $sid;

        //Retrieve Question data
        $query = "SELECT q.question, q.aid, q.num_answers, q.num_required, q.page, q.oid, q.orientation, s.survey_text_mode
                  FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}surveys s
                  WHERE q.sid = $sid AND q.sid = s.sid AND qid = $qid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        $this->data['question_data'] = $rs->FetchRow($rs);
        $this->data['question_data']['question'] = $this->SfStr->getSafeString($this->data['question_data']['question'],SAFE_STRING_TEXTAREA);

        $key = array_search($this->data['question_data']['orientation'],$this->CONF['orientation']);
        if($key !== FALSE)
        { $this->data['orientation']['selected'][$key] = FORM_SELECTED; }

        $this->data['num_answers'] = array();
        for($i=1;$i<=NUM_ANSWERS;$i++)
        { $this->data['num_answers'][] = $i; }
        $this->data['num_answers_selected'] = array_fill(0,NUM_ANSWERS,'');
        $this->data['num_answers_selected'][$this->data['question_data']['num_answers']-1] = FORM_SELECTED;

        $this->data['num_required'] = array();
        for($i=0;$i<=NUM_ANSWERS+1;$i++)
        { $this->data['num_required'][] = $i; }
        $this->data['num_required_selected'] = array_fill(0,NUM_ANSWERS+1,'');
        $this->data['num_required_selected'][$this->data['question_data']['num_required']] = FORM_SELECTED;

        //Retrieve Answer Types from database
        $rs = $this->db->Execute("SELECT aid, name FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE sid = $sid ORDER BY name ASC");
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        while ($r = $rs->FetchRow($rs))
        {
            if($r['aid'] == $this->data['question_data']['aid'])
            { $r['selected'] = FORM_SELECTED; }
            $r['name'] = $this->SfStr->getSafeString($r['name'],SAFE_STRING_TEXT);
            $this->data['answer'][] = $r;
        }

        /*/Retrieve existing question numbers
        //for questions BEFORE this one being edited
        //and create Javascript for dependency <select> boxes	// FIXME: unify with loadQuestions
        $query = "SELECT q.qid, at.type, av.avid, av.value FROM {$this->CONF['db_tbl_prefix']}questions q,
                  {$this->CONF['db_tbl_prefix']}answer_types at LEFT JOIN {$this->CONF['db_tbl_prefix']}answer_values av
                  ON at.aid = av.aid WHERE q.sid = $sid AND
                  (q.page < {$this->data['question_data']['page']} OR (q.page = {$this->data['question_data']['page']} AND q.oid < {$this->data['question_data']['oid']}))
                  AND q.aid = at.aid ORDER BY page ASC, oid ASC";


        $question_count = 1;
        $av_count = 0;
        $old_qid = '';
        $this->data['js'] = '';
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return;}
        if($r = $rs->FetchRow($rs))
        {
            do
            {
                if($r['type'] != ANSWER_TYPE_N)
                {	// BEGIN: TODO: replace by calls to _prepare_data4dependencies nad _prepare_js4dependencies
                    if($r['type'] != ANSWER_TYPE_S && $r['type'] != ANSWER_TYPE_T)
                    {
                        if($r['qid'] != $old_qid)
                        {
                            if($av_count)
                            { $this->data['js'] .= "Num_Answers['{$old_qid}'] = '{$av_count}';\n"; }

                            $av_count = 0;
                            $this->data['qnum'][$r['qid']] = $question_count;
                            $old_qid = $r['qid'];

                        }

                        $this->data['js'] .= "Answers['{$r['qid']},{$av_count}'] = '{$r['avid']}';\n";
                        $this->data['js'] .= "Values['{$r['qid']},{$av_count}'] = '" . addslashes($r['value']) . "';\n";

                        $av_count++;

                    }
                    ++$question_count;
                } // END
            }while($r = $rs->FetchRow($rs));

            $this->data['js'] .= "Num_Answers['{$old_qid}'] = '{$av_count}';\n";

            if(!empty($this->data['qnum']))
            {
                $this->data['dep_qid'] = array_keys($this->data['qnum']);
                $this->data['dep_qnum'] = array_values($this->data['qnum']);
            }
        }//*/
        //load all questions for this survey
        $query = "SELECT q.qid, q.aid, q.page, a.type, q.oid
                  FROM {$this->CONF['db_tbl_prefix']}questions q,
                  {$this->CONF['db_tbl_prefix']}answer_types a
                  WHERE q.aid = a.aid AND q.sid = $sid
                  AND (q.page < {$this->data['question_data']['page']} OR (q.page = {$this->data['question_data']['page']} AND q.oid < {$this->data['question_data']['oid']}))
                  ORDER BY q.page, q.oid, a.aid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        $page = 1;
        $q_num = 1;
        $label_num = 1;

        while($r = $rs->FetchRow($rs))
        {
            //Load data for each question into the $data array
            while($page != $r['page'])
            { $page++; }

            if($r['type'] != ANSWER_TYPE_N)
            { $this->data['qnum'][$r['qid']] = $q_num++; }
            else
            { $this->data['qnum'][$r['qid']] = 'L'.$label_num++; }

            // Prepare possible answers to question we depend upon
			$this->_prepare_data4dependencies($r, $this->data['qnum'][$r['qid']]);
        }

        $this->_prepare_js4dependencies();
        //  $this->data['qnum'][$r['qid']] = $question_count;

        //Retrieve existing dependencies for question
        $this->data['dependencies'] = array();
        $query = "SELECT d.dep_id, d.qid, d.dep_qid, d.dep_option, av.value FROM {$this->CONF['db_tbl_prefix']}dependencies d
                      LEFT JOIN {$this->CONF['db_tbl_prefix']}answer_values av ON (d.dep_aid = av.avid) WHERE d.qid = $qid";

        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

        while($r = $rs->FetchRow($rs))
        {
            $this->data['edep']['dep_id'][] = $r['dep_id'];
            $option = isset($this->CONF['dependency_modes'][$r['dep_option']])?
            	$this->CONF['dependency_modes'][$r['dep_option']] : $r['dep_option'];
            $option = $this->SfStr->getSafeString($option,SAFE_STRING_TEXT);
            $this->data['edep']['option'][] = $option;
            $this->data['edep']['qnum'][] = $this->data['qnum'][$r['dep_qid']];
            $answer_value = isset($r['value'])? $this->SfStr->getSafeString($r['value'],$this->data['question_data']['survey_text_mode']) : "???";
            // A selector dependency is satisfied for any answer:
            if($r['dep_option'] === DEPEND_MODE_SELECTOR)
            { $answer_value = $this->lang('whatever'); }
            $this->data['edep']['value'][] = $answer_value;
        }
    }

	/**
	 * Prepare arrays of possible answers to questions a question may depend
	 * upon. Used later to construct the JavaScript that presents them to user
	 * when defining a new dependency.
	 * Modifies $this->data['dep_avid'] and $this->data['dep_value'].
	 * @param array $row Array of an elements of answer_types JOINed with
	 * answer_values. It must contain the keys: qid, aid
	 * @param string $question_label Label for the given qid (used in the
	 * select tag of questions to depend upon.)
	 * @access private
	 */
	function _prepare_data4dependencies(&$row, $question_label)
	{
		$can_depend = true;	// Is it possible to depend on the given question?

		switch($row['type'])
		{
			case ANSWER_TYPE_N:
				return; // break;

			case ANSWER_TYPE_MS:
			case ANSWER_TYPE_MM:
	            //Retrieve answer value in SAFE_STRING_JAVASCRIPT mode
	            //so they can be shown in dependency <select>; html entities will be replaced
	            // by JavaScript itself in the constructor of Option
	            $temp = $this->get_answer_values($row['aid'],BY_AID,SAFE_STRING_JAVASCRIPT);
	            $this->data['dep_avid'][$row['qid']] = $temp['avid'];
	            $this->data['dep_value'][$row['qid']] = $temp['value'];
	            break;

			case ANSWER_TYPE_S:
				// A selector dependancy of a dynamic answer type question also needs st. to display:
	        	$this->data['dep_avid'][$row['qid']][] = SELECTOR_DEP_AVID;	// dummy id
	        	$this->data['dep_value'][$row['qid']][] = '[a text]'; // L10N: text_answer_label
	        	break;

			default:
				$can_depend = false;
		}

        // Prepare data for possible dependencies
        if($can_depend)
        {
            $this->data['dep_qid'][] = $row['qid'];
            $this->data['dep_qnum'][] = $question_label;
        }
	}

	/**
	 * Create javascript to fill <select> boxes when creating dependencies.
	 * The result is stored into $this->data['js'].
	 * The necessary data is taken from $this->data, namely the keys dep_avid,
	 * dep_value.
	 * It's assumed that the data has been prepared by
	 * _prepare_data4dependencies and that the array contains prepared data for
	 * all questions  on which one can become dependant.
	 *
	 * @access private
	 */
	function _prepare_js4dependencies()
	{
		//Create javascript to fill <select> boxes when creating dependencies
        if(isset($this->data['dep_avid']) && count($this->data['dep_avid']))
        {
            $this->data['js'] = '';

            foreach($this->data['dep_avid'] as $qid=>$avid_array)
            {
                foreach($avid_array as $key=>$avid)
                {
                    $this->data['js'] .= "Answers['$qid,$key'] = '$avid';\n";
                    $value = addslashes($this->data['dep_value'][$qid][$key]);
                    $this->data['js'] .= "Values['$qid,$key'] = '$value';\n";
                }
                $c = count($avid_array);
                $this->data['js'] .= "Num_Answers['$qid'] = '$c';\n";
            }
        }
	}

	/**
	 * Is the given answer type dynamic?
	 * @param int $id either aid (answer type id) or qid (question id)
	 * @param int $mode says whether the id is an aid (BY_AID) or a qid (BY_QID)
	 */
	function _isDynamic($id, $mode)
	{
		// Result = is_dynamic = 0 or 1; on a failure result === false.
		if($mode === BY_QID)
		{
			$query = "SELECT at.is_dynamic FROM {$this->CONF['db_tbl_prefix']}questions q " .
				"JOIN {$this->CONF['db_tbl_prefix']}answer_types at ON (at.aid=q.aid) WHERE qid=$id";
		}
		elseif($mode === BY_AID)
		{
			$query = "SELECT is_dynamic FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE aid=$id";
		}
		$result = $this->db->GetOne($query);
		if($result === false)
		{ $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return false; }
		return (bool)$result;
	}

	/**
	 * If the given question/answer type is dynamic, check it has the right
	 * number of selector dependencies.
	 * @param array $dependencies Info about dependencies of the question as
	 * extracted from the request by _validateDependency.
	 * @param int $id either aid (answer type id) or qid (question id)
	 * @param int $mode says whether the id is an aid (BY_AID) or a qid (BY_QID)
	 * @return array Array of errors (or an empty one)
	 */
	function _validateQuestionIfDynamic(&$dependencies, $id, $mode)
	{
		$errors = array();

		if(! $this->_isDynamic($id, $mode))
		{ return $errors; }

		$count_selector_deps = 0;

		// Existing question - retrieve existing dependencies
		if($mode === BY_QID)
		{
			$option_selector = $this->SfStr->getSafeString(DEPEND_MODE_SELECTOR,SAFE_STRING_DB);
			$query = "SELECT count(*) FROM {$this->CONF['db_tbl_prefix']}dependencies
                        WHERE qid=$id AND dep_option=$option_selector" ;
            $rs = $this->db->GetOne($query);
            if($rs === FALSE)
            { $errors[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); return false; }
            else
            { $count_selector_deps = (int)$rs; }
		}

		$count_selector_deps += isset($dependencies['input']['count_selector_deps'])?
    		$dependencies['input']['count_selector_deps'] : 0;
    	// Check the number of selectordependencies; we allow to delete a selector dep. of an
    	// existing question because we assume the user is going to add one later.
        if($count_selector_deps > 1 || (($count_selector_deps < 1) && ($mode !== BY_QID)))
        { $errors[] = $this->lang('err.must_1_selector_dep') . "$count_selector_deps"; }
        return $errors;
	}

}

?>