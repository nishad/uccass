<?php

include('survey.class.php');

class Special_Results extends survey
{
    function results_table($sid)
    {
        $sid = (int)$sid;
        $data = array();
        $qid = array();
        $survey = array();

        $survey['sid'] = $sid;

        $query = "SELECT q.qid, q.question, s.name, s.user_text_mode, s.survey_text_mode, s.results_access, s.date_format
                  FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}surveys s
                  WHERE q.sid = $sid and s.sid = q.sid ORDER BY q.page, q.oid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('Error in query: ' . $this->db->ErrorMsg()); return; }

        $questions = array();
        if($r = $rs->FetchRow($rs))
        {
            if($r['results_access'] != 'public' && !isset($_SESSION['result_access'][$sid]) && !isset($_SESSION['admin_logged_in']))
            { $this->error("Results for this survey require a password"); return; }

            $survey_text_mode = $r['survey_text_mode'];
            $user_text_mode = $r['user_text_mode'];
            $date_format = $r['date_format'];
            $survey['name'] = $r['name'];

            do{
                $data['questions'][] = $this->safe_string($r['question'],$survey_text_mode);
                $qid[$r['qid']] = $r['qid'];
            }while($r = $rs->FetchRow($rs));
        }
        else
        { $this->error('No questions for this survey.'); return; }

        if(isset($_SESSION['filter_text'][$sid]) && isset($_SESSION['filter'][$sid]) && strlen($_SESSION['filter_text'][$sid])>0)
        { $this->smarty->assign_by_ref('filter_text',$_SESSION['filter_text'][$sid]); }
        else
        { $_SESSION['filter'][$sid] = ''; }

        $query = "select if(r.qid is null, rt.qid, r.qid) as qid, if(r.sequence is null, rt.sequence, r.sequence) as seq,
                  if(r.entered is null,rt.entered,r.entered) as entered,
                  q.question, av.value, rt.answer from {$this->CONF['db_tbl_prefix']}questions q left join {$this->CONF['db_tbl_prefix']}results
                  r on q.qid = r.qid left join {$this->CONF['db_tbl_prefix']}results_text rt on q.qid = rt.qid left join
                  {$this->CONF['db_tbl_prefix']}answer_values av on r.avid = av.avid where q.sid = $sid {$_SESSION['filter'][$sid]} order by seq, q.page, q.oid";

        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('Error in query: ' . $this->db->ErrorMsg()); return; }

        $seq = '';
        $x = -1;
        while($r = $rs->FetchRow($rs))
        {
            if(!empty($r['qid']))
            {
                if($seq != $r['seq'])
                {
                    $x++;
                    $seq = $r['seq'];
                    $answers[$x]['date'] = date($date_format,$r['entered']);
                }
                if(isset($answers[$x][$r['qid']]))
                { $answers[$x][$r['qid']] .= ' -- ' . $this->safe_string($r['value'] . $r['answer'],$user_text_mode); }
                else
                { $answers[$x][$r['qid']] = $this->safe_string($r['value'] . $r['answer'],$user_text_mode); }
            }
            $last_date = date($date_format,$r['entered']);
        }
        $answers[$x]['date'] = $last_date;

        $xvals = array_keys($answers);

        foreach($xvals as $x)
        {
            foreach($qid as $qid_value)
            {
                if(isset($answers[$x][$qid_value]))
                { $data['answers'][$x][] = $answers[$x][$qid_value]; }
                else
                { $data['answers'][$x][] = '&nbsp;'; }
            }
            $data['answers'][$x][] = $answers[$x]['date'];
        }

        $this->smarty->assign_by_ref('data',$data);
        $this->smarty->assign_by_ref('survey',$survey);
        return $this->smarty->fetch($this->template.'/results_table.tpl');
    }

    function results_csv($sid)
    {
        //Increase time limit of script to 2 minutes to ensure
        //very large results can be exported to a file
        set_time_limit(120);

        $sid = (int)$sid;
        $retval = '';

        $query = "SELECT q.qid, q.question, s.results_access, s.date_format
                  FROM {$this->CONF['db_tbl_prefix']}questions q, {$this->CONF['db_tbl_prefix']}surveys s
                  WHERE q.sid = $sid and s.sid = q.sid ORDER BY q.page, q.oid";
        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('Error in query: ' . $this->db->ErrorMsg()); return; }

        $questions = array();
        if($r = $rs->FetchRow($rs))
        {
            $date_format = $r['date_format'];
            if($r['results_access'] != 'public' && !isset($_SESSION['result_access'][$sid]) && !isset($_SESSION['admin_logged_in']))
            { $this->error("Results for this survey require a password"); return; }

            do{
                $questions[$r['qid']] = $r['question'];
            }while($r = $rs->FetchRow($rs));
        }
        else
        { $this->error('No questions for this survey'); return; }

        if(isset($_SESSION['filter_text'][$sid]) && isset($_SESSION['filter'][$sid]) && strlen($_SESSION['filter_text'][$sid])>0)
        { $this->smarty->assign_by_ref('filter_text',$_SESSION['filter_text'][$sid]); }
        else
        { $_SESSION['filter'][$sid] = ''; }


        $query = "select if(r.qid is null, rt.qid, r.qid) as qid, if(r.sequence is null, rt.sequence, r.sequence) as seq,
                  if(r.entered is null, rt.entered, r.entered) as entered,
                  q.question, av.value, rt.answer from {$this->CONF['db_tbl_prefix']}questions q left join {$this->CONF['db_tbl_prefix']}results
                  r on q.qid = r.qid left join {$this->CONF['db_tbl_prefix']}results_text rt on q.qid = rt.qid left join
                  {$this->CONF['db_tbl_prefix']}answer_values av on r.avid = av.avid where q.sid = $sid {$_SESSION['filter'][$sid]} order by seq, q.page, q.oid";

        $rs = $this->db->Execute($query);
        if($rs === FALSE)
        { $this->error('Error in query: ' . $this->db->ErrorMsg()); return; }

        $seq = '';
        $x = 0;
        while($r = $rs->FetchRow($rs))
        {
            if(!empty($r['qid']))
            {
                if($seq != $r['seq'])
                {
                    $x++;
                    $seq = $r['seq'];
                    $answers[$x]['date'] = date($date_format,$r['entered']);
                }
                if(isset($answers[$x][$r['qid']]))
                { $answers[$x][$r['qid']] .= ' -- ' . $r['value'] . $r['answer']; }
                else
                { $answers[$x][$r['qid']] = $r['value'] . $r['answer']; }
            }
            $last_date = date($date_format,$r['entered']);
        }
        $answers[$x]['date'] = $last_date;

        $line = '';
        foreach($questions as $question)
        { $line .= "\"" . str_replace('"','""',$question) . "\","; }
        $retval .= $line . "Datetime\n";
        //$retval = substr($line,0,-1) . "\n";

        $xvals = array_keys($answers);

        foreach($xvals as $x)
        {
            $line = '';
            foreach($questions as $qid=>$question)
            {
                if(isset($answers[$x][$qid]))
                {
                    if(is_numeric($answers[$x][$qid]))
                    { $line .= "{$answers[$x][$qid]},"; }
                    else
                    { $line .= "\"" . str_replace('"','""',$answers[$x][$qid]) . "\","; }
                }
                else
                { $line .= ","; }
            }
            $retval .= $line . '"' . $answers[$x]['date'] . "\"\n";
        }

        return $retval;
    }
}

?>