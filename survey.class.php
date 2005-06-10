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

//Set Error Reporting Level to not
//show notices or warnings
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
//error_reporting(E_ALL);

//Turn off runtime escaping of quotes
set_magic_quotes_runtime(0);

//Define CONSTANTS
define('BY_AID',1);
define('BY_QID',2);

define('SAFE_STRING_TEXT',0);
define('SAFE_STRING_LIMHTML',1);
define('SAFE_STRING_FULLHTML',2);
define('SAFE_STRING_DB',3);
define('SAFE_STRING_ESC',4);

class Survey
{
    //Default variables
    var $smarty;
    var $db;
    var $survey_name = '';
    var $CONF;

    /**************
    * CONSTRUCTOR *
    **************/
    function Survey()
    {
        session_start();
        $this->load_configuration();
    }

    /*********************
    * LOAD CONFIGURATION *
    *********************/
    function load_configuration()
    {
        //Ensure install.php file has be removed
        if(!isset($_REQUEST['config_submit']) && file_exists('install.php'))
        { $this->error("WARNING: install.php file still exists. Survey System will not run with this file present. Click <a href=\"install.php\">here</a> to run the installation program or move/rename the install.php file so that the installation program can not be re-run."); return; }

        $ini_file = 'survey.ini.php';
        //Load values from .ini. file
        if(file_exists($ini_file))
        {
            $this->CONF = @parse_ini_file($ini_file);
            if(count($this->CONF) == 0)
            { $this->error("Error parsing {$ini_file} file"); return; }
        }
        else
        { $this->error("Cannot find {$ini_file}"); return; }

        //Version of Survey System
        $this->CONF['version'] = 'v1.05';

        //Default path to Smarty
        if(!isset($this->CONF['smarty_path']) || $this->CONF['smarty_path'] == '')
        { $this->CONF['smarty_path'] = $this->CONF['path'] . '/smarty'; }

        //Default path to ADOdb
        if(!isset($this->CONF['adodb_path']) || $this->CONF['adodb_path'] == '')
        { $this->CONF['adodb_path'] = $this->CONF['path'] . '/ADOdb'; }

        //Load ADOdb files
        $adodb_file = $this->CONF['adodb_path'] . '/adodb.inc.php';
        if(file_exists($adodb_file))
        { require($this->CONF['adodb_path'] . '/adodb.inc.php'); }
        else
        { $this->error("Cannot find file: $adodb_file"); return; }

        //Load Smarty Files
        $smarty_file = $this->CONF['smarty_path'] . '/Smarty.class.php';
        if(file_exists($smarty_file))
        { require($this->CONF['smarty_path'] . '/Smarty.class.php'); }
        else
        { $this->error("Cannot find file: $smarty_file"); return; }

        //Create Smarty object and set
        //paths within object
        $this->smarty = new Smarty;
        $this->smarty->template_dir    =  $this->CONF['path'] . '/templates';                    // name of directory for templates
        $this->smarty->compile_dir     =  $this->CONF['smarty_path'] . '/templates_c';     // name of directory for compiled templates
        $this->smarty->config_dir      =  $this->CONF['smarty_path'] . '/configs';         // directory where config files are located
        $this->smarty->plugins_dir     =  array($this->CONF['smarty_path'] . '/plugins');  // plugin directories

        if(!$this->set_template_paths($this->CONF['default_template']))
        { $this->error("WARNING: Cannot find default template path. Expecting: {$this->CONF['template_path']}"); return; }

        //Ensure templates_c directory is writable
        if(!is_writable($this->smarty->compile_dir))
        { $this->error("WARNING: Compiled template directory is not writable ({$this->smarty->compile_dir}). Please refer to the installation document for instructions."); return; }

        //If SAFE_MODE is ON in PHP, turn off subdirectory use for Smarty
        if(ini_get('safe_mode'))
        { $this->smarty->use_sub_dirs = FALSE; }

        //Establish Connection to database
        $this->db = NewADOConnection($this->CONF['db_type']);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $conn = $this->db->Connect($this->CONF['db_host'],$this->CONF['db_user'],$this->CONF['db_password'],$this->CONF['db_database']);
        if(!$conn)
        { $this->error('Error connecting to database: '. $this->db->ErrorMsg()); return; }

        $this->CONF['orientation'] = array('Vertical','Horizontal','Dropdown','Matrix');
        $this->CONF['text_modes'] = array('Text Only','Limited HTML','Full HTML');
        $this->CONF['dependency_modes'] = array('Hide','Require','Show');

        //Validate and set default survey and user text modes
        $this->CONF['survey_text_mode'] = (int)$this->CONF['survey_text_mode'];
        if($this->CONF['survey_text_mode'] < 0 || $this->CONF['survey_text_mode'] > 2)
        { $this->CONF['survey_text_mode'] = 0; }

        $this->CONF['user_text_mode'] = (int)$this->CONF['user_text_mode'];
        if($this->CONF['user_text_mode'] < 0 || $this->CONF['user_text_mode'] > 2)
        { $this->CONF['user_text_mode'] = 0; }

        //Assign configuration values to template
        $this->smarty->assign_by_ref('conf',$this->CONF);

        return;
    }

    /*********************
    * SET TEMPLATE PATHS *
    *********************/
    function set_template_paths($template)
    {
        $this->template = $template;

        $this->CONF['template_path'] = $this->CONF['path'] . '/templates/' . $template;
        if(!file_exists($this->CONF['template_path']))
        { return(FALSE); }

        $this->CONF['template_html'] = $this->CONF['html'] . '/templates/' . $template;

        if(file_exists($this->CONF['template_path'] . '/images'))
        {
            $this->CONF['images_html'] = $this->CONF['html'] . '/templates/' . $template . '/images';
            $this->CONF['images_path'] = $this->CONF['path'] . '/templates/' . $template . '/images';
        }
        else
        {
            $this->CONF['images_html'] = $this->CONF['html'] . '/templates/' . $template;
            $this->CONF['images_path'] = $this->CONF['path'] . '/templates/' . $template;
        }

        return(TRUE);
    }

    /*********
    * HEADER *
    *********/
    function com_header($title='')
    {
        //Assign title of page to template
        //and return header template
        if(empty($title))
        { $values['title'] = $this->CONF['site_name']; }
        else
        { $values['title'] = $this->safe_string($title,SAFE_STRING_TEXT); }

        $this->smarty->assign_by_ref('values',$values);
        return $this->smarty->fetch($this->template.'/main_header.tpl') . $this->showmessage();
    }

    /*********
    * FOOTER *
    *********/
    function com_footer()
    {
        //Close connection to database
        $this->db->Close();

        //Return footer template
        return $this->smarty->fetch($this->template.'/main_footer.tpl');
    }

    /*********************
    * PROCESS NEW SURVEY *
    *********************/
    function process_survey($s)
    {
        //$s is all data to create new survey

        //Default variables
        $sid = FALSE;
        $page = 1;
        $oid = 1;

        //Default values for new survey
        $s['activate'] = 0;
        $s['template'] = $this->CONF['default_template'];
        $s['date_format'] = $this->safe_string($this->CONF['date_format'],SAFE_STRING_ESC);
        $s['created'] = time();

        //////////////////
        //CREATE SURVEY //
        //////////////////
        $sid = $this->db->GenID($this->CONF['db_tbl_prefix'].'surveys_sequence');

        $sql[1] = "INSERT INTO {$this->CONF['db_tbl_prefix']}surveys (sid, name, active, edit_password, template, date_format, created) VALUES
                   ($sid,'{$s['survey_name']}',{$s['activate']},'{$s['edit_password']}','{$s['template']}','{$s['date_format']}',{$s['created']})";
        if($rs1 = $this->query($sql[1],'Error creating survey'))
        {
            //Make copy of "copy_sid". If "copy_sid" key is not
            //passed in $s array, then use Zero to copy a
            //predetermined set of answer types and values
            //to new servey.
            if(!isset($s['copy_sid']))
            { $copy_sid = 0; }
            else
            { $copy_sid = $s['copy_sid']; }

            ///////////////////////////////////////////////
            // COPY ANSWERS AND VALUES FROM OTHER SURVEY //
            ///////////////////////////////////////////////
            $query = "SELECT aid, name, type, label FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE sid = {$copy_sid}";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error('Error retrieving answer types: ' . $this->db->ErrorMsg()); }
            while($r = $rs->FetchRow($rs))
            {
                $name = $this->safe_string($r['name'],SAFE_STRING_ESC);
                $type = $this->safe_string($r['type'],SAFE_STRING_ESC);
                $label = $this->safe_string($r['label'],SAFE_STRING_ESC);
                $aid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_types_sequence');
                $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_types (aid, name, type, label, sid) VALUES
                          ($aid, '$name','$type','$label',$sid)";
                $rs2 = $this->db->Execute($query);
                if($rs2 === FALSE)
                { $this->error('Error copying answer type: ' . $this->db->ErrorMsg()); }

                $s['new_aid'][$r['aid']] = $aid;

                $query = "SELECT avid, value, group_id, image FROM {$this->CONF['db_tbl_prefix']}answer_values
                          WHERE aid = {$r['aid']}";
                $rs3 = $this->db->Execute($query);
                if($rs3 === FALSE)
                { $this->error('Error retrieving answer values: ' . $this->db->ErrorMsg()); }
                while($r3 = $rs3->FetchRow($rs3))
                {
                    $value = $this->safe_string($r3['value'],SAFE_STRING_ESC);
                    $image = $this->safe_string($r3['image'],SAFE_STRING_ESC);
                    $avid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_values_sequence');

                    $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_values (avid, aid, value, group_id, image)
                              VALUES ($avid, $aid,'$value',{$r3['group_id']},'$image')";
                    $rs4 = $this->db->Execute($query);
                    if($rs4 === FALSE)
                    { $this->error('Error copying answer value: ' . $this->db->ErrorMsg()); }

                    $s['new_avid'][$r3['avid']] = $avid;
                }
            }

            //////////////////////
            // INSERT QUESTIONS //
            //////////////////////
            if(isset($s['question']) && count($s['question'])>0)
            {
                //Loop through each question and create SQL
                //needed to insert them into table
                $numq = count($s['question']);
                for($x=0;$x<$numq;$x++)
                {
                    //If question matches "page break" text, increment
                    //the $page counter, and reset the order ID (oid) counter
                    if(strcasecmp($s['question'][$x],$this->CONF['page_break']) == 0)
                    { $page++; $oid = 1;}
                    else
                    {
                        $aid = $s['new_aid'][$s['answer'][$x]];
                        //Create SQL to insert question and increment order ID (oid)
                        $qid = $this->db->GenID($this->CONF['db_tbl_prefix'].'questions_sequence');
                        $q = "($qid,'{$s['question'][$x]}',$aid,{$s['num_answers'][$x]},$sid,$page,{$s['num_required'][$x]},$oid,'{$s['orientation'][$x]}')";
                        $sql[2] = "INSERT INTO {$this->CONF['db_tbl_prefix']}questions (qid,question,aid,num_answers,sid,page,num_required,oid,orientation) VALUES $q";
                        $rs2 = $this->query($sql[2],'Error inserting question');
                        $s['new_qid'][$s['qid'][$x]] = $qid;

                        $oid++;
                    }
                }

                ///////////////////////
                // COPY DEPENDENCIES //
                ///////////////////////
                if(isset($s['copy_sid']))
                {
                    $query = "SELECT dep_id, qid, dep_qid, dep_aid, dep_option FROM {$this->CONF['db_tbl_prefix']}dependencies
                              WHERE sid = {$s['copy_sid']}";
                    $rs = $this->db->query($query,'Error retrieving dependencies');

                    $dep_insert = '';
                    while($r = $rs->FetchRow($rs))
                    {
                        //Replace old question IDs with
                        //new question IDs of questions just inserted above
                        $qid = $s['new_qid'][$r['qid']];
                        $dep_qid = $s['new_qid'][$r['dep_qid']];
                        $dep_aid = $s['new_avid'][$r['dep_aid']];

                        $dep_id = $this->db->GenID($this->CONF['db_tbl_prefix'].'dependencies_sequence');
                        $dep_insert .= "($dep_id, $sid, $qid, $dep_qid, $dep_aid, '{$r['dep_option']}'),";
                    }

                    if(!empty($dep_insert))
                    {
                        $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}dependencies (dep_id, sid, qid, dep_qid, dep_aid, dep_option)
                                  VALUES " . substr($dep_insert,0,-1);
                        $rs = $this->db->Query($query,'Error inserting dependencies');
                    }
                }
            }
        }

        //Return the Survey ID (sid)
        //of newly created survey
        return $sid;
    }

    /********************
    * AVAILABLE SURVEYS *
    ********************/
    function available_surveys()
    {
        if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout')
        {
            unset($_SESSION['admin_logged_in']);
            unset($_SESSION['edit_survey']);
        }

        $survey = array();

        $x = 0;
        $y = 0;
        $now = time();

        //Turn on/off surveys depending on start/end date
        $rs = $this->Query("UPDATE {$this->CONF['db_tbl_prefix']}surveys SET active = 1 WHERE start_date != 0 AND (start_date < $now) OR (start_date < $now AND $now < end_date)");
        $rs = $this->Query("UPDATE {$this->CONF['db_tbl_prefix']}surveys SET active = 0 WHERE end_date != 0 AND ($now < start_date OR $now > end_date)");

        $query = "SELECT sid, name, survey_access, results_access, active, survey_text_mode FROM {$this->CONF['db_tbl_prefix']}surveys ORDER BY name ASC";
        $rs = $this->Query($query,'Unable to get survey access information');
        while($r = $rs->FetchRow())
        {
            $survey_name = $this->safe_string($r['name'],$r['survey_text_mode']);
            $all_surveys['name'][] = $survey_name;
            $all_surveys['sid'][] = $r['sid'];

            if($r['active'] == 1)
            {
                if($r['survey_access'] == 'public')
                {
                    $survey[$x]['display'] = $survey_name;
                    $survey[$x]['sid'] = $r['sid'];
                    if($r['results_access'] == 'public')
                    { $results[$x] = TRUE; }
                    else
                    {
                        $priv_results['display'][] = $survey_name;
                        $priv_results['sid'][] = $r['sid'];
                    }
                    $x++;
                }
                else
                {
                    $priv_survey['display'][] = $survey_name;
                    $priv_survey['sid'][] = $r['sid'];
                    $priv_results['display'][] = $survey_name;
                    $priv_results['sid'][] = $r['sid'];
                }
            }
        }

        if(isset($_SESSION['admin_logged_in']) || !empty($_SESSION['edit_survey']))
        { $show['logout'] = TRUE; }
        else
        { $show['logout'] = FALSE; }

        $this->smarty->assign_by_ref('show',$show);

        $this->smarty->assign_by_ref("all_surveys",$all_surveys);

        if(isset($survey) && count($survey) > 0)
        { $this->smarty->assign_by_ref("survey",$survey); }
        if(isset($results))
        { $this->smarty->assign_by_ref('results',$results); }
        if(isset($priv_survey))
        { $this->smarty->assign_by_ref('priv_survey',$priv_survey); }
        if(isset($priv_results))
        { $this->smarty->assign_by_ref('priv_results',$priv_results); }

        $retval = $this->smarty->fetch($this->template.'/available_surveys.tpl');

        return $retval;
    }

    /*************
    * NEW SURVEY *
    *************/
    function new_survey()
    {
        //If Clear button is pressed, reset
        //step to zero
        if(isset($_REQUEST['clear']))
        {
            unset($_REQUEST);
            unset($_SESSION['new_survey']);
        }

        ////////////////////
        // PROCESS SURVEY //
        ////////////////////
        $error = "";

        if(isset($_REQUEST['next']))
        {
            // PROCESS NAME OF FORM
            if(strlen($_REQUEST['survey_name']) > 0)
            {
                $name = $this->safe_string($_REQUEST['survey_name'],SAFE_STRING_DB);
                $query = "SELECT 1 FROM {$this->CONF['db_tbl_prefix']}surveys WHERE name = '$name'";
                $rs = $this->Query($query,'Unable to see if survey name matches another');

                if($rs->FetchRow($rs))
                { $error = "A survey already exists with that name."; }
                else
                {
                    $_SESSION['new_survey']['survey_name'] = $name;
                    @$_SESSION['new_survey']['step']++;
                }
            }
            else
            { $error = "Please enter a name. "; }

            if($copy_sid = (int)$_REQUEST['copy_survey'])
            {
                $query = "SELECT sid FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid = $copy_sid AND survey_access='Public'";
                $rs = $this->Query($query,'Error getting copy survey info');

                if($r = $rs->FetchRow($rs))
                {
                    $_SESSION['new_survey']['copy_sid'] = $copy_sid;

                    $query = "SELECT qid, question, aid, num_answers, num_required, page, orientation FROM {$this->CONF['db_tbl_prefix']}questions
                              WHERE sid = $copy_sid ORDER BY page, oid";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $this->error("Error getting questions to copy survey: " . $this->db->ErrorMsg()); return; }

                    $old_page = 1;
                    $x = 0;
                    while($r = $rs->FetchRow($rs))
                    {
                        if($r['page'] != $old_page)
                        {
                            $_SESSION['new_survey']['question'][$x] = $this->CONF['page_break'];
                            $_SESSION['new_survey']['answer'][$x] = 0;
                            $_SESSION['new_survey']['num_answers'][$x] = 0;
                            $_SESSION['new_survey']['num_required'][$x] = 0;
                            $old_page = $r['page'];
                            $x++;
                        }

                        $_SESSION['new_survey']['qid'][$x] = $r['qid'];
                        $_SESSION['new_survey']['question'][$x] = $r['question'];
                        $_SESSION['new_survey']['answer'][$x] = $r['aid'];
                        $_SESSION['new_survey']['num_answers'][$x] = $r['num_answers'];
                        $_SESSION['new_survey']['num_required'][$x] = $r['num_required'];
                        $_SESSION['new_survey']['orientation'][$x] = $r['orientation'];

                        $x++;
                    }
                }
                else
                { $error = "Invalid survey passed to copy"; }
            }

            if(strlen($_REQUEST['edit_password']) == 0 || strlen($_REQUEST['edit_password'] > 20))
            { $error .= "Edit Password is not set or exceeds 20 characters. "; }
            else
            { $_SESSION['new_survey']['edit_password'] = $_REQUEST['edit_password']; }

            if(strlen($error) == 0)
            {
                $r = $this->process_survey($_SESSION['new_survey']);
                if(is_int($r))
                {
                    $_SESSION['new_survey']['sid'] = $r;
                    unset($_SESSION['new_survey']);
                    $_SESSION['edit_survey'][$r] = 1;
                    header("Location: {$this->CONF['html']}/edit_survey.php?sid=$r");
                    exit();
                }
                else
                { $error = $r; }
            }
        }

        $show['start_over_button'] = TRUE;
        $show['next_button'] = TRUE;

        //////////////////////////////
        // DISPLAY COPY SURVEY LIST //
        //////////////////////////////
        $public_surveys = Array();

        $show['survey_name'] = TRUE;

        if(isset($_SESSION['new_survey']['survey_name']))
        { $this->smarty->assign('survey_name',$this->safe_string($_SESSION['new_survey']['survey_name'],SAFE_STRING_TEXT)); }

        $query = "SELECT sid, name FROM {$this->CONF['db_tbl_prefix']}surveys WHERE survey_access = 'Public' order by name ASC";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error("Cannot select public surveys: " . $this->db->ErrorMsg()); return; }

        $public_surveys['sid'][] = '';
        $public_surveys['name'][] = 'None - Start with blank survey';

        while($r = $rs->FetchRow($rs))
        {
            $public_surveys['sid'][] = $r['sid'];
            $public_surveys['name'][] = $this->safe_string($r['name'],SAFE_STRING_TEXT);
        }
        $this->smarty->assign('public_surveys',$public_surveys);

        //Assign Smarty variables
        $this->smarty->assign('show',$show);

        if(isset($url))
        { $this->smarty->assign('url',$url); }

        if(isset($button))
        { $this->smarty->assign('button',$button); }

        if(isset($error))
        { $this->smarty->assign('error',$error); }

        //Retrieve parsed smarty template
        $retval = $this->smarty->fetch($this->template.'/add_survey.tpl');

        return $retval;
    }

    /***************************
    * DISPLAY POSSIBLE ANSWERS *
    ***************************/
    function display_answers($sid)
    {
        $old_name = '';
        $x = 0;
        $sid = (int)$sid;

        $rs = $this->db->Execute("SELECT at.name, at.type, at.label, av.value, s.survey_text_mode
                                  FROM {$this->CONF['db_tbl_prefix']}answer_types at
                                  LEFT JOIN {$this->CONF['db_tbl_prefix']}answer_values av ON at.aid = av.aid,
                                  {$this->CONF['db_tbl_prefix']}surveys s
                                  WHERE s.sid = $sid AND s.sid = at.sid
                                  ORDER BY name, av.avid ASC");

        if($rs === FALSE) { die($this->db->ErrorMsg()); }
        while($r = $rs->FetchRow())
        {
            if($old_name != $r['name'])
            {
                if(!empty($old_name))
                { $x++; }

                $answers[$x]['name'] = $this->safe_string($r['name'],$r['survey_text_mode']);
                $answers[$x]['type'] = $r['type'];
                $answers[$x]['value'][] = $this->safe_string($r['value'],$r['survey_text_mode']);

                if(empty($r['label']))
                { $answers[$x]['label'] = '&nbsp;'; }
                else
                { $answers[$x]['label'] = $this->safe_string($r['label'],$r['survey_text_mode']); }


                $old_name = $r['name'];
            }
            else
            { $answers[$x]['value'][] = $this->safe_string($r['value'],$r['survey_text_mode']); }
        }

        $this->smarty->assign_by_ref("answers",$answers);

        $retval = $this->smarty->fetch($this->template.'/display_answers.tpl');

        return $retval;
    }

    /**************
    * TAKE SURVEY *
    **************/
    function take_survey($sid)
    {
        //defaults
        $show['previous_button'] = TRUE;
        $show['next_button'] = TRUE;
        $show['quit_button'] = TRUE;
        $show['page_num'] = TRUE;
        $now = time();
        $sid = (int)$sid;
        $stay_on_same_page = 0;

        if(!isset($_SESSION['take_survey']['sid']))
        { $_SESSION['take_survey']['sid'] = $sid; }
        elseif($_SESSION['take_survey']['sid'] != $sid)
        {
            unset($_SESSION['take_survey']);
            $_SESSION['take_survey']['sid'] = $sid;
        }

        $survey['sid'] = $sid;
        if(!isset($_SESSION['take_survey']['page']))
        { $_SESSION['take_survey']['page'] = 1; }

        if(!isset($_SESSION['take_survey']['start_time']))
        { $_SESSION['take_survey']['start_time'] = time(); }

        //Retrieve survey information
        $rs = $this->db->Execute("SELECT s.name, s.start_date, s.end_date, s.redirect_page,
            s.active, MAX(q.page) AS max_page, s.survey_access, s.survey_password, s.template, s.survey_text_mode, s.time_limit
            FROM {$this->CONF['db_tbl_prefix']}surveys s, {$this->CONF['db_tbl_prefix']}questions q
            WHERE s.sid = $sid AND s.sid = q.sid GROUP BY q.sid");

        if($rs === FALSE) { $this->error("Error retrieving Survey:" . $this->db->ErrorMsg());return; }
        if($r = $rs->FetchRow($rs))
        {
            if($r['survey_access'] == 'private' && !isset($_SESSION['admin_logged_in']))
            {
                if(isset($_REQUEST['password']))
                {
                    if($_REQUEST['password'] == $r['survey_password'])
                    { $_SESSION['survey_access'][$sid] = 1; }
                    else
                    { $this->error("Incorrect Password"); return; }
                }
                elseif(!isset($_SESSION['survey_access'][$sid]))
                { $this->error("This survey requires a password"); return; }
            }

            if($r['active'] == 0 || $now < $r['start_date'] || ($now > $r['end_date'] && $r['end_date'] != 0))
            { $this->error("Survey #$sid. <em>{$r['name']}</em> in not active at this time");return; }
        }
        else
        { $this->error("Survey $sid does not exist or has no questions."); return; }

        $survey = array_merge($survey,$r);
        //Set survey name to be used outside
        //of class to set page title
        $this->survey_name = $r['name'];
        $_SESSION['take_survey']['redirect_page'] = $r['redirect_page'];

        if($this->CONF['default_template'] != $survey['template'])
        {
            if(!$this->set_template_paths($survey['template']))
            { $this->error("Unable to load template for survey. Expecting to find template in {$this->CONF['template_path']}"); return; }
        }

        $survey['total_pages'] = $r['max_page'];
        $now = time();
        $survey['elapsed_hours'] = floor(($now - $_SESSION['take_survey']['start_time']) / 3600);
        $survey['elapsed_minutes'] = floor(($now - $_SESSION['take_survey']['start_time']) / 60);
        $survey['elapsed_seconds'] = sprintf('%02d',($now - $_SESSION['take_survey']['start_time']) % 60);

        if(isset($_REQUEST['quit']))
        { $_SESSION['take_survey']['page'] = $survey['total_pages']+2; }

        /////////////////////////
        // PROCESS SURVEY PAGE //
        /////////////////////////
        //Verify answers to required questions have been provided
        $page = $_SESSION['take_survey']['page'];

        if(isset($_SESSION['take_survey']['req'][$page]) && !isset($_REQUEST['previous']))
        {
            foreach($_SESSION['take_survey']['req'][$page] as $qid=>$num_required)
            {
                //Check for no answers submitted or less than required
                if(!isset($_REQUEST['answer'][$qid]))
                {
                    $error = "Required questions were not answered.";
                    $stay_on_same_page = 1;
                }
                else
                {
                    $num_answered = 0;
                    foreach($_REQUEST['answer'][$qid] as $value)
                    {
                        if(is_array($value))
                        {
                            foreach($value as $value2)
                            {
                                if(strlen($value2) > 0)
                                { $num_answered++; }
                            }
                        }
                        else
                        {
                            if(strlen($value) > 0)
                            { $num_answered++; }
                        }
                    }

                    if($num_answered < $num_required)
                    {
                        $error = 'Required questions were not answered.';
                        $stay_on_same_page = 1;
                    }
                }
            }
        }

        //Check for answers being present and only
        //save answers into session if time limit hasn't
        //been passed
        if(isset($_REQUEST['answer']) && ($survey['time_limit']==0 || ($now < $_SESSION['take_survey']['start_time'] + (60 * $survey['time_limit']) + 5)))
        {
            foreach($_REQUEST['answer'] as $qid=>$value)
            {
                $qid = (int)$qid;

                if(isset($_SESSION['take_survey']['answer'][$qid]))
                { unset($_SESSION['take_survey']['answer'][$qid]); }
                if(!empty($value))
                {
                    foreach($value as $key2=>$value2)
                    {
                        if(is_array($value2))
                        {
                            foreach($value2 as $key3=>$value3)
                            {
                                if(strlen($value3) > 0)
                                {$_SESSION['take_survey']['answer'][$qid][$key2][$key3] = $value3; }
                            }
                        }
                        else
                        {
                            if(strlen($value2) > 0)
                            { $_SESSION['take_survey']['answer'][$qid][$key2] = $value2; }
                        }
                    }
                }
            }
        }

        if(!$stay_on_same_page)
        {
            if(isset($_REQUEST['next']) && $_SESSION['take_survey']['page'] < $survey['total_pages']+1)
            { $_SESSION['take_survey']['page']++; }
            elseif(isset($_REQUEST['previous']) && $_SESSION['take_survey']['page'] > 1)
            { $_SESSION['take_survey']['page']--; }
        }

        if($survey['time_limit'] && ($now > $_SESSION['take_survey']['start_time'] + (60 * $survey['time_limit']) + 5))
        {
            $_SESSION['take_survey']['page'] = $survey['total_pages']+1;
            $this->setmessage('Time Limit Exceeded','You exceeded the time limit set for the survey. Your last page of results were not saved.');
        }

        //////////////////////
        // SHOW SURVEY PAGE //
        //////////////////////
        switch($_SESSION['take_survey']['page'])
        {
            //Process answers to survey
            case $survey['total_pages']+1:
                $this->process_answers($_SESSION['take_survey']);

                switch($_SESSION['take_survey']['redirect_page'])
                {
                    case 'index':
                    case '':
                        $url = $this->CONF['html'] . '/index.php';
                    break;
                    case 'results':
                        $url = $this->CONF['html'] . '/results.php?sid=' . $sid;
                    break;
                    default:
                        $url = $_SESSION['take_survey']['redirect_page'];
                    break;
                }

                unset($_SESSION['take_survey']);
                header("Location: $url");
                exit();
                break;

            //Quit survey message
            case $survey['total_pages']+2:
                $show['quit'] = TRUE;
                $show['main_url'] = TRUE;
                $show['previous_button'] = FALSE;
                $show['next_button'] = FALSE;
                $show['quit_button'] = FALSE;
                $show['page_num'] = FALSE;

                $etime = $now - $_SESSION['take_survey']['start_time'];
                $sequence = $this->db->GenID($this->CONF['db_tbl_prefix'].'sequence');
                $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}time_limit (sequence,sid,elapsed_time,quitflag)
                          VALUES ($sequence,$sid,$etime,1)";
                $rs = $this->db->Execute($query);
                if($rs === FALSE) { $this->error('Error updating elapsed time: ' . $this->db->ErrorMsg()); }
                unset($_SESSION['take_survey']);
                break;

            //Questions
            case $survey['total_pages']:
                $button['next'] = 'Finish';

            default:
                $show['question'] = TRUE;

                //Get all questions for current page
                $page = $_SESSION['take_survey']['page'];

                //Clear requirements for current page
                $_SESSION['take_survey']['req'][$page] = array();

                $qpage = $_SESSION['take_survey']['page'];

                if(!isset($_SESSION['take_survey']['qstart'][1]))
                { $_SESSION['take_survey']['qstart'][1] = 1; }

                $qstart = $_SESSION['take_survey']['qstart'][$page];

                //Retrieve dependencies for current page
                $sql = "SELECT d.qid, d.dep_qid, d.dep_aid, d.dep_option FROM {$this->CONF['db_tbl_prefix']}dependencies d,
                        {$this->CONF['db_tbl_prefix']}questions q WHERE d.sid = $sid AND d.qid = q.qid AND
                        q.page = $qpage";
                $rs = $this->db->Execute($sql);
                if($rs === FALSE)
                { $this->error("Error retrieving dependencies: " . $this->db->ErrorMsg()); return; }

                if($r = $rs->FetchRow($rs))
                {
                    $check_dependencies = 1;
                    do
                    {
                        $depend[$r['qid']]['dep_qid'][] = $r['dep_qid'];
                        $depend[$r['qid']]['dep_aid'][] = $r['dep_aid'];
                        $depend[$r['qid']]['dep_option'][] = $r['dep_option'];
                    }while($r = $rs->FetchRow($rs));

                    $depend_keys = array_keys($depend);
                }
                else
                { $check_dependencies = 0; }

                //Retrieve questions for current page
                $sql = "select q.qid, q.question, q.num_answers, q.num_required, q.orientation, a.type, a.label, a.aid  from
                        {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}answer_types a
                        where q.sid = $sid and q.aid = a.aid and q.page=$qpage order by q.oid ASC";

                $rs = $this->db->Execute($sql);
                if($rs === FALSE) { $this->error("Error selecting questions: " . $this->db->ErrorMsg()); return(FALSE);}
                $x = 0;
                $no_counts = 0;
                $question_text = '';
                $matrix_aid = FALSE;
                $end_matrix = FALSE;
                $begin_matrix = FALSE;

                while($r = $rs->FetchRow())
                {
                    $hide_question = 0;
                    $require_question = 0;
                    $show_question = 0;
                    $q = array();

                    //Check if current question has any dependencies
                    if($check_dependencies && in_array($r['qid'],$depend_keys))
                    {
                        //current question has dependencies, so loop
                        //through the dependent question
                        foreach($depend[$r['qid']]['dep_qid'] as $key => $dep_qid)
                        {
                            //and see if user has given an answer for each
                            //dependant question
                            if(isset($_SESSION['take_survey']['answer'][$dep_qid]))
                            {
                                //user has given answer, so see if dependant answer
                                //is present in the answers the user chose
                                //First check if answer saved in session is an
                                //array or not
                                if(is_array($_SESSION['take_survey']['answer'][$dep_qid]))
                                {
                                    //Answer is an array (such as MM). Loop through
                                    //answer array and look for matching dependant answer
                                    foreach($_SESSION['take_survey']['answer'][$dep_qid] as $aid)
                                    {
                                        if(is_array($aid))
                                        {
                                            if(in_array($depend[$r['qid']]['dep_aid'][$key],$aid))
                                            {
                                                switch($depend[$r['qid']]['dep_option'][$key])
                                                {
                                                    case 'Hide':
                                                        $hide_question = 1; break;
                                                    case 'Require':
                                                        $require_question = 1; break;
                                                    case 'Show':
                                                        $show_question = 1; break;
                                                }
                                            }
                                        }
                                        elseif($aid == $depend[$r['qid']]['dep_aid'][$key])
                                        {
                                            switch($depend[$r['qid']]['dep_option'][$key])
                                            {
                                                case 'Hide':
                                                    $hide_question = 1; break;
                                                case 'Require':
                                                    $require_question = 1; break;
                                                case 'Show':
                                                    $show_question = 1; break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if($hide_question && !$show_question)
                    { unset($_SESSION['take_survey']['answer'][$r['qid']]); }
                    else
                    {
                        $q['qid'] = $r['qid'];
                        $q['question'] = nl2br($this->safe_string($r['question'],$survey['survey_text_mode']));
                        $q['num_answers'] = $r['num_answers'];

                        if($require_question)
                        { $r['num_required'] = $r['num_answers']; }

                        if($r['num_required'] > 0)
                        {
                            $_SESSION['take_survey']['req'][$page][$r['qid']] = $r['num_required'];
                            $q['num_required'] = $r['num_required'];

                            if($r['num_answers'] > 1)
                            { $q['req_label'] = $r['num_required']; }

                            $q['required_text'] = $this->smarty->fetch($this->template.'/question_required.tpl');
                        }

                        $q['label'] = $this->safe_string($r['label'],$survey['survey_text_mode']);

                        if($r['type'] == 'T' || $r['type'] == 'S' || $r['type'] == 'N')
                        {
                            $q[$r['type']][$x] = TRUE;
                            $q['value'][$x] = '';

                            if(isset($_SESSION['take_survey']['answer'][$r['qid']]))
                            { $q['answer'] = $this->safe_string($_SESSION['take_survey']['answer'][$r['qid']],SAFE_STRING_TEXT,1); }

                            $template = "take_survey_question_{$r['type']}.tpl";
                        }
                        else
                        {
                            //Get arrays of answers values and answer avid numbers
                            //Answer values are returned properly escaped according
                            //to the survey_text_mode setting for the survey
                            $tmp = $this->get_answer_values($r['aid'],BY_AID,$survey['survey_text_mode']);
                            $q['value'] = $tmp['value'];
                            $q['avid'] = $tmp['avid'];

                            $q['num_values'] = count($q['value']);

                            $r['orientation'] = substr($r['orientation'],0,1);

                            $xx = 0;

                            switch($r['orientation'])
                            {
                                //Vertical & Horizontal
                                case 'V':
                                case 'H':
                                    $template = "take_survey_question_{$r['type']}_{$r['orientation']}.tpl";
                                    $selected_text = ' checked';
                                    if($matrix_aid)
                                    {
                                        $matrix_aid = FALSE;
                                        $end_matrix = TRUE;
                                    }
                                break;

                                //Dropdown
                                case 'D':
                                    $template = "take_survey_question_{$r['type']}_{$r['orientation']}.tpl";
                                    $selected_text = ' selected';
                                    if($matrix_aid)
                                    {
                                        $matrix_aid = FALSE;
                                        $end_matrix = TRUE;
                                    }
                                break;

                                //Matrix
                                case 'M':
                                    $selected_text = ' checked';
                                    if($matrix_aid != $r['aid'])
                                    {
                                        if($matrix_aid !== FALSE)
                                        { $end_matrix = TRUE; }

                                        $begin_matrix = TRUE;
                                        $matrix_aid = $r['aid'];
                                    }
                                    $template = "take_survey_question_{$r['type']}_{$r['orientation']}.tpl";
                                break;
                            }

                            if(isset($_SESSION['take_survey']['answer'][$r['qid']]))
                            {
                                switch($r['type'])
                                {
                                    case "MM":
                                        foreach($_SESSION['take_survey']['answer'][$r['qid']] as $value)
                                        {
                                            if(is_array($value))
                                            {
                                                foreach($value as $val)
                                                {
                                                    $key = array_search($val,$q['avid']);
                                                    $q['selected'][$xx][$key] = $selected_text;
                                                }
                                                $xx++;
                                            }
                                        }
                                    break;

                                    case "MS":
                                        foreach($_SESSION['take_survey']['answer'][$r['qid']] as $value)
                                        {
                                            $key = array_search($value,$q['avid']);
                                            $q['selected'][$xx++][$key] = $selected_text;
                                        }
                                    break;
                                }
                            }
                        }

                        if($r['type'] != "N")
                        { $q['question_num'] = $qstart + $x - $no_counts; }
                        else
                        { $no_counts++; }

                        $this->smarty->assign_by_ref('q',$q);


                        if($end_matrix)
                        {
                            $question_text .= $this->smarty->fetch($this->template.'/take_survey_question_MF.tpl');
                            $end_matrix = FALSE;
                        }

                        if($begin_matrix)
                        {
                            $question_text .= $this->smarty->fetch($this->template.'/take_survey_question_MH.tpl');
                            $begin_matrix = FALSE;
                        }

                        $question_text .= $this->smarty->fetch($this->template.'/'.$template);

                        $x++;
                    }
                }

                if($matrix_aid !== FALSE)
                {
                    $matrix_aid = FALSE;
                    $end_matrix = FALSE;
                    $begin_matrix = FALSE;
                    $question_text .= $this->smarty->fetch($this->template.'/take_survey_question_MF.tpl');
                }

                $_SESSION['take_survey']['qstart'][$page+1] = $qstart + $x - $no_counts;

                if(empty($question_text))
                { return $this->take_survey($sid); }

            //End default display
            break;
        }

        if(isset($_SESSION['take_survey']['page']))
        { $survey['page'] = $_SESSION['take_survey']['page']; }

        if(isset($button))
        { $this->smarty->assign("button",$button); }

        $this->smarty->assign("survey",$survey);
        $this->smarty->assign("show",$show);

        if(isset($question_text))
        { $this->smarty->assign('question_text',$question_text); }

        if(isset($error))
        { $this->smarty->assign('error',$error); }
        if(isset($message))
        { $this->smarty->assign('message',$message); }

        return $this->smarty->fetch($this->template.'/take_survey.tpl');
    }

    /*************************
    * RETRIEVE ANSWER VALUES *
    *************************/
    function get_answer_values($id,$by=BY_AID,$mode=SAFE_STRING_TEXT)
    {
        $retval = FALSE;
        static $answer_values;

        $id = (int)$id;
        $sid = (int)$_REQUEST['sid'];

        if(isset($answer_values[$id]))
        { $retval = $answer_values[$id]; }
        else
        {
            if($by==BY_QID)
            {
                $query = "SELECT av.avid, av.value, av.group_id, av.image FROM {$this->CONF['db_tbl_prefix']}answer_values av,
                          {$this->CONF['db_tbl_prefix']}questions q WHERE q.aid = av.aid AND q.qid = $id AND q.sid = $sid
                          ORDER BY av.avid ASC";
            }
            else
            {
                $query = "SELECT avid, value, group_id, image FROM {$this->CONF['db_tbl_prefix']}answer_values
                          WHERE aid = $id ORDER BY avid ASC";
            }

            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { return $this->error("Error getting answer values: " . $this->db->ErrorMsg()); }

            while($r = $rs->FetchRow($rs))
            {
                $retval['avid'][] = $r['avid'];
                $retval['value'][] = $this->safe_string($r['value'],$mode);
                $retval['group_id'][] = $r['group_id'];
                $retval['image'][] = $r['image'];
                $retval[$r['avid']] = $r['value'];
            }

            if(count($retval['group_id']) != count(array_unique($retval['group_id'])))
            { $retval['has_groups'] = TRUE; }

            $answer_values[$id] = $retval;
        }

        return $retval;
    }

    /****************************
    * PROCESS ANSWERS TO SURVEY *
    ****************************/
    function process_answers($survey)
    {
        //Get sequence number to identify this answer set
        $id = $this->db->GenID($this->CONF['db_tbl_prefix'].'sequence');
        $now = time();

        //Track the IP address of user and the survey
        //they are answering if enabled
        if($this->CONF['track_ip'])
        { $this->db->Execute("INSERT INTO {$this->CONF['db_tbl_prefix']}ip_track (ip,sid) VALUES('{$_SERVER['REMOTE_ADDR']}',{$survey['sid']})"); }

        //Get all question numbers for current survey
        $results_text = array();
        $results = array();

        //remove spaces from after commas in filter text
        //and create array to search within
        if(isset($this->CONF['text_filter']) && !empty($this->CONF['text_filter']))
        {
            $this->CONF['text_filter'] = str_replace(', ',',',$this->CONF['text_filter']);
            $text_filter = explode(',',$this->CONF['text_filter']);
        }
        else
        { $text_filter = array(); }

        $rs = $this->db->Execute("SELECT q.qid, a.type FROM {$this->CONF['db_tbl_prefix']}questions q,
                                  {$this->CONF['db_tbl_prefix']}answer_types a WHERE q.aid = a.aid AND
                                  q.sid = {$survey['sid']}");
        if($rs === FALSE) { $this->error("Error selecting questions: " . $this->db->ErrorMsg()); }
        while($r = $rs->FetchRow($rs))
        {
            if(isset($survey['answer'][$r['qid']]))
            {
                foreach($survey['answer'][$r['qid']] as $answer)
                {
                    switch($r['type'])
                    {
                        case "T":
                        case "S":
                            //Do not save answer if it's empty or matches a word
                            //in the text filter list set in the INI file.
                            if(!empty($answer) && !in_array(strtolower($answer),$text_filter))
                            {
                                $rid = $this->db->GenID($this->CONF['db_tbl_prefix'].'results_text_sequence');
                                $results_text[] = "($rid,$id,{$survey['sid']},{$r['qid']},'$answer',$now)";
                            }
                            break;

                        case "MM":
                            if(is_array($answer))
                            {
                                foreach($answer as $a)
                                {
                                    $a = (int)$a;
                                    if($a)
                                    {
                                        $rid = $this->db->GenID($this->CONF['db_tbl_prefix'].'results_sequence');
                                        $results[] = "($rid,$id,{$survey['sid']},{$r['qid']},$a,$now)";
                                    }
                                }
                            }
                            break;

                        case "MS":
                            $answer = (int)$answer;
                            if($answer)
                            {
                                $rid = $this->db->GenID($this->CONF['db_tbl_prefix'].'results_sequence');
                                $results[] = "($rid,$id,{$survey['sid']},{$r['qid']},$answer,$now)";
                            }
                            break;
                    }
                }
            }
        }

        //insert answers to questions in each table
        if(count($results_text) > 0)
        {
            $t_string = implode(",",$results_text);
            $rs = $this->db->Execute("INSERT INTO {$this->CONF['db_tbl_prefix']}results_text (rid, sequence, sid, qid, answer, entered) VALUES $t_string");
            if($rs === FALSE)
            { $this->error("Error inserting text answers: " . $this->db->ErrorMsg()); }
        }

        if(count($results) > 0)
        {
            $r_string = implode(",",$results);
            $rs = $this->db->Execute("INSERT INTO {$this->CONF['db_tbl_prefix']}results (rid, sequence, sid, qid, avid, entered) VALUES $r_string");
            if($rs === FALSE)
            { $this->error("Error inserting numeric answers: " . $this->db->ErrorMsg()); }
        }

        //Insert elapsed time to take survey
        $etime = $now - $survey['start_time'];
        $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}time_limit (sequence,sid,elapsed_time) VALUES ($id,{$survey['sid']},$etime)";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('Error inserting elapsed time: ' . $this->db->ErrorMsg()); }

        return;
    }

    /*************************
    * VIEW RESULTS OF SURVEY *
    *************************/
    function survey_results($sid=0)
    {
        $sid = (int)$sid;

        if($sid <= 0)
        { $this->error("Invalid Survey ID"); return; }

        //defaults
        $q_num = 1;

        //Retrieve survey information
        $rs = $this->db->Execute("SELECT name, results_access, results_password, survey_text_mode
                                  FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid = $sid");
        if($rs === FALSE) { $this->error("Survey $sid does not exist"); return; }
        if($r = $rs->FetchRow($rs))
        {
            if($r['results_access'] == 'private' && !isset($_SESSION['admin_logged_in']))
            {
                if(isset($_REQUEST['password']))
                {
                    if($_REQUEST['password'] == $r['results_password'])
                    {
                        $_SESSION['result_access'][$sid] = 1;
                        header("Location: {$this->CONF['html']}/results.php?sid=$sid");
                        exit();
                    }
                    else
                    { $this->error("Incorrect password for results"); return; }
                }
                elseif(!isset($_SESSION['result_access'][$sid]))
                { $this->error("Results for this survey require a password"); return; }
            }
        }
        else
        { $this->error("Survey $sid does not exist"); return; }

        $survey['name'] = $this->safe_string($r['name'],$r['survey_text_mode']);
        $survey['sid'] = $sid;
        $survey['survey_text_mode'] = $r['survey_text_mode'];

        //Set class variable of name to use outside of function
        $this->survey_name = $this->safe_string($r['name'],SAFE_STRING_TEXT);

        //if viewing answers to single
        //question with text box
        if(isset($_REQUEST['qid']))
        { return $this->survey_results_text($sid,$_REQUEST['qid']); }
        elseif(isset($_SESSION['results']['page']))
        { unset($_SESSION['results']['page']); }

        //Set default for group/ungroup
        if(isset($_SESSION['group_answers'][$sid]))
        {
            $survey['show_ungroup_answers'] = TRUE;
            $survey['show_group_answers'] = FALSE;
        }
        else
        {
            $survey['show_group_answers'] = TRUE;
            $survey['show_ungroup_answers'] = FALSE;
        }

        //Set defaults for show/hide questions
        $hide_show_where = '';
        $survey['hide_show_questions'] = TRUE;
        $survey['show_all_questions'] = FALSE;

        //Retrieve hide/show question status
        //from session if it's present
        if(isset($_SESSION['hide-show'][$sid]))
        {
            $hide_show_where = $_SESSION['hide-show'][$sid];
            $survey['show_all_questions'] = TRUE;
            $survey['hide_show_questions'] = FALSE;
        }

        $survey['required'] = $this->smarty->fetch($this->template.'/question_required.tpl');

        if(isset($_REQUEST['results_action']))
        {
            switch($_REQUEST['action'])
            {
                case "group_answers":
                    $_SESSION['group_answers'][$sid] = TRUE;
                    $survey['show_ungroup_answers'] = TRUE;
                    $survey['show_group_answers'] = FALSE;
                break;

                case "ungroup_answers":
                    unset($_SESSION['group_answers'][$sid]);
                    $survey['show_group_answers'] = TRUE;
                    $survey['show_ungroup_answers'] = FALSE;
                break;

                case "hide_questions":
                case "show_questions":
                    if(isset($_REQUEST['select_qid']) && !empty($_REQUEST['select_qid']))
                    {
                        $survey['show_all_questions'] = TRUE;
                        $survey['hide_show_questions'] = FALSE;
                        $list = '';
                        foreach($_REQUEST['select_qid'] as $select_qid)
                        { $list .= (int)$select_qid . ','; }

                        $not = '';
                        if($_REQUEST['action'] == 'hide_questions')
                        { $not = 'NOT'; }

                        $hide_show_where = " AND q.qid $not IN (" . substr($list,0,-1) . ') ';
                        $_SESSION['hide-show'][$sid] = $hide_show_where;
                    }
                break;

                case "show_all_questions":
                    $hide_show_where = '';
                    unset($_SESSION['hide-show'][$sid]);
                    $survey['hide_show_questions'] = TRUE;
                    $survey['show_all_questions'] = FALSE;
                break;

                case "filter":
                    if(isset($_REQUEST['select_qid']) && !empty($_REQUEST['select_qid']))
                    { return $this->filter($sid); }
                break;

                case "clear_filter":
                    $_SESSION['filter'][$sid] = '';
                    $_SESSION['filter_total'][$sid] = '';
                    $_SESSION['filter_text'][$sid] = '';
                break;
            }
        }

        //Determine sequence filter for following queries
        if(isset($_REQUEST['filter_submit']))
        {
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
                        $answer_values = $this->get_answer_values($filter_qid,BY_QID,$survey['survey_text_mode']);
                        $selected_answers = '';
                        $avid_list = '';
                        foreach($value as $avid)
                        {
                            if(isset($answer_values[$avid]))
                            {
                                $selected_answers .= $answer_values[$avid] . ', ';
                                $avid_list .= $avid . ',';
                            }
                        }
                        $selected_answers = $this->safe_string(substr($selected_answers,0,-2),$survey['survey_text_mode']);
                        $avid_list = substr($avid_list,0,-1);
                        $criteria[] = "(q.qid = $filter_qid AND r.avid IN ({$avid_list}))";

                        $question_text = $this->safe_string($_REQUEST['name'][$filter_qid],$survey['survey_text_mode'],1);

                        $_SESSION['filter_text'][$sid] .= "{$question_text} => $selected_answers<br>";
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
                        $start_date = $this->safe_string($_REQUEST['start_date'],SAFE_STRING_TEXT);
                        $_SESSION['filter_text'][$sid] .= "Start Date: {$start_date}<br />";
                        $num_dates++;
                    }
                }
                if(!empty($_REQUEST['end_date']))
                {
                    if($end_date = strtotime($_REQUEST['end_date'] . ' 23:59:59'))
                    {
                        $where .= " AND r.entered < $end_date ";
                        $end_date = $this->safe_string($_REQUEST['end_date'],SAFE_STRING_TEXT);
                        $_SESSION['filter_text'][$sid] .= "End Date: {$end_date}<br />";
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
                if($rs === FALSE) { return $this->error("Error selecting sequences: " . $this->db->ErrorMsg()); }

                $sequence = array();
                while($r = $rs->FetchRow($rs))
                { $sequence[] = $r['sequence']; }

                if($num = count($sequence))
                {
                    if($num > $this->CONF['filter_limit'])
                    {
                        $seq_list = implode(',',$sequence);

                        $_SESSION['filter'][$sid] = " AND r.sequence IN ($seq_list) ";
                        $_SESSION['filter_total'][$sid] = " AND (r.sequence IN ($seq_list) OR rt.sequence IN ($seq_list) OR (".$this->db->IfNull('r.sequence',1)." AND ".$this->db->IfNull('rt.sequence',1).")) ";
                    }
                    else
                    { $_SESSION['filter_text'][$sid] = "<span class=\"error\">Number of completed surveys matching filter is below the Filter Limit set in the configuration. Showing all results.</span><br>\n"; }
                }
                else
                { $_SESSION['filter_text'][$sid] = "<span class=\"error\">Filter criteria did not match any records. Showing all results.</span><br>"; }
            }
            else
            {
                $_SESSION['filter'][$sid] = '';
                $_SESSION['filter_total'][$sid] = '';
            }
        }
        elseif(!isset($_SESSION['filter'][$sid]))
        {
            $_SESSION['filter'][$sid] = '';
            $_SESSION['filter_total'][$sid] = '';
        }

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
                FROM {$this->CONF['db_tbl_prefix']}time_limit r WHERE r.sid = $sid {$_SESSION['filter'][$sid]}
                GROUP BY r.quitflag";
        $rs = $this->db->Execute($sql);
        if($rs === FALSE) {$this->error('Error getting average, min and max survey times: ' . $this->db->ErrorMsg()); return; }
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

        //retrieve questions
        $sql = "SELECT q.qid, q.question, q.num_required, q.aid, a.type, a.label, COUNT(r.qid) AS r_total, COUNT(rt.qid) AS rt_total
                FROM {$this->CONF['db_tbl_prefix']}questions q LEFT JOIN {$this->CONF['db_tbl_prefix']}results r
                  ON q.qid = r.qid LEFT JOIN {$this->CONF['db_tbl_prefix']}results_text rt ON q.qid = rt.qid,
                  {$this->CONF['db_tbl_prefix']}answer_types a
                WHERE q.sid = $sid and q.aid = a.aid
                  and ((q.qid = r.qid AND ".$this->db->IfNull('rt.qid',1).") OR (q.qid = rt.qid AND ".$this->db->IfNull('r.qid',1).")
                  OR (".$this->db->IfNull('r.qid',1)." AND ".$this->db->IfNull('rt.qid',1)."))
                  $hide_show_where {$_SESSION['filter_total'][$sid]}
                GROUP BY q.qid
                ORDER BY q.page, q.oid";
        $rs = $this->db->Execute($sql);
        if($rs === FALSE) { $this->error("Error retrieving questions: " . $this->db->ErrorMsg()); return;}

        while($r = $rs->FetchRow($rs))
        {
            $qid[$x] = $r['qid'];
            $question[$x] = nl2br($this->safe_string($r['question'],$survey['survey_text_mode']));
            $num_answers[$x] = max($r['r_total'],$r['rt_total']);

            if($r['num_required']>0)
            { $num_required[$x] = $r['num_required']; }

            if($r['type'] != "N")
            { $question_num[$x] = $q_num++; }
            $type[$x] = $r['type'];
            switch($r['type'])
            {
                case "MM":
                case "MS":
                    $answer[$x] = $this->get_answer_values($r['aid'],BY_AID,$survey['survey_text_mode']);
                    $count[$x] = array_fill(0,count($answer[$x]['avid']),0);
                    $show['numanswers'][$x] = TRUE;
                break;

                case "T":
                case "S":
                    $text[$x] = $r['qid'];
                    $show['numanswers'][$x] = TRUE;
                break;

                case 'N':
                    $show['numanswers'][$x] = FALSE;
                break;
            }
            $x++;
        }

        //retrieve answers to questions
        $sql = "SELECT r.qid, r.avid, count(*) AS c FROM {$this->CONF['db_tbl_prefix']}results r,
                {$this->CONF['db_tbl_prefix']}answer_values av,
                {$this->CONF['db_tbl_prefix']}questions q
                WHERE r.qid = q.qid and r.sid = $sid and r.avid = av.avid $hide_show_where
                {$_SESSION['filter'][$sid]}
                GROUP BY r.qid, r.avid
                ORDER BY r.avid ASC";
        $rs = $this->db->Execute($sql);
        if($rs === FALSE) { $this->error("Error retrieving answers: " . $this->db->ErrorMsg()); return;}
        while($r = $rs->FetchRow($rs))
        {
            $key = array_search($r['qid'],$qid);
            if($key !== FALSE)
            {
                $k = array_search($r['avid'],$answer[$key]['avid']);
                if($k !== FALSE)
                { $count[$key][$k] = $r['c']; }
            }
        }

        //Filter text has already had safe_string() applied
        if(isset($_SESSION['filter_text'][$sid]) && strlen($_SESSION['filter_text'][$sid])>0)
        { $this->smarty->assign('filter_text',$_SESSION['filter_text'][$sid]); }
        if(strlen($_SESSION['filter'][$sid])>0)
        {
            $show['clear_filter'] = TRUE;
            $this->smarty->assign('show',$show);
        }

        if(isset($_SESSION['group_answers'][$sid]))
        {
            //loop through each answer
            //answer values within $answer are
            //already encoded with safe_string()
            foreach($answer as $num=>$answer_array)
            {
                //determine if answer has groups or not
                if(isset($answer_array['has_groups']))
                {
                    //determine what groups there are and
                    //loop through each one
                    $unique_groups = array_unique($answer_array['group_id']);
                    foreach($unique_groups as $key=>$group)
                    {
                        //grab all keys that match current group
                        //and loop through them
                        $group_keys = array_keys($answer_array['group_id'],$group);
                        foreach($group_keys as $group_key)
                        {
                            //add each value and count matching current group
                            //key to temporary array
                            $delim = (empty($temp_value[$group])) ? '' : ', ';
                            @$temp_value[$group] .= $delim . $answer_array['value'][$group_key];
                            @$temp_count[$group] += $count[$num][$group_key];
                        }
                    }

                    $answer[$num]['value'] = array_values($temp_value);
                    $count[$num] = array_values($temp_count);
                }
            }
        }

        if(isset($count) && count($count) > 0)
        {
            foreach($count as $key=>$value)
            {
                $total[$key] = array_sum($count[$key]);
                foreach($count[$key] as $k=>$v)
                {
                    if($total[$key] > 0)
                    { $p = 100 * $v / $total[$key]; }
                    else
                    { $p = 0; }
                    $percent[$key][$k] = sprintf('%2.2f',$p);
                    $width[$key][$k] = round($this->CONF['image_width'] * $p/100);

                    $img_size = getimagesize($this->CONF['images_path'] . '/' . $answer[$key]['image'][$k]);
                    $height[$key][$k] = $img_size[1];

                    //Check for _left image (beginning of bar)
                    $img = $answer[$key]['image'][$k];
                    $last_period = strrpos($img,'.');

                    $left_img = substr($img,0,$last_period) . '_left' . substr($img,$last_period);
                    $right_img = substr($img,0,$last_period) . '_right' . substr($img,$last_period);

                    if(file_exists($this->CONF['images_path'] . '/' . $left_img))
                    { $answer[$key]['left_image'][$k] = $left_img; }

                    if(file_exists($this->CONF['images_path'] . '/' . $right_img))
                    { $answer[$key]['right_image'][$k] = $right_img; }

                    $show[$key]['middle_image'][$k] = FALSE;
                    if(isset($answer[$key]['left_image'][$k]) && isset($answer[$key]['right_image'][$k]))
                    { $show[$key]['left_right_image'][$k] = TRUE; }
                    else
                    {
                        if(isset($answer[$key]['left_image'][$k]))
                        { $show[$key]['left_image'][$k] = TRUE; }
                        elseif(isset($answer[$key]['left_image'][$k]))
                        { $show[$key]['right_image'][$k] = TRUE; }
                        else
                        {
                            $show[$key]['left_right_image'][$k] = FALSE;
                            $show[$key]['left_image'][$k] = FALSE;
                            $show[$key]['right_image'][$k] = FALSE;
                            $show[$key]['middle_image'][$k] = TRUE;
                        }
                    }
                }
            }
        }

        $this->smarty->assign_by_ref('survey',$survey);
        $this->smarty->assign_by_ref('question',$question);
        $this->smarty->assign_by_ref('qid',$qid);
        $this->smarty->assign_by_ref('question_num',$question_num);

        if(isset($num_required))
        { $this->smarty->assign_by_ref('num_required',$num_required); }
        if(isset($answer))
        { $this->smarty->assign_by_ref('answer',$answer); }
        if(isset($num_answers))
        { $this->smarty->assign_by_ref('num_answers',$num_answers); }
        if(isset($count))
        { $this->smarty->assign_by_ref('count',$count); }
        if(isset($text))
        { $this->smarty->assign_by_ref('text',$text); }
        if(isset($total))
        { $this->smarty->assign_by_ref('total',$total);}
        if(isset($percent))
        { $this->smarty->assign_by_ref('percent',$percent); }
        if(isset($width))
        { $this->smarty->assign_by_ref('width',$width); }
        if(isset($height))
        { $this->smarty->assign_by_ref('height',$height); }
        if(isset($show))
        { $this->smarty->assign_by_ref('show',$show); }

        $retval = $this->smarty->fetch($this->template.'/results.tpl');

        if(empty($_SESSION['filter'][$sid]))
        { unset($_SESSION['filter_text'][$sid]); }

        return $retval;
    }

    /********************
    * VIEW TEXT RESULTS *
    ********************/
    function survey_results_text($sid,$qid)
    {
        $delete_access = $this->check_access($sid);

        if(!empty($_REQUEST['delete_rid']) && $delete_access)
        {
            $rid_list = '';
            foreach($_REQUEST['delete_rid'] as $rid)
            { $rid_list .= (int)$rid . ','; }
            $rid_list = substr($rid_list,0,-1);
            $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}results_text WHERE rid IN ($rid_list) AND sid = $sid AND qid = $qid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error('Error deleting checked answers: ' . $this->db->ErrorMsg()); return; }
        }

        $rs = $this->db->Execute("SELECT q.question, a.type, s.survey_text_mode, s.user_text_mode
                                  FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}answer_types a,
                                  {$this->CONF['db_tbl_prefix']}surveys s
                                  WHERE q.sid = $sid AND q.qid = $qid AND q.sid = s.sid
                                  AND q.aid = a.aid AND a.type IN ('T','S')");
        if($rs === FALSE) { return $this->error("Unable to select question: " . $this->db->ErrorMsg()); }
        if($r = $rs->FetchRow($rs))
        { $question = nl2br($this->safe_string($r['question'],$r['survey_text_mode'])); }
        else
        { return $this->error("Question $qid does not exist for survey $sid or is not the correct type (Text or Sentence)"); }

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
            $answer['search_text'] = $this->safe_string($_REQUEST['search'],SAFE_STRING_TEXT);

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
            $selected[$per_page] = " selected";
        }
        else
        { $per_page = $this->CONF['text_results_per_page']; }

        $start = $per_page * $_SESSION['results']['page'];

        $rs = $this->db->Execute("SELECT COUNT(*) AS c FROM {$this->CONF['db_tbl_prefix']}results_text r WHERE qid = $qid
                                  $search {$_SESSION['filter'][$sid]}");
        if($rs === FALSE)
        { return $this->error("Error getting count of answers: " . $this->db->ErrorMsg()); }
        $r = $rs->FetchRow($rs);
        $answer['num_answers'] = $r['c'];

        $rs = $this->db->SelectLimit("SELECT rid, answer FROM {$this->CONF['db_tbl_prefix']}results_text r WHERE qid = $qid
                                  $search {$_SESSION['filter'][$sid]} ORDER BY entered DESC",$per_page,$start);
        if($rs === FALSE)
        { return $this->error("Error selecting answers: " . $this->db->ErrorMsg()); }

        $answer['text'] = array();
        $answer['rid'] = array();
        $answer['num'] = array();
        $answer['delete_access'] = $answer['num_answers'] & $delete_access;
        $cnt = 0;
        while($r = $rs->FetchRow($rs))
        {
            $answer['num'][] = $answer['num_answers'] - $start - $cnt++;
            $answer['text'][] = $this->safe_string($r['answer'],$user_text_mode);
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

        $retval = $this->smarty->fetch($this->template.'/results_text.tpl');
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
        if($rs === FALSE) { $this->error("Error selecting filter questions: " . $this->db->ErrorMsg()); }
        if($r = $rs->FetchRow())
        {
            do
            {
                $question['question'][] = nl2br($this->safe_string($r['question'],$r['survey_text_mode']));
                $question['encquestion'][] = $this->safe_string($r['question'],SAFE_STRING_TEXT);
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
        if($rs === FALSE) { $this->error("Error selecting min/max survey dates: " . $this->db->ErrorMsg()); }
        $r = $rs->FetchRow();
        $date['min'] = date('Y-m-d',$r['mindate']);
        $date['max'] = date('Y-m-d',$r['maxdate']);

        $this->smarty->assign('date',$date);


        $this->smarty->assign('sid',$sid);

        $retval = $this->smarty->fetch($this->template.'/filter.tpl');


        return $retval;
    }

    /******************
    * NEW ANSWER TYPE *
    ******************/
    function new_answer_type($sid)
    {
        $error = '';

        if(!$this->check_login($sid))
        { return(FALSE); }

        //The following values are also set
        //upon a successful submission to "reset"
        //the form...
        $input['name'] = '';
        $input['label'] = '';
        $input['value'] = array();
        $input['group'] = array();
        $input['num_answers'] = 6;
        $input['show_add_answers'] = TRUE;
        $input['sid'] = (int)$sid;
        $input['allowable_images'] = $this->get_image_names();

        if(isset($_REQUEST['submit']) || isset($_REQUEST['add_answers_submit']))
        {
            if(strlen($_REQUEST['name']) > 0)
            { $input['name'] = $this->safe_string($_REQUEST['name'],SAFE_STRING_DB); }
            else
            { $error .= "Please enter a name. "; }

            $input['label'] = $this->safe_string($_REQUEST['label'],SAFE_STRING_DB);

            switch($_REQUEST['type'])
            {
                case 'T':
                case 'S':
                case 'N':
                    $input['type'] = $_REQUEST['type'];
                    if(isset($_REQUEST['add_answers_submit']))
                    { $error .= ' Cannot add answers to types T, S, or N.'; }
                break;
                case 'MM':
                case 'MS':
                case 'S':
                    $input['type'] = $_REQUEST['type'];

                    if(isset($_REQUEST['value']) && is_array($_REQUEST['value']) &&
                       isset($_REQUEST['group']) && is_array($_REQUEST['group']) &&
                       count($_REQUEST['value']) <= 99)
                    {
                        $input['num_answers'] = min(99,count($_REQUEST['value']));

                        //Determine what group numbers
                        //the user did not use
                        for($x=1;$x<=100;$x++)
                        { $group[] = $x; }
                        $group = array_diff($group,$_REQUEST['group']);
                        reset($group);

                        foreach($_REQUEST['value'] as $key=>$value)
                        {
                            if(strlen($value) > 0)
                            {
                                $input['value'][] = $this->safe_string($value,SAFE_STRING_DB);
                                $user_image = $this->safe_string($_REQUEST['image'][$key],SAFE_STRING_DB);

                                $image_key = array_search($user_image,$input['allowable_images']);
                                if($image_key === FALSE)
                                { $input['image'][] = ''; }
                                else
                                {
                                    $input['image'][] = $user_image;
                                    $selected['image'][] = array($image_key => ' selected');
                                }

                                if(!empty($_REQUEST['group'][$key]))
                                {
                                    $g = (int)$_REQUEST['group'][$key];
                                    if($g > 0 && $g < 100)
                                    { $input['group'][] = $g; }
                                    else
                                    {
                                        $g = each($group);
                                        $input['group'][] = $g['value'];
                                    }
                                }
                                else
                                {
                                    $g = each($group);
                                    $input['group'][] = $g['value'];
                                }
                            }
                        }

                        if(count($input['value']) == 0)
                        { $error .= ' Answer values must be provided.'; }
                    }
                    else
                    { $error .= " Bad value or group entries."; }

                    if(!isset($input['num_answers']))
                    { $input['num_answers'] = 6; }

                    if(isset($_REQUEST['add_answers_submit']))
                    { $input['num_answers'] += (int)$_REQUEST['add_answer_num']; }

                    if($input['num_answers'] > 99)
                    {
                        $input['num_answers'] = 99;
                        $error .= ' Only 99 answers are allowed.';
                        $input['show_add_answers'] = FALSE;
                    }
                    elseif($input['num_answers'] == 99)
                    { $input['show_add_answers'] = FALSE; }

                break;
                default:
                    $error .= "Incorrect Answer Type";
                break;
            }

            if(!isset($_REQUEST['add_answers_submit']) && (!isset($error) || strlen($error) == 0))
            {
                $aid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_types_sequence');
                $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_types (aid, name, type, label, sid) VALUES
                          ($aid, '{$input['name']}','{$input['type']}','{$input['label']}',{$input['sid']})";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error("Error inserting new answer: " . $this->db->ErrorMsg()); }
                else
                {
                    if($c = count($input['value']))
                    {
                        $sql = '';
                        for($x=0;$x<$c;$x++)
                        {
                            $avid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_values_sequence');
                            $sql .= "($avid,$aid,'{$input['value'][$x]}',{$input['group'][$x]},'{$input['image'][$x]}'),";
                        }
                        $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_values (avid, aid, value, group_id, image) VALUES " . substr($sql,0,-1);
                        $rs = $this->db->Execute($query);


                        if($rs === FALSE)
                        {
                            $this->error("Error inserting answer values: " . $this->db->ErrorMsg());
                            $this->db->Execute("DELETE FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE aid = $aid");
                        }
                    }

                    $success=TRUE;
                    $this->smarty->assign('success',$success);

                    $allowable_images = $input['allowable_images'];

                    $input = array();
                    $input['name'] = '';
                    $input['label'] = '';
                    $input['value'] = array();
                    $input['group'] = array();
                    $input['num_answers'] = 6;
                    $input['show_add_answers'] = TRUE;
                    $input['sid'] = (int)$_REQUEST['sid'];
                    $input['allowable_images'] = $allowable_images;
                }
            }
        }

        //Encode $input values so they are safe to "reshow"
        //in the form in case of an error
        $input['name'] = $this->safe_string($input['name'],SAFE_STRING_TEXT,1);
        $input['label'] = $this->safe_string($input['label'],SAFE_STRING_TEXT,1);
        foreach($input['value'] as $key => $value)
        { $input['value'][$key] = $this->safe_string($value,SAFE_STRING_TEXT,1); }

        $this->smarty->assign_by_ref('input',$input);

        if(strlen($error)>0)
        {
            $selected[$input['type']] = ' selected';
            $this->smarty->assign('selected',$selected);
            $show['error'] = $error;
        }

        $this->smarty->assign('property',array('sid'=>$input['sid']));
        $show['links'] = $this->smarty->fetch($this->template.'/edit_survey_links.tpl');
        $show['content'] = $this->smarty->fetch($this->template.'/edit_survey_new_at.tpl');

        $this->smarty->assign_by_ref('show',$show);

        $retval = $this->smarty->fetch($this->template.'/edit_survey.tpl');

        return $retval;
    }

    /***************************
    * GET TEMPLATE IMAGE NAMES *
    ***************************/
    function get_image_names($mode = SAFE_STRING_TEXT)
    {
        $retval = array();

        $allowable_extensions = str_replace(array(' ',','),array('','|'),$this->CONF['image_extensions']);

        $d = dir($this->CONF['images_path']);

        while($file = $d->read())
        {
            if(preg_match('/\.(' . $allowable_extensions . ')$/i',$file))
            { $retval[] = $this->safe_string($file,$mode); }
        }

        if(empty($retval))
        { $retval = FALSE; }

        return $retval;
    }

    /**************
    * EDIT SURVEY *
    **************/
    function edit_survey($sid)
    {
        $sid = (int)$sid;
        $show['error'] = '';

        //Default is to show links at
        //top of edit page
        $show['links'] = TRUE;

        if(!$this->check_login($sid))
        { return(FALSE); }

        if(!isset($_REQUEST['mode']))
        { $_REQUEST['mode'] = 'properties'; }

        //determine what part of the survey
        //is being edited
        switch($_REQUEST['mode'])
        {
            ////////////////
            // PROPERTIES //
            ////////////////
            case "properties":
                if(isset($_REQUEST['edit_survey_submit']))
                {
                    ///////////////////
                    // DELETE SURVEY //
                    ///////////////////
                    if(isset($_REQUEST['delete_survey']))
                    {
                        //Delete all references to this survey in database
                        $tables = array('questions','results','results_text','ip_track','surveys','dependencies','time_limit');
                        foreach($tables as $tbl)
                        { $this->db->Execute("DELETE FROM {$this->CONF['db_tbl_prefix']}$tbl WHERE sid = $sid"); }

                        $query1 = "SELECT aid FROM {$this->CONF['db_tbl_prefix']}answer_types at WHERE at.sid = $sid";
                        $rs = $this->db->Execute($query1);
                        if($rs === FALSE)
                        { $this->error('Error getting aid values from answer_types table: ' . $this->db->ErrorMsg()); return; }
                        else
                        {
                            $aid_list = '';
                            while($r = $rs->FetchRow($rs))
                            { $aid_list .= $r['aid'] . ','; }
                            if(!empty($aid_list))
                            {
                                $aid_list = substr($aid_list,0,-1);
                                $query2 = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE aid IN ($aid_list)";
                                $rs = $this->db->Execute($query2);
                                if($rs === FALSE)
                                { $this->error('Error deleting answer values: ' . $this->db->ErrorMsg()); return; }
                            }
                        }

                        $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE sid = $sid";
                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        { $this->error('Error deleting answer types: ' . $this->db->ErrorMsg()); return; }

                        $show['links'] = FALSE;
                        $_REQUEST['mode'] = 'delete';
                        $mode = '';

                        $show['notice'] = "Survey has been deleted.";

                        //break from switch
                        break;
                    }

                    ///////////////////
                    // CLEAR ANSWERS //
                    ///////////////////
                    if(isset($_REQUEST['clear_answers']))
                    {
                        $tables = array("results","results_text","ip_track");
                        foreach($tables as $tbl)
                        { $this->db->Execute("DELETE FROM {$this->CONF['db_tbl_prefix']}$tbl WHERE sid = $sid"); }
                    }

                    ////////////////
                    // PROPERTIES //
                    ////////////////
                    //validate data
                    if(strlen($_REQUEST['name']) > 0)
                    { $input['name'] = $this->safe_string($_REQUEST['name'],SAFE_STRING_DB); }
                    else
                    { $show['error'] .= "Enter a Survey Name. "; }

                    if(!empty($_REQUEST['template']))
                    { $input['template'] = $this->safe_string(str_replace(array('\\','/'),'',$_REQUEST['template']),SAFE_STRING_DB); }
                    else
                    { $show['error'] = 'Invalid Template'; }

                    $today = mktime(0,0,0,date('m'),date('d'),date('Y'));

                    if(!empty($_REQUEST['start']))
                    {
                        $s = strtotime($_REQUEST['start'] . '00:00:01');
                        if($s >= 0)
                        { $input['start'] = $s; }
                        else
                        { $show['error'] .= "Invalid start date. "; }
                    }
                    else {$input['start'] = 0; }

                    if(!empty($_REQUEST['end']))
                    {
                        $e = strtotime($_REQUEST['end'] . ' 23:59:59');
                        if($e >= 0)
                        { $input['end'] = $e; }
                        else
                        { $show['error'] .= "Invalid end date. "; }
                    }
                    else {$input['end'] = 0; }

                    if($_REQUEST['active'] == 0 || $_REQUEST['active'] == 1)
                    { $input['active'] = $_REQUEST['active']; }
                    else
                    { $show['error'] .= "Invalid Active/Inactive status. "; }

                    $input['survey_text_mode'] = (int)$_REQUEST['survey_text_mode'];
                    if($input['survey_text_mode'] < 0 || $input['survey_text_mode'] > 2)
                    { $show['error'] .= 'Invalid Survey Text Mode selected. '; }

                    $input['user_text_mode'] = (int)$_REQUEST['user_text_mode'];
                    if($input['user_text_mode'] < 0 || $input['user_text_mode'] > 2)
                    { $show['error'] .= 'Invalid User Text Mode selected. '; }

                    if(!empty($_REQUEST['date_format']))
                    { $input['date_format'] = $this->safe_string($_REQUEST['date_format'],SAFE_STRING_DB); }
                    else
                    { $input['date_format'] = $this->safe_string($this->CONF['date_format'],SAFE_STRING_ESC); }

                    if(!empty($_REQUEST['time_limit']))
                    { $input['time_limit'] = (int)$_REQUEST['time_limit']; }
                    else
                    { $input['time_limit'] = 0; }

                    if(!isset($_REQUEST['redirect_page']))
                    { $show['error'] .= 'Invalid Completion Redirect Page. '; }
                    else
                    {
                        switch($_REQUEST['redirect_page'])
                        {
                            case 'index':
                            case 'results':
                                $input['redirect_page'] = $this->safe_string($_REQUEST['redirect_page'],SAFE_STRING_DB);
                            break;

                            case 'custom':
                                if(empty($_REQUEST['redirect_page_text']))
                                { $show['error'] .= 'You must supply a redirect page when choosing "Custom" for Completion Redirect Page'; }
                                else
                                {
                                    if(preg_match('/https?:\/\//i',$_REQUEST['redirect_page_text']))
                                    { $input['redirect_page'] = $this->safe_string($_REQUEST['redirect_page_text'],SAFE_STRING_DB); }
                                    else
                                    { $show['error'] .= 'Invalid custom Completion Redirect page. Pages must be complete URLs and start with http:// or https://'; }
                                }
                            break;

                            default:
                                $show['error'] .= 'Invalid Completion Redirect Page. ';
                            break;
                        }
                    }

                    if($_REQUEST['survey_access'] == 'private')
                    {
                        $input['survey_access'] = 'private';
                        if(strlen($_REQUEST['survey_password']) > 0)
                        { $input['survey_password'] = $this->safe_string($_REQUEST['survey_password'],SAFE_STRING_DB); }
                        else
                        { $show['error'] = "Must set password to take survey. "; }
                    }
                    else
                    {
                        $input['survey_access'] = 'public';
                        $input['survey_password'] = '';
                    }

                    if($_REQUEST['results_access'] == 'private')
                    {
                        $input['results_access'] = 'private';
                        if(strlen($_REQUEST['results_access']) > 0)
                        { $input['results_password'] = $this->safe_string($_REQUEST['results_password'],SAFE_STRING_DB); }
                        else
                        { $show['error'] .= "Must set password for results. "; }
                    }
                    else
                    {
                        $input['results_access'] = 'public';
                        $input['results_password'] = '';
                    }

                    if(strlen($_REQUEST['edit_password']) == 0 || strlen($_REQUEST['edit_password'] > 20))
                    { $show['error'] .= "Edit Password is not set or exceeds 20 characters. "; }
                    else
                    { $input['edit_password'] = $this->safe_string($_REQUEST['edit_password'],SAFE_STRING_DB); }

                    //if the validation did not
                    //set an error, proceed with update
                    if(strlen($show['error']) == 0)
                    {
                        $query = "UPDATE {$this->CONF['db_tbl_prefix']}surveys SET name='{$input['name']}', start_date={$input['start']},
                                  end_date={$input['end']}, active={$input['active']}, survey_access='{$input['survey_access']}',
                                  survey_password='{$input['survey_password']}', results_access='{$input['results_access']}',
                                  results_password='{$input['results_password']}', edit_password='{$input['edit_password']}',
                                  template = '{$input['template']}', redirect_page = '{$input['redirect_page']}',
                                  survey_text_mode = {$input['survey_text_mode']}, user_text_mode = {$input['user_text_mode']},
                                  date_format = '{$input['date_format']}', time_limit = {$input['time_limit']}
                                  WHERE sid = $sid";
                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        { $this->error("Error updating survey: " . $this->db->ErrorMsg()); return; }
                        else
                        { $show['notice'] = "Survey updated successfully."; }
                    }
                }
            break;

            ///////////////
            // QUESTIONS //
            ///////////////
            case "edit_question":
                $qid = (int)$_REQUEST['qid'];

                ////////////
                // DELETE //
                ////////////
                if(isset($_REQUEST['delete_question']))
                {
                    if(isset($_REQUEST['page_break']))
                    {
                        //////////////////////
                        // DELETE PAGEBREAK //
                        //////////////////////

                        //qid is actually the next page when requesting a
                        //page break to be deleted

                        $page = $qid;
                        $prev_page = $qid - 1;

                        $query = "SELECT COUNT(*) AS c FROM {$this->CONF['db_tbl_prefix']}dependencies d, {$this->CONF['db_tbl_prefix']}questions q1,
                                  {$this->CONF['db_tbl_prefix']}questions q2 WHERE q1.page = $prev_page AND d.dep_qid = q1.qid AND q2.page = $page
                                  AND d.qid = q2.qid AND d.sid = $sid";
                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        { $this->error('Error getting dependant count: ' . $this->db->ErrorMsg()); return; }
                        $r = $rs->FetchRow($rs);

                        if($r['c'] == 0)
                        {
                            $query = "SELECT MAX(oid) as max_oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE sid=$sid and page = " . ($page-1);
                            $rs = $this->db->Execute($query);
                            if($rs === FALSE)
                            { $this->error('Error getting max oid: ' . $this->db->ErrorMsg()); return; }
                            $r = $rs->FetchRow($rs);

                            if($r['max_oid'] > 0)
                            {
                                $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET oid = oid + {$r['max_oid']} WHERE sid=$sid and page=$page";
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE)
                                { $this->error("Error updating oid: " . $this->db->ErrorMsg()); return; }
                            }

                            $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page - 1 WHERE page >= $page and sid = $sid";
                            $rs = $this->db->Execute($query);
                            if($rs === FALSE)
                            { $this->error("Error updating page: " . $this->db->ErrorMsg()); return; }
                            else
                            { $notice = "Page Break Successfully Deleted"; }
                        }
                        else
                        { $error = 'Cannot delete page break because of questions on next page having dependencies on questions from previous page.'; }
                    }
                    else
                    {
                        /////////////////////
                        // DELETE QUESTION //
                        /////////////////////
                        if(isset($_REQUEST['del_qid']))
                        {
                            $tables = array('questions','results','results_text','dependencies');
                            $error='';
                            foreach($tables as $tbl)
                            {
                                $del_qid = (int)$_REQUEST['del_qid'];
                                $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}$tbl WHERE qid = $del_qid and sid=$sid";
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE)
                                { $error .= "Error deleting from $tbl: " . $this->db->ErrorMsg(); }
                            }
                            if(strlen($error) > 0)
                            { $this->error($error); return; }
                            else
                            { $notice = "Question and answers successfully deleted."; }
                        }
                        else
                        { $error = "No checkbox selected."; }
                    }

                    $_REQUEST['mode'] = "questions";
                }
                elseif(isset($_REQUEST['edit_question_submit']))
                {
                    ///////////////////
                    // EDIT QUESTION //
                    ///////////////////
                    //Validate data from user
                    $question = $this->safe_string($_REQUEST['question'],SAFE_STRING_DB);
                    if(strlen($question) == 0)
                    { $error = "Question cannot be empty"; }
                    else
                    {
                        $sid = (int)$_REQUEST['sid'];
                        $qid = (int)$_REQUEST['qid'];
                        $aid = (int)$_REQUEST['answer'];
                        $num_answers = (int)$_REQUEST['num_answers'];
                        $num_required = (int)$_REQUEST['num_required'];

                        if(in_array($_REQUEST['orientation'],$this->CONF['orientation']))
                        { $orientation = $this->safe_string($_REQUEST['orientation'],SAFE_STRING_DB); }
                        else
                        { $orientation = 'Vertical'; }

                        if($num_required > $num_answers)
                        {
                            $error = 'Number of required answers cannot exceed the number of answers';
                            $_REQUEST['mode'] = 'edit_question';
                        }

                        //update question with new values
                        $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET question = '$question', aid = $aid,
                                  num_answers = $num_answers, num_required = $num_required,
                                  orientation = '$orientation' WHERE sid = $sid and qid = $qid";
                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        { $this->error("Error updating question: " . $this->db->ErrorMsg()); return; }
                        else
                        { $notice = "Question successfully updated."; }

                        //Delete any checked dependencies
                        if(isset($_REQUEST['edep_id']) && is_array($_REQUEST['edep_id']) && !empty($_REQUEST['edep_id']))
                        {
                            $id_list = '';
                            foreach($_REQUEST['edep_id'] as $id)
                            { $id_list .= (int)$id . ','; }
                            $id_list = substr($id_list,0,-1);

                            $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}dependencies WHERE sid = $sid AND dep_id IN ($id_list)";
                            $rs = $this->db->Execute($query);
                            if($rs === FALSE)
                            { $this->error('Error deleting dependencies: ' . $this->db->ErrorMsg()); return; }
                            $notice = 'Checked dependencies deleted';
                        }

                        //check for dependencies
                        if(isset($_REQUEST['option']))
                        {
                            $dep_insert = '';

                            foreach($_REQUEST['option'] as $num=>$option)
                            {
                                if(!empty($option) && !empty($_REQUEST['dep_qid'][$num]) && !empty($_REQUEST['dep_aid'][$num])
                                   && in_array($option,$this->CONF['dependency_modes']))
                                {
                                    $dep_qid = (int)$_REQUEST['dep_qid'][$num];

                                    $check_query = "SELECT q1.page, q1.oid, q2.page AS dep_page, q2.oid AS dep_oid
                                                    FROM {$this->CONF['db_tbl_prefix']}questions q1, {$this->CONF['db_tbl_prefix']}questions q2
                                                    WHERE q1.qid = $qid AND q2.qid = $dep_qid";

                                    $rs = $this->db->Execute($check_query);
                                    if($rs === FALSE)
                                    { $this->error('Error checking dependencies: ' . $this->db->ErrorMsg()); return; }

                                    while($r = $rs->FetchRow($rs))
                                    {
                                        if($r['dep_page'] > $r['page'] || ($r['dep_page'] == $r['page'] && $r['dep_oid'] > $r['oid']))
                                        { $error = 'Error: Dependencies can only be based on questions displayed BEFORE the question being added'; }
                                        elseif($r['page'] == $r['dep_page'])
                                        { $dep_require_pagebreak = 1; }
                                    }

                                    foreach($_REQUEST['dep_aid'][$num] as $dep_aid)
                                    {
                                        $dep_id = $this->db->GenID($this->CONF['db_tbl_prefix'].'dependencies_sequence');
                                        $dep_insert .= "($dep_id,$sid,$qid,$dep_qid," . (int)$dep_aid . ",'$option'), ";
                                    }
                                }
                            }

                            if(empty($error) && !empty($dep_insert))
                            {
                                $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}dependencies (dep_id, sid, qid, dep_qid, dep_aid, dep_option)
                                          VALUES " . substr($dep_insert,0,-2);
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE)
                                { $this->error('Error inserting dependencies: ' . $this->db->ErrorMsg()); return; }
                                $notice = 'Dependency added';
                            }
                        }


                        //send user back to form with complete list of questions
                        //if there are no errors
                        if(!isset($error) || strlen($error) == 0)
                        { $_REQUEST['mode'] = 'questions'; }
                    }
                }

                elseif(isset($_REQUEST['edit_cancel']))
                { $_REQUEST['mode'] = "questions"; }

                /////////////
                // MOVE UP //
                /////////////
                elseif(isset($_REQUEST['move_up']))
                {
                    $_REQUEST['mode'] = 'questions';

                    $sid = (int)$_REQUEST['sid'];
                    $qid = (int)$_REQUEST['qid'];
                    //Get page and oid for requested question
                    $query = "SELECT page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE qid = $qid AND sid = $sid";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $this->error('Error getting data to move question up: ' . $this->db->ErrorMsg()); return; }
                    elseif($row = $rs->FetchRow($rs))
                    {
                        //Get question, page, and oid of question directly "above"
                        //the question being moved up.
                        $query = "SELECT qid, page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE sid = $sid AND
                                  ((page = {$row['page']} AND oid < {$row['oid']}) OR page < {$row['page']}) AND page > 0
                                  ORDER BY page DESC, oid DESC";
                        $rs2 = $this->db->SelectLimit($query,1);
                        if($rs2 === FALSE)
                        { $this->error("Error retrieving swap data to move question up: " . $this->db->ErrorMsg()); return; }
                        elseif($row2 = $rs2->FetchRow($rs2))
                        {
                            //If question being moved up is passing page boundary, just
                            //reduce the page number by one and set oid to one more than
                            //oid of previous question retrieved
                            if($row['page'] != $row2['page'])
                            {
                                //Check to see if there are any questions on the previous
                                //page that the question being moved is dependant upon
                                $query = "SELECT COUNT(*) AS c FROM {$this->CONF['db_tbl_prefix']}dependencies d, {$this->CONF['db_tbl_prefix']}questions q
                                          WHERE q.page = {$row2['page']} AND d.qid = $qid AND d.dep_qid = q.qid";
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE)
                                { $this->error('Error counting dependencies: ' . $this->db->ErrorMsg()); return; }
                                $r = $rs->FetchRow($rs);

                                if($r['c'] == 0)
                                {

                                    $oid2 = $row2['oid'] + 1;
                                    $swap_query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page - 1, oid = $oid2 WHERE qid = $qid";
                                    $swap_result = $this->db->Execute($swap_query);
                                    if($swap_result === FALSE)
                                    { $this->error("Error moving question across page boundary: " . $this->db->ErrorMsg()); return; }
                                }
                                else
                                { $error = "Cannot move question up because of dependencies on questions on previous page."; }
                            }
                            else
                            {
                                //Otherwise just swap page and oids of the two questions
                                $swap_query1 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = {$row2['page']}, oid = {$row2['oid']} WHERE qid = $qid";
                                $swap_query2 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = {$row2['page']}, oid = {$row['oid']} WHERE qid = {$row2['qid']}";
                                $swap_result1 = $this->db->Execute($swap_query1);
                                $swap_result2 = $this->db->Execute($swap_query2);
                                if($swap_result1 === FALSE || $swap_result2 === FALSE)
                                { $this->error("Error swapping 'oid' of questions to move up: " . $this->db->ErrorMsg()); return; }
                            }
                        }
                        else
                        { $error = "Cannot move question; question already at beginning of survey"; }
                    }
                    else
                    { $error = "Invalid Question"; }
                }

                ///////////////
                // MOVE DOWN //
                ///////////////
                elseif(isset($_REQUEST['move_down']))
                {
                    $_REQUEST['mode'] = 'questions';

                    $sid = (int)$_REQUEST['sid'];
                    $qid = (int)$_REQUEST['qid'];
                    $query = "SELECT page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE qid = $qid AND sid = $sid";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $this->error('Error getting data to move question down: ' . $this->db->ErrorMsg()); return; }
                    elseif($row = $rs->FetchRow($rs))
                    {
                        $query = "SELECT qid, page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE sid = $sid AND
                                  ((page = {$row['page']} AND oid > {$row['oid']}) OR page > {$row['page']})
                                  ORDER BY page ASC, oid ASC";
                        $rs2 = $this->db->SelectLimit($query,1);
                        if($rs2 === FALSE)
                        { $this->error("Error retrieving swap data to move question down: " . $this->db->ErrorMsg()); return; }
                        elseif($row2 = $rs2->FetchRow($rs2))
                        {
                            if($row['page'] != $row2['page'])
                            {
                                //Check to see if there are questions on the next page
                                //that have dependencies based upon the question being moved
                                $query = "SELECT COUNT(*) AS c FROM {$this->CONF['db_tbl_prefix']}dependencies d, {$this->CONF['db_tbl_prefix']}questions q
                                          WHERE q.page = {$row2['page']} AND q.qid = d.qid AND d.dep_qid = $qid";
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE)
                                { $this->error('Error checking depedencies for next page: ' . $this->db->ErrorMsg()); return; }
                                $r = $rs->FetchRow($rs);

                                if($r['c'] == 0)
                                {
                                    $page2 = $row['page'] + 1;
                                    $swap_query1 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET oid = oid + 1 WHERE page = $page2 AND sid = $sid";
                                    $swap_query2 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = $page2, oid = 1 WHERE qid = $qid";
                                    $swap_result1 = $this->db->Execute($swap_query1);
                                    $swap_result2 = $this->db->Execute($swap_query2);
                                    if($swap_result1 === FALSE || $swap_result2 === FALSE)
                                    { $this->error("Error moving question across page boundary: " . $this->db->ErrorMsg()); return; }
                                }
                                else
                                { $error = 'Cannot move requested question down because questions on next page have dependencies on requested question. '; }
                            }
                            else
                            {
                                $swap_query1 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = {$row2['page']}, oid = {$row2['oid']} WHERE qid = $qid";
                                $swap_query2 = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = {$row2['page']}, oid = {$row['oid']} WHERE qid = {$row2['qid']}";
                                $swap_result1 = $this->db->Execute($swap_query1);
                                $swap_result2 = $this->db->Execute($swap_query2);
                                if($swap_result1 === FALSE || $swap_result2 === FALSE)
                                { $this->error("Error swapping 'oid' of questions to move down: " . $this->db->ErrorMsg()); return; }
                            }
                        }
                        else
                        { $error = "Cannot move question; question already at end of survey"; }
                    }
                    else
                    { $error = "Invalid Question"; }
                }
            break;

            //////////////////
            // NEW QUESTION //
            //////////////////
            case "questions":
                if(isset($_REQUEST['add_new_question']))
                {
                    if(strlen($_REQUEST['question']) == 0)
                    { $error = "Question cannot be blank."; }
                    else
                    {
                        $x = explode('-',$_REQUEST['insert_after']);
                        $page = (int)$x[0];
                        $oid = (int)$x[1];

                        if(strcasecmp($_REQUEST['question'],$this->CONF['page_break'])==0)
                        {
                            if($page == 0 && $oid == 0)
                            { $error = "Cannot insert PAGE BREAK as first question. Please use the drop down to select what question to insert the page break after."; }
                            else
                            {
                                $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page + 1 WHERE sid = $sid AND
                                          (page > $page) OR (page = $page AND oid > $oid)";
                                $rs = $this->db->Execute($query);
                                if($rs === FALSE)
                                { $this->error("Cannot insert page break: " . $this->db->ErrorMsg()); return; }
                                elseif($this->db->Affected_Rows() > 0)
                                { $notice = "PAGE BREAK inserted successfully."; }
                                else
                                { $error = 'Cannot insert PAGE BREAK as last question.'; }
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
                            { $this->error("Error updating OID to insert question: " . $this->db->ErrorMsg()); return; }

                            //Increment oid, since new question is
                            //inserted "after" what was chosen
                            $oid++;
                            $question = $this->safe_string($_REQUEST['question'],SAFE_STRING_DB);
                            $num_answers = (int)$_REQUEST['num_answers'];
                            $num_required = (int)$_REQUEST['num_required'];
                            $aid = (int)$_REQUEST['answer'];

                            if($num_required > $num_answers)
                            { $error = 'Number of required answers cannot exceed the number of answers'; }

                            if(in_array($_REQUEST['orientation'],$this->CONF['orientation']))
                            { $orientation = $this->safe_string($_REQUEST['orientation'],SAFE_STRING_DB); }
                            else
                            { $orientation = 'Vertical'; }

                            $_SESSION['answer_orientation'] = $_REQUEST['orientation'];

                            if(!isset($error) || empty($error))
                            {
                                $dep_insert = '';
                                $dep_require_pagebreak = 0;

                                //check for dependencies
                                if(isset($_REQUEST['option']))
                                {
                                    foreach($_REQUEST['option'] as $num=>$option)
                                    {
                                        if(!empty($option) && !empty($_REQUEST['dep_qid'][$num]) && !empty($_REQUEST['dep_aid'][$num])
                                           && in_array($option,$this->CONF['dependency_modes']))
                                        {
                                            $dep_qid = (int)$_REQUEST['dep_qid'][$num];

                                            $check_query = "SELECT page, oid FROM {$this->CONF['db_tbl_prefix']}questions WHERE qid = $dep_qid";

                                            $rs = $this->db->Execute($check_query);
                                            if($rs === FALSE)
                                            { $this->error('Error checking dependencies: ' . $this->db->ErrorMsg()); return; }

                                            while($r = $rs->FetchRow($rs))
                                            {
                                                if($r['page'] > $page || ($r['page'] == $page && $r['oid'] > $oid))
                                                { $error = "Error: Dependencies can only be based on questions displayed BEFORE the question being added"; }
                                                elseif($r['page'] == $page)
                                                { $dep_require_pagebreak = 1; }
                                            }

                                            foreach($_REQUEST['dep_aid'][$num] as $dep_aid)
                                            {
                                                $dep_id = $this->db->GenID($this->CONF['db_tbl_prefix'].'dependencies_sequence');
                                                $dep_insert .= "($dep_id,$sid,%%,$dep_qid," . (int)$dep_aid . ",'$option'), ";
                                            }
                                        }
                                    }
                                }

                                if(!isset($error) || empty($error))
                                {
                                    $qid = $this->db->GenID($this->CONF['db_tbl_prefix'].'questions_sequence');
                                    $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}questions (qid, sid, question, aid, num_answers, num_required, page, oid, orientation)
                                              VALUES ($qid, $sid, '$question', $aid, $num_answers, $num_required, $page, $oid, '$orientation')";
                                    $rs = $this->db->Execute($query);
                                    if($rs === FALSE)
                                    { $this->error("Error inserting new question: " . $this->db->ErrorMsg()); return; }
                                    else
                                    {
                                        if(!empty($dep_insert))
                                        {
                                            $dep_query = "INSERT INTO {$this->CONF['db_tbl_prefix']}dependencies (dep_id,sid,qid,dep_qid,dep_aid,dep_option) VALUES " . substr($dep_insert,0,-2);
                                            $dep_query = str_replace('%%',$qid,$dep_query);

                                            $rs = $this->db->Execute($dep_query);
                                            if($rs === FALSE)
                                            { $this->error('Error adding dependencies: ' . $this->db->ErrorMsg()); return; }

                                            if($dep_require_pagebreak)
                                            {
                                                $query = "UPDATE {$this->CONF['db_tbl_prefix']}questions SET page = page + 1 WHERE sid = $sid AND
                                                          (page > $page OR (page = $page AND oid > $oid) OR qid = $qid)";
                                                $rs = $this->db->Execute($query);
                                                if($rs === FALSE)
                                                { $this->error("Cannot insert dependency page break: " . $this->db->ErrorMsg()); return; }
                                            }
                                        }

                                        $notice = "Question inserted successfully.";
                                        $_REQUEST['num_answers'] = 1;
                                        $_REQUEST['num_required'] = 0;
                                    }
                                }
                            }
                            else
                            { $this->smarty->assign('question',$question); }

                        }
                    }
                }
            break;
        }

        //load survey
        if(!isset($_REQUEST['mode']))
        { $_REQUEST['mode'] = 'properties'; }

        switch($_REQUEST['mode'])
        {
            ////////////////////
            // SHOW QUESTIONS //
            ////////////////////
            case "questions":
                $mode['questions'] = 'edit_question';
                $mode['new_question'] = "questions";
                $content = 'questions';
                $property['sid'] = $sid;

                //load all questions for this survey
                $query = "SELECT q.qid, q.aid, q.question, q.page, a.type, q.oid, s.survey_text_mode
                          FROM {$this->CONF['db_tbl_prefix']}questions q,
                          {$this->CONF['db_tbl_prefix']}answer_types a, {$this->CONF['db_tbl_prefix']}surveys s
                          WHERE q.aid = a.aid and q.sid = $sid AND q.sid = s.sid order by q.page, q.oid, a.aid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error("Error selecting questions: " . $this->db->ErrorMsg()); return; }
                $page = 1;
                $x = 0;
                $q_num = 1;
                $label_num = 1;
                $num_demographics = 0;
                $answer = array();
                $show['dep'] = TRUE;

                if($r = $rs->FetchRow($rs))
                {
                    $survey_text_mode = $r['survey_text_mode'];

                    do
                    {
                        while($page != $r['page'])
                        {
                            $property['qid'][$x] = $r['page'];
                            $property['question'][$x] = $this->CONF['page_break'];
                            $property['qnum'][$x] = '&nbsp;';
                            $property['page_break'][$x] = TRUE;
                            $property['show_dep'][$x] = FALSE;
                            $x++;
                            $page += 1;
                        }
                        $property['qid'][$x] = $r['qid'];
                        $property['question'][$x] = nl2br($this->safe_string($r['question'],$survey_text_mode));

                        if($r['type'] == 'MS' || $r['type'] == 'MM')
                        {
                            //Retrieve answer value in safe_text mode
                            //so they can be shown in <select>
                            $temp = $this->get_answer_values($r['aid'],BY_AID,SAFE_STRING_TEXT);
                            $property['dep_avid'][$r['qid']] = $temp['avid'];
                            $property['dep_value'][$r['qid']] = $temp['value'];
                        }

                        if($r['type'] != 'N')
                        {
                            $property['qnum'][$x] = $q_num++;
                            $property['page_oid'][] = $r['page'] . '-' . $r['oid'];
                            $property['qnum2'][] = $property['qnum'][$x];
                            $property['qnum2_selected'][] = '';

                            if($r['type'] != 'S' && $r['type'] != 'T')
                            {
                                $property['dep_qid'][] = $r['qid'];
                                $property['dep_qnum'][] = $property['qnum'][$x];
                            }

                        }
                        else
                        {
                            $property['qnum'][$x] = 'L'.$label_num++;
                            $property['page_oid'][] = $r['page'] . '-' . $r['oid'];
                            $property['qnum2'][] = $property['qnum'][$x];
                            $property['qnum2_selected'][] = '';
                        }

                        $property['show_edep'][$x] = FALSE;

                        $x++;

                    }while($r = $rs->FetchRow($rs));

                    //load dependencies for current survey
                    $query = "SELECT d.qid, d.dep_qid, av.value, d.dep_option FROM {$this->CONF['db_tbl_prefix']}dependencies d,
                              {$this->CONF['db_tbl_prefix']}answer_values av WHERE d.dep_aid = av.avid AND d.sid = $sid";
                    $rs = $this->Query($query,'Error selecting dependencies for survey: ');
                    if($rs === FALSE) { return; }

                    while($r = $rs->FetchRow($rs))
                    {
                        // __hide__ if question __xx__ is __a,b,c__
                        $x = array_search($r['qid'],$property['qid']);
                        $key = array_search($r['dep_qid'],$property['qid']);
                        $qnum = $property['qnum'][$key];

                        $property['show_edep'][$x] = TRUE;
                        if(isset($property['edep_value'][$x]) && in_array($qnum,$property['edep_qnum'][$x]))
                        {
                            $key2 = array_search($qnum,$property['edep_qnum'][$x]);

                            if($property['edep_option'][$x][$key2] == $r['dep_option'])
                            { $property['edep_value'][$x][$key2] .= ', ' . $this->safe_string($r['value'],$survey_text_mode); }
                            else
                            {
                                $property['edep_option'][$x][] = $this->safe_string($r['dep_option'],$survey_text_mode);
                                $property['edep_value'][$x][] = $this->safe_string($r['value'],$survey_text_mode);
                                $property['edep_qnum'][$x][] = $qnum;
                            }
                        }
                        else
                        {
                            $property['edep_option'][$x][] = $this->safe_string($r['dep_option'],$survey_text_mode);
                            $property['edep_value'][$x][] = $this->safe_string($r['value'],$survey_text_mode);
                            $property['edep_qnum'][$x][] = $qnum;
                        }
                    }
                }
                else
                { $show['dep'] = FALSE; }

                if(isset($property['dep_avid']) && count($property['dep_avid']))
                {
                    $property['js'] = '';

                    foreach($property['dep_avid'] as $qid=>$avid_array)
                    {
                        foreach($avid_array as $key=>$avid)
                        {
                            $property['js'] .= "Answers['$qid,$key'] = '$avid';\n";
                            $value = addslashes($property['dep_value'][$qid][$key]);
                            $property['js'] .= "Values['$qid,$key'] = '$value';\n";
                        }

                        $c = count($avid_array);
                        $property['js'] .= "Num_Answers['$qid'] = '$c';\n";
                    }

                }

                //Set "insert question after..." select box to last element
                if(isset($property['qnum2_selected']))
                { $property['qnum2_selected'][count($property['qnum2_selected'])-1] = ' selected'; }

                $num_answers = array("1","2","3","4","5");
                $num_answers_selected = array_fill(0,5,"");
                if(isset($_REQUEST['num_answers']))
                { $num_answers_selected[$_REQUEST['num_answers']-1] = " selected"; }

                $num_required = array("0","1","2","3","4","5");
                $num_required_selected = array_fill(0,6,"");
                if(isset($_REQUEST['num_required']))
                { $num_required_selected[$_REQUEST['num_required']] = " selected"; }

                //retrieve answer types from database
                $rs = $this->db->Execute("SELECT aid, name FROM {$this->CONF['db_tbl_prefix']}answer_types
                                          WHERE sid = $sid ORDER BY name ASC");
                if($rs === FALSE)
                { $this->error('Unable to retrieve answer types: ' . $this->db->ErrorMsg()); return; }
                while ($r = $rs->FetchRow($rs))
                {
                    $r['name'] = $this->safe_string($r['name'],SAFE_STRING_TEXT);
                    $answer[] = $r;
                }

                if(isset($_SESSION['answer_orientation']))
                {
                    $key = array_search($_SESSION['answer_orientation'],$this->CONF['orientation']);
                    $property['orientation']['selected'][$key] = ' selected';
                }

                $this->smarty->assign('answer',$answer);
                $this->smarty->assign('num_answers',$num_answers);
                $this->smarty->assign('num_answers_selected',$num_answers_selected);
                $this->smarty->assign('num_required',$num_required);
                $this->smarty->assign('num_required_selected',$num_required_selected);

                $this->smarty->assign('property',$property);
            break;

            ///////////////////
            // EDIT QUESTION //
            ///////////////////
            case "edit_question":
                $mode = "edit_question";
                $content = 'edit_question';

                $qid = (int)$_REQUEST['qid'];

                //Retrieve Question data
                $query = "SELECT q.question, q.aid, q.num_answers, q.num_required, q.page, q.oid, q.orientation, s.survey_text_mode
                          FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}surveys s
                          WHERE q.sid = $sid AND q.sid = s.sid AND qid = $qid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->Error("Error selecting data for question: ". $this->db->ErrorMsg()); return; }

                $question_data = $rs->FetchRow($rs);
                $question_data['question'] = $this->safe_string($question_data['question'],SAFE_STRING_TEXT);

                $key = array_search($question_data['orientation'],$this->CONF['orientation']);
                if($key !== FALSE)
                { $data['orientation']['selected'][$key] = ' selected'; }

                $num_answers = array("1","2","3","4","5");
                $num_answers_selected = array_fill(0,5,"");
                $num_answers_selected[$question_data['num_answers']-1] = " selected";

                $num_required = array("0","1","2","3","4","5");
                $num_required_selected = array_fill(0,6,"");
                $num_required_selected[$question_data['num_required']] = " selected";

                //Retrieve Answer Types from database
                $rs = $this->db->Execute("SELECT aid, name FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE sid = $sid ORDER BY name ASC");
                while ($r = $rs->FetchRow($rs))
                {
                    if($r['aid'] == $question_data['aid'])
                    { $r['selected'] = ' selected'; }
                    $r['name'] = $this->safe_string($r['name'],SAFE_STRING_TEXT);
                    $answer[] = $r;
                }

                //Retrieve existing question numbers
                //for questions BEFORE this one being edited
                $query = "SELECT q.qid, at.type, av.avid, av.value FROM {$this->CONF['db_tbl_prefix']}questions q,
                          {$this->CONF['db_tbl_prefix']}answer_types at LEFT JOIN {$this->CONF['db_tbl_prefix']}answer_values av
                          ON at.aid = av.aid WHERE q.sid = $sid AND
                          (q.page < {$question_data['page']} OR (q.page = {$question_data['page']} AND q.oid < {$question_data['oid']}))
                          AND q.aid = at.aid ORDER BY page ASC, oid ASC";

                $x = 1;
                $av_count = 0;
                $old_qid = '';
                $data['js'] = '';
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error('Error retrieving existing question numbers: ' . $this->db->ErrorMsg()); }
                if($r = $rs->FetchRow($rs))
                {
                    do
                    {
                        if($r['type'] != 'N')
                        {
                            if($r['type'] == 'S' || $r['type'] == 'T')
                            { $x++; }
                            else
                            {
                                if($r['qid'] != $old_qid)
                                {
                                    if($av_count)
                                    { $data['js'] .= "Num_Answers['{$old_qid}'] = '{$av_count}';\n"; }

                                    $av_count = 0;
                                    $data['qnum'][$r['qid']] = $x++;
                                    $old_qid = $r['qid'];

                                }

                                $data['js'] .= "Answers['{$r['qid']},{$av_count}'] = '{$r['avid']}';\n";
                                $data['js'] .= "Values['{$r['qid']},{$av_count}'] = '" . addslashes($r['value']) . "';\n";

                                $av_count++;

                            }
                        }
                    }while($r = $rs->FetchRow($rs));

                    $data['js'] .= "Num_Answers['{$old_qid}'] = '{$av_count}';\n";

                    $data['dep_qid'] = array_keys($data['qnum']);
                    $data['dep_qnum'] = array_values($data['qnum']);
                }

                //Retrieve existing dependencies for question
                $dependencies = array();
                $query = "SELECT d.dep_id, d.dep_qid, d.dep_option, av.value FROM {$this->CONF['db_tbl_prefix']}dependencies d,
                          {$this->CONF['db_tbl_prefix']}answer_values av WHERE d.dep_aid = av.avid AND d.qid = $qid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error('Unable to retrieve existing dependencies: ' . $this->db->ErrorMsg()); return; }

                while($r = $rs->FetchRow($rs))
                {
                    $data['edep']['dep_id'][] = $r['dep_id'];
                    $data['edep']['option'][] = $r['dep_option'];
                    $data['edep']['qnum'][] = $data['qnum'][$r['dep_qid']];
                    $data['edep']['value'][] = $this->safe_string($r['value'],$question_data['survey_text_mode']);
                }

                if(!empty($data))
                { $this->smarty->assign_by_ref('data',$data); }

                $this->smarty->assign('num_answers',$num_answers);
                $this->smarty->assign('num_answers_selected',$num_answers_selected);
                $this->smarty->assign('num_required',$num_required);
                $this->smarty->assign('num_required_selected',$num_required_selected);
                $this->smarty->assign('answer',$answer);
                $this->smarty->assign('question',$question_data['question']);
                $this->smarty->assign('qid',(int)$_REQUEST['qid']);
                $this->smarty->assign('property',array('sid'=>$sid));
            break;

            ////////////
            // DELETE //
            ////////////
            case "delete":
                //Don't show anything if survey was deleted
                //other than notice message.
            break;

            ////////////////
            // PROPERTIES //
            ////////////////
            default: //properties
                $mode = 'properties';

                //load survey properties
                $query = "SELECT sid, name, start_date, end_date, active, survey_access, survey_password, results_access,
                          results_password, edit_password, template, redirect_page, survey_text_mode, user_text_mode, created, date_format, time_limit FROM
                          {$this->CONF['db_tbl_prefix']}surveys WHERE sid = $sid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error("Error loading Survey #$sid" . $this->db->ErrorMsg()); return;}

                //set properties into an array
                //setting default values
                if($r = $rs->FetchRow($rs))
                {
                    $content = 'properties';

                    $r['name'] = $this->safe_string($r['name'],SAFE_STRING_TEXT);

                    $r['survey_password'] = $this->safe_string($r['survey_password'],SAFE_STRING_TEXT);
                    $r['edit_password'] = $this->safe_string($r['edit_password'],SAFE_STRING_TEXT);
                    $r['results_password'] = $this->safe_string($r['results_password'],SAFE_STRING_TEXT);
                    $r['date_format'] = $this->safe_string($r['date_format'],SAFE_STRING_TEXT);
                    $r['created'] = $this->safe_string(date($this->CONF['date_format'],$r['created']),SAFE_STRING_TEXT);
                    $r['time_limit'] = $this->safe_string($r['time_limit'],SAFE_STRING_TEXT);

                    if($r['active'] == 1)
                    { $r['active_selected'] = " checked"; }
                    else
                    { $r['inactive_selected'] = " checked"; }

                    if($r['start_date'] == 0)
                    { $r['start_date'] = ''; }
                    else
                    { $r['start_date'] = strtoupper(date('Y-m-d',$r['start_date'])); }

                    if($r['end_date'] == 0)
                    { $r['end_date'] = ''; }
                    else
                    { $r['end_date'] = strtoupper(date('Y-m-d',$r['end_date'])); }

                    $t1 = "survey_" . $r['survey_access'];
                    $r[$t1] = " checked";
                    $t2 = "results_" . $r['results_access'];
                    $r[$t2] = " checked";

                    switch($r['redirect_page'])
                    {
                        case 'index':
                        case '':
                            $r['redirect_index'] = ' checked';
                        break;
                        case 'results':
                            $r['redirect_results'] = ' checked';
                        break;
                        default:
                            $r['redirect_custom'] = ' checked';
                            $r['redirect_page_text'] = $this->safe_string($r['redirect_page'],SAFE_STRING_TEXT);
                        break;
                    }

                    //Set arrays for holding text mode values, options, and selected element to
                    //create drop down boxes
                    $survey_text_mode = array_slice($this->CONF['text_modes'],0,$this->CONF['survey_text_mode']+1);
                    $r['survey_text_mode_values'] = array_values($survey_text_mode);
                    $r['survey_text_mode_options'] = array_keys($survey_text_mode);
                    $r['survey_text_mode_selected'][$r['survey_text_mode']] = ' selected';

                    $user_text_mode = array_slice($this->CONF['text_modes'],0,$this->CONF['user_text_mode']+1);
                    $r['user_text_mode_values'] = array_values($user_text_mode);
                    $r['user_text_mode_options'] = array_keys($user_text_mode);
                    $r['user_text_mode_selected'][$r['user_text_mode']] = ' selected';

                    if(in_array(2,$r['survey_text_mode_options']) || in_array(2,$r['user_text_mode_options']))
                    { $show['fullhtmlwarning'] = TRUE; }

                    $dh = opendir($this->CONF['path'] . '/templates');
                    while($file = readdir($dh))
                    {
                        if($file != '.' && $file != '..')
                        {
                            $r['templates'][] = $this->safe_string($file,SAFE_STRING_TEXT);
                            if($r['template'] == $file)
                            { $r['selected_template'][] = ' selected'; }
                            else
                            { $r['selected_template'][] = ''; }
                        }
                    }

                    sort($r['templates']);

                    $this->smarty->assign("property",$r);
                }
                else
                { $this->error("Survey #$sid does not exist."); return; }
            break;
        }

        $show['links'] = ($show['links']) ? $this->smarty->Fetch($this->template.'/edit_survey_links.tpl') : '';

        if(isset($notice))
        { $show['notice'] = $notice; }

        if(isset($error) && strlen($error) > 0)
        { $show['error'] = $error; }

        $this->smarty->assign('mode',$mode);

        $this->smarty->assign_by_ref('show',$show);

        if(isset($content))
        { $show['content'] = $this->smarty->Fetch($this->template.'/edit_survey_' . $content . '.tpl'); }

        $retval = $this->smarty->Fetch($this->template.'/edit_survey.tpl');
        return $retval;
    }

    /*******************
    * EDIT ANSWER TYPE *
    *******************/
    function edit_answer($sid,$aid)
    {
        if(!$this->check_login($sid))
        { return(FALSE); }

        $sid = (int)$sid;
        $aid = (int)$aid;

        if(empty($aid))
        { return $this->edit_answer_type_choose($sid); }

        $error = '';
        $show = array();
        $show['warning'] = FALSE;
        $load_answer = TRUE;

        //The following values are also set
        //upon a successful submission to "reset"
        //the form...
        $input['value'] = array();
        $input['group_id'] = array();
        $input['num_answers'] = 6;
        $input['show_add_answers'] = TRUE;
        $input['delete_avid'] = array();
        $input['sid'] = $sid;
        $input['allowable_images'] = $this->get_image_names();

        $show['admin_link'] = TRUE;
        $show['delete'] = FALSE;

        $query = "SELECT COUNT(aid) AS c FROM {$this->CONF['db_tbl_prefix']}questions WHERE aid = $aid AND sid = $sid";

        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error("Error getting survey count: " . $this->db->ErrorMsg()); return; }
        $r = $rs->FetchRow($rs);
        if($r['c'] > 0)
        {
            $show['warning'] = TRUE;
            $this->smarty->assign('num_usedanswers',$r['c']);
            if($r['c'] > 1)
            { $this->smarty->assign('usedanswers_plural','s'); }
        }
        else
        { $show['delete'] = TRUE; }

        if(isset($_REQUEST['delete_submit']) && isset($_REQUEST['delete']))
        {
            $query1 = "SELECT aid FROM {$this->CONF['db_tbl_prefix']}answer_types at WHERE at.sid = $sid";
            $rs = $this->db->Execute($query1);
            if($rs === FALSE)
            { $this->error('Error getting aid values from answer_types table: ' . $this->db->ErrorMsg()); return; }
            else
            {
                while($r = $rs->FetchRow($rs))
                { $aid_list = $r['aid'] . ','; }
                $aid_list = substr($aid_list,0,-1);
                $query2 = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE aid IN ($aid_list)";
                $rs = $this->db->Execute($query2);
                if($rs === FALSE)
                { $this->error('Error deleting answer values: ' . $this->db->ErrorMsg()); return; }
            }

            $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE aid = $aid AND sid = $sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error('Error deleting answer types: ' . $this->db->ErrorMsg()); return; }

            $show['del_message'] = TRUE;
            $this->smarty->assign_by_ref('show',$show);

            return $this->edit_answer_type_choose($sid);
        }
        elseif(isset($_REQUEST['delete_submit']))
        { $show['message'] = "Checkbox must be selected in order to delete answer."; }

        if(isset($_REQUEST['submit']) || isset($_REQUEST['add_answers_submit']))
        {
            $error = '';
            $load_answer = FALSE;

            if(strlen($_REQUEST['name']) > 0)
            { $input['name'] = $this->safe_string($_REQUEST['name'],SAFE_STRING_DB); }
            else
            { $error .= "Please enter a name. "; }

            $input['label'] = $this->safe_string($_REQUEST['label'],SAFE_STRING_DB);

            $input['aid'] = (int)$_REQUEST['aid'];

            $new_answer_count = 0;

            switch($_REQUEST['type'])
            {
                case 'T':
                case 'S':
                case 'N':
                    $input['value'] = '';
                    $input['delim'] = '';
                    $input['type'] = $_REQUEST['type'];
                    if(isset($_REQUEST['add_answers_submit']))
                    { $error .= ' Cannot add answers to types T, S, or N.'; }
                    $input['show_add_answers'] = FALSE;
                    $input['num_answers'] = 0;
                    if(isset($_REQUEST['value']))
                    { $input['delete_avid'] = array_keys($_REQUEST['value']); }
                    $load_answer = TRUE;
                break;
                case 'MM':
                case 'MS':
                case 'S':
                    $input['type'] = $_REQUEST['type'];
                    $input['selected'][$input['type']] = ' selected';

                    if(isset($_REQUEST['value']) && is_array($_REQUEST['value']) &&
                       isset($_REQUEST['group_id']) && is_array($_REQUEST['group_id']) &&
                       count($_REQUEST['value']) <= 99)
                    {
                        $input['num_answers'] = min(99,count($_REQUEST['value']));

                        //Determine what group numbers
                        //the user did not use
                        for($x=1;$x<=100;$x++)
                        { $group[] = $x; }
                        $group = array_diff($group,$_REQUEST['group_id']);
                        reset($group);

                        //$new_answer_count = 0;

                        foreach($_REQUEST['value'] as $avid=>$value)
                        {
                            if(strlen($value) > 0)
                            {
                                //An 'x' on the answer value id (avid)
                                //is used to mark a "new" answer that
                                //has been added. it must be INSERTed and
                                //not UPDATEd during the DB operations
                                if($avid{0}=='x')
                                { $input['avid'][] = 'x' . $new_answer_count++; }
                                else
                                { $input['avid'][] = $avid; }

                                $input['value'][] = $this->safe_string($value,SAFE_STRING_DB);

                                $user_image = $_REQUEST['image'][$avid];
                                $image_key = array_search($user_image,$input['allowable_images']);
                                if($image_key === FALSE)
                                { $input['image'][] = ''; }
                                else
                                {
                                    $input['image'][] = $this->safe_string($user_image,SAFE_STRING_DB);
                                    $input['image_selected'][] = array($image_key => ' selected');
                                }

                                if(!empty($_REQUEST['group_id'][$avid]))
                                {
                                    $g = (int)$_REQUEST['group_id'][$avid];
                                    if($g > 0 && $g < 100)
                                    { $input['group_id'][] = $g; }
                                    else
                                    {
                                        $g = each($group);
                                        $input['group_id'][] = $g['value'];
                                    }
                                }
                                else
                                {
                                    $g = each($group);
                                    $input['group_id'][] = $g['value'];
                                }
                            }
                            else
                            {
                                //If a previous answer has been "emptied",
                                //then record it's answer value ID (avid),
                                //as it must be DELETEd during the DB operations
                                if(!empty($avid) && $avid{0} != 'x')
                                { $input['delete_avid'][] = (int)$avid; }
                            }
                        }

                        if(count($input['value']) == 0)
                        { $error .= ' Answer values must be provided.'; }
                    }
                    else
                    { $error .= " Bad value or group entries."; }

                    if(!isset($input['num_answers']))
                    { $input['num_answers'] = 6; }

                    if(isset($_REQUEST['add_answers_submit']))
                    {
                        $num = (int)$_REQUEST['add_answer_num'];
                        $input['num_answers'] += $num;
                    }

                    if($input['num_answers'] > 99)
                    {
                        $input['num_answers'] = 99;
                        $error .= ' Only 99 answers are allowed.';
                        $input['show_add_answers'] = FALSE;
                    }
                    elseif($input['num_answers'] == 99)
                    { $input['show_add_answers'] = FALSE; }

                    $diff = $input['num_answers'] - @count($input['value']);

                    for($x=0;$x<$diff;$x++)
                    {
                        //Create an answer value ID (avid) for the
                        //remainder of the empty boxes with an 'x'
                        //in the  name. The 'x' is used to mark
                        //new answers and trigger an INSERT instead
                        //of an UPDATE in the database
                        $input['avid'][] = 'x' . $new_answer_count++;
                    }

                break;
                default:
                    $error .= "Incorrect Answer Type";
                break;
            }

            if(empty($error) && !isset($_REQUEST['add_answers_submit']))
            {

                $query = "UPDATE {$this->CONF['db_tbl_prefix']}answer_types SET
                          name='{$input['name']}',type='{$input['type']}',label='{$input['label']}'
                          WHERE aid = $aid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error("Error updating answer: " . $this->db->ErrorMsg()); }
                else
                {
                    $query = array();

                    switch($input['type'])
                    {
                        case 'T':
                        case 'S':
                        case 'N':
                            $query[] = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE aid = $aid";
                        break;

                        case 'MS':
                        case 'MM':

                            $sql_value = '';
                            $sql_group_id = '';
                            $sql_image = '';
                            $sql_avid = '';
                            $insert = array();

                            for($x=0;$x<$input['num_answers'];$x++)
                            {
                                if(isset($input['value'][$x]))
                                {
                                    if(substr($input['avid'][$x],0,1) == 'x')
                                    {
                                        $avid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_values_sequence');
                                        $insert[] = "($avid, $aid,'{$input['value'][$x]}',{$input['group_id'][$x]},'{$input['image'][$x]}')";
                                    }
                                    else
                                    {
                                        $sql_value .= "WHEN avid = {$input['avid'][$x]} THEN '{$input['value'][$x]}' ";
                                        $sql_group_id .= "WHEN avid = {$input['avid'][$x]} THEN {$input['group_id'][$x]} ";
                                        $sql_image .= "WHEN avid = {$input['avid'][$x]} THEN '{$input['image'][$x]}' ";
                                        $sql_avid .= $input['avid'][$x] . ',';
                                    }
                                }
                            }

                            if(!empty($sql_avid))
                            {
                                $sql_avid = substr($sql_avid,0,-1);
                                $query[] = "UPDATE {$this->CONF['db_tbl_prefix']}answer_values SET value = CASE $sql_value END, group_id = CASE $sql_group_id END, image = CASE $sql_image END WHERE avid IN ($sql_avid)";
                            }

                            if(count($insert))
                            { $query[] = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_values (avid,aid,value,group_id,image) VALUES " . implode(',',$insert); }


                            if(count($input['delete_avid']))
                            { $query[] = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE avid IN (" . implode(',',$input['delete_avid']) . ')'; }
                        break;
                    }

                    foreach($query as $q)
                    {
                        $rs = $this->db->Execute($q);
                        if($rs === FALSE)
                        { $this->error("Error updating answer values: " . $this->db->ErrorMsg()); }
                    }

                    $load_answer = TRUE;
                    $show['success']=TRUE;
                }
            }

            $this->smarty->assign_by_ref('answer',$input);
        }

        if($load_answer)
        {
            $query = "SELECT aid, name, type, label, sid FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE aid = $aid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error("Error selecting answer type information: " . $this->db->ErrorMsg()); return;}
            if($r = $rs->FetchRow($rs))
            {
                $answer = array();
                $answer = $r;
                $answer['name'] = $this->safe_string($answer['name'],SAFE_STRING_TEXT);
                $answer['label'] = $this->safe_string($answer['label'],SAFE_STRING_TEXT);
                $answer['selected'][$r['type']] = ' selected';
                $answer['allowable_images'] = $input['allowable_images'];

                $query = "SELECT avid, value, group_id, image FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE aid = $aid ORDER BY avid ASC";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error('Error getting answer values: ' . $this->db->ErrorMsg()); return;}
                if($r = $rs->FetchRow($rs))
                {
                    do{
                        $answer['avid'][] = $r['avid'];
                        $answer['value'][] = $this->safe_string($r['value'],SAFE_STRING_TEXT);
                        $answer['group_id'][] = $r['group_id'];
                        $key = array_search($r['image'],$answer['allowable_images']);
                        $answer['image_selected'][] = array($key => ' selected');
                    }while($r = $rs->FetchRow($rs));

                    $answer['num_answers'] = count($answer['avid']);

                    if($answer['num_answers'] < 100)
                    { $answer['show_add_answers'] = TRUE; }
                }
                else
                {
                    $answer['num_answers'] = 6;
                    for($x=0;$x<$answer['num_answers'];$x++)
                    { $answer['avid'][] = "x$x"; }
                    $answer['show_add_answers'] = TRUE;
                }

                $this->smarty->assign_by_ref('answer',$answer);
            }
            else
            { $error = "Invalid answer type"; }
        }
        else
        {
            $input['name'] = $this->safe_string($input['name'],SAFE_STRING_TEXT,1);
            $input['label'] = $this->safe_string($input['label'],SAFE_STRING_TEXT,1);
            foreach($input['value'] as $key=>$value)
            { $input['value'][$key] = $this->safe_string($value,SAFE_STRING_TEXT,1); }
        }

        if(isset($error))
        { $show['error'] = $error; }

        $this->smarty->assign('property',array('sid'=>$input['sid']));
        $show['links'] = $this->smarty->Fetch($this->template.'/edit_survey_links.tpl');

        $this->smarty->assign_by_ref('show',$show);

        $show['content'] = $this->smarty->Fetch($this->template.'/edit_survey_edit_at.tpl');

        $retval = $this->smarty->Fetch($this->template.'/edit_survey.tpl');

        return $retval;
    }

    /*****************************
    * CHOOSE ANSWER TYPE TO EDIT *
    *****************************/
    function edit_answer_type_choose($sid)
    {
        $answer = array();

        $answer['sid'] = (int)$sid;
        $query = "SELECT aid, name FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE sid = {$answer['sid']} ORDER BY name ASC";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error("Error selecting answers: " . $this->db->ErrorMsg()); }
        while($r = $rs->FetchRow())
        {
            $answer['aid'][] = $r['aid'];
            $answer['name'][] = $this->safe_string($r['name'],SAFE_STRING_TEXT);
        }
        $this->smarty->assign('answer',$answer);

        $this->smarty->assign('property',array('sid'=>$sid));
        $show['links'] = $this->smarty->Fetch($this->template.'/edit_survey_links.tpl');

        $show['content'] = $this->smarty->Fetch($this->template.'/edit_survey_edit_atc.tpl');

        $this->smarty->assign_by_ref('show',$show);

        $retval = $this->smarty->Fetch($this->template.'/edit_survey.tpl');

        return $retval;
    }

    /****************
    * ERROR MESSAGE *
    ****************/
    function error($msg)
    {
        $this->error_occurred = 1;

        if(is_object($this->smarty))
        {
            $this->smarty->assign("error",$msg);
            echo $this->smarty->fetch($this->template.'/error.tpl');
        }
        else
        { echo "Error: $msg"; exit(); }
    }

    /**************
    * SET MESSAGE *
    **************/
    function setmessage($title,$text)
    {
        if(!empty($title) && !empty($text))
        {
            $_SESSION['message']['title'] = $title;
            $_SESSION['message']['text'] = $text;
        }
    }

    /***************
    * SHOW MESSAGE *
    ***************/
    function showmessage()
    {
        $retval = '';
        if(!empty($_SESSION['message']['title']) && !empty($_SESSION['message']['text']))
        {
            $this->smarty->assign_by_ref('message',$_SESSION['message']);
            $retval = $this->smarty->fetch($this->template.'/message.tpl');
            unset($_SESSION['message']);
        }
        return $retval;
    }

    /*************
    * ADMIN PAGE *
    *************/
    function admin()
    {
        if(isset($_REQUEST['admin_password']))
        {
            if($_REQUEST['admin_password'] == $this->CONF['admin_password'])
            {
                $_SESSION['admin_logged_in'] = 1;
                header("Location: {$this->CONF['html']}/admin.php");
                exit();
            }
            else
            { $this->smarty->assign('message','Incorrect Password'); }
        }

        if(!isset($_SESSION['admin_logged_in']))
        { $retval = $this->smarty->Fetch($this->template.'/admin_login.tpl'); }
        else
        {
            $survey = array();

            $query = "SELECT sid, name FROM {$this->CONF['db_tbl_prefix']}surveys ORDER BY name ASC";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error("Error selecting surveys: " . $this->db->ErrorMsg()); }
            while($r = $rs->FetchRow())
            {
                $survey['sid'][] = $r['sid'];
                $survey['name'][] = $this->safe_string($r['name'],SAFE_STRING_TEXT);
            }

            $this->smarty->assign('survey',$survey);

            $this->smarty->assign_by_ref("all_surveys",$all_surveys);

            if(isset($survey) && count($survey) > 0)
            { $this->smarty->assign_by_ref("survey",$survey); }
            if(isset($results))
            { $this->smarty->assign_by_ref('results',$results); }
            if(isset($priv_survey))
            { $this->smarty->assign_by_ref('priv_survey',$priv_survey); }
            if(isset($priv_results))
            { $this->smarty->assign_by_ref('priv_results',$priv_results); }

            $retval = $this->smarty->Fetch($this->template.'/admin.tpl');
        }

        return $retval;
    }

    /********************
    * SQL Query Wrapper *
    ********************/
    function query($sql,$label = '',$report_error=1)
    {
        //Execute query
        $rs = $this->db->Execute($sql);

        //If error occurs and "report_error"
        //is set, show error
        if($rs === FALSE && $report_error)
        { $this->error($label . ' -- ' . $this->db->ErrorMsg()); }

        return $rs;
    }

    /**************
    * Safe String *
    **************/
    //Converts all special characters, including single
    //and double quotes, to HTML entities. Returned string
    //is safe to insert into databases or display to user
    function safe_string($str,$mode=SAFE_STRING_TEXT,$unescape=0)
    {
        if(is_array($str))
        {
            foreach($str as $key => $value)
            { $str[$key] = $this->safe_string($value,$mode,$unescape); }
        }
        else
        {
            $magic_quotes = get_magic_quotes_gpc();

            if($unescape && $magic_quotes)
            { $str = stripslashes($str); }

            switch($mode)
            {
                case SAFE_STRING_DB:
                    if(!$magic_quotes)
                    { $str = addslashes($str); }
                break;

                case SAFE_STRING_ESC:
                    $str = addslashes($str);
                break;

                case SAFE_STRING_LIMHTML:
                    $str = str_replace(array('{$images_html}','{$html}'),array($this->CONF['images_html'],$this->CONF['html']),$str);
                    $str = htmlentities($str,ENT_QUOTES);
                    $str = preg_replace('#&lt;b&gt;(.*?)&lt;/b&gt;#i','<b>\1</b>',$str);
                    $str = preg_replace('#&lt;i&gt;(.*?)&lt;/i&gt;#i','<i>\1</i>',$str);
                    $str = preg_replace('#&lt;u&gt;(.*?)&lt;/u&gt;#i','<u>\1</u>',$str);
                    $str = preg_replace_callback('#&lt;(div)(.*?)&gt;(.*?)&lt;/div&gt;#i',array(&$this,'safe_string_callback'),$str);
                    $str = preg_replace_callback('#&lt;(span)(.*?)&gt;(.*?)&lt;/span&gt;#i',array(&$this,'safe_string_callback'),$str);
                    $str = preg_replace_callback('#&lt;(a)(.*?)&gt;(.*?)&lt;/a&gt;#i',array(&$this,'safe_string_callback'),$str);
                    $str = preg_replace_callback('#&lt;(img)(.*?)&gt;#i',array(&$this,'safe_string_callback'),$str);
                break;

                case SAFE_STRING_FULLHTML:
                    $str = str_replace(array('{$images_html}','{$html}'),array($this->CONF['images_html'],$this->CONF['html']),$str);
                break;

                case SAFE_STRING_TEXT:
                default:
                    $str = htmlentities($str,ENT_QUOTES);
                break;
            }
        }

        return $str;
    }

    //Function to validate/sanitize limited HTML strings
    function safe_string_callback($matches)
    {
        $attrib = array('div' => 'class,style,id',
                   'span' => 'class,style,id',
                   'img' => 'border,id,class,style,src,height,width,alt',
                   'a' => 'id,class,style,href,target');

        //print_r($matches);
        if(isset($matches[2]) && !empty($matches[2]))
        {
            $allowed_attrib = str_replace(array(',',' '),array('|',''),$attrib[$matches[1]]);
            $matches[2] = str_replace('=','&#61;',$matches[2]);
            $pattern = "/({$allowed_attrib})&#61;(&quot;|&#039;)(.*)(&quot;|&#039;)/iU";
            $matches[2] = preg_replace($pattern,'\1="\3"',$matches[2]);
        }

        switch($matches[1])
        {
            case 'img':
                $retval = "<{$matches[1]}{$matches[2]}>";
            break;

            default:
                $retval = "<{$matches[1]}{$matches[2]}>{$matches[3]}</{$matches[1]}>";
            break;
        }

        return $retval;
    }

    /**************
    * PRINT ARRAY *
    **************/
    function print_array($ar)
    {
        echo '<pre>'.print_r($ar,TRUE).'</pre>';
    }

    /*****************
    * VALIDATE LOGIN *
    *****************/
    function check_login($sid)
    {
        //This function validates that the user is either logged in
        //to edit this survey or has logged in as an administrator

        if(isset($_SESSION['admin_logged_in']))
        {
            $this->smarty->assign('admin_link',TRUE);
            $_SESSION['edit_survey'][$sid] = 1;
        }

        //see if user has already provided
        //a password for this survey
        if((isset($_REQUEST['edit_survey_password']) && !empty($_REQUEST['edit_survey_password'])) || !isset($_SESSION['edit_survey'][$sid]))
        {
            if(!isset($_REQUEST['edit_survey_password']))
            { $this->error("This survey requires a password to edit."); return(FALSE); }

            //verify password against what's in the database
            $query = "SELECT 1 FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid = $sid and edit_password = '{$_REQUEST['edit_survey_password']}'";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error("Error checking password for edit: " . $this->db->ErrorMsg()); return(FALSE); }

            //if password is correct, set session value,
            //otherwise throw an error
            if($r = $rs->FetchRow($rs))
            { $_SESSION['edit_survey'][$sid] = 1; }
            else
            { $this->error("Incorrect password to edit survey."); return(FALSE); }
        }

        return(TRUE);
    }

    function check_access($sid)
    {
        if(isset($_SESSION['admin_logged_in']) || isset($_SESSION['edit_survey'][$sid]))
        { return TRUE; }
        else
        { return FALSE; }
    }
}

?>