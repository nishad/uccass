<?php

//Upgrading from 1.04 to 1.05 requires the Welcome and Thank You
//text to be moved into the survey and the addition of a database
//column

$upgrade_104_105 = FALSE;

//Get welcome and thank you text for each survey
$survey_result = $survey->Query("SELECT sid, welcome_text, thank_you_text FROM {$survey->CONF['db_tbl_prefix']}surveys");
if($survey_result !== FALSE)
{
    while($survey_data = $survey_result->FetchRow($survey_result))
    {
        $upgrade_104_105 = FALSE;
        $sid = $survey_data['sid'];

        //Verify survey has 'N' answer type created. If not, create one
        //Capture aid of 'N' answer type
        $aid_result = $survey->Query("SELECT aid FROM {$survey->CONF['db_tbl_prefix']}answer_types WHERE type='N' AND sid = $sid");
        if($aid_result !== FALSE)
        {
            $aid = FALSE;
            if($aid_data = $aid_result->FetchRow($aid_result))
            { $aid = $aid_data['aid']; }
            else
            {
                $query = "INSERT INTO {$survey->CONF['db_tbl_prefix']}answer_types (aid, name, type, label, sid) VALUES (NULL,'No Answer (Label)','N','',$sid)";
                $rs3 = $survey->Query($query);
                if($rs3 === FALSE)
                { echo 'Unable to insert "N" answer type: ' . $survey->db->ErrorMsg(); }
                $aid = $survey->db->Insert_ID();
            }

            if($aid)
            {

                //Increment current page count to make room for welcome text on page 1
                $pageinc_result = $survey->Query("UPDATE {$survey->CONF['db_tbl_prefix']}questions SET page = page + 1 WHERE sid = $sid");
                if($pageinc_result !== FALSE)
                {

                    //Escape welcome and thank you text
                    $welcome_text = $survey->safe_string($survey_data['welcome_text'],SAFE_STRING_ESC);
                    $thank_you_text = $survey->safe_string($survey_data['thank_you_text'],SAFE_STRING_ESC);

                    //Insert welcome text into beginning of survey
                    $welcome_result = $survey->Query("INSERT INTO {$survey->CONF['db_tbl_prefix']}questions (question,aid,sid,page,num_answers,num_required,oid,orientation)
                                                    VALUES ('$welcome_text',$aid,$sid,1,1,0,1,'Vertical')");
                    if($welcome_result !== FALSE)
                    {

                        //Determine the maximum page currently in the survey
                        //since the thank you text will go on a new last page
                        $maxpage_result = $survey->Query("SELECT MAX(page) AS maxpage FROM {$survey->CONF['db_tbl_prefix']}questions WHERE sid = $sid");
                        $maxpage = $maxpage_result->FetchRow($maxpage_result);
                        $page = $maxpage['maxpage'] + 1;

                        if($page)
                        {
                            //Insert thank_you text to last page of survey
                            $thankyou_result = $survey->Query("INSERT INTO {$survey->CONF['db_tbl_prefix']}questions (question, aid, sid, page, num_answers, num_required, oid, orientation)
                                                             VALUES ('$thank_you_text',$aid,$sid,$page,1,0,1,'Vertical')");
                            if($thankyou_result !== FALSE)
                            { $upgrade_104_105 = TRUE; }
                            else
                            { echo 'Unable to insert Thank You text: ' . $survey->db->ErrorMsg(); }
                        }
                    }
                    else
                    { echo 'Unable to insert welcome text: ' . $survey->db->ErrorMsg(); }
                }
                else
                { echo 'Unable to increment page count to make room for welcome message: ' . $survey->db->ErrorMsg(); }
            }
        }
    }
}

//////////////////////////////////////////////
// RUN html_entity_decode() on everything?? //
//////////////////////////////////////////////

if($upgrade_104_105)
{
    echo "Database updated to version 1.05<br />
          <br />
          If you are using your own templates, the following template names have changed. Please change the names
          of the following files to match the new name. If you are using the templates that came with v1.05, then
          you will not need to change any filenames.<br />
          <br />
          Old Name => New Name
          <br />
          edit_survey_edit_answer_type_choose.tpl => edit_survey_edit_atc.tpl<br />
          take_survey_question_MatrixFooter.tpl => take_survey_question_MF.tpl<br />
          take_survey_question_MatrixHeader.tpl => take_survey_question_MH.tpl<br />
          take_survey_question_MM_Dropdown.tpl => take_survey_question_MM_D.tpl<br />
          take_survey_question_MM_Horizontal.tpl => take_survey_question_MM_H.tpl<br />
          take_survey_question_MM_Matrix.tpl => take_survey_question_MM_M.tpl<br />
          take_survey_question_MM_Vertical.tpl => take_survey_question_MM_V.tpl<br />
          take_survey_question_MS_Dropdown.tpl => take_survey_question_MS_D.tpl<br />
          take_survey_question_MS_Horizontal.tpl => take_survey_question_MS_H.tpl<br />
          take_survey_question_MS_Matrix.tpl => take_survey_question_MS_M.tpl<br />
          take_survey_question_MS_Vertical.tpl => take_survey_question_MS_V.tpl<br />
          edit_survey_edit_answer_type.tpl => edit_survey_edit_at.tpl<br />
          edit_survey_edit_answer_type_choose.tpl => edit_survey_edit_atc.tpl<br />
          edit_survey_new_answer_type.tpl => edit_survey_new_at.tpl<br />
          <br />
          These name changes were required to eliminate problems with some operating systems not accepting
          filenames over 30 characters. ";
}

?>