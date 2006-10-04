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
define('USERSTATUS_AWAITING_INVITE',4);
define('USERSTATUS_AWAITING_LOGIN',5);
define('USERSTATUS_INVITE_FAILED',6);
define('USERSTATUS_LOGIN_FAILED',7);
define('NEW_CODE', 'x');

//String to seperate headers from
//body in email templates
define('HEADER_SEPERATOR','<!-- HEADER SEPERATOR - DO NOT REMOVE -->');

//Invitation code types
define('INVITECODE_ALPHANUMERIC','alphanumeric');
define('INVITECODE_WORDS','words');
define('INVITECODE_NUMERIC','numeric');
define('INVITECODE_ALPHA','alpha');

//Invitation Code Settings
define('ALPHA_MAXLENGTH',20);
define('ALPHA_DEFAULTLENGTH',10);
define('ALPHANUMERIC_MAXLENGTH',20);
define('ALPHANUMERIC_DEFAULTLENGTH',10);
define('NUMERIC_MAXLENGTH',10);
define('NUMERIC_DEFAULTLENGTH',4);
define('WORDCODE_SEPERATOR','-');
define('WORDCODE_NUMWORDS',2);

//Survey Limits
define('SL_MINUTES',0);
define('SL_HOURS',1);
define('SL_DAYS',2);
define('SL_EVER',3);

//Bulk Email Settings
define('EMAILS_PER_REFRESH', 50);

define('MODE_MANAGE_USER',1);
define('MODE_MANAGE_INVITE',2);

define('USER_FILE',1);
define('INVITE_FILE',2);

class UCCASS_AccessControl extends UCCASS_Main
{
    //Load configuration and initialize data variable
    function UCCASS_AccessControl() {
        $this->load_configuration();
        $this->data = array();
    }

    //Show edit survey page based upon request variables
    function show($sid) {
        $sid = (int)$sid;
        $retval = '';

        //Ensure user is logged in with valid privileges
        //for the requested survey or is an administrator
        if(!$this->_CheckLogin($sid,EDIT_PRIV,"access_control.php?sid=$sid"))
        { return $this->showLogin('edit_survey.php',array('sid'=>$sid)); }

        //Show links at top of page
        $this->data['show']['links'] = TRUE;
        $this->data['content'] = MODE_ACCESSCONTROL;
        $this->data['sid'] = $sid;
        $this->data['mode_user'] = MODE_MANAGE_USER;
        $this->data['mode_invite'] = MODE_MANAGE_INVITE;

        $qid = (int)@$_REQUEST['qid'];

        switch(TRUE) {
            case (isset($_REQUEST['refresh'])):
                $this->data['show']['links'] = FALSE;
                $this->data['content'] = 'refresh';
                switch($_REQUEST['refresh']) {
                    case USERSTATUS_AWAITING_LOGIN:
                        $this->data['url_variables'] = 'users_go=1&users_selection=remind';
                    break;
                    case USERSTATUS_AWAITING_INVITE:
                        $this->data['url_variables'] = 'invite_go=1&invite_selection=invite';
                    break;
                }
            break;

            case (isset($_REQUEST['update_access_control'])):
                $this->_processUpdateAccessControl($sid);
            break;

            case (isset($_REQUEST['users_go'])):
                $this->_processUsersAction($sid);
            break;

            case (isset($_REQUEST['invite_go'])):
                $this->_processInviteAction($sid);
            break;

            case (!empty($_FILES['user_file']['tmp_name'])):
                $this->_processFile($sid,USER_FILE, &$_FILES['user_file']);
            break;

            case (!empty($_FILES['invite_file']['tmp_name'])):
                $this->_processFile($sid,INVITE_FILE, &$_FILES['invite_file']);
            break;

            case (isset($_REQUEST['user_export'])):
                $this->_processExport($sid,USER_FILE);
            break;

            case (isset($_REQUEST['invite_export'])):
                $this->_processExport($sid,INVITE_FILE);
            break;

            default:
                switch(@$_REQUEST['mode']) {
                    case MODE_MANAGE_INVITE:
                        $this->data['content'] = 'ac_invite';
                        $this->data['mode'] = MODE_MANAGE_INVITE;
                    break;
                    case MODE_MANAGE_USER:
                        $this->data['content'] = 'ac_users';
                        $this->data['mode'] = MODE_MANAGE_USER;
                    break;
                    default:
                        $this->data['content'] = MODE_ACCESSCONTROL;
                    break;
                }
                $this->_loadAccessControl($sid);
            break;
        }

        $this->smarty->assign_by_ref('data',$this->data);

        //Retrieve template that shows links for edit survey page
        $this->data['links'] = ($this->data['show']['links']) ? $this->smarty->Fetch($this->CONF['template'].'/edit_survey_links.tpl') : '';

        if(isset($this->data['content'])) {
            if($this->data['content'] == 'refresh') {
                $this->data['meta_refresh'] = $this->smarty->Fetch($this->CONF['template'].'/meta_refresh.tpl');
            }
            $this->data['content'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_' . $this->data['content'] . '.tpl');
        }

        //Retrieve entire edit surey page based upon the content set above
        return $this->com_header() . $this->smarty->Fetch($this->CONF['template'].'/edit_survey.tpl') . $this->com_footer();
    }

    // LOAD ACCESS CONTROL SETTINGS FOR SURVEY //
    function _loadAccessControl($sid) {
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
                  survey_limit_times, survey_limit_number, survey_limit_unit, manual_codes
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
                    $this->data['actioncolspan']+=3;
                    $this->data['show']['clear_completed'] = TRUE;
                break;

                case AC_INVITATION:
                    $this->data['acs']['invitation'] = FORM_SELECTED;
                    $this->data['show']['invite'] = TRUE;
                    $this->data['show']['clear_completed'] = TRUE;

                    if(isset($_SESSION['invite_code_type']))
                    { $this->data['invite_code_type'][$_SESSION['invite_code_type']] = FORM_CHECKED; }
                    else
                    { $this->data['invite_code_type'][INVITECODE_ALPHANUMERIC] = FORM_CHECKED; }

                    if(isset($_SESSION['invite_alphanumericcode_length']) && $_SESSION['invite_alphanumericcode_length'] > 0 && $_SESSION['invite_alphanumericcode_length'] <= ALPHANUMERIC_MAXLENGTH)
                    { $this->data['invite_alphanumericcode_length'] = (int)$_SESSION['invite_alphanumericcode_length']; }
                    else
                    { $this->data['invite_alphanumericcode_length'] = ALPHANUMERIC_DEFAULTLENGTH; }

                    if(isset($_SESSION['invite_alphacode_length']) && $_SESSION['invite_alphacode_length'] > 0 && $_SESSION['invite_alphacode_length'] <= ALPHA_MAXLENGTH)
                    { $this->data['invite_alphacode_length'] = (int)$_SESSION['invite_alphacode_length']; }
                    else
                    { $this->data['invite_alphacode_length'] = ALPHA_DEFAULTLENGTH; }

                    if(isset($_SESSION['invite_numcode_length']) && $_SESSION['invite_numcode_length'] > 0 && $_SESSION['invite_numcode_length'] <= NUMERIC_MAXLENGTH)
                    { $this->data['invite_numcode_length'] = (int)$_SESSION['invite_numcode_length']; }
                    else
                    { $this->data['invite_numcode_length'] = NUMERIC_DEFAULTLENGTH; }

                    $this->data['alpha']['maxlength'] = ALPHA_MAXLENGTH;
                    $this->data['alpha']['defaultlength'] = ALPHA_DEFAULTLENGTH;
                    $this->data['alphanumeric']['maxlength'] = ALPHANUMERIC_MAXLENGTH;
                    $this->data['alphanumeric']['defaultlength'] = ALPHANUMERIC_DEFAULTLENGTH;
                    $this->data['numeric']['maxlength'] = NUMERIC_MAXLENGTH;
                    $this->data['numeric']['defaultlength'] = NUMERIC_DEFAULTLENGTH;

                    if(!empty($r['manual_codes'])) {
                        $this->data['manual_codes_checked'] = FORM_CHECKED;
                        $this->data['show']['manual_codes'] = TRUE;
                    }
                break;

                case AC_NONE:
                default:
                    $this->data['acs']['none'] = FORM_SELECTED;
                    $this->data['show']['survey_limit'] = FALSE;
                break;
            }
        }
        else
        { $this->error($this->lang['survey_not_exist']); exit(); }
    }

    function _loadUsers($sid,$access_control,$date_format) {
        $sid = (int)$sid;
        $access_control = (int)$access_control;

        $x = 0;
        $y = 0;

        $invite_codes = array(USERSTATUS_INVITEE,USERSTATUS_INVITED,USERSTATUS_AWAITING_INVITE,USERSTATUS_INVITE_FAILED);

        //Load current users for survey from database and add to user list or invite list based
        //upon the access control setting.
        $query = "SELECT u.uid, u.name, u.email, u.username, u.password, u.take_priv, u.results_priv,
                  u.edit_priv, u.status, u.status_date, MAX(cs.completed) AS completed, COUNT(u.uid) AS num_completed, u.invite_code
                  FROM {$this->CONF['db_tbl_prefix']}users u LEFT JOIN {$this->CONF['db_tbl_prefix']}completed_surveys cs ON u.uid = cs.uid
                  WHERE u.sid = $sid GROUP BY u.uid
                  , u.name, u.email, u.username, u.password, u.take_priv, u.results_priv
                  , u.edit_priv, u.status, u.status_date, u.invite_code
                  ORDER BY u.name, u.username";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        elseif($r = $rs->FetchRow($rs))
        {
            do
            {
                //If access control is INVITATION ONLY, then add users with a status of INVITEE or INVITED
                //to the invitee list within $data
                if($access_control == AC_INVITATION && in_array($r['status'], $invite_codes))
                {
                    $key = 'invite';
                    $num = &$y;

                    if(!empty($r['invite_code']))
                    { $this->data[$key][$num]['invite_code'] = $this->SfStr->getSafeString($r['invite_code'],SAFE_STRING_TEXT); }
                    else
                    { $this->data[$key][$num]['invite_code'] = NBSP; }

                    switch($r['status']) {
                        case USERSTATUS_INVITEE:
                            $this->data[$key][$num]['status_date'] = $this->lang['no'];
                        break;
                        case USERSTATUS_INVITED:
                            $this->data[$key][$num]['status_date'] = $this->lang['yes'] . BR . date($date_format,$r['status_date']);
                        break;
                        default:
                            $this->data[$key][$num]['status_date'] = $this->lang['failed'] . BR . date($date_format,$r['status_date']);
                        break;
                    }

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

                    switch($r['status']) {
                        case USERSTATUS_SENTLOGIN:
                            $this->data[$key][$num]['status_date'] = $this->lang['yes'] . BR . date($date_format,$r['status_date']);
                        break;
                        case USERSTATUS_LOGIN_FAILED:
                        case USERSTATUS_AWAITING_LOGIN:
                            $this->data[$key][$num]['status_date'] = $this->lang['failed'] . BR . date($date_format,$r['status_date']);
                        break;
                        default:
                            $this->data[$key][$num]['status_date'] = $this->lang['no'];
                        break;
                    }

                    if($r['take_priv'])
                    { $this->data[$key][$num]['take_priv'] = FORM_CHECKED; }
                    if($r['results_priv'])
                    { $this->data[$key][$num]['results_priv'] = FORM_CHECKED; }
                    if($r['edit_priv'])
                    { $this->data[$key][$num]['edit_priv'] = FORM_CHECKED; }

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
            $this->data['invite'][$y]['uid'] = NEW_CODE.$z;
            $this->data['invite'][$y]['status_date'] = NBSP;
            $this->data['invite'][$y]['invite_code'] = NBSP;
            $this->data['invite'][$y++]['num_completed'] = 0;
            $this->data['users'][$x]['num_completed'] = 0;
            $this->data['users'][$x]['status_date'] = NBSP;
            $this->data['users'][$x++]['uid'] = NEW_CODE.$z;
        }

        //Remove any error messages that were set for users and invitees
        if(isset($_SESSION['update_users']['erruid']))
        { unset($_SESSION['update_users']['erruid']); }
        if(isset($_SESSION['invite']['erruid']))
        { unset($_SESSION['invite']['erruid']); }
    }

    // PROCESS UPDATING ACCESS CONTROL OPTIONS //
    function _processUpdateAccessControl($sid) {
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

        if($input['access_control'] == AC_INVITATION && isset($_REQUEST['manual_codes'])) {
            $input['manual_codes'] = 1;
        } else {
            $input['manual_codes'] = 0;
        }

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
            { $error[] = $this->lang['survey_times']; }
            elseif(empty($_REQUEST['survey_limit_number']) && !empty($_REQUEST['survey_limit_times']) && $_REQUEST['survey_limit_unit'] != SL_EVER)
            { $error[] = $this->lang['survey_units']; }
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
                    survey_limit_unit = {$input['survey_limit_unit']}, survey_limit_seconds = {$input['survey_limit_seconds']},
                    manual_codes = {$input['manual_codes']}
                    WHERE sid = {$sid}";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $error[] = $this->lang['db_query_error']. $this->db->ErrorMsg(); }
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
                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
            }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=access_control");

        if(empty($error))
        { $this->setMessage($this->lang['notice'],$this->lang['access_updated'],MSGTYPE_NOTICE); }
        else
        { $this->setMessage($this->lang['error'],implode(BR,$error),MSGTYPE_ERROR); }
    }

    // PROCESS UPDATING USER LIST //
    function _processUpdateUsers($sid) {
        $sid = (int)$sid;
        $error = array();
        $erruid = array();

        //Retrieve current access control and public results setting for survey
        //to determine what fields are required for users.
        $query = "SELECT access_control, public_results FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $error[2] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        else
        {
            $r = $rs->FetchRow($rs);
            $access_control = $r['access_control'];
            $public_results = $r['public_results'];

            //Loop through each user and validate data entered. If the UID passed
            //begins with an 'x', then the data is for a new user
            foreach($_REQUEST['name'] as $uid=>$name)
            {
                if($uid{0} != NEW_CODE || ($uid{0}==NEW_CODE && (!empty($_REQUEST['name'][$uid]) || !empty($_REQUEST['email'][$uid]) || !empty($_REQUEST['username'][$uid]) || !empty($_REQUEST['password'][$uid]))))
                {
                    $process_errors = $this->_processUserData($uid, $sid, $access_control, $public_results, $_REQUEST);
                    if(!empty($process_errors)) {
                        $error = array_merge($error, $process_errors);
                        $erruid[$uid] = 1;
                    }
                }
            }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=".MODE_MANAGE_USER);

        if(empty($error))
        { $this->setMessage($this->lang['notice'],$this->lang['user_updated'],MSGTYPE_NOTICE); }
        else
        {
            $_SESSION['update_users']['erruid'] = $erruid;
            $this->setMessage($this->lang['error'],implode(BR,$error),MSGTYPE_ERROR);
        }
    }

    function _processUserData($uid,$sid,$access_control,$public_results,$data) {
        $input = array();
        $error = array();

        //Validate name, email, username and password.
        $input['name'] = $this->SfStr->getSafeString($data['name'][$uid],SAFE_STRING_DB);
        $input['email'] = $this->SfStr->getSafeString($data['email'][$uid],SAFE_STRING_DB);
        if(empty($data['username'][$uid]))
        {
            $error[0] = $this->lang['no_username'];
        }
        else
        { $input['username'] = $this->SfStr->getSafeString($data['username'][$uid],SAFE_STRING_DB); }
        if(empty($data['password'][$uid]))
        {
            $error[1] = $this->lang['no_password'];
        }
        else
        { $input['password'] = $this->SfStr->getSafeString($data['password'][$uid],SAFE_STRING_DB); }

        //Validate privileges based upon the access control setting for the survey
        if($access_control == AC_USERNAMEPASSWORD)
        {
            if(isset($data['take_priv'][$uid]))
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
            if(!empty($data['results_priv'][$uid]))
            { $input['results_priv'] = 1; }
            else
            { $input['results_priv'] = 0; }
        }

        if(!empty($data['edit_priv'][$uid]))
        { $input['edit_priv'] = 1; }
        else
        { $input['edit_priv'] = 0; }

        //Insert or Update new user data
        if(empty($error))
        {
            if($uid{0} == NEW_CODE)
            {
                $uid = $this->db->GenID($this->CONF['db_tbl_prefix'].'users_sequence');
                $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}users
                          (uid, sid, name, email, username, password, take_priv, results_priv, edit_priv) VALUES
                          ($uid, $sid, {$input['name']}, {$input['email']}, {$input['username']}, {$input['password']},
                          {$input['take_priv']}, {$input['results_priv']}, {$input['edit_priv']})";
            }
            else
            {
                $uid = (int)$uid;
                $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET name = {$input['name']}, email = {$input['email']},
                          username = {$input['username']}, password = {$input['password']}, take_priv = {$input['take_priv']},
                          results_priv = {$input['results_priv']}, edit_priv = {$input['edit_priv']}
                          WHERE uid = $uid";
            }

            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            {
                $error[] = $this->lang['db_query_error']. $this->db->ErrorMsg();
            }
        }

        return $error;
    }

    // PROCESS SELECTED ACTION ON SELECTED USERS //
    function _processUsersAction($sid) {
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
            break;
        }
    }

    // DELETE USERS //
    function _processDeleteUsers($sid,$users) {
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
                    { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                    else
                    { $numdeleted++; }
                }
                else
                { $numtodelete--; }
            }
        }

        if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == MODE_MANAGE_INVITE) {
            $mode = MODE_MANAGE_INVITE;
        } else {
            $mode = MODE_MANAGE_USER;
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode={$mode}");

        if(empty($error))
        { $this->setMessage($this->lang['notice'], $numdeleted . $this->lang['users_deleted'], MSGTYPE_NOTICE); }
        else
        { $this->setMessage($this->lang['error'], $numdeleted . $this->lang['users_deleted'] . BR . implode(BR,$error),MSGTYPE_ERROR); }
    }

    // SEND USERNAME AND PASSWORD INFORMATION TO USER //
    function _processSendLoginInfo($sid,$users,$template) {
        set_time_limit(120);

        $sid = (int)$sid;
        $error = array();
        $numtoemail = 0;
        $numemailed = 0;
        $now = time();
        $counter = 0;

        //if user list was sent, flag complete list of users as awaiting login.
        if(!empty($users))
        {
            $list_to_email = array();
            foreach($users as $uid=>$val) {
                $uid = (int)$uid;
                if($uid) {
                    $list_to_email[] = $uid;
                }
            }

            if(!empty($list_to_email)) {
                $text_list = implode(',', $list_to_email);
                $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET status=".USERSTATUS_AWAITING_LOGIN.", status_date={$now} WHERE sid = {$sid} AND uid IN ({$text_list})";
                $rs = $this->db->Execute($query);
                if($rs === FALSE) {
                    $this->error($this->lang['no_flag_logins'] . $this->db->ErrorMsg());
                    return;
                }
            }
        }

        //Retrieve settings for survey
        $query = "SELECT * FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
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

            //Retrieve user information
            $query = "SELECT * FROM {$this->CONF['db_tbl_prefix']}users WHERE sid=$sid AND status=".USERSTATUS_AWAITING_LOGIN;
            $rsu = $this->db->SelectLimit($query,EMAILS_PER_REFRESH);
            if($rsu === FALSE)
            { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
            else
            {
                while($user = $rsu->FetchRow($rsu)) {
                    $counter++;

                    //Ensure user has an email set
                    if(!empty($user['email']))
                    {
                        $uid = $user['uid'];

                        //If user has permission to view results, set flag
                        //to show results URL in email
                        if($survey['public_results'])
                        { $user['results_priv'] = 1; }

                        //Retrieve email text
                        $mail = $this->_parseEmailTemplate($survey,$user,$template);

                        //Send email and update status of user to show they were
                        //sent a login reminder
                        if(!empty($mail)) {
                            $send = @mail($mail['to'],$mail['subject'],$mail['message'],$mail['headers']);
                            if($send) {
                                $numemailed++;
                                $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET status = ".USERSTATUS_SENTLOGIN.", status_date = {$now} WHERE uid=$uid AND sid=$sid";
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE)
                                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                            } else {
                                $error[] = $this->lang['send_email'] . $user['name'] . ' - ' . $user['email'];
                                //Set status back to zero since email could not be sent
                                $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET status = ".USERSTATUS_LOGIN_FAILED.", status_date={$now} WHERE uid={$uid} AND sid={$sid}";
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE) {
                                    $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg();
                                }
                            }
                        }
                    }
                    else
                    { $error[] = $this->lang['no_email'] . ' - ' . $user['username']; }
                }
            }
        }
        else
        { $error[] = $this->lang['invalid_survey']; }

        if($counter < EMAILS_PER_REFRESH) {
            if($counter == 0) {
                $msg = $this->lang['no_users_matched'];
            } else {
                $msg = $numemailed . $this->lang['users_emailed'];
            }
            $this->setMessageRedirect("access_control.php?sid=$sid&mode=".MODE_MANAGE_USER);
        } else {
            $msg = $numemailed . $this->lang['users_emailed'];
            $this->setMessageRedirect("access_control.php?sid={$sid}&mode=".MODE_MANAGE_USER."&users_selection=remind&users_go=1&refresh=".USERSTATUS_AWAITING_LOGIN);
        }

        if(empty($error))
        { $this->setMessage($this->lang['notice'],$msg,MSGTYPE_NOTICE); }
        else
        { $this->setMessage($this->lang['error'],$msg.BR.implode(BR,$error),MSGTYPE_ERROR); }
    }

    // PARSE EMAIL TEMPLATE //
    function _parseEmailTemplate(&$survey, &$user, $template) {
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
            { $retval['subject'] = $this->lang['email_subject']; }
        }

        return $retval;
    }

    // MOVE USERS TO/FROM USER/INVITEE LIST //
    function _processMoveToList($sid,$users,$status) {
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
                    { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                    else
                    { $nummoved++; }
                }
                else
                { $numtomove--; }
            }
        }

        $mode = MODE_MANAGE_USER;
        if(!empty($_REQUEST['mode'])) {
            $mode = ($_REQUEST['mode'] == MODE_MANAGE_INVITE) ? MODE_MANAGE_INVITE : MODE_MANAGE_USER;
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode={$mode}");
        $msg = $nummoved . $this->lang['users_moved'];

        if(empty($error))
        { $this->setMessage($this->lang['notice'],$msg,MSGTYPE_NOTICE); }
        else
        { $this->setMessage($this->lang['error'],$msg.BR.implode(BR,$error),MSGTYPE_ERROR); }
    }

    // PROCESS CHANGES TO INVITEE LIST //
    function _processUpdateInvite($sid) {
        $error = array();
        $erruid = array();

        //Loop through invitees and validate data. If the first character
        //of UID is 'x', then the information is for a new invitee
        if(!empty($_REQUEST['invite_name']))
        {
            foreach($_REQUEST['invite_name'] as $uid=>$name)
            {
                if($uid{0} != NEW_CODE || ($uid{0} == NEW_CODE && (!empty($_REQUEST['invite_name'][$uid]) || !empty($_REQUEST['invite_email'][$uid]))))
                {
                    $process_error = $this->_processInviteeData($uid, $sid, $_REQUEST);
                    if(!empty($process_error)) {
                        $error = array_merge($error, $process_error);
                        $erruid[$uid] = 1;
                    }
                }
            }
        }

        $this->setMessageRedirect("access_control.php?sid=$sid&mode=".MODE_MANAGE_INVITE);

        if(empty($error))
        { $this->setMessage($this->lang['notice'],$this->lang['invitee_added'],MSGTYPE_NOTICE); }
        else
        {
            $_SESSION['invite']['erruid'] = $erruid;
            $this->setMessage($this->lang['error'],implode(BR,$error),MSGTYPE_ERROR);
        }
    }

    function _processInviteeData($uid, $sid, $data) {

        $input = array();
        $error = array();

        //Validate email address (required)
        if(empty($data['invite_email'][$uid]))
        {
            $error[1] = $this->lang['invitee_email'];
        }
        elseif(strlen($data['invite_email'][$uid])<5 || strpos($data['invite_email'][$uid],'@')===FALSE)
        {
            $error[2] = $this->lang['invitee_bad_email'];
        }
        else
        { $input['email'] = $this->SfStr->getSafeString($data['invite_email'][$uid],SAFE_STRING_DB); }

        //Validate name and set status to INVITEE
        $input['name'] = $this->SfStr->getSafeString($data['invite_name'][$uid],SAFE_STRING_DB);
        $input['status'] = USERSTATUS_INVITEE;

        if(isset($data['invite_results_priv'][$uid]))
        { $input['results_priv'] = 1; }
        else
        { $input['results_priv'] = 0; }

        if(!empty($data['invite_code'][$uid])) {
            $input['invite_code'] = $this->SfStr->getSafeString($data['invite_code'][$uid],SAFE_STRING_DB);
        } else {
            $input['invite_code'] = $this->SfStr->getSafeString('',SAFE_STRING_DB);
        }

        //If there were no errors, INSERT or UPDATE invitee information
        if(empty($error))
        {
            if($uid{0}==NEW_CODE)
            {
                $uid = $this->db->GenID($this->CONF['db_tbl_prefix'].'users_sequence');
                $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}users (uid, sid, name, email, status, results_priv,invite_code)
                          VALUES ($uid, $sid, {$input['name']}, {$input['email']}, {$input['status']},{$input['results_priv']},{$input['invite_code']})";
            }
            else
            {
                $uid = (int)$uid;
                $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET name = {$input['name']},
                          email = {$input['email']}, results_priv = {$input['results_priv']}, invite_code = {$input['invite_code']}
                          WHERE uid=$uid AND sid=$sid";
            }

            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        }

        return $error;
    }

    // PROCESS SELECTED ACTION ON SELECTED INVITEES //
    function _processInviteAction($sid) {
        $sid = (int)$sid;

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
    function _processSendInvitation($sid,$users,$template) {
        @set_time_limit(120);

        $sid = (int)$sid;
        $error = array();
        $numtoemail = 0;
        $numemailed = 0;
        $counter = 0;
        $now = time();

        if(isset($_REQUEST['invite_code_type'])) {
            $_SESSION['invite_code_type'] = @$_REQUEST['invite_code_type'];
            $_SESSION['invite_alphanumericcode_length'] = @$_REQUEST['invite_alphanumericcode_length'];
            $_SESSION['invite_alphacode_length'] = @$_REQUEST['invite_alphacode_length'];
            $_SESSION['invite_numcode_length'] = @$_REQUEST['invite_numcode_length'];
        }

        //if user list was sent, flag complete list of users as awaiting login.
        if(!empty($users))
        {
            $list_to_email = array();
            foreach($users as $uid=>$val) {
                $uid = (int)$uid;
                if($uid) {
                    $list_to_email[] = $uid;
                }
            }

            if(!empty($list_to_email)) {
                $text_list = implode(',', $list_to_email);
                $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET status=".USERSTATUS_AWAITING_INVITE.", status_date={$now} WHERE sid = {$sid} AND uid IN ({$text_list})";
                $rs = $this->db->Execute($query);
                if($rs === FALSE) {
                    $this->error($this->lang['no_flag_logins'] . $this->db->ErrorMsg());
                    return;
                }
            }
        }

        $query = "SELECT * FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
        elseif($survey = $rs->FetchRow($rs))
        {
            //Create variables to be used in email template
            $survey['main_url'] = $this->CONF['html'];
            $survey['take_url'] = $this->CONF['html'] . "/survey.php?sid=$sid";
            $survey['results_url'] = $this->CONF['html'] . "/results.php?sid=$sid";

            $this->smarty->assign_by_ref('survey',$survey);
            $user = array();
            $this->smarty->assign_by_ref('user',$user);

            //Retrieve information for selected invitee
            $query = "SELECT * FROM {$this->CONF['db_tbl_prefix']}users WHERE sid=$sid AND status = ".USERSTATUS_AWAITING_INVITE;
            $rsu = $this->db->SelectLimit($query,EMAILS_PER_REFRESH);
            if($rsu === FALSE)
            { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
            else
            {
                while($user = $rsu->FetchRow($rsu))
                {
                    $counter++;
                    if(!empty($user['email']))
                    {
                        //Set flag for whether user has privileges to view results
                        if($survey['public_results'])
                        { $user['results_priv'] = 1; }

                        //Retreive random code for user to access survey
                        //and set URL to take survey to be used in email
                        $user['code'] = $this->_getInviteCode($sid,$user['uid'],$_SESSION['invite_code_type']);
                        $user['take_url'] = $survey['take_url'] . '&invite_code=' . urlencode($user['code']);

                        //Retrieve email template
                        $mail = $this->_parseEmailTemplate($survey,$user,$template);

                        if(!empty($mail) && $user['code'])
                        {
                            //Send email and update invitee status to INVITED
                            $send = @mail($mail['to'],$mail['subject'],$mail['message'],$mail['headers']);
$send = true;
                            if($send)
                            {
                                $numemailed++;
                                $user['code'] = $this->SfStr->getSafeString($user['code'],SAFE_STRING_DB);

                                $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET take_priv = 1, status = ".USERSTATUS_INVITED.", status_date = {$now}, invite_code={$user['code']} WHERE uid={$user['uid']} AND sid=$sid";
                                $rs2 = $this->db->Execute($query);
                                if($rs2 === FALSE)
                                { $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                            }
                            else
                            {
                                $error[] = $this->lang['invite_no_send'] . ' - ' . $user['name'] . ' ' . $user['email'];
                                $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET status=".USERSTATUS_INVITE_FAILED." WHERE uid={$user['uid']} AND sid={$sid}";
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE) {
                                    $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg();
                                }
                            }
                        }
                        else
                        { $error[] = $this->lang['invite_no_code'] . ' - ' . $user['uid']; }
                    }
                    else
                    { $error[] = $this->lang['no_emaill'] . ' - ' . $user['username']; }
                }
            }
        }
        else
        { $error[] = $this->lang['invalid_survey']; }

        if($counter < EMAILS_PER_REFRESH) {
            if($counter == 0) {
                $msg = $this->lang['no_users_matched'];
            } else {
                $msg = $numemailed . $this->lang['users_invited'];
            }
            $this->setMessageRedirect("access_control.php?sid=$sid&mode=".MODE_MANAGE_INVITE);
        } else {
            $msg = $numemailed . $this->lang['users_invited'];
            $this->setMessageRedirect("access_control.php?sid={$sid}&mode=".MODE_MANAGE_INVITE."&invite_selection=invite&invite_go=1&refresh=".USERSTATUS_AWAITING_INVITE);
        }

        if(empty($error))
        { $this->setMessage($this->lang['notice'],$msg,MSGTYPE_NOTICE); }
        else
        { $this->setMessage($this->lang['error'],$msg.BR.implode(BR,$error),MSGTYPE_ERROR); }
    }

    // GENERATE RANDOM INVITATION CODE FOR INVITEES //
    function _getInviteCode($sid,$uid,$type) {
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
                case INVITECODE_NUMERIC:
                    $code = $this->_getNumericCode($_SESSION['invite_numcode_length']);
                break;
                case INVITECODE_ALPHA:
                    $code = $this->_getAlphaCode($_SESSION['invite_alphacode_length']);
                break;
                case INVITECODE_ALPHANUMERIC:
                default:
                    $code = $this->_getAlphanumericCode($_SESSION['invite_alphanumericcode_length']);
                break;

            }

            //Ensure code is not already being used in this survey.
            if($code)
            {
                $dbcode = $this->SfStr->getSafeString($code,SAFE_STRING_DB);
                $query = "SELECT invite_code FROM {$this->CONF['db_tbl_prefix']}users WHERE sid=$sid AND invite_code={$dbcode}";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
                elseif($r = $rs->FetchRow($rs))
                {
                    $code = FALSE;
                    $recursion_level++;
                }
            }
        }

        $recursion_level = 0;

        //Moved saving of invite code to after successful email is sent
        //Update user information to include the code that was chosen
        /*if($code)
        {
            $query = "UPDATE {$this->CONF['db_tbl_prefix']}users SET invite_code = {$dbcode} WHERE sid=$sid AND uid=$uid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
        }*/

        return $code;
    }

    // GENERATE ALPHABETIC RANDOM INVITATION CODE //
    function _getAlphaCode($length) {
        $retval = '';
        static $values = '';
        static $numvalues = 0;

        $length = (int)$length;
        if($length <= 0 || $length > ALPHANUMERIC_MAXLENGTH)
        { $length = ALPHANUMERIC_DEFAULTLENGTH; }

        //Create code from the values in $str for the requested length
        if(empty($values))
        {
            //note: Some letters and numbers were left out to avoid confusiong between 1 (one) and I (capital letter I) and l (lowercase letter l), for example.
            $str = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
            $values = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
            $numvalues = count($values);
        }

        for($x=0;$x<$length;$x++)
        { $retval .= $values[mt_rand(0,$numvalues-1)]; }

        return $retval;
    }

    // GENERATE ALPHANUMERIC RANDOM INVITATION CODE //
    function _getAlphanumericCode($length) {
        $retval = '';
        static $values = '';
        static $numvalues = 0;

        $length = (int)$length;
        if($length <= 0 || $length > ALPHANUMERIC_MAXLENGTH)
        { $length = ALPHANUMERIC_DEFAULTLENGTH; }

        //Create code from the values in $str for the requested length
        if(empty($values))
        {
            //note: Some letters and numbers were left out to avoid confusiong between 1 (one) and I (capital letter I) and l (lowercase letter l), for example.
            $str = "23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
            $values = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
            $numvalues = count($values);
        }

        for($x=0;$x<$length;$x++)
        { $retval .= $values[mt_rand(0,$numvalues-1)]; }

        return $retval;
    }

    // Generate numeric code a certain number of digits long
    function _getNumericCode($length) {
        $retval = 0;

        $length = (int)$length;
        if($length <= 0 || $length > NUMERIC_MAXLENGTH)
        { $length = NUMERIC_DEFAULTLENGTH; }

        $max = pow(10,$length);
        $retval = mt_rand(1,$max);
        $retval = str_pad($retval,$length,'0',STR_PAD_LEFT);

        return $retval;
    }

    // GENERATE ENGLISH WORD CODE //
    function _getWordCode() {
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

    function _processRefresh($sid, $mode, $selection) {
        $this->data['content'] = 'refresh';

        switch($selection) {
            case 'refresh':
                $this->data['url_variables'] = 'users_selection=remind';
            break;
        }

        return;
    }

    function _processFile($sid, $type, $files) {
        $error = array();
        $mode = ($type == INVITE_FILE) ? MODE_MANAGE_INVITE : MODE_MANAGE_USER;
        $num_users = 0;
        $linenum = 1;
        $ignore_uid = FALSE;


        if(isset($files['error']) && $files['error'] != UPLOAD_ERR_OK) {
            $error[] = $this->lang['file_errors'.$files['error']];
            $msg = $this->lang['file_error'];
        } else {
            $fp = fopen($files['tmp_name'], 'r');
            if($fp) {

                //Retrieve current access control and public results setting for survey
                //to determine what fields are required for users.
                if($type == USER_FILE) {
                    $query = "SELECT access_control, public_results FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $error[2] = $this->lang['db_query_error'] . $this->db->ErrorMsg(); }
                    else
                    {
                        $r = $rs->FetchRow($rs);
                        $access_control = $r['access_control'];
                        $public_results = $r['public_results'];
                    }
                }

                if(isset($_POST['ignore_uid'])) {
                    $ignore_uid = TRUE;
                }

                while($line = fgetcsv($fp,1024,',','"')) {
                    $data = array();

                    $uid = @$line[0];
                    if(empty($uid) || $ignore_uid) {
                        $uid = NEW_CODE;
                    }

                    switch($type) {
                        case USER_FILE:
                            $data['name'][$uid] = @$line[1];
                            $data['email'][$uid] = @$line[2];
                            $data['username'][$uid] = @$line[3];
                            $data['password'][$uid] = @$line[4];
                            $data['take_priv'][$uid] = @$line[5];
                            $data['results_priv'][$uid] = @$line[6];
                            $data['edit_priv'][$uid] = @$line[7];

                            $process = $this->_processUserData($uid, $sid, $access_control, $public_results, $data);
                        break;
                        case INVITE_FILE:
                            $data['invite_name'][$uid] = @$line[1];
                            $data['invite_email'][$uid] = @$line[2];
                            $data['invite_code'][$uid] = @$line[3];
                            $data['invite_results_priv'][$uid] = @$line[4];

                            if(empty($data['uid'])) {
                                $data['uid'] = NEW_CODE;
                            }
                            $process = $this->_processInviteeData($uid, $sid, $data);
                        break;
                    }

                    if(!empty($process)) {
                        $error[] = $this->lang['error_line'] . $line;
                        $error = array_merge($error, $process);
                    } else {
                        $num_users++;
                    }
                    $linenum++;
                }
            } else {
                $msg = $this->lang['file_error'] . $this->lang['file_open'];
            }
        }

        $this->setMessageRedirect("access_control.php?sid={$sid}&mode={$mode}");

        if($num_users == 0) {
            $msg = $this->lang['no_users_matched'];
        } else {
            $msg = $num_users . $this->lang['users_loaded'];
        }

        if(empty($error)) {
            $this->setMessage($this->lang['notice'],$msg,MSGTYPE_NOTICE);
        }else {
            $this->setMessage($this->lang['error'],$msg.BR.implode(BR,$error),MSGTYPE_ERROR);
        }
    }

    function _processExport($sid, $type) {
        $error = array();
        $sid = (int)$sid;
        $where = '';

        if($sid) {
            switch($type) {
                case USER_FILE:
                    $where = "AND status IN (".USERSTATUS_NONE.",".USERSTATUS_SENTLOGIN.",".USERSTATUS_AWAITING_LOGIN.",".USERSTATUS_LOGIN_FAILED.")";
                break;
                case INVITE_FILE:
                    $where = "AND status IN (".USERSTATUS_INVITEE.",".USERSTATUS_INVITED.",".USERSTATUS_AWAITING_INVITE.",".USERSTATUS_INVITE_FAILED.")";
                break;
            }
            $query = "SELECT uid, name, email, username, password, take_priv, results_priv, edit_priv, invite_code FROM {$this->CONF['db_tbl_prefix']}users WHERE sid = {$sid} {$where} ORDER BY uid ASC";
            $rs = $this->db->Execute($query);
            if($rs === FALSE) {
                $error[] = $this->lang['db_query_error'] . $this->db->ErrorMsg();
            } else {
                header("Content-Type: text/plain; charset={$this->CONF['charset']}");
                header("Content-Disposition: attachment; filename={$this->lang['csv_filename']}");

                while($r = $rs->FetchRow($rs)) {
                    switch($type) {
                        case USER_FILE:
                            echo "{$r['uid']},\"{$r['name']}\",\"{$r['email']}\",\"{$r['username']}\",\"{$r['password']}\",{$r['take_priv']},{$r['results_priv']},{$r['edit_priv']}".CR.NL;
                        break;
                        case INVITE_FILE:
                            echo "{$r['uid']},\"{$r['name']}\",\"{$r['email']}\",\"{$r['invite_code']}\",{$r['results_priv']}".CR.NL;
                        break;
                    }
                }
            }
        } else {
            $error[] = $this->lang['survey_not_exist'];
        }

        if(!empty($error)) {
            if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == MODE_MANAGE_INVITE) {
                $mode = MODE_MANAGE_INVITE;
            } else {
                $mode = MODE_MANAGE_USER;
            }

            $this->setMessageRedirect("access_control.php?sid={$sid}&mode={$mode}");
            $this->setMessage($this->lang['error'],BR.implode(BR,$error),MSGTYPE_ERROR);
        } else {
            exit();
        }
    }
}
?>