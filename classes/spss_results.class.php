<?php

//======================================================
// Copyright (C) 2006 Claudio Redaelli, All Rights Reserved
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

define('ORDERID', 1);
define('SEQUENCEID', 2);
define('OK', 0x01);
define('ERROR', 0x10);

define('SPSS_SEP', ',');
define('SPSS_DEL', '"');
define('SPSS_ALTDEL', '\'');
define('SPSS_TEXT', 1);
define('SPSS_ALTCR', '<BR />');
define('SPSS_ORDER', 1);

class UCCASS_SPSS_Results extends UCCASS_Special_Results {

	var $answersTable = array();	// Answers numeric values two dimensional matrix
    var $answersTypesArray = array();
    var $answersDatesArray = array();
    var $dataTable = array();
    var $headersCounter = 0;
    var $headersArray = array();
    var $output = "";
    var $parameters = array();
    var $prefix = 'q_';
    var $resTable;
    var $sid = 0;
    var $status = OK;
    var $survey = array();

	function UCCASS_SPSS_Results($sid=0, $sep=SPSS_SEP, $del=SPSS_DEL, $altdel=SPSS_ALTDEL, $text=SPSS_TEXT, $altcr=SPSS_ALTCR, $order=SPSS_ORDER ) {

        $this->load_configuration();

        //Increase time limit of script to 2 minutes to ensure
        //very large results can be shown or exported
        @set_time_limit(120);	// suppress warning because if disabled set_time_limit in the safe mode

		/* Controls that survey id is legal */
		$this->sid = (int)$sid;

        if(!$this->_CheckAccess($this->sid,RESULTS_PRIV,'results.php?sid='.$this->sid)) {
            switch($this->_getAccessControl($this->sid)) {
                case AC_INVITATION: return $this->showInvite('results.php',array('sid'=>$this->sid)); break;
                case AC_USERNAMEPASSWORD:
                default: return $this->showLogin('results.php',array('sid'=>$this->sid)); break;
            }
        }

        if($this->sid <= 0) {
        	$this->status = ERROR;
        	$this->error($this->lang['invalid_survey']);
        	return;
        }

        //Retrieve survey information
        $SQLsurveyInfo =	"SELECT name, survey_text_mode, date_format FROM {$this->CONF['db_tbl_prefix']}surveys WHERE sid = {$this->sid}";

        $rs = $this->db->Execute($SQLsurveyInfo);
        if($rs === FALSE) { $this->error($this->lang['db_query_error'] . $this->db->ErrorMsg()); return; }
        if($r = $rs->FetchRow($rs))
        {
            $this->survey['name'] = $this->SfStr->getSafeString($r['name'],$r['survey_text_mode']);
            $this->survey['sid'] = $this->sid;
            $this->survey['survey_text_mode'] = $r['survey_text_mode'];
            $this->survey['date_format'] = $r['date_format'];
        }

        if(isset($_GET['sep'])) {
            //Save form values in cookies and load parameter array for this object
            $this->saveParameterCookies($sep, $del, $altdel, $text, $altcr, $order);
        } elseif(isset($_COOKIE['spss_parameters'])) {
            //Retrieve parameters from stored cookies
            $this->parameters = $this->getParameterCookieValues();
        } else {
            $this->parameters['separator'] = $sep;
            $this->parameters['delimiter'] = $del;
            $this->parameters['altdelimiter'] = $altdel;
            $this->parameters['export_text'] = (int)$text;
            $this->parameters['text_checked'][$text] = FORM_CHECKED;
            $this->parameters['altnewline'] = $altcr;
            $this->parameters['order'] = (int)$order;
            $this->parameters['order_checked'][$order] = FORM_CHECKED;
        }
	}

    function saveParameterCookies($sep, $del, $altdel, $text, $altcr, $order) {
        //Save SPSS export parameters in cookies good for 7 days
        setcookie('spss_parameters', 1, time() + 604800);
        setcookie('spss_sep', $sep, time() + 604800);
        setcookie('spss_del', $del, time() + 604800);
        setcookie('spss_altdel', $altdel, time() + 604800);
        setcookie('spss_text', $text, time() + 604800);
        setcookie('spss_altcr', $altcr, time() + 604800);
        setcookie('spss_order', $order, time() + 604800);

        //Load parameter array with values passed in form
        $this->parameters['separator'] = $this->SfStr->getSafeString($sep, SAFE_STRING_NOMAGIC);
        $this->parameters['delimiter'] = $this->SfStr->getSafeString($del, SAFE_STRING_NOMAGIC);
        $this->parameters['altdelimiter'] = $this->SfStr->getSafeString($altdel, SAFE_STRING_NOMAGIC);
        $this->parameters['export_text'] = (int)$text;
        $this->parameters['text_checked'][$text] = FORM_CHECKED;
        $this->parameters['altnewline'] = $this->SfStr->getSafeString($altcr, SAFE_STRING_NOMAGIC);
        $this->parameters['order'] = (int)$order;
        $this->parameters['order_checked'][$order] = FORM_CHECKED;
    }

    function getParameterCookieValues() {
        $parameters = array();

        $parameters['separator'] = $this->SfStr->getSafeString(@$_COOKIE['spss_sep'], SAFE_STRING_NOMAGIC);
        $parameters['delimiter'] = $this->SfStr->getSafeString(@$_COOKIE['spss_del'], SAFE_STRING_NOMAGIC);
        $parameters['altdelimiter'] = $this->SfStr->getSafeString(@$_COOKIE['spss_altdel'], SAFE_STRING_NOMAGIC);
        $parameters['export_text'] = (int)@$_COOKIE['spss_text'];
        $parameters['text_checked'][$parameters['export_text']] = FORM_CHECKED;
        $parameters['altnewline'] = $this->SfStr->getSafeString(@$_COOKIE['spss_altcr'], SAFE_STRING_NOMAGIC);
        $parameters['order'] = (int)@$_COOKIE['spss_order'];
        $parameters['order_checked'][$parameters['order']] = FORM_CHECKED;

        return $parameters;
    }

	function getData() {
		$this->initializeMetaData();
		$this->loadData();
		$this->manageData();
		$this->writeData();
		$format = str_replace(':', '-', $this->survey['date_format']);
		$format = str_replace(' ', '_', $format);
		$fileName = 'UCCASS_' . $this->sid . '_SPSS_' . date($format) . '.csv' ;
		header('Content-Type: text/plain; charset={' . $this->CONF['charset'] . '}');
		header('Content-Disposition: attachment; filename=' . $fileName);
		return($this->output);
	}

	function initializeMetaData() {
		$SQLgetIDs = 	'SELECT q.qid, at.type, count(*) as possible_answers, q.oid
						FROM ' . $this->CONF['db_tbl_prefix'] . 'questions AS q
						LEFT JOIN ' . $this->CONF['db_tbl_prefix'] . 'answer_types AS at ON q.aid = at.aid
						LEFT JOIN ' . $this->CONF['db_tbl_prefix'] . 'answer_values AS av ON at.aid = av.aid
						WHERE at.sid = ' . $this->sid . '
						GROUP BY q.qid';
		switch ($this->parameters['order']) {
			case ORDERID:
                $SQLgetIDs .= ' ORDER BY q.page ASC, q.oid ASC';
            break;

            case SEQUENCEID:
            default:
                $SQLgetIDs .= ' ORDER BY q.qid ASC';
            break;
		}

		$RSgetIDs = $this->db->Execute($SQLgetIDs);
        if($RSgetIDs === FALSE) {
			$this->error($this->lang['db_query_error'] . $this->db->ErrorMsg());
			return;
        }

		while($r = $RSgetIDs->FetchRow($RSgetIDs)) {
        	// If answer type is "none" I skip it
        	if ($r['type']==ANSWER_TYPE_N)
        		continue;
        	//$this->answersTypesArray[$r['qid']] = $r['type'];
			if ( $r['type']==ANSWER_TYPE_MM ) {
				for ($i=1; $i <= $r['possible_answers']; $i++) {
					$this->headersCounter++;
					$index = $r['qid'] . '_' . $i;
					$this->headersArray[$this->headersCounter] = $index;
                    $this->answersTypesArray[$index] = $r['type'];
					$this->output .= $this->prefix . $index . ($i==$r['possible_answers'] ? '' : $this->parameters['separator']);
				}
				$this->output .= $this->parameters['separator'];
			}
			else {
				// Output text fields only if requested
				if ( $r['type']!=ANSWER_TYPE_T || $this->parameters['export_text'] == 1 ) {
					$this->headersCounter++;
					$this->headersArray[$this->headersCounter] = $r['qid'];
                    $this->answersTypesArray[$r['qid']] = $r['type'];
					$this->output .= $this->prefix . $r['qid'];
					$this->output .= $this->parameters['separator'];
				}
			}
        }

		$this->output .= 'Datetime' . CR . NL;
	}

	// Loads data from db into a table for fields direct access
	function loadData() {
		$SQLgetAllAnswers = 'SELECT GREATEST(rt.qid, r.qid) AS qid,
							GREATEST(rt.sequence, r.sequence) AS seq,
                 			av.numeric_value, rt.answer
							FROM ' . $this->CONF['db_tbl_prefix'] . 'questions q
							LEFT JOIN ' . $this->CONF['db_tbl_prefix'] . 'results r ON q.qid = r.qid
							LEFT JOIN ' . $this->CONF['db_tbl_prefix'] . 'results_text rt ON q.qid = rt.qid
							LEFT JOIN ' . $this->CONF['db_tbl_prefix'] . 'answer_values av ON r.avid = av.avid
							WHERE q.sid = ' . $this->sid;

		switch ($this->parameters['order']) {
			case ORDERID:
                $SQLgetAllAnswers .= ' ORDER BY seq ASC, q.page ASC, q.oid ASC';
            break;

            case SEQUENCEID:
            default:
                $SQLgetAllAnswers .= ' ORDER BY seq ASC, qid ASC';
            break;
		}

		$RSgetAllAnswers = $this->db->Execute($SQLgetAllAnswers);
		if($RSgetAllAnswers === FALSE) {
			$this->error($this->lang['db_query_error'] . $this->db->ErrorMsg());
			return;
        }
		// I load all results from db in a table indexed by fields labels
		while($r = $RSgetAllAnswers->FetchRow($RSgetAllAnswers)) {
			if ( !empty($r['qid']) )
        		$this->dataTable[] = array( 'qid'=>$r['qid'], 'seq'=>$r['seq'], 'numeric_value'=>$r['numeric_value'], 'answer'=>$r['answer'] );
        }

        // I retrive only answers insertion dates
        $SQLgetAnswersDates = 	'SELECT GREATEST(rt.qid, r.qid) AS qid, GREATEST(rt.sequence, r.sequence) AS seq, GREATEST(rt.entered, r.entered) AS entered
							FROM ' . $this->CONF['db_tbl_prefix'] . 'questions q
							LEFT JOIN ' . $this->CONF['db_tbl_prefix'] . 'results r ON q.qid = r.qid
							LEFT JOIN ' . $this->CONF['db_tbl_prefix'] . 'results_text rt ON q.qid = rt.qid
							LEFT JOIN ' . $this->CONF['db_tbl_prefix'] . 'answer_values av ON r.avid = av.avid
							WHERE q.sid = ' . $this->sid . '
							GROUP BY entered
							ORDER BY seq ASC, qid ASC';

        $RSgetAnswersDates = $this->db->Execute($SQLgetAnswersDates);
		if($RSgetAnswersDates === FALSE) {
			$this->error($this->lang['db_query_error'] . $this->db->ErrorMsg());
			return;
        }
        $i=0;
		while($r = $RSgetAnswersDates->FetchRow($RSgetAnswersDates)) {
        	$this->answersDatesArray[$i] = $r['entered'];
        	$i++;
        }
	}

	// Moves data into a table which structure is the same of output file
	function manageData() {
		$seq = -1;
		for ( $i=0; $i < count($this->dataTable); $i++ ) {
			// Initialize a new row every new sequence number
			if ( $this->dataTable[$i]['seq'] > $seq ) {
				$seq = $this->dataTable[$i]['seq'];
				$this->answersTable[$seq] = array();
				// Fields initialization with default values
				foreach ( $this->headersArray as $key=>$value ) {
					switch ( $this->answersTypesArray[$value] ) {
						// Non multiple
						case ANSWER_TYPE_T:
                            $this->answersTable[$seq][$value] = '';
                        break;

                        case ANSWER_TYPE_S:
                            $this->answersTable[$seq][$value] = '';
                        break;

                        case ANSWER_TYPE_MS:
                            $this->answersTable[$seq][$value] = 0;
                        break;

                        //Multiple
                        case ANSWER_TYPE_MM:
			       		default:
                            $this->answersTable[$seq][$value] = 0;
                        break;
					}
				}
			}
			// Puts values into answers table
			$qid = $this->dataTable[$i]['qid'];

            $switchType = '';

            if(isset($this->answersTypesArray[$qid])) {
                $switchType = $this->answersTypesArray[$qid];
            } else {
                $index = $qid . '_' . $this->dataTable[$i]['numeric_value'];
                if(isset($this->answersTypesArray[$index])) {
                    $switchType = $this->answersTypesArray[$index];
                }
            }
			switch ( $switchType ) {
				case ANSWER_TYPE_T:
                    $this->answersTable[$seq][$qid] = $this->dataTable[$i]['answer'];
                break;

        		case ANSWER_TYPE_S:
                    $this->answersTable[$seq][$qid] = $this->dataTable[$i]['answer'];
                break;

	       		case ANSWER_TYPE_MS:
                    $this->answersTable[$seq][$qid] = $this->dataTable[$i]['numeric_value'];
                break;

	   		    case ANSWER_TYPE_MM:
                    $index = $qid . '_' . $this->dataTable[$i]['numeric_value'];
                    $this->answersTable[$seq][$index] = 1;
	   		    break;
			}
		}
	}

	function requestParameters() {
		if ($this->status === OK) {

            $this->parameters['separator'] = $this->SfStr->getSafeString($this->parameters['separator'], SAFE_STRING_TEXT);
            $this->parameters['delimiter'] = $this->SfStr->getSafeString($this->parameters['delimiter'], SAFE_STRING_TEXT);
            $this->parameters['altdelimiter'] = $this->SfStr->getSafeString($this->parameters['altdelimiter'], SAFE_STRING_TEXT);
            $this->parameters['altnewline'] = $this->SfStr->getSafeString($this->parameters['altnewline'], SAFE_STRING_TEXT);

	        $this->smarty->assign('export_type',EXPORT_SPSS);
			$this->smarty->assign_by_ref('survey',$this->survey);
			$this->smarty->assign_by_ref('parameters',$this->parameters);
			$this->output = $this->smarty->fetch($this->CONF['template'].'/spss_parameters.tpl');
		}
		return($this->output);
	}

	// Dumps data from table to output
	function writeData() {
		// Write out pre-formatted resuls table
		// Starting from 1 because element 0 is always empty (why?)
		$i=1;
		foreach( $this->answersTable as $seq => $row ) {
        	foreach ( $row as $key => $value ) {
        		if ( $this->answersTypesArray[$key] == ANSWER_TYPE_T || $this->answersTypesArray[$key] == ANSWER_TYPE_S ) {
        			if ( $this->parameters['export_text'] == 1 ) {
	        			// Text types (T=textarea, S=textbox)
	        			$temp_str = str_replace(CR . NL, $this->parameters['altnewline'], $value);
	        			$temp_str = str_replace($this->parameters['delimiter'], $this->parameters['altdelimiter'], $temp_str);
						$this->output .= $this->parameters['delimiter'] . $temp_str . $this->parameters['delimiter'] . $this->parameters['separator'] ;
        			}
        		}
				else
					// Numeric types
					$this->output .= $value . $this->parameters['separator'] ;
        	}

			$this->output .= $this->parameters['delimiter']. date($this->survey['date_format'], $this->answersDatesArray[$i]) . $this->parameters['delimiter'] . CR . NL;
			$i++;
        }
	}
}

?>