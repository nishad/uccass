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

define('MODE_ACCESSCONTROL','access_control');

//Access Control Layout
define('ACTION_COLSPAN',5);
define('INVITE_COLSPAN',4);

//User Status
define('USERSTATUS_NONE',0);
define('USERSTATUS_INVITEE',1);
define('USERSTATUS_INVITED',2);
define('USERSTATUS_SENTLOGIN',3);

//String to seperate headers from
//body in email templates
define('HEADER_SEPERATOR','<!-- HEADER SEPERATOR - DO NOT REMOVE -->');

//Invitation code types
define('INVITECODE_ALPHANUMERIC','alphanumeric');
define('INVITECODE_WORDS','words');
define('ALPHANUMERIC_MAXLENGTH',20);
define('ALPHANUMERIC_DEFAULTLENGTH',10);
define('WORDCODE_SEPERATOR','-');
define('WORDCODE_NUMWORDS',2);

//Survey Limits
define('SL_MINUTES',0);
define('SL_HOURS',1);
define('SL_DAYS',2);
define('SL_EVER',3);

class UCCASS_AccessControl extends UCCASS_Main
{
    //Load configuration and initialize data variable
    function UCCASS_AccessControl()
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
        $this->data['content'] = MODE_ACCESSCONTROL;
        //$this->data['mode'] = MODE_PROPERTIES;
        $this->data['sid'] = $sid;

        $qid = (int)@$_REQUEST['qid'];

        if(isset($_REQUEST['update_access_control']))
        { $this->_processUpdateAccessControl($sid); }
        elseif(isset($_REQUEST['users_go']))
        { $this->_processUsersAction($sid); }
        elseif(isset($_REQUEST['invite_go']))
        { $this->_processInviteAction($sid); }
        else
        {
            $this->data['content'] = MODE_ACCESSCONTROL;
            $this->_loadAccessControl($sid);
        }

        $this->smarty->assign_by_ref('data',$this->data);

        //Retrieve template that shows links for edit survey page
        $this->data['links'] = ($this->data['show']['links']) ? $this->smarty->Fetch($this->CONF['template'].'/edit_survey_links.tpl') : '';

        if(isset($this->data['content']))
        { $this->data['content'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_' . $this->data['content'] . '.tpl'); }

        //Retrieve entire edit surey page based upon the content set above
        return $this->smarty->Fetch($this->CONF['template'].'/edit_survey.tpl');
    }

    // LOAD ACCESS CONTROL SETTINGS FOR SURVEY //
    function _loadAccessControl($sid)
    {
        $sid = (int)$sid;

        //Set default values for access control page/form
        $this->data['mode'] = MODE_ACCESSCONTROL;
        $this->data['actioncolspan'] = ACTION_COLSPAN;
        $this->data['inviteactioncolspan'] = INVITE_COLSPAN;
        $this->data['show']['take_priv'] = FALSE;
        $this->data['show']['results_priv'] = FALSE;
        $this->data['show']['invite'] = FALSE;
        $this->data['show']['survey_limit'] = TRUE;
        $this->data['show']['sentlogininfo'] = FALSE;
        $this->data['show']['clear_completed'] = FALSE;

        $query = "SELECT access_control, hidden, public_results, date_format,
                  survey_limit_times, survey_limit_number, survey_limit_unit
                  FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        elseif($r = $rs->FetchRow($rs))
        {
            $this->_loadUsers($sid,$r['access_control'],$r['date_format']);

            $this->data['access_control'] = $this->SfStr->getSafeString($r['access_control'],SAFE_STRING_TEXT);
            if($r['hidden'])
            { $this->data['hidden_checked'] = FORM_CHECKED; }
            if($r['public_results'])
            { $this->data['public_results_checked'] = FORM_CHECKED; }
            else
            {
                $this->data['show']['results_priv'] = TRUE;
                $this->data['actioncolspan']++;
                $this->data['inviteactioncolspan']++;
            }
            $this->data['survey_limit_times'] = (int)$r['survey_limit_times'];
            $this->data['survey_limit_number'] = (int)$r['survey_limit_number'];
            $this->data['survey_limit_unit'][(int)$r['survey_limit_unit']] = FORM_SELECTED;

            switch($r['access_control'])
            {
                //set form values based upon what kind of access control is being used for survey
                case AC_COOKIE:
                    $this->data['acs']['cookie'] = FORM_SELECTED;
                break;

                case AC_IP:
                    $this->data['acs']['ip'] = FORM_SELECTED;
                    $this->data['show']['clear_completed'] = TRUE;
                break;

                case AC_USERNAMEPASSWORD:
                    $this->data['acs']['usernamepassword'] = FORM_SELECTED;
                    $this->data['show']['take_priv'] = TRUE;
                    $this->data['actioncolspan']+=2;
                    $this->data['show']['clear_completed'] = TRUE;
                break;

                case AC_INVITATION:
                    $this->data['acs']['invitation'] = FORM_SELECTED;
                    $this->data['show']['invite'] = TRUE;
                    $this->data['show']['clear_completed'] = TRUE;

                    if(isset($_SESSION['invite_code_type']) && $_SESSION['invite_code_type'] == INVITECODE_WORDS)
                    { $this->data['invite_code_type'][INVITECODE_WORDS] = FORM_CHECKED; }
                    else
                    { $this->data['invite_code_type'][INVITECODE_ALPHANUMERIC] = FORM_CHECKED; }

                    if(isset($_SESSION['invite_code_length']) && $_SESSION['invite_code_length'] > 0 && $_SESSION['invite_code_length'] <= ALPHANUMERIC_MAXLENGTH)
                    { $this->data['invite_code_length'] = (int)$_SESSION['invite_code_length']; }
                    else
                    { $this->data['invite_code_length'] = ALPHANUMERIC_DEFAULTLENGTH; }

                    $this->data['alphanumeric']['maxlength'] = ALPHANUMERIC_MAXLENGTH;
                    $this->data['alphanumeric']['defaultlength'] = ALPHANUMERIC_DEFAULTLENGTH;
                break;

                case AC_NONE:
                default:
                    $this->data['acs']['none'] = FORM_SELECTED;
                    $this->data['show']['survey_limit'] = FALSE;
                break;
            }
        }
        else
        { $this->error($lang['survey_not_exist']); exit(); }
    }

    function _loadUsers($sid,$access_control,$date_format)
    {
        $sid = (int)$sid;
        $access_control = (int)$access_control;

        $x = 0;
        $y = 0;

        //Load current users for survey from database and add to user list or invite list based
        //upon the access control setting.
        $query = "SELECT u.uid, u.name, u.email, u.username, u.password, u.take_priv, u.results_priv,
                  u.edit_priv, u.status, u.status_date, MAX(cs.completed) AS completed, COUNT(u.uid) AS num_completed, u.invite_code
                  FROM {$this->CONF['db_tbl_prefix']}users u LEFT JOIN {$this->CONF['db_tbl_prefix']}completed_surveys cs ON u.uid = cs.uid
                  WHERE u.sid = $sid GROUP BY u.uid ORDER BY u.name, u.username";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        elseif($r = $rs->FetchRow($rs))
        {
            do
            {
                //If access control is INVITATION ONLY, then add users with a status of INVITEE or INVITED
                //to the invitee list within $data
                if($access_control == AC_INVITATION && ($r['status'] == USERSTATUS_INVITEE || $r['status'] == USERSTATUS_INVITED))
                {
                    $key = 'invite';
                    $num = &$y;

                    if(!empty($r['invite_code']))
                    { $this->data[$key][$num]['invite_code'] = $this->SfStr->getSafeString($r['invite_code'],SAFE_STRING_TEXT); }
                    else
                    { $this->data[$key][$num]['invite_code'] = NBSP; }

                    if($r['status'] == USERSTATUS_INVITEE)
                    { $this->data[$key][$num]['status_date'] = 'N'; }
                    elseif($r['status'] == USERSTATUS_INVITED)
                    { $this->data[$key][$num]['status_date'] = date($date_format,$r['status_date']); }

                    if($r['results_priv'])
                    { $this->data[$key][$num]['results_priv'] = FORM_CHECKED; }
                }
                else
                {
                //Otherwise add the users to the normal users list
                    $key = 'users';
                    $num = &$x;
                    $this->data[$key][$num]['username'] = $this->SfStr->getSafeString($r['username'],SAFE_STRING_TEXT);
                    $this->data[$key][$num]['password'] = $this->SfStr->getSafeString($r['password'],SAFE_STRING_TEXT);

                    if($access_control == AC_USERNAMEPASSWORD && $r['status'] == USERSTATUS_SENTLOGIN)
                    { $this->data[$key][$num]['status_date'] = date($date_format,$r['status_date']); }
                    else
                    { $this->data[$key][$num]['status_date'] = 'N'; }

                    if($r['take_priv'])
                    { $this->data[$key][$num]['take_priv'] = ' checked'; }
                    if($r['results_priv'])
                    { $this->data[$key][$num]['results_priv'] = ' checked'; }
                    if($r['edit_priv'])
                    { $this->data[$key][$num]['edit_priv'] = ' checked'; }

                }

                //If user has completed a survey, create date and time stamp of
                //last completed time
                if(!empty($r['completed']))
                {
                    $this->data[$key][$num]['completed'] = date($date_format,$r['completed']);
                    $this->data[$key][$num]['num_completed'] = $r['num_completed'];
                }
                else
                {
                    $this->data[$key][$num]['completed'] = '';
                    $this->data[$key][$num]['num_completed'] = '0';
                }

                $this->data[$key][$num]['uid'] = $r['uid'];
                $this->data[$key][$num]['name'] = $this->SfStr->getSafeString($r['name'],SAFE_STRING_TEXT);
                $this->data[$key][$num]['email'] = $this->SfStr->getSafeString($r['email'],SAFE_STRING_TEXT);

                //Check if previous errors were set for the current User ID. If so, set a flag to the
                //user or invitee row can be highlighted in the template
                if(isset($_SESSION['update_users']['erruid'][$r['uid']]) || isset($_SESSION['invite']['erruid'][$r['uid']]))
                { $this->data[$key][$num]['erruid'] = 1; }

                $num++;
            }while($r = $rs->FetchRow($rs));
        }

        //Create data for five empty rows after existing
        //users and invitees that can be used to create new users or invitees.
        for($z=0;$z<5;$z++)
        {
            $this->data['invite'][$y]['uid'] = 'x'.$z;
            $this->data['invite'][$y]['status_date'] = '&nbsp;';
            $this->data['invite'][$y]['invite_code'] = '&nbsp;';
            $this->data['invite'][$y++]['num_completed'] = '-';
            $this->data['users'][$x]['num_completed'] = '-';
            $this->data['users'][$x]['status_date'] = '&nbsp;';
            $this->data['users'][$x++]['uid'] = 'x'.$z;
        }

        //Remove any error messages that were set for users and invitees
        if(isset($_SESSION['update_users']['erruid']))
        { unset($_SESSION['update_users']['erruid']); }
        if(isset($_SESSION['invite']['erruid']))
        { unset($_SESSION['invite']['erruid']); }
    }

    // PROCESS UPDATING ACCESS CONTROL OPTIONS //
    function _processUpdateAccessControl($sid)
    {
        $sid = (int)$sid;

        $error = array();

        //Validate access control setting.
        $input['access_control'] = (int)$_REQUEST['access_control'];
        if($input['access_control'] < AC_NONE || $input['access_control'] > AC_INVITATION)
        { $input['access_control'] = 0; }

        //Validate whether survey is hidden or note
        if(isset($_REQUEST['hidden']))
        { $input['hidden'] = 1; }
        else
        { $input['hidden'] = 0; }

        //Validate whether results are public or not.
        if(isset($_REQUEST['public_results']))
        { $input['public_results'] = 1; }
        else
        { $input['public_results'] = 0; }

        if($input['access_control'] != AC_NONE && isset($_REQUEST['survey_limit_unit']))
        {
            //Validate any limit placed on the number of times users
            //can complete surveys
            $input['survey_limit_times'] = (int)$_REQUEST['survey_limit_times'];
            $input['survey_limit_number'] = (int)$_REQUEST['survey_limit_number'];
            $input['survey_limit_unit'] = min(3,abs((int)$_REQUEST['survey_limit_unit']));

            switch($_REQUEST['survey_limit_unit'])
            {
                case SL_MINUTES:
                    $input['survey_limit_seconds'] = 60 * $input['survey_limit_number'];
                break;
                case SL_HOURS:
                    $input['survey_limit_seconds'] = 60 * 60 * $input['survey_limit_number'];
                break;
                case SL_DAYS:
                    $input['survey_limit_seconds'] = 60 * 60 * 24 * $input['survey_limit_number'];
                break;
                case SL_EVER:
                default:
                    $input['survey_limit_seconds'] = 0;
                break;
            }

            if(empty($_REQUEST['survey_limit_times']) && !empty($_REQUEST['survey_limit_number']))
            { $error[] = 'Number of times for survey limit is required if number of units is supplied'; }
            elseif(empty($_REQUEST['survey_limit_number']) && !empty($_REQUEST['survey_limit_times']) && $_REQUEST['survey_limit_unit'] != SL_EVER)
            { $error[] = 'Number of units for survey limit is required if number of times is supplied'; }
        }
        else
        {
            //If survey limits cannot be created for this survey because of the
            //access control settings, just set the column equal to themselves so
            //there are no changes to existing values.
            $input['survey_limit_times'] = 'survey_limit_times';
            $input['survey_limit_number'] = 'survey_limit_number';
            $input['survey_limit_unit'] = 'survey_limit_unit';
            $input['survey_limit_seconds'] = 'survey_limit_seconds';
        }

        //Update survey with new access control settings
        if(empty($error))
        {
            $query = "UPDATE {$this->CONF['db_tbl_prefix']}surveys SET access_control = {$input['access_control']},
                    hidden = {$input['hidden']}, public_results = {$input['public_results']},
                    survey_limit_times = {$input['survey_limit_times']}, survey_limit_number = {$input['survey_limit_number']},
                    survey_limit_unit = {$input['survey_limit_unit']}, survey_limit_seconds = {$input['survey_limit_seconds']}
                    WHERE sid = {$sid}";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $error[] = 'Error updating access control: ' . $this->db->ErrorMsg(); }
        }

        //If user choses to reset the completed surveys tracking, then delete any references to
        //the surve in the ip_track and completed_surveys table. Answers provided by users will
        //not be removed, but the system will think the user has completed the survey zero times.
        if(isset($_REQUEST['clear_completed']))
        {
            $tables = array('ip_track','completed_surveys');
            foreach($tables as $tbl)
            {
                $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}{$tbl} WHERE sid = $sid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $error[] = "Error resetting completed surveys within table $tbl: " . $this->db->ErrorMsg(); }
            }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=access_control");

        if(empty($error))
        { $this->setMessage('Notice','Access controls sucessfully updated.',MSGTYPE_NOTICE); }
        else
        { $this->setMessage('Error',implode(BR,$error),MSGTYPE_ERROR); }
    }

    // PROCESS UPDATING USER LIST //
    function _processUpdateUsers($sid)
    {
        $sid = (int)$sid;
        $error = array();
        $erruid = array();

        //Retrieve current access control and public results setting for survey
        //to determine what fields are required for users.
        $query = "SELECT access_control, public_results FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $error[2] = 'Unable to retrieve survey access control information: ' . $this->db->ErrorMsg(); }
        else
        {
            $r = $rs->FetchRow($rs);
            $access_control = $r['access_control'];
            $public_results = $r['public_results'];

            //Loop through each user and validate data entered. If the UID passed
            //begins with an 'x', then the data is for a new user
            foreach($_REQUEST['name'] as $uid=>$name)
            {
                if($uid{0} != 'x' || ($uid{0}=='x' && (!empty($_REQUEST['name'][$uid]) || !empty($_REQUEST['email'][$uid]) || !empty($_REQUEST['username'][$uid]) || !empty($_REQUEST['password'][$uid]))))
                {
                    $input = array();
                    //Validate name, email, username and password.
                    $input['name'] = $this->SfStr->getSafeString($_REQUEST['name'][$uid],SAFE_STRING_DB);
                    $input['email'] = $this->SfStr->getSafeString($_REQUEST['email'][$uid],SAFE_STRING_DB);
                    if(empty($_REQUEST['username'][$uid]))
                    {
                        $error[0] = 'Username can not be empty.';
                        $erruid[$uid] = 1;
                    }
                    else
                    { $input['username'] = $this->SfStr->getSafeString($_REQUEST['username'][$uid],SAFE_STRING_DB); }
                    if(empty($_REQUEST['password'][$uid]))
                    {
                        $error[1] = 'Password can not be empty.';
                        $erruid[$uid] = 1;
                    }
                    else
                    { $input['password'] = $this->SfStr->getSafeString($_REQUEST['password'][$uid],SAFE_STRING_DB); }

                    //Validate privileges based upon the access control setting for the survey
                    if($access_control == AC_USERNAMEPASSWORD)
                    {
                        if(isset($_REQUEST['take_priv'][$uid]))
                        { $input['take_priv'] = 1; }
                        else
                        { $input['take_priv'] = 0; }
                    }
                    else
                    { $input['take_priv'] = 'take_priv'; }

                    if($public_results)
                    { $input['results_priv'] = 'results_priv'; }
                    else
                    {
                        if(isset($_REQUEST['results_priv'][$uid]))
                        { $input['results_priv'] = 1; }
                        else
                        { $input['results_priv'] = 0; }
                    }

                    if(isset($_REQUEST['edit_priv'][$uid]))
                    { $input['edit_priv'] = 1; }
                    else
                    { $input['edit_priv'] = 0; }

                    //Insert or Update new user data
                    if(!isset($erruid[$uid]))
                    {
                        if($uid{0} == 'x')
                        {
                            $keyword = 'inserting';
                            $uid = $this->db->GenID($this->CONF['db_tbl_prefix'].'users_sequence');
                            $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}users
                                      (uid, sid, name, email, username, password, take_priv, results_priv, edit_priv) VALUES
                                      ($uid, $sid, {$input['name']}, {$input['email']}, {$input['username']}, {$input['password']},
                                      {$input['take_priv']}, {$input['results_priv']}, {$input['edit_priv']})";
                        }
                        else
                        {
                            $keyword = 'updating';
                            $uid = (int)$uid;
                            $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET name = {$input['name']}, email = {$input['email']},
                                      username = {$input['username']}, password = {$input['password']}, take_priv = {$input['take_priv']},
                                      results_priv = {$input['results_priv']}, edit_priv = {$input['edit_priv']}
                                      WHERE uid = $uid";
                        }

                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        {
                            $error[] = "Error $keyword user information: " . $this->db->ErrorMsg();
                            $erruid[$uid] = 1;
                        }
                    }
                }
            }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=access_control");

        if(empty($error))
        { $this->setMessage('Notice','User information updated sucessfully.',MSGTYPE_NOTICE); }
        else
        {
            $_SESSION['update_users']['erruid'] = $erruid;
            $this->setMessage('Error',implode(BR,$error),MSGTYPE_ERROR);
        }
    }

    // PROCESS SELECTED ACTION ON SELECTED USERS //
    function _processUsersAction($sid)
    {
        switch($_REQUEST['users_selection'])
        {
            //Delete selected users
            case 'delete':
                $this->_processDeleteUsers($sid,@$_REQUEST['users_checkbox']);
            break;
            //Send username and password reminder to user's email address
            case 'remind':
                $this->_processSendLoginInfo($sid,@$_REQUEST['users_checkbox'],'mail_usernamepassword.tpl');
            break;
            //Move selected users to invite list (if access control if INVITATION ONLY)
            case 'movetoinvite':
                $this->_processMoveToList($sid,$_REQUEST['users_checkbox'],USERSTATUS_INVITEE);
            break;
            //Save All Users
            case 'saveall':
            default:
                $this->_processUpdateUsers($sid);
                //$this->setMessageRedirect("edit_survey.php?sid=$sid&mode=access_control");
                //$this->setMessage('Notice','Please choose an action from the dropdown to perform.',MSGTYPE_NOTICE);
            break;
        }
    }

    // DELETE USERS //
    function _processDeleteUsers($sid,$users)
    {
        $sid = (int)$sid;
        $error = array();
        $numdeleted = 0;
        $numtodelete = 0;

        if(!empty($users))
        {
            //Loop through user array and delete users, keeping
            //track of how many were successfully deleted.
            $numtodelete = count($users);

            foreach($users as $uid=>$val)
            {
                if($uid{0} != 'x')
                {
                    $uid = (int)$uid;
                    $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}users WHERE uid=$uid AND sid=$sid";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $error[] = "Unable to delete user (uid:$uid): " . $this->db->ErrorMsg(); }
                    else
                    { $numdeleted++; }
                }
                else
                { $numtodelete--; }
            }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=access_control");

        if(empty($error))
        { $this->setMessage('Notice',"{$numdeleted} of {$numtodelete} users deleted.",MSGTYPE_NOTICE); }
        else
        { $this->setMessage('Error',"{$numdeleted} of {$numtodelete} users deleted. <br />" . implode(BR,$error),MSGTYPE_ERROR); }
    }

    // SEND USERNAME AND PASSWORD INFORMATION TO USER //
    function _processSendLoginInfo($sid,$users,$template)
    {
        set_time_limit(120);

        $sid = (int)$sid;
        $error = array();
        $numtoemail = 0;
        $numemailed = 0;

        if(!empty($users))
        {
            //Retrieve settings for survey
            $query = "SELECT * FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $error[] = 'Unable to retrieve survey information: ' . $this->db->ErrorMsg(); }
            elseif($survey = $rs->FetchRow($rs))
            {
                //Set variables to be used in mail templates
                $survey['main_url'] = $this->CONF['html'];
                $survey['take_url'] = $this->CONF['html'] . "/survey.php?sid=$sid";
                $survey['results_url'] = $this->CONF['html'] . "/results.php?sid=$sid";
                $survey['edit_url'] = $this->CONF['html'] . "/edit_survey.php?sid=$sid";

                $this->smarty->assign_by_ref('survey',$survey);
                $user = array();
                $this->smarty->assign_by_ref('user',$user);

                $numtoemail = count($users);

                //Loop through each user and create reminder email.
                foreach($users as $uid=>$val)
                {
                    if($uid{0} != 'x')
                    {
                        $uid = (int)$uid;

                        //Retrieve user information
                        $query = "SELECT * FROM {$this->CONF['db_tbl_prefix']}users WHERE sid=$sid AND uid=$uid";
                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        { $error[] = "Unable to get user information (uid:$uid): " . $this->db->ErrorMsg(); }
                        elseif($user = $rs->FetchRow($rs))
                        {
                            //Ensure user has an email set
                            if(!empty($user['email']))
                            {
                                //If user has permission to view results, set flag
                                //to show results URL in email
                                if($survey['public_results'])
                                { $user['results_priv'] = 1; }

                                //Retrieve email text
                                $mail = $this->_parseEmailTemplate($survey,$user,$template);

                                //Send email and update status of user to show they were
                                //sent a login reminder
                                if(!empty($mail))
                                {
                                    $send = @mail($mail['to'],$mail['subject'],$mail['message'],$mail['headers']);
                                    if($send)
                                    {
                                        $numemailed++;
                                        $now = time();
                                        $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET status = ".USERSTATUS_SENTLOGIN.", status_date = {$now} WHERE uid=$uid AND sid=$sid";
                                        $rs = $this->db->Execute($query);
                                        if($rs === FALSE)
                                        { $error[] = "Unable to update user status (uid:$uid): " . $this->db->ErrorMsg(); }
                                    }
                                    else
                                    { $error[] = "Unable to send email to &quot;{$user['name']}&quot; at &quot;{$user['email']}&quot; for unknown reason."; }
                                }
                            }
                            else
                            { $error[] = "Username &quot;{$user['username']}&quot; does not have an email address."; }
                        }
                    }
                    else
                    { $numtoemail--; }
                }
            }
            else
            { $error[] = 'Invalid survey.'; }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=access_control");
        $msg = "{$numemailed} of {$numtoemail} users emailed. ";

        if(empty($error))
        { $this->setMessage('Notice',$msg,MSGTYPE_NOTICE); }
        else
        { $this->setMessage('Error',$msg.BR.implode(BR,$error),MSGTYPE_ERROR); }
    }

    // PARSE EMAIL TEMPLATE //
    function _parseEmailTemplate(&$survey, &$user, $template)
    {
        $retval = array();

        //Fetch selected email template
        if($template{0} != '/')
        { $template = '/' . $template; }

        $emailtext = $this->smarty->Fetch($this->CONF['template'] . $template);

        //Split email on HEADER_SEPERATOR. Lines before seperator are used as
        //headers for the email and text after the seperator is the body of the email.
        //This allows you to create customized headers for the emails from
        //within the template
        if(preg_match('/(.*)('.preg_quote(HEADER_SEPERATOR).')(.*)/s',$emailtext,$match))
        {
            $retval['headers'] = $match[1];
            $retval['message'] = $match[3];

            //Extract To: and Subject: headers from mail template.
            //If headers do not exist, set a default
            if(preg_match('/^To:(.*)$/im',$retval['headers'],$to))
            {
                $retval['to'] = trim($to[1]);
                $retval['headers'] = preg_replace("/^To:.*\r?\n/im",'',$retval['headers']);
            }
            else
            { $retval['to'] = $user['email']; }

            if(preg_match('/^Subject:(.*)$/im',$retval['headers'],$subject))
            {
                $retval['subject'] = trim($subject[1]);
                $retval['headers'] = preg_replace("/^Subject:.*\r?\n/im",'',$retval['headers']);
            }
            else
            { $retval['subject'] = 'Survey Information'; }
        }

        return $retval;
    }

    // MOVE USERS TO/FROM USER/INVITEE LIST //
    function _processMoveToList($sid,$users,$status)
    {
        $sid = (int)$sid;
        $error = array();
        $numtomove = 0;
        $nummoved = 0;

        //Loop through users and update status to match what was passed.
        //Setting status to USERSTATUS_INVITEE or USERTATUS_INVITED will place
        //the user on the Invitee list, while setting to USERSTATUS_NONE will
        //put the user on the User list.
        if(!empty($users))
        {
            $numtomove = count($users);

            foreach($users as $uid=>$val)
            {
                if($uid{0} != 'x')
                {
                    $uid = (int)$uid;
                    $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET status=$status WHERE uid=$uid AND sid=$sid";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $error[] = "Unable to move user (uid:$uid): " . $this->db->ErrorMsg(); }
                    else
                    { $nummoved++; }
                }
                else
                { $numtomove--; }
            }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=access_control");
        $msg = "{$nummoved} of {$numtomove} users moved. ";

        if(empty($error))
        { $this->setMessage('Notice',$msg,MSGTYPE_NOTICE); }
        else
        { $this->setMessage('Error',$msg.BR.implode(BR,$error),MSGTYPE_ERROR); }
    }

    // PROCESS CHANGES TO INVITEE LIST //
    function _processUpdateInvite($sid)
    {
        $error = array();
        $erruid = array();

        $_SESSION['invite_code_type'] = $_REQUEST['invite_code_type'];
        $_SESSION['invite_code_length'] = $_REQUEST['invite_code_length'];

        //Loop through invitees and validate data. If the first character
        //of UID is 'x', then the information is for a new invitee
        if(!empty($_REQUEST['invite_name']))
        {
            foreach($_REQUEST['invite_name'] as $uid=>$name)
            {
                if($uid{0} != 'x' || ($uid{0} == 'x' && (!empty($_REQUEST['invite_name'][$uid]) || !empty($_REQUEST['invite_email'][$uid]))))
                {
                    //Validate email address (required)
                    if(empty($_REQUEST['invite_email'][$uid]))
                    {
                        $error[1] = 'Email address is required for invitee.';
                        $erruid[$uid] = 1;
                    }
                    elseif(strlen($_REQUEST['invite_email'][$uid])<5 || strpos($_REQUEST['invite_email'][$uid],'@')===FALSE)
                    {
                        $error[2] = 'Incorrect email address format.';
                        $erruid[$uid] = 1;
                    }
                    else
                    { $input['email'] = $this->SfStr->getSafeString($_REQUEST['invite_email'][$uid],SAFE_STRING_DB); }

                    //Validate name and set status to INVITEE
                    $input['name'] = $this->SfStr->getSafeString($_REQUEST['invite_name'][$uid],SAFE_STRING_DB);
                    $input['status'] = USERSTATUS_INVITEE;

                    if(isset($_REQUEST['invite_results_priv'][$uid]))
                    { $input['results_priv'] = 1; }
                    else
                    { $input['results_priv'] = 0; }

                    //If there were no errors, INSERT or UPDATE invitee information
                    if(!isset($erruid[$uid]))
                    {
                        if($uid{0}=='x')
                        {
                            $uid = $this->db->GenID($this->CONF['db_tbl_prefix'].'users_sequence');
                            $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}users (uid, sid, name, email, status, results_priv)
                                      VALUES ($uid, $sid, {$input['name']}, {$input['email']}, {$input['status']},{$input['results_priv']})";
                        }
                        else
                        {
                            $uid = (int)$uid;
                            $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET name = {$input['name']},
                                      email = {$input['email']}, results_priv = {$input['results_priv']}
                                      WHERE uid=$uid AND sid=$sid";
                        }

                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        { $error[] = 'Error updating/inserting invitee: ' . $this->db->ErrorMsg(); }
                    }
                }
            }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=access_control");

        if(empty($error))
        { $this->setMessage('Notice','Invitees added/updated',MSGTYPE_NOTICE); }
        else
        {
            $_SESSION['invite']['erruid'] = $erruid;
            $this->setMessage('Error',implode(BR,$error),MSGTYPE_ERROR);
        }
    }

    // PROCESS SELECTED ACTION ON SELECTED INVITEES //
    function _processInviteAction($sid)
    {
        $sid = (int)$sid;

        $_SESSION['invite_code_type'] = $_REQUEST['invite_code_type'];
        $_SESSION['invite_code_length'] = $_REQUEST['invite_code_length'];


        switch($_REQUEST['invite_selection'])
        {
            //Delete selected invitees
            case 'delete':
                $this->_processDeleteUsers($sid,@$_REQUEST['invite_checkbox']);
            break;
            //Send invitation code to selected invitees
            case 'invite':
                $this->_processSendInvitation($sid,@$_REQUEST['invite_checkbox'],'mail_invitation.tpl');
            break;
            //Move invitees to User list
            case 'movetousers':
                $this->_processMoveToList($sid,@$_REQUEST['invite_checkbox'],USERSTATUS_NONE);
            break;
            //Save all invitees
            case 'saveall':
            default:
                $this->_processUpdateInvite($sid);
                //$this->setMessageRedirect("edit_survey.php?sid=$sid&mode=access_control");
                //$this->setMessage('Notice','Please choose an invite action from the dropdown.',MSGTYPE_NOTICE);
            break;
        }
    }

    // SEND EMAIL INVITATION CODE TO INVITEES //
    function _processSendInvitation($sid,$users,$template)
    {
        @set_time_limit(120);

        $sid = (int)$sid;
        $error = array();
        $numtoemail = 0;
        $numemailed = 0;

        //Loop through invitees
        if(!empty($users))
        {
            $query = "SELECT * FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $error[] = 'Unable to retrieve survey information: ' . $this->db->ErrorMsg(); }
            elseif($survey = $rs->FetchRow($rs))
            {
                //Create variables to be used in email template
                $survey['main_url'] = $this->CONF['html'];
                $survey['take_url'] = $this->CONF['html'] . "/survey.php?sid=$sid";
                $survey['results_url'] = $this->CONF['html'] . "/results.php?sid=$sid";

                $this->smarty->assign_by_ref('survey',$survey);
                $user = array();
                $this->smarty->assign_by_ref('user',$user);

                $numtoemail = count($users);

                $uid_list = '';
                foreach($users as $uid=>$val)
                {
                    if($uid{0} != 'x')
                    { $uid_list .= (int)$uid . ','; }
                    else
                    { $numtoemail--; }
                }

                if(!empty($uid_list))
                {
                    $uid_list = substr($uid_list,0,-1);

                    //Retrieve information for selected invitee
                    $query = "SELECT * FROM {$this->CONF['db_tbl_prefix']}users WHERE sid=$sid AND uid IN ($uid_list) AND (status = " . USERSTATUS_INVITEE . ' OR status = ' . USERSTATUS_INVITED . ')';
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $error[] = 'Unable to get invitee information: ' . $this->db->ErrorMsg(); }
                    else
                    {
                        $now = time();
                        while($user = $rs->FetchRow($rs))
                        {
                            if(!empty($user['email']))
                            {
                                //Set flag for whether user has privileges to view results
                                if($survey['public_results'])
                                { $user['results_priv'] = 1; }

                                //Retreive random code for user to access survey
                                //and set URL to take survey to be used in email
                                $user['code'] = $this->_getInviteCode($sid,$user['uid'],$_REQUEST['invite_code_type'],$_REQUEST['invite_code_length']);
                                $user['take_url'] = $survey['take_url'] . '&invite_code=' . urlencode($user['code']);

                                //Retrieve email template
                                $mail = $this->_parseEmailTemplate($survey,$user,$template);

                                if(!empty($mail) && $user['code'])
                                {
                                    //Send email and update invitee status to INVITED
                                    $send = @mail($mail['to'],$mail['subject'],$mail['message'],$mail['headers']);
                                    if($send)
                                    {
                                        $numemailed++;
                                        $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET take_priv = 1, status = ".USERSTATUS_INVITED.", status_date = {$now} WHERE uid={$user['uid']} AND sid=$sid";
                                        $rs2 = $this->db->Execute($query);
                                        if($rs2 === FALSE)
                                        { $error[] = "Unable to update invitee status (uid:{$user['uid']}): " . $this->db->ErrorMsg(); }
                                    }
                                    else
                                    { $error[] = "Unable to send invitation to &quot;{$user['name']}&quot; at &quot;{$user['email']}&quot; for unknown reason."; }
                                }
                                else
                                { $error[] = "Unable to get invitation template and/or code for inviteee (uid:{$user['uid']})."; }
                            }
                            else
                            { $error[] = "Username &quot;{$user['username']}&quot; does not have an email address."; }
                        }
                    }
                }
            }
            else
            { $error[] = 'Invalid survey.'; }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=access_control");
        $msg = "{$numemailed} of {$numtoemail} users sent invitations.";

        if(empty($error))
        { $this->setMessage('Notice',$msg,MSGTYPE_NOTICE); }
        else
        { $this->setMessage('Error',$msg.BR.implode(BR,$error),MSGTYPE_ERROR); }
    }

    // GENERATE RANDOM INVITATION CODE FOR INVITEES //
    function _getInviteCode($sid,$uid,$type,$length)
    {
        static $recursion_level = 0;
        $recursion_limit = 10;
        $code = FALSE;

        //Try at least 10 times to create a random invitation code
        //that's not already being used for this survey
        while(!$code && $recursion_level <= $recursion_limit)
        {
            //Retrieve either a english word code or
            //alphanumeric code.
            switch($type)
            {
                case INVITECODE_WORDS:
                    $code = $this->_getWordCode();
                break;
                case INVITECODE_ALPHANUMERIC:
                default:
                    $code = $this->_getAlphanumericCode($length);
                break;
            }

            //Ensure code is not already being used in this survey.
            if($code)
            {
                $dbcode = $this->SfStr->getSafeString($code,SAFE_STRING_DB);
                $query = "SELECT invite_code FROM {$this->CONF['db_tbl_prefix']}users WHERE sid=$sid AND invite_code={$dbcode}";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error('Error checking for duplicate code: ' . $this->db->ErrorMsg()); return FALSE; }
                elseif($r = $rs->FetchRow($rs))
                {
                    fwrite($this->fp,"Code {$code} already in use ($sid:$uid)\r\n");
                    $code = FALSE;
                    $recursion_level++;
                }
            }
        }

        $recursion_level = 0;

        //Update user information to include the code that was chosen
        if($code)
        {
            $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET invite_code = {$dbcode} WHERE sid=$sid AND uid=$uid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error("Error updating invitation code for invitee (uid:$uid): " . $this->db->ErrorMsg()); return FALSE; }
        }

        return $code;
    }

    // GENERATE ALPHANUMERIC RANDOM INVITATION CODE //
    function _getAlphanumericCode($length)
    {
        $retval = '';
        static $values = '';
        static $numvalues = 0;

        $length = (int)$length;
        if($length <= 0 || $length > ALPHANUMERIC_MAXLENGTH)
        { $length = ALPHANUMERIC_DEFAULTLENGTH; }

        //Create code from the values in $str for the requested length
        if(empty($values))
        {
            $str = "23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";
            $values = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
            $numvalues = count($values);
        }

        for($x=0;$x<$length;$x++)
        { $retval .= $values[mt_rand(0,$numvalues)]; }

        return $retval;
    }

    // GENERATE ENGLISH WORD CODE //
    function _getWordCode()
    {
        $retval = '';
        $chosenwords = array();

        //Select a number of words from the following file
        //to create an invitation code
        $file = $this->CONF['path'].'/utils/words.txt';
        $fp = fopen($file,'r');
        $fsize = filesize($file);

        for($x=0;$x<WORDCODE_NUMWORDS;$x++)
        {
            //Select random position in file and seek backwards until
            //a newline or beginning of file is hit. In either case, grab
            //the current line as the random word
            $pos = mt_rand(0,$fsize);
            fseek($fp,$pos);
            while(fgetc($fp) != "\n" && $pos != 0)
            { fseek($fp,--$pos); }
            $chosenwords[] = trim(fgets($fp));
        }

        return implode(WORDCODE_SEPERATOR,$chosenwords);
    }
}
?>