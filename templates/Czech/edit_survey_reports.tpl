{literal}
<script language="JavaScript">
    function en()
    { document.report_form.elements['crosstab_questions[]'].disabled = false; }

    function dis()
    { document.report_form.elements['crosstab_questions[]'].disabled = true; }
</script>
{/literal}

<div class="whitebox">
    Existing Report Questions
</div>

<div class="indented_cell">
    <table border="1" width="100%" cellpadding="2" cellspacing="0">
        <tr>
            <td>Number</td>
            <td>Question</td>
            <td>Layout</td>
            <td>Display</td>
            <td>Crosstab Question IDs</td>
            <td>Delete</td>
        </tr>
        {section name="rq" loop=$data.report.qid}
            <tr>
                <td>{$data.report.order_id[rq]}</td>
                <td>{$data.report.question[rq]}</td>
                <td>{$data.report.layout[rq]}</td>
                <td>{$data.report.display[rq]}</td>
                <td>{$data.report.crosstab_questions[rq]}</td>
                <td>(<a href="reports.php?sid={$data.sid}&report_id={$data.report_id}&delete={$data.report.rqid[rq]}">Delete</a>)</td>
            </tr>
        {/section}
    </table>
</div>

<form method="post" action="reports.php" name="report_form">
    <input type="hidden" name="sid" value="{$data.sid}" />
    <input type="hidden" name="report_id" value="{$data.report_id}" />

    <div class="whitebox">
        Report Name
    </div>

    <div class="indented_cell">
        <input type="text" name="report_name" value="{$data.report_name}" size="50" maxlength="100" />
        <input type="submit" name="save_submit" value="Save Name" />
    </div>

    <div style="text-align:center; font-weight:bold; font-size:larger; text-decoration:underline">
        Add New Questions to Custom Report
    </div>

    <div class="whitebox">
        Choose questions to add to report
    </div>

    <div class="indented_cell">
        <select name="questions[]" size="7" multiple>
            {section name="sq" loop=$data.survey_questions.qid}
                <option value="{$data.survey_questions.qid[sq]}">{$data.survey_questions.question_num[sq]}. {$data.survey_questions.question[sq]}</option>
            {/section}
        </select>
    </div>

    <div class="whitebox">
        Insert after question number:
    </div>

    <div class="indented_cell">
        <select name="order_id" size="1">
            <option value="0">First</option>
            {section name="oid" loop=$data.report.order_id}
                <option value="{$data.report.order_id[oid]}"{$data.report.order_id_selected[oid]}>{$data.report.order_id[oid]}</option>
            {/section}
        </select>
    </div>

    <div class="whitebox">
        Layout type for chosen questions
    </div>

    <div class="indented_cell">
        <input type="radio" name="layout" value="bar_graph" checked onclick="dis();" /> Bar Graph<br />
        <input type="radio" name="layout" value="numeric" onclick="dis();" /> Numbers Only<br />
        <input type="radio" name="layout" value="crosstab" onclick="en();" /> Cross Tab (choose against what questions below)
    </div>

    <div class="whitebox">
        Display
    </div>

    <div class="indented_cell">
        <input type="checkbox" name="display[]" value="total" checked /> Total Answers<br />
        <input type="checkbox" name="display[]" value="percentage" checked /> Percentage<br />
        <input type="checkbox" name="display[]" value="average" /> Average
    </div>

    <div class="whitebox">
        Cross Tab Questions
    </div>

    <div class="indented_cell">
        <select name="crosstab_questions[]" size="7" multiple disabled>
            {section name="cq" loop=$data.crosstab_questions.qid}
                <option value="{$data.crosstab_questions.qid[cq]}">{$data.crosstab_questions.question_num[cq]}. {$data.crosstab_questions.question[cq]}</option>
            {/section}
        </select>
    </div>

    <div style="text-align:center">
        <input type="submit" name="add_questions" value="Add Question(s)" />
    </div>
</form>