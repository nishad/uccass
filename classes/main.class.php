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

//Start Session
session_start();

//Set Error Reporting Level to not
//show notices or warnings
//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
error_reporting(E_ALL);

//Turn off runtime escaping of quotes
set_magic_quotes_runtime(0);

//Define CONSTANTS
define('BY_AID',1);
define('BY_QID',2);

//Privileges
define('ADMIN_PRIV',0);
define('CREATE_PRIV',1);
define('TAKE_PRIV',2);
define('RESULTS_PRIV',3);
define('EDIT_PRIV',4);

//Message types
define('MSGTYPE_NOTICE',1);
define('MSGTYPE_ERROR',2);

//Access control options
define('AC_NONE',0);
define('AC_COOKIE',1);
define('AC_IP',2);
define('AC_USERNAMEPASSWORD',3);
define('AC_INVITATION',4);

//Message for already completed survey
define('ALREADY_COMPLETED',1);

//Answer types
define('ANSWER_TYPE_T','T');    //Textarea
define('ANSWER_TYPE_S','S');    //Textbox (sentence)
define('ANSWER_TYPE_N','N');    //None
define('ANSWER_TYPE_MS','MS');  //Multiple choice, single answer
define('ANSWER_TYPE_MM','MM');  //Multiple choice, multiple answer
define('LABEL_PREFIX','L');

//Orientation Types
define('ANSWER_ORIENTATION_H','H'); //Horizontal
define('ANSWER_ORIENTATION_V','V'); //Vertical
define('ANSWER_ORIENTATION_D','D'); //Dropdown
define('ANSWER_ORIENTATION_M','M'); //Matrix

//Form Elements
define('FORM_CHECKED',' checked');
define('FORM_SELECTED',' selected');

//Lookback Settings
define('LOOKBACK_TEXT','$lookback.');
define('LOOKBACK_START_DELIMITER','{');
define('LOOKBACK_END_DELIMITER','}');

//Export CSV Settings
define('EXPORT_CSV_TEXT',1);
define('EXPORT_CSV_NUMERIC',2);
define('MULTI_ANSWER_SEPERATOR',', ');

//HTML Constants
define('BR','<br />');
define('NBSP','&nbsp;');
define('NL',"\n");
define('CR',"\r");
define('NOT', 'NOT');

// Dependency modes of questions
// We use string values to preserve compatibility with previous versions of uccass
// that used those strings.
define('DEPEND_MODE_HIDE', 'Hide');
define('DEPEND_MODE_REQUIRE', 'Require');
define('DEPEND_MODE_SHOW', 'Show');
define('DEPEND_MODE_SELECTOR', 'Selector');

//Hack to get rid of cookies named "sid"
if(isset($_POST['sid']))
{ $_REQUEST['sid'] = $_POST['sid']; }
elseif(isset($_GET['sid']))
{ $_REQUEST['sid'] = $_GET['sid']; }
else
{ unset($_REQUEST['sid']); }

class UCCASS_Main
{
    function UCCASS_Main()
    { $this->load_configuration(); }

    /*********************
    * LOAD CONFIGURATION *
    *********************/
    function load_configuration()
    {
        require('language.default.php');
        $this->lang = &$lang;

        //Ensure install.php file has be removed
        if(!isset($_REQUEST['config_submit']) && file_exists('install.php'))
        {
            $this->error($this->lang['install_warning']);
            return;
        }

        $ini_file = 'survey.ini.php';
        //Load values from .ini. file
        if(file_exists($ini_file))
        {
            $this->CONF = @parse_ini_file($ini_file);
            if(count($this->CONF) == 0)
            { $this->error($this->lang['config_parse_error']); return; }
        }
        else
        { $this->error($this->lang['config_not_found']); return; }

        //Version of Survey System
        $this->CONF['version'] = $this->lang['version'];

        //Default path to Smarty
        if(!isset($this->CONF['smarty_path']) || $this->CONF['smarty_path'] == '')
        { $this->CONF['smarty_path'] = $this->CONF['path'] . '/smarty'; }

        //Default path to ADOdb
        if(!isset($this->CONF['adodb_path']) || $this->CONF['adodb_path'] == '')
        { $this->CONF['adodb_path'] = $this->CONF['path'] . '/ADOdb'; }

        //Load ADOdb files
        $adodb_file = $this->CONF['adodb_path'] . '/adodb.inc.php';
        if(file_exists($adodb_file))
        { require_once($this->CONF['adodb_path'] . '/adodb.inc.php'); }
        else
        { $this->error($this->lang['file_not_found'] . ': ' . $adodb_file); return; }

        //Load Smarty Files
        $smarty_file = $this->CONF['smarty_path'] . '/Smarty.class.php';
        if(file_exists($smarty_file))
        { require_once($this->CONF['smarty_path'] . '/Smarty.class.php'); }
        else
        { $this->error($this->lang['file_not_found'] . ': ' . $smarty_file); return; }

        //Create Smarty object and set
        //paths within object
        $this->smarty = new Smarty;
        $this->smarty->template_dir    =  $this->CONF['path'] . '/templates';                    // name of directory for templates
        $this->smarty->compile_dir     =  $this->CONF['smarty_path'] . '/templates_c';     // name of directory for compiled templates
        $this->smarty->config_dir      =  $this->CONF['smarty_path'] . '/configs';         // directory where config files are located
        $this->smarty->plugins_dir     =  array($this->CONF['smarty_path'] . '/plugins');  // plugin directories

        //Ensure templates_c directory is writable
        if(!is_writable($this->smarty->compile_dir))
        { $this->error($this->lang['template_path_writable_warning']); return; }

        //If SAFE_MODE is ON in PHP, turn off subdirectory use for Smarty
        if(ini_get('safe_mode'))
        { $this->smarty->use_sub_dirs = FALSE; }

        //Establish Connection to database
        $this->db = NewADOConnection($this->CONF['db_type']);
        //$this->db->debug = true; // Print all the queries.
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $conn = $this->db->Connect($this->CONF['db_host'],$this->CONF['db_user'],$this->CONF['db_password'],$this->CONF['db_database']);
        if(!$conn)
        { $this->error($this->lang['db_connect_error'] . $this->db->ErrorMsg()); return; }
        // MySQL: It's very important that both database tables and the client (php) use 
        // the same encoding - if they were different, MySQL would convert from one to the other.
        // The client encoding is determined by php - by default it's latin1 but may be changed;
        // you can find out you current encoding - call mysql_client_encoding().
        // This holds even if you've set utf-8 in uccass config - it doesn't matter (I hope) that
        // the data is in utf-8 and the DB believes it's in latin1 - the important point is that
        // MySQL doesn't perform any destructive conversions.    
        if( strcasecmp($this->CONF['db_type'], 'mysql') == 0 )
        { $this->db->Execute("SET NAMES 'latin1'"); /*Expect/send data in latin1 == don't perform any conversions.*/ }

        //Create SafeString object for escaping user text
        require_once($this->CONF['path'] . '/classes/safestring.class.php');
        $this->SfStr = new SafeString($this->CONF['db_type'],$this->CONF['charset']);

        //Set template, html and image paths/directories into configuration
        if(!$this->set_template_paths($this->CONF['default_template']))
        { $this->error($this->lang['template_path_warning'] . $this->CONF['template_path']); return; }

        $this->SfStr->setHTML($this->CONF['html']);
        $this->SfStr->setImagesHTML($this->CONF['images_html']);

        //Define variables
        $this->CONF['orientation'] = array($this->lang['vertical'],$this->lang['horizontal'],$this->lang['dropdown'],$this->lang['matrix']);
        $this->CONF['text_modes'] = array($this->lang['text_only'],$this->lang['limited_html'],$this->lang['full_html']);
        $this->CONF['dependency_modes'] = array(DEPEND_MODE_HIDE => $this->lang['hide'],
        	DEPEND_MODE_REQUIRE=>$this->lang['require'], DEPEND_MODE_SHOW => $this->lang['show'],
        	DEPEND_MODE_SELECTOR => $this->lang['selector']);

        //Validate and set default survey text modes
        $this->CONF['survey_text_mode'] = (int)$this->CONF['survey_text_mode'];
        if($this->CONF['survey_text_mode'] < 0 || $this->CONF['survey_text_mode'] > 2)
        { $this->CONF['survey_text_mode'] = 0; }

        //Validate and set default user text mode
        $this->CONF['user_text_mode'] = (int)$this->CONF['user_text_mode'];
        if($this->CONF['user_text_mode'] < 0 || $this->CONF['user_text_mode'] > 2)
        { $this->CONF['user_text_mode'] = 0; }

        //Set default value on permission to create new surveys (default is private)
        if(strcasecmp($this->CONF['create_access'],'public')==0)
        { $this->CONF['create_access'] = 0; }
        else
        { $this->CONF['create_access'] = 1; }

        //Check session for admin_priv flag and if present, set configuration
        //flag to show Admin links
        if(isset($_SESSION['priv'][0][ADMIN_PRIV]))
        { $this->CONF['show_admin_link'] = 1; }

        //Assign configuration values to template
        $this->smarty->assign_by_ref('conf',$this->CONF);

        return;
    }

    /*********************
    * SET TEMPLATE PATHS *
    *********************/
    function set_template_paths($template)
    {
        //Look in URL or Form data for "sid" and load survey template
        $sid = 0;
        if(isset($_GET['sid']))
        { $sid = (int)$_GET['sid']; }
        elseif(isset($_POST['sid']))
        { $sid = (int)$_POST['sid']; }

        if($sid)
        {
            //Retrieve template for passed "sid"
            $query = "SELECT name, template, survey_text_mode FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid = {$sid}";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_connect_error'] . $this->db->ErrorMsg()); return; }
            if($r = $rs->FetchRow($rs))
            {
                $this->CONF['template'] = $r['template'];
                $this->CONF['survey_name'] = $this->SfStr->getSafeString($r['name'],$r['survey_text_mode']);
                $this->CONF['sid'] = $sid;
            }
        }
        else
        {
            //If no "sid" detected, load template that was passed
            $this->CONF['template'] = $template;
        }

        //Set template path into configuration array and ensure template directory exists
        $this->CONF['template_path'] = $this->CONF['path'] . '/templates/' . $this->CONF['template'];
        if(!file_exists($this->CONF['template_path']))
        { return(FALSE); }

        //Load HTTP path to site and template
        $this->CONF['template_html'] = $this->CONF['html'] . '/templates/' . $this->CONF['template'];

        //Check for images directory within template
        if(file_exists($this->CONF['template_path'] . '/images'))
        {
            //Set image paths to images folder within template
            $this->CONF['images_html'] = $this->CONF['html'] . '/templates/' . $this->CONF['template'] . '/images';
            $this->CONF['images_path'] = $this->CONF['path'] . '/templates/' . $this->CONF['template'] . '/images';
        }
        else
        {
            //No image folder exists in template, so set image paths to template folder itself
            $this->CONF['images_html'] = $this->CONF['html'] . '/templates/' . $this->CONF['template'];
            $this->CONF['images_path'] = $this->CONF['path'] . '/templates/' . $this->CONF['template'];
        }

        //Include language file for chosen template
        include($this->CONF['template_path'] . '/language.tpl');
        $this->lang = &$lang;

        return(TRUE);
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
            echo $this->smarty->fetch($this->CONF['template'].'/error.tpl');
        }
        else
        { echo ucfirst($this->lang['error']) . ": {$msg}"; exit(); }
    }

    /**************
    * SET MESSAGE *
    **************/
    function setMessage($title,$text,$type=MSGTYPE_NOTICE)
    {
        if(!empty($title) && !empty($text))
        {
            $_SESSION['message']['title'] = $title;
            $_SESSION['message']['text'] = $text;
            $_SESSION['message']['type'] = $type;

            if(!empty($this->_messageredirect))
            {
                session_write_close();
                header("Location: {$this->_messageredirect}");
                exit();
            }
        }
    }

    /*******************************
    * SET MESSAGE REDIRECTION PAGE *
    *******************************/
    function setMessageRedirect($page)
    {
        if(strpos($page,$this->CONF['html'])===FALSE)
        {
            if($page{0} != '/')
            { $page = '/' . $page; }
            $page = $this->CONF['html'] . $page;
        }
        $this->_messageredirect = $page;
    }


    /***************
    * SHOW MESSAGE *
    ***************/
    function showMessage()
    {
        $retval = '';
        if(!empty($_SESSION['message']['title']) && !empty($_SESSION['message']['text']))
        {
            switch($_SESSION['message']['type'])
            {
                case MSGTYPE_ERROR:
                    $this->smarty->assign_by_ref('error',$_SESSION['message']['text']);
                    $retval = $this->smarty->fetch($this->CONF['template'].'/error.tpl');
                break;
                default:
                    $this->smarty->assign_by_ref('message',$_SESSION['message']);
                    $retval = $this->smarty->fetch($this->CONF['template'].'/message.tpl');
                break;
            }
            unset($_SESSION['message']);
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
    * PRINT ARRAY *
    **************/
    function print_array($ar)
    {
        echo '<pre>'.print_r($ar,TRUE).'</pre>';
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
        { $values['title'] = $this->SfStr->getSafeString($title,SAFE_STRING_TEXT); }

        $this->smarty->assign_by_ref('values',$values);
        return $this->smarty->fetch($this->CONF['template'].'/main_header.tpl') . $this->showMessage();
    }

    /*********
    * FOOTER *
    *********/
    function com_footer()
    {
        //Close connection to database
        $this->db->Close();

        //Return footer template
        return $this->smarty->fetch($this->CONF['template'].'/main_footer.tpl');
    }

    /*************************
    * RETRIEVE ANSWER VALUES *
    * @param int $id id of an answer type or a question whose answer values we
    * want
    * @param int $by BY_AID (id is an id of an answer type) or BY_QID (...
    * of a question)
    * @param int $mode
    * @param mixed $selectors (array/bool) Either an array of strings used to
    * limit the number of selected dynamic answer type answers or false (==
    * select all).
    * 
    * @return mixed: Failure => FALSE; Success => array (keys: avid, value,
    * numeric_value, image, the value of avid; values: arrays). 
    * Ex. (2 answers): array ('avid' => array ( 0=> '1', 1 => '2', ), 'value' =>
    * array ( 0 => 'A blondie', 1 => 'A brunette', ), 'numeric_value' => array (
    * 0 => '1', 1 => '2', ), 'image' => array ( 0 => 'bar.gif', 1 => 'bar.gif',
    * ), 1 => 'A blondie', 2 => 'A brunette', )
    * 
    *************************/ // FIXME: $selector is false or an array of answers
    function get_answer_values($id,$by=BY_AID,$mode=SAFE_STRING_TEXT, $selectors = false)
    {
        $retval = FALSE;
        static $answer_values;

        $id = (int)$id;
        $sid = (int)$_REQUEST['sid'];

        if(isset($answer_values[$id]))
        { $retval = $answer_values[$id]; }
        else
        {
        	// Prepare the selectors to be included in a where condition
        	$selector_where_clause = " 1=1 ";	// a condition that is always true
        	$selector_join_av = " LEFT JOIN {$this->CONF['db_tbl_prefix']}dyna_answer_selectors avs ON (av.avid = avs.avid)";
        	if(is_array($selectors))
            {
        		$selector_join_atype = " ";
        		$operator = '=';
        		$selector_where_clause = "("; // will be: (avs.selector = val1 [OR ...] OR avs.selector is null))
        		
        		foreach($selectors as $selector_value)
            {
        			$selector_value = $this->SfStr->getSafeString($selector_value, SAFE_STRING_DB); 
        			$selector_where_clause .= "avs.selector $operator $selector_value OR "; 
            }
        		$selector_where_clause .= "avs.selector is null)";
        	}

        	// Create the query
    		$query = "SELECT av.avid, av.value, av.numeric_value, av.image, atype.is_dynamic 
    				  FROM {$this->CONF['db_tbl_prefix']}answer_values av $selector_join_av" .
    				  	(($by==BY_QID)? "JOIN {$this->CONF['db_tbl_prefix']}questions q ON (q.aid = av.aid) " : "") .  
            			"JOIN {$this->CONF['db_tbl_prefix']}answer_types atype ON (atype.aid = av.aid)".
                      "WHERE $selector_where_clause AND " .
                      	(($by==BY_QID)? "q.qid = $id AND q.sid = $sid " : "av.aid = $id ") .
                      "ORDER BY av.avid ASC";
			
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { return $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }

            $isDynamic = false;
            while($r = $rs->FetchRow($rs))
            {
                $retval['avid'][] = $r['avid'];
                $retval['value'][] = $this->SfStr->getSafeString($r['value'],$mode);
                $retval['numeric_value'][] = $r['numeric_value'];
                $retval['image'][] = $r['image'];
                $retval[$r['avid']] = $r['value'];
                $isDynamic = $isDynamic || ($r['is_dynamic'] == 1); // FIXME: check the field is_dynamic instead
            }

            if(!$isDynamic)
            { $answer_values[$id] = $retval; }	// don't save  dynamic answer type answer values
        }

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

                $answers[$x]['name'] = $this->SfStr->getSafeString($r['name'],$r['survey_text_mode']);
                $answers[$x]['type'] = $r['type'];
                $answers[$x]['value'][] = $this->SfStr->getSafeString($r['value'],$r['survey_text_mode']);

                if(empty($r['label']))
                { $answers[$x]['label'] = '&nbsp;'; }
                else
                { $answers[$x]['label'] = $this->SfStr->getSafeString($r['label'],$r['survey_text_mode']); }


                $old_name = $r['name'];
            }
            else
            { $answers[$x]['value'][] = $this->SfStr->getSafeString($r['value'],$r['survey_text_mode']); }
        }

        $this->smarty->assign_by_ref("answers",$answers);

        $retval = $this->smarty->fetch($this->CONF['template'].'/display_answers.tpl');

        return $retval;
    }

    /*****************
    * VALIDATE LOGIN *
    *****************/
    function _CheckLogin($sid=0, $priv=EDIT_PRIV,$redirect_page='')
    {
        $retval = FALSE;

        $sid = (int)$sid;
        $priv = (int)$priv;

        //Checks to see if user is already logged in with required
        //privledge for specific survey (or zero for no survey)
        if((isset($_SESSION['priv'][0][ADMIN_PRIV]) && $_SESSION['priv'][0][ADMIN_PRIV]==1)
            || (isset($_SESSION['priv'][$sid][$priv]) && $_SESSION['priv'][$sid][$priv]==1))
        { $retval = TRUE; }
        else
        {
            $retval = $this->_checkUsernamePassword($sid,$priv);

            if($retval)
            {
                if(!empty($redirect_page))
                {
                    session_write_close();
                    header("Location: {$this->CONF['html']}/{$redirect_page}");
                    exit();
                }
            }
        }

        return $retval;
    }

    function _CheckAccess($sid=0,$priv=TAKE_PRIV,$redirect_page='')
    {
        $retval = FALSE;

        $sid = (int)$sid;
        $priv = (int)$priv;

        //Checks to see if user is already logged in with required
        //privledge for specific survey (or zero for no survey)
        if((isset($_SESSION['priv'][0][ADMIN_PRIV]) && $_SESSION['priv'][0][ADMIN_PRIV]==1)
            || (isset($_SESSION['priv'][$sid][$priv]) && $_SESSION['priv'][$sid][$priv]==1))
        {
            if($sid != 0 && isset($_SESSION['priv'][0]['uid']))
            { $_SESSION['priv'][$sid]['uid'] = $_SESSION['priv'][0]['uid']; }
            $retval = TRUE;
        }
        else
        {
            $query = "SELECT access_control, public_results, survey_limit_times, survey_limit_seconds FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
            elseif($r = $rs->FetchRow($rs))
            {
                $_SESSION['access_control'][$sid] = $r['access_control'];

                if($priv == RESULTS_PRIV && $r['public_results'])
                {
                    $retval = TRUE;
                    $redirect_page = '';
                }
                elseif($priv == RESULTS_PRIV && ($r['access_control'] == AC_IP || $r['access_control'] == AC_COOKIE || $r['access_control'] == AC_NONE))
                { $retval = $this->_checkUsernamePassword($sid,$priv); }
                else
                {
                    switch($r['access_control'])
                    {
                        case AC_USERNAMEPASSWORD:
                            $retval = $this->_checkUsernamePassword($sid,$priv,$r['survey_limit_times'],$r['survey_limit_seconds']);
                        break;
                        case AC_INVITATION:
                            $retval = $this->_checkInvitation($sid,$priv,$r['survey_limit_times'],$r['survey_limit_seconds']);
                        break;
                        case AC_IP:
                            $retval = $this->_checkIP($sid,$priv,$r['survey_limit_times'],$r['survey_limit_seconds']);
                            $redirect_page = '';
                        break;
                        case AC_COOKIE:
                            $retval = $this->_checkCookie($sid,$priv,$r['survey_limit_times'],$r['survey_limit_seconds']);
                            $redirect_page = '';
                        break;
                        case AC_NONE:
                        default:
                            $retval = TRUE;
                            $redirect_page = '';
                        break;
                    }
                }
            }

            if($retval === TRUE)
            {
                if(!empty($redirect_page))
                {
                    session_write_close();
                    header("Location: {$this->CONF['html']}/{$redirect_page}");
                    exit();
                }
            }
        }

        return $retval;
    }

    function _checkUsernamePassword($sid,$priv,$numallowed=0,$numseconds=0)
    {
        $retval = FALSE;

        if(isset($_REQUEST['username']) && isset($_REQUEST['password']))
        {
            if($sid != 0)
            { $sid_check = " (sid = $sid OR sid = 0) "; }
            else
            { $sid_check = " sid = $sid "; }

            $input['username'] = $this->SfStr->getSafeString($_REQUEST['username'],SAFE_STRING_DB);
            $input['password'] = $this->SfStr->getSafeString($_REQUEST['password'],SAFE_STRING_DB);
            $query = "SELECT password, uid, name, email, admin_priv, create_priv, take_priv, results_priv, edit_priv FROM
                      {$this->CONF['db_tbl_prefix']}users WHERE {$sid_check} AND username = {$input['username']}
                      and password={$input['password']}";

            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }

            if($r = $rs->FetchRow($rs))
            {
                //Case sensitive compare done in PHP
                //to be compatible across different databases
                if(!strcmp($_REQUEST['password'],$r['password']))
                {
                    if($r['admin_priv'])
                    {
                        $_SESSION['priv'][0] = array(ADMIN_PRIV => 1, CREATE_PRIV => 1);
                        if($sid != 0)
                        { $_SESSION['priv'][$sid] = array(TAKE_PRIV => 1, EDIT_PRIV => 1, RESULTS_PRIV => 1); }
                        $retval = TRUE;
                    }
                    elseif($priv == TAKE_PRIV && $numallowed)
                    {
                        if($numallowed && $numseconds==0)
                        { $lim = 0; }
                        else
                        { $lim = time() - $numseconds; }

                        $query = "SELECT COUNT(uid) AS count_uid FROM {$this->CONF['db_tbl_prefix']}completed_surveys WHERE uid={$r['uid']} AND completed > $lim GROUP BY uid";
                        $rs = $this->db->Execute($query);
                        if($rs === FALSE)
                        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
                        elseif($r2 = $rs->FetchRow($rs))
                        {
                            if($r2['count_uid'] < $numallowed)
                            { $retval = TRUE; }
                            else
                            { $retval = ALREADY_COMPLETED; }
                        }
                        else
                        { $retval = TRUE; }

                        if($retval === TRUE)
                        {
                            $_SESSION['priv'][$sid] = array(TAKE_PRIV => $r['take_priv'], EDIT_PRIV => $r['edit_priv'],
                                                            RESULTS_PRIV => $r['results_priv']);
                        }
                    }
                    else
                    {
                        $_SESSION['priv'][$sid] = array(TAKE_PRIV => $r['take_priv'], EDIT_PRIV => $r['edit_priv'],
                                                        RESULTS_PRIV => $r['results_priv'], CREATE_PRIV => $r['create_priv']);

                        if(isset($_SESSION['priv'][$sid][$priv]) && $_SESSION['priv'][$sid][$priv] == 1)
                        { $retval = TRUE; }
                    }

                    $_SESSION['priv'][$sid]['name'] = $r['name'];
                    $_SESSION['priv'][$sid]['email'] = $r['email'];
                    $_SESSION['priv'][$sid]['uid'] = $r['uid'];
                }
            }
        }

        return $retval;
    }

    function _checkInvitation($sid,$priv,$numallowed=0,$numseconds=0)
    {
        $sid = (int)$sid;
        $retval = FALSE;

        if(isset($_REQUEST['invite_code']))
        {
            $input['invite_code'] = $this->SfStr->getSafeString($_REQUEST['invite_code'],SAFE_STRING_DB);
            $query = "SELECT uid, name, email, take_priv, results_priv FROM {$this->CONF['db_tbl_prefix']}users WHERE sid=$sid AND invite_code = {$input['invite_code']}";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
            elseif($r = $rs->FetchRow($rs))
            {
                if($priv == TAKE_PRIV && $numallowed)
                {
                    if($numallowed && $numseconds==0)
                    { $lim = 0; }
                    else
                    { $lim = time() - $numseconds; }

                    $query = "SELECT COUNT(uid) AS count_uid FROM {$this->CONF['db_tbl_prefix']}completed_surveys WHERE uid={$r['uid']} AND completed > $lim GROUP BY uid";
                    $rs = $this->db->Execute($query);
                    if($rs === FALSE)
                    { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return FALSE; }
                    elseif($r2 = $rs->FetchRow($rs))
                    {
                        if($r2['count_uid'] < $numallowed)
                        { $retval = TRUE; }
                        else
                        { $retval = ALREADY_COMPLETED; }
                    }
                    else
                    { $retval = TRUE; }
                }
                else
                {
                    $_SESSION['priv'][$sid][TAKE_PRIV] = $r['take_priv'];
                    $_SESSION['priv'][$sid][RESULTS_PRIV] = $r['results_priv'];

                    if($_SESSION['priv'][$sid][$priv] == 1)
                    { $retval = TRUE; }
                }

                $_SESSION['priv'][$sid]['name'] = $r['name'];
                $_SESSION['priv'][$sid]['email'] = $r['email'];
                $_SESSION['priv'][$sid]['uid'] = $r['uid'];

                if($retval === TRUE)
                { $_SESSION['priv'][$sid][TAKE_PRIV] = 1; }
            }
        }

        return $retval;
    }

    function _checkCookie($sid,$priv,$numallowed=0,$numseconds=0)
    {
        $retval = FALSE;
        $name = 'uccass'.md5($sid);

        if(isset($_COOKIE[$name]))
        {
            $now = time();
            $times = unserialize($_COOKIE[$name]);
            if(is_array($times))
            {
                if(count($times) < $numallowed)
                { $retval = TRUE; }
                elseif($numallowed && $numseconds)
                {
                    rsort($times);
                    $times = array_slice($times,0,$numallowed);

                    if($numseconds && ($times[$numallowed-1] < $now - $numseconds))
                    { $retval = TRUE; }
                    $times = serialize($times);
                    setcookie($name,$times,$now+31557600);
                }
            }
            else
            { $retval = FALSE; }
        }
        else
        { $retval = TRUE; }

        return $retval;
    }

    function _checkIP($sid,$priv,$numallowed=0,$numseconds=0)
    {
        $retval = FALSE;
        $ip = $this->SfStr->getSafeString($_SERVER['REMOTE_ADDR'],SAFE_STRING_DB);
        $criteria = '';

        if($priv == TAKE_PRIV && $numallowed)
        {
            if($numallowed && $numseconds == 0)
            { $lim = 0; }
            else
            { $lim = time() - $numseconds; }
            $criteria = " AND completed > $lim ";
        }

        $query = "SELECT COUNT(sid) as count_sid FROM {$this->CONF['db_tbl_prefix']}ip_track WHERE ip = $ip AND sid = $sid $criteria GROUP BY sid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
        elseif($r = $rs->FetchRow($rs))
        {
            if($r['count_sid'] < $numallowed)
            { $retval = TRUE; }
            else
            { $retval = ALREADY_COMPLETED; }
        }
        else
        { $retval = TRUE; }

        return $retval;
    }

    function _hasPriv($priv,$sid=0)
    {
        if(isset($_SESSION['priv'][$sid][$priv]) && $_SESSION['priv'][$sid][$priv]==1)
        { return TRUE; }
        else
        { return FALSE; }
    }

    function _getAccessControl($sid)
    {
        $retval = FALSE;
        if(isset($_SESSION['access_control'][$sid]))
        { $retval = $_SESSION['access_control'][$sid]; }
        else
        {
            $query = "SELECT access_control FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid=$sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
            elseif($r = $rs->FetchRow($rs))
            { $retval = $r['access_control']; }
        }
        return $retval;
    }

    function showLogin($page, $hidden = array())
    {
        //If validation fails, but username data was present,
        //set an error message and show login form again.
        if(isset($_REQUEST['username']))
        {
            $data['message'] = $this->lang['wrong_login_info'];
            $data['username'] = $this->SfStr->getSafeString($_REQUEST['username'],SAFE_STRING_TEXT);
        }
        //Set required data for login page
        //and show login form
        $data['page'] = $page;
        if(is_array($hidden))
        {
            foreach($hidden as $key=>$val)
            {
                $data['hiddenkey'][] = $key;
                $data['hiddenval'][] = $val;
            }
        }
        $this->smarty->assign_by_ref('data',$data);
        return $this->smarty->Fetch($this->CONF['template'].'/login.tpl');
    }

    function showInvite($page, $hidden)
    {
        if(isset($_REQUEST['invite_code']))
        {
            $data['message'] = $this->lang['wrong_invite_code'];
            $data['invite_code'] = $this->SfStr->getSafeString($_REQUEST['invite_code'],SAFE_STRING_TEXT);
        }

        $data['page'] = $page;
        if(is_array($hidden))
        {
            foreach($hidden as $key=>$val)
            {
                $data['hiddenkey'][] = $key;
                $data['hiddenval'][] = $val;
            }
        }

        $this->smarty->assign_by_ref('data',$data);
        return $this->smarty->Fetch($this->CONF['template'].'/invite_code.tpl');
    }

    function setError($error)
    {
        if(is_array($error))
        {
            foreach($error as $msg)
            { $this->setError($msg); }
        }
        $this->error[] = $error;
    }

    function clearError()
    { $this->error = array(); }

    function isError()
    { return count($this->error); }

    function lang($key)
    { return (isset($this->lang[$key])) ? $this->lang[$key] : ''; }
}

?>