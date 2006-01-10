<form method="get" action="reports.php">
    <input type="hidden" name="sid" value="{$data.sid}" />
    <div class="whitebox">
        Choose Report
    </div>

    <div class="indented_cell">
        <select name="report_id" size="1">
            {section name="id" loop=$data.avail_reports.report_id}
                <option value="{$data.avail_reports.report_id[id]}">{$data.avail_reports.report_name[id]}</option>
            {/section}
        </select>
        <input type="submit" name="submit_load_report" value="Load Report" />
    </div>

    <div class="whitebox">
        Create New Report
    </div>

    <div class="indented_cell">
        <input type="text" name="report_name" value="{$data.report_name}" />
        <input type="submit" name="submit_new_report" value="Create New" />
    </div>
</form>