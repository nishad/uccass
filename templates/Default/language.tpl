<?php

$lang = array();

$lang['error'] = 'Error';
$lang['notice'] = 'Notice';

//Orientation Modes
$lang['vertical'] = 'Vertical';
$lang['horizontal'] = 'Horizontal';
$lang['dropdown'] = 'Dropdown';
$lang['matrix'] = 'Matrix';

//Text Modes
$lang['text_only'] = 'Text Only';
$lang['limited_html'] = 'Limited HTML';
$lang['full_html'] = 'Full HTML';

//Dependency Modes
$lang['hide'] = 'Hide';
$lang['require'] = 'Require';
$lang['show'] = 'Show';

//Database Messages
$lang['db_query_error'] = 'Error executing datbase query: ';
$lang['db_table_error'] = 'Error deleting data from table: ';

//User/Invitee Errors
$lang['wrong_login_info'] = 'Incorrect Username and/or Password';
$lang['wrong_invite_code'] = 'Incorrect invitation code.';

//Answer Types Information/Warnings/Errors
$lang['enter_name'] = ' Please enter a name.';
$lang['add_answer_error'] = ' Cannot add answers to types T, S, or N.';
$lang['bad_image'] = ' Invalid image name.';
$lang['bad_image_sel'] = 'Invalid image selection.';
$lang['answer_value_error'] = ' Answer values must be provided.';
$lang['bad_answer-numeric_value'] = ' Bad display answer value or numeric value was entered.';
$lang['only_99_allowed'] = ' Only 99 answers are allowed.';
$lang['bad_answer_type'] = 'Incorrect Answer Type';
$lang['must_checkbox'] = 'Checkbox must be selected in order to delete answer.';

//Page Titles
$lang['title_new_answer_type'] = 'New Answer Type';
$lang['title_survey_results'] = 'Survey Results';
$lang['title_take_survey'] = 'Take Survey';

//Edit Survey Information
$lang['survey_deleted'] = 'Survey has been deleted.';
$lang['error_deleting'] = 'Delete Survey Error';
$lang['delete_page_break'] = 'Page breaks successfully deleted';
$lang['error_del_page_break'] = 'Cannot delete page break because of questions on next page
                                 having dependencies on questions from previous page.';
$lang['question_deleted'] = 'Question and answers successfully deleted.';
$lang['answers_cleared'] = 'All answers cleared from survey';
$lang['properties_updated'] = 'Survey properties updated';
$lang['name_required'] = 'Survey name is required.';
$lang['invalid_template'] = 'Invalid template selection.';
$lang['invalid_start_date'] = 'Invalid start date. Please ensure the date is in the correct format shown.';
$lang['invalid_end_date'] = 'Invalid end date. Please ensure the date is in the correct format shown.';
$lang['end_before_start'] = 'End date can not be before start date.';
$lang['cannot_activate'] = 'Cannot activate a survey with no questions.';
$lang['invalid_survey_text_mode'] = 'Invalid survey text mode selected. ';
$lang['invalid_user_text_mode'] = 'Invalid user text mode selected. ';
$lang['invalid_complete_page'] = 'Invalid completion redirect page. ';
$lang['invalid_custom_redirect'] = 'You must supply a redirect page when choosing "Custom" for completion redirect page';
$lang['question_edited'] = 'Question successfully edited.';
$lang['empty_question'] = 'Please provide the text for the question.';
$lang['no_choose_question'] = 'No question was chosen to edit.';
$lang['no_answer_type'] = 'Please choose an answer type for the question.';
$lang['to_many_required'] = 'Number of required answers cannot exceed the number of answers';
$lang['choose_dep_type'] = 'Please choose a valid dependency option (hide, show, etc)';
$lang['choose_dep_question'] = 'Please choose a question to add a dependency to.';
$lang['choose_dep_question2'] = 'Please choose a question to base the new dependency on.';
$lang['dep_order_error'] = 'Dependencies can only be based on questions displayed BEFORE the question being added';
$lang['survey_not_exist'] = 'Requested survey does not exist.';
$lang['invalid_move'] = 'Invalid question chosen to move.';
$lang['question_moved'] = 'Question successfully moved.';
$lang['move_question_dep'] = 'Cannot move question up because of dependencies on questions on previous page.';
$lang['move_question_dep2'] = 'Cannot move requested question down because questions on next page have dependencies on requested question. ';
$lang['move_question_begin'] = 'Cannot move question; question already at beginning of survey';
$lang['move_question_end'] = 'Cannot move question; question already at end of survey';
$lang['page_break_first'] = 'Cannot insert PAGE BREAK as first question. Please use the drop down to
                             select what question to insert the page break after.';
$lang['page_break_inserted'] = 'PAGE BREAK inserted successfully.';
$lang['page_break_end'] = 'Cannot insert PAGE BREAK as last question.';
$lang['question_added'] = 'Question successfully added to survey.';

//Reports
$lang['new_report'] = 'New Report created successfully.';
$lang['report_invalid_id'] = 'Invalid report ID';
$lang['no_report_name'] = 'Please give a name for the report.';
$lang['report_no_layout'] = 'Please choose a layout type for the report.';
$lang['report_no_display'] = 'You must choose at least one display value for the report';
$lang['report_no_questions'] = 'Please choose at least one question to add to the report';
$lang['report_no_crosstab'] = 'Please choose at least one crosstab question when layout type is set to "crosstab"';
$lang['report_name_used'] = 'Report name already in use for this survey. Please choose another';
$lang['report_questions_added'] = 'Question(s) successfully added to report.';
$lang['report_name_saved'] = 'Report successfully named and saved.';
$lang['report_question_deleted'] = 'Question deleted from report.';
$lang['report_invalid_delete'] = 'Invalid question chosen for deletion.';

//Results
$lang['invalid_survey'] = 'Invalid survey chosen.';
$lang['invalid_text_question'] = 'Selected question does not exist within survey or is not the correct type (Text or Sentence)';
$lang['filter_answer_seperator'] = ', ';
$lang['filter_seperator'] = ' => ';
$lang['filter_start_date'] = 'Start Date: ';
$lang['filter_end_date'] = 'End Date: ';
$lang['filter_limit'] = '<span class="error">Number of completed surveys matching filter is below the Filter Limit set in the configuration. Showing all results.</span>';
$lang['filter_no_match'] = '<span class="error">Filter criteria did not match any records. Showing all results.</span>';
$lang['no_questions'] = 'No questions for this survey.';
$lang['csv_filename'] = 'Export.csv';
$lang['datetime'] = 'Datetime';

//New Survey
$lang['survey_name_used'] = 'A survey already exists with that name.';
$lang['invalid_copy_survey'] = 'Invalid survey chosen to copy from.';
$lang['invalid_new_username'] = 'Username field is required. ';
$lang['invalid_new_password'] = 'Password field is required. ';
$lang['default_copy_name'] = 'None - Start with blank survey';
$lang['survey_created'] = 'Survey successfully created. ';

//Take survey
$lang['take.bttn.quit'] = 'Quit Survey - Do not save answers';
$lang['take.bttn.previous'] = '&lt;&lt;&nbsp;Previous Page';
$lang['take.bttn.next'] = 'Next Page&nbsp;&gt;&gt;';
$lang['take.bttn.finish'] = 'Finish';
$lang['take.msg.already_completed'] = 'You have already completed the requested survey.';
$lang['take.err.required'] = 'Required questions were not answered.';
$lang['take.err.time_limit.hdr'] = 'Time Limit Exceeded';
$lang['take.err.time_limit.msg'] = 'You exceeded the time limit set for the survey. Your last page of results were not saved.';
?>