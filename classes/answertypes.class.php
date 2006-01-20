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

class UCCASS_AnswerTypes extends UCCASS_Main
{
    function UCCASS_AnswerTypes()
    { $this->load_configuration(); }

    /******************
    * NEW ANSWER TYPE *
    ******************/
    function new_answer_type($sid)
    {
        $error = '';

        //Ensure user is logged in with valid privileges
        //for the requested survey or is an administrator
        if(!$this->_CheckLogin($sid,EDIT_PRIV,"new_answer_type.php?sid=$sid"))
        { return $this->showLogin('new_answer_type.php',array('sid'=>$sid)); }

        //The following values are also set
        //upon a successful submission to "reset"
        //the form...
        $input['name'] = '';
        $input['label'] = '';
        $input['value'] = array();
        $input['numeric_value'] = array();
        $input['num_answers'] = 6;
        $input['show_add_answers'] = TRUE;
        $input['sid'] = (int)$sid;
        $input['allowable_images'] = $this->get_image_names();

        if(isset($_REQUEST['submit']) || isset($_REQUEST['add_answers_submit']))
        {
            if(isset($_REQUEST['add_answers_submit']))
            { $ss_type = SAFE_STRING_TEXT; }
            else
            { $ss_type = SAFE_STRING_DB; }

            if(strlen($_REQUEST['name']) > 0)
            { $input['name'] = $this->SfStr->getSafeString($_REQUEST['name'],$ss_type); }
            else
            { $error .= $this->lang['enter_name']; }

            $input['label'] = $this->SfStr->getSafeString($_REQUEST['label'],$ss_type);
            
            // Note: it's only meaningful for answer types with multiple answer values to be dynamic
        	$input['is_dynamic'] = isset($_REQUEST['is_dynamic']) && !empty($_REQUEST['is_dynamic']);

            switch($_REQUEST['type'])
            {
                case ANSWER_TYPE_T:
                case ANSWER_TYPE_S:
                case ANSWER_TYPE_N:
                    $input['type'] = $this->SfStr->getSafeString($_REQUEST['type'],$ss_type);
                    if(isset($_REQUEST['add_answers_submit']))
                    { $error .= $this->lang['add_answer_error']; }
                break;
                case ANSWER_TYPE_MM:
                case ANSWER_TYPE_MS:
                case ANSWER_TYPE_S:
                    $input['type'] = $this->SfStr->getSafeString($_REQUEST['type'],$ss_type);

                    if(isset($_REQUEST['value']) && is_array($_REQUEST['value']) &&
                       isset($_REQUEST['numeric_value']) && is_array($_REQUEST['numeric_value']) &&
                       count($_REQUEST['value']) <= 99)
                    {
                        $input['num_answers'] = min(99,count($_REQUEST['value']));

                        foreach($_REQUEST['value'] as $key=>$value)
                        {
                            if(strlen($value) > 0)
                            {
                                $input['value'][] = $this->SfStr->getSafeString($value,$ss_type);

                                $image_key = array_search($_REQUEST['image'][$key],$input['allowable_images']);
                                if($image_key === FALSE)
                                { $error .= $this->lang['bad_image']; }
                                else
                                {
                                    $input['image'][] = $this->SfStr->getSafeString($_REQUEST['image'][$key],$ss_type);
                                    $selected['image'][] = array($image_key => ' selected');
                                }

                                if(empty($_REQUEST['numeric_value'][$key]))
                                { $input['numeric_value'][] = 0; }
                                else
                                { $input['numeric_value'][] = (int)$_REQUEST['numeric_value'][$key];  }
                            }
                        }

                        if(count($input['value']) == 0)
                        { $error .= $this->lang['answer_value_error']; }
                    }
                    else
                    { $error .= $this->lang['bad_answer-numeric_value']; }

                    if(!isset($input['num_answers']))
                    { $input['num_answers'] = 6; }

                    if(isset($_REQUEST['add_answers_submit']))
                    { $input['num_answers'] += (int)$_REQUEST['add_answer_num']; }

                    if($input['num_answers'] > 99)
                    {
                        $input['num_answers'] = 99;
                        $error .= $this->lang['only_99_allowed'];
                        $input['show_add_answers'] = FALSE;
                    }
                    elseif($input['num_answers'] == 99)
                    { $input['show_add_answers'] = FALSE; }

                break;
                default:
                    $error .= $this->lang['bad_answer_type'];
                break;
            }

            if(!isset($_REQUEST['add_answers_submit']) && (!isset($error) || strlen($error) == 0))
            {
            	// Store the answer into the databese
            	if( $this->db_insert_answer_type($input) === true )
                {
                    $success=TRUE;
                    $this->smarty->assign('success',$success);

                    $allowable_images = $input['allowable_images'];

                    $input = array();
                    $input['name'] = '';
                    $input['label'] = '';
                    $input['value'] = array();
                    $input['numeric_value'] = array();
                    $input['num_answers'] = 6;
                    $input['show_add_answers'] = TRUE;
                    $input['sid'] = (int)$_REQUEST['sid'];
                    $input['allowable_images'] = $allowable_images;
                }
            }
        }

        if(isset($_REQUEST['type']))
        {
            $selected[$_REQUEST['type']] = ' selected';
            $this->smarty->assign('selected',$selected);
        }

        if(strlen($error)>0)
        {
            //Encode $input values so they are safe to "reshow"
            //in the form in case of an error
            $input['name'] = $this->SfStr->getSafeString($_REQUEST['name'],SAFE_STRING_TEXT);
            $input['label'] = $this->SfStr->getSafeString($_REQUEST['label'],SAFE_STRING_TEXT);
            foreach($_REQUEST['value'] as $key => $value)
            {
                $input['value'][$key] = $this->SfStr->getSafeString($value,SAFE_STRING_TEXT);
                $input['numeric_value'][$key] = $this->SfStr->getSafeString($_REQUEST['numeric_value'][$key],SAFE_STRING_TEXT);
                $input['image'][$key][$_REQUEST['image'][$key]] = ' selected';
            }

            $show['error'] = $error;
        }
        $data['sid'] = $input['sid'];

        $this->smarty->assign_by_ref('input',$input);
        $this->smarty->assign_by_ref('show',$show);
        $this->smarty->assign_by_ref('data',$data);

        $data['links'] = $this->smarty->fetch($this->CONF['template'].'/edit_survey_links.tpl');
        $data['content'] = $this->smarty->fetch($this->CONF['template'].'/edit_survey_new_at.tpl');

        $retval = $this->smarty->fetch($this->CONF['template'].'/edit_survey.tpl');

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
            { $retval[] = $this->SfStr->getSafeString($file,$mode); }
        }

        if(empty($retval))
        { $retval = FALSE; }

        return $retval;
    }

    /*******************
    * EDIT ANSWER TYPE *
    *******************/
    function edit_answer($sid,$aid)
    {
        //Ensure user is logged in with valid privileges
        //for the requested survey or is an administrator
        if(!$this->_CheckLogin($sid,EDIT_PRIV,"edit_answer.php?sid=$sid"))
        { return $this->showLogin('edit_survey.php',array('sid'=>$sid)); }

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
        $input['numeric_value'] = array();
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
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
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
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
            else
            {
                while($r = $rs->FetchRow($rs))
                { $aid_list = $r['aid'] . ','; }
                $aid_list = substr($aid_list,0,-1);
                $query2 = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE aid IN ($aid_list)";
                $rs = $this->db->Execute($query2);
                if($rs === FALSE)
                { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
            }

            $query = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE aid = $aid AND sid = $sid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }

            $show['del_message'] = TRUE;
            $this->smarty->assign_by_ref('show',$show);

            return $this->edit_answer_type_choose($sid);
        }
        elseif(isset($_REQUEST['delete_submit']))
        { $show['message'] = $this->lang['must_checkbox']; }

        if(isset($_REQUEST['submit']) || isset($_REQUEST['add_answers_submit']))
        {
            if(isset($_REQUEST['add_answers_submit']))
            { $ss_type = SAFE_STRING_TEXT; }
            else
            { $ss_type = SAFE_STRING_DB; }

            $error = '';
            $load_answer = FALSE;

            if(strlen($_REQUEST['name']) > 0)
            { $input['name'] = $this->SfStr->getSafeString($_REQUEST['name'],$ss_type); }
            else
            { $error .= $this->lang['enter_name']; }

            $input['label'] = $this->SfStr->getSafeString($_REQUEST['label'],$ss_type);

            $input['aid'] = (int)$_REQUEST['aid'];

            $new_answer_count = 0;
            
            // Note: it's only meaningful for answer types with multiple answer values to be dynamic
        	$input['is_dynamic'] = isset($_REQUEST['is_dynamic']) && !empty($_REQUEST['is_dynamic']);

            switch($_REQUEST['type'])
            {
                case ANSWER_TYPE_T:
                case ANSWER_TYPE_S:
                case ANSWER_TYPE_N:
                    $input['value'] = '';
                    $input['type'] = $this->SfStr->getSafeString($_REQUEST['type'],$ss_type);
                    $input['selected'][$_REQUEST['type']] = ' selected';

                    if(isset($_REQUEST['add_answers_submit']))
                    { $error .= $this->lang['add_answer_error']; }
                    $input['show_add_answers'] = FALSE;
                    $input['num_answers'] = 0;
                    if(isset($_REQUEST['value']))
                    { $input['delete_avid'] = array_keys($_REQUEST['value']); }
                    $load_answer = TRUE;
                break;
                case ANSWER_TYPE_MM:
                case ANSWER_TYPE_MS:
                case ANSWER_TYPE_S:
                    $input['type'] = $this->SfStr->getSafeString($_REQUEST['type'],$ss_type);
                    $input['selected'][$_REQUEST['type']] = ' selected';

                    if(isset($_REQUEST['value']) && is_array($_REQUEST['value']) &&
                       isset($_REQUEST['numeric_value']) && is_array($_REQUEST['numeric_value']) &&
                       count($_REQUEST['value']) <= 99)
                    {
                        $input['num_answers'] = min(99,count($_REQUEST['value']));

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

                                $input['value'][] = $this->SfStr->getSafeString($value,$ss_type);

                                $user_image = $_REQUEST['image'][$avid];
                                $image_key = array_search($user_image,$input['allowable_images']);
                                if($image_key === FALSE)
                                { $error .= $this->lang['bad_image_sel']; }
                                else
                                {
                                    $input['image'][] = $this->SfStr->getSafeString($user_image,$ss_type);
                                    $input['image_selected'][] = array($image_key => ' selected');
                                }

                                if(empty($_REQUEST['numeric_value'][$avid]))
                                { $input['numeric_value'][] = 0; }
                                else
                                { $input['numeric_value'][] = (int)$_REQUEST['numeric_value'][$avid]; }
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
                        { $error .= $this->lang['answer_value_error']; }
                    }
                    else
                    { $error .= $this->lang['bad_answer-numeric_value']; }

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
                        $error .= $this->lang['only_99_allowed'];
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
                    $error .= $this->lang['bad_answer_type'];
                break;
            }

            if(empty($error) && !isset($_REQUEST['add_answers_submit']))
            {

                $is_dynamic = ($input['is_dynamic'])? 1 : 0;
                $query = "UPDATE {$this->CONF['db_tbl_prefix']}answer_types SET
                          name={$input['name']},type={$input['type']},label={$input['label']}
                          is_dynamic=$is_dynamic WHERE aid = $aid";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
                else
                {
                    $query = array();

                    switch($_REQUEST['type'])
                    {
                        case ANSWER_TYPE_T:
                        case ANSWER_TYPE_S:
                        case ANSWER_TYPE_N:
                            $query[] = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE aid = $aid";
                        break;

                        case ANSWER_TYPE_MS:
                        case ANSWER_TYPE_MM:

                            $sql_value = '';
                            $sql_numeric_value = '';
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
                                        $insert[] = "($avid, $aid,{$input['value'][$x]},{$input['numeric_value'][$x]},{$input['image'][$x]})";
                                    }
                                    else
                                    {
                                        $sql_value .= "WHEN avid = {$input['avid'][$x]} THEN {$input['value'][$x]} ";
                                        $sql_numeric_value .= "WHEN avid = {$input['avid'][$x]} THEN {$input['numeric_value'][$x]} ";
                                        $sql_image .= "WHEN avid = {$input['avid'][$x]} THEN {$input['image'][$x]} ";
                                        $sql_avid .= $input['avid'][$x] . ',';
                                    }
                                }
                            }

                            if(!empty($sql_avid))
                            {
                                $sql_avid = substr($sql_avid,0,-1);
                                $query[] = "UPDATE {$this->CONF['db_tbl_prefix']}answer_values SET value = CASE $sql_value END, numeric_value = CASE $sql_numeric_value END, image = CASE $sql_image END WHERE avid IN ($sql_avid)";
                            }

                            if(count($insert))
                            {
								// J.Holy: only MySQL allows multiple inserts
                            	foreach($insert as $single_insert)
								{ $query[] = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_values (avid,aid,value,numeric_value,image) VALUES " . $single_insert; } 
							}


                            if(count($input['delete_avid']))
                            { $query[] = "DELETE FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE avid IN (" . implode(',',$input['delete_avid']) . ')'; }
                        break;
                    }

                    foreach($query as $q)
                    {
                        $rs = $this->db->Execute($q);
                        if($rs === FALSE)
                        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
                    }

                    $load_answer = TRUE;
                    $show['success']=TRUE;
                }
            }

            $this->smarty->assign_by_ref('answer',$input);
        }

        //////////////////////////////////
        //			LOAD ANSWER			//
        //////////////////////////////////
        if($load_answer)
        {
            $query = "SELECT aid, name, type, label, sid, is_dynamic FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE aid = $aid";
            $rs = $this->db->Execute($query);
            if($rs === FALSE)
            { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return;}
            if($r = $rs->FetchRow($rs))
            {
                $answer = array();
                $answer = $r;
                $answer['name'] = $this->SfStr->getSafeString($answer['name'],SAFE_STRING_TEXT);
                $answer['label'] = $this->SfStr->getSafeString($answer['label'],SAFE_STRING_TEXT);
                $answer['selected'][$r['type']] = ' selected';
                $answer['allowable_images'] = $input['allowable_images'];

                $query = "SELECT avid, value, numeric_value, image FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE aid = $aid ORDER BY avid ASC";
                $rs = $this->db->Execute($query);
                if($rs === FALSE)
                { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return;}
                if($r = $rs->FetchRow($rs))
                {
                    do{
                        $answer['avid'][] = $r['avid'];
                        $answer['value'][] = $this->SfStr->getSafeString($r['value'],SAFE_STRING_TEXT);
                        $answer['numeric_value'][] = $r['numeric_value'];
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
            { $error = $this->lang['bad_answer_type']; }
        }

        if(!empty($error))
        {
            $input['name'] = $this->SfStr->getSafeString($_REQUEST['name'],SAFE_STRING_TEXT);
            $input['label'] = $this->SfStr->getSafeString($_REQUEST['label'],SAFE_STRING_TEXT);
            $count = 0;
            foreach($_REQUEST['value'] as $key=>$value)
            {
                $input['value'][$count] = $this->SfStr->getSafeString($value,SAFE_STRING_TEXT);
                $input['numeric_value'][$count] = $this->SfStr->getSafeString($_REQUEST['numeric_value'][$key],SAFE_STRING_TEXT);
                $input['image_selected'][$count][$_REQUEST['image'][$key]] = ' selected';
                $count++;
            }
            $show['error'] = $error;
        }

        $data['sid'] = $input['sid'];
        $this->smarty->assign_by_ref('data',$data);
        $this->smarty->assign_by_ref('show',$show);
        $data['links'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_links.tpl');



        $data['content'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_edit_at.tpl');

        $retval = $this->smarty->Fetch($this->CONF['template'].'/edit_survey.tpl');

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
        { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); }
        while($r = $rs->FetchRow())
        {
            $answer['aid'][] = $r['aid'];
            $answer['name'][] = $this->SfStr->getSafeString($r['name'],SAFE_STRING_TEXT);
        }
        $data['sid'] = $answer['sid'];

        $this->smarty->assign('answer',$answer);
        $this->smarty->assign_by_ref('data',$data);

        $data['links'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_links.tpl');

        $data['content'] = $this->smarty->Fetch($this->CONF['template'].'/edit_survey_edit_atc.tpl');

        $this->smarty->assign_by_ref('show',$show);

        $retval = $this->smarty->Fetch($this->CONF['template'].'/edit_survey.tpl');

        return $retval;
    }
	
	
    /***************************
    * INSERT NEW ANSWER TYPE INTO DATABASE
    * Stores the new answer types and its answer values into a database.
    * 
    * @param array $input An associative array representing the input form with
    * answer type/values data.
    * @return True upon success
    * 
    * @access private
    * @author Jakub Holy
    * ***************************/
    function db_insert_answer_type($input) 
    {
        $is_dynamic = ($input['is_dynamic'])? 1 : 0;
        $aid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_types_sequence');
        $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_types (aid, name, type, label, sid, is_dynamic) VALUES"
                  ."($aid, {$input['name']},{$input['type']},{$input['label']},{$input['sid']}, $is_dynamic)";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { 
        	$this->error($this->lang['db_query_error'] . $this->db->ErrorMsg());
        	return false; 
        }
        else		// answer type successfully inserted into the DB
        {
            // Save all answer values of the new answer type if any exist:
            if($c = count($input['value']))
            {
                $inserted_answers_ids = array();
                for($x=0;$x<$c;$x++)	// for all answer values
                {
                    $avid = $this->db->GenID($this->CONF['db_tbl_prefix'].'answer_values_sequence');
                    $sql = "($avid,$aid,{$input['value'][$x]},{$input['numeric_value'][$x]},{$input['image'][$x]})";
                    $query = "INSERT INTO {$this->CONF['db_tbl_prefix']}answer_values (avid, aid, value, numeric_value, image) VALUES " . $sql;
                    // Insert the answer value into the DB...
                    $rs = $this->db->Execute($query);
					
					// Check the result: has the insertion succeded?
                    if($rs === FALSE)
                    {
                        $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg());
                        $this->db->Execute("DELETE FROM {$this->CONF['db_tbl_prefix']}answer_types WHERE aid = $aid");
                        // Delete the answer values already successfully inserted, if any
                        foreach($inserted_answers_ids as $inserted_avid) 
                        { $this->db->Execute("DELETE FROM {$this->CONF['db_tbl_prefix']}answer_values WHERE avid = $inserted_avid"); }
                        return false;
                    } 
                    else	// Store the id of the successfully inserted value for deletion on error 
                    { $inserted_answers_ids[] = $avid; }
                } // for all answer values
                
            } // if any answer values exist
            return true;
        } // if-else answer the type inserted with success
    } // db_insert_answer_type

}

?>
