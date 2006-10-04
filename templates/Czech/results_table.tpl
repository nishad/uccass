<table width="98%" align="center" cellpadding="0" cellspacing="0">
  <tr class="grayboxheader">
    <td width="14"><img src="{$conf.images_html}/box_left.gif" border="0" width="14"></td>
    <td background="{$conf.images_html}/box_bg.gif">Survey Results</td>
    <td width="14"><img src="{$conf.images_html}/box_right.gif" border="0" width="14"></td>
  </tr>
</table>
<table width="98%" align="center" class="bordered_table">
  <tr>
    <td>

      <div style="text-align:center">
        [ <a href="{$conf.html}/index.php">Main</a> ]
        &nbsp;&nbsp;
        [ <a href="{$conf.html}/results.php?sid={$survey.sid}">Graphic Results</a> ]
        &nbsp;&nbsp;
        [ Export Results to CSV as
          <a href="{$conf.html}/results_csv.php?sid={$survey.sid}&export_type={$survey.export_csv_text}">Text</a> or
          <a href="{$conf.html}/results_csv.php?sid={$survey.sid}&export_type={$survey.export_csv_numeric}">Numeric</a> Values
          <a href="{$conf.html}/docs/index.html#csv_export">[?]</a> ]
      </div>

      <div class="whitebox">
        Results for Survey #{$conf.sid}: {$conf.survey_name}
      </div>

      {section name="filter_text" loop=1 show=$filter_text}
        <br><span class="message">Notice: This result page shows the results filtered by the following questions:</span><br>
        <span style="font-size:x-small">{$filter_text}</span>
      {/section}

      <div style="text-align:right">
        <form method="GET" action="{$conf.html}/results_table.php">
          <input type="hidden" name="sid" value="{$survey.sid}" />
          Results per page:
          <select name="num_table_results" size="1">
            <option value="5"{$data.num_table_results.selected[5]}>5</option>
            <option value="10"{$data.num_table_results.selected[10]}>10</option>
            <option value="15"{$data.num_table_results.selected[15]}>15</option>
            <option value="20"{$data.num_table_results.selected[20]}>20</option>
            <option value="25"{$data.num_table_results.selected[25]}>25</option>
            <option value="50"{$data.num_table_results.selected[50]}>50</option>
            <option value="75"{$data.num_table_results.selected[75]}>75</option>
            <option value="100"{$data.num_table_results.selected[100]}>100</option>
          </select>
          <input type="submit" name="submit" value="Go" />
       </form>
      </div>

      <div style="text-align:left;font-size:xx-small;margin-left:5px;margin-right:5px;margin-top:0px;margin-bottom:0px">
        {$data.prev_next_text}
      </div>

      <div>
        <form method="post" action="{$conf.html}/results_table.php">
        <input type="hidden" name="sid" value="{$survey.sid}" />
        <input type="hidden" name="page" value="{$data.page}" />
        <table border="1" cellpadding="2" cellspacing="2" style="font-size:xx-small;margin-left:25px;margin-right:25px;margin-top:0px;margin-bottom:0px">
          <tr>
            <th>Action</a>
            <th>
              <a href="{$conf.html}/results_table.php?sid={$survey.sid}&page={$data.page}&sortcol=-1&sortdir=asc">
                <img src="{$conf.images_html}/up_arrow.gif" /></a>
              Seq#
              <a href="{$conf.html}/results_table.php?sid={$survey.sid}&page={$data.page}&sortcol=-1&sortdir=desc">
                <img src="{$conf.images_html}/down_arrow.gif" /></a>
            </th>
            {section name=q loop=$data.questions show=TRUE}
              <th>
                <a href="{$conf.html}/results_table.php?sid={$survey.sid}&page={$data.page}&sortcol={$smarty.section.q.index}&sortdir=asc">
                  <img src="{$conf.images_html}/up_arrow.gif" /></a>
                {$data.questions[q]}
                <a href="{$conf.html}/results_table.php?sid={$survey.sid}&page={$data.page}&sortcol={$smarty.section.q.index}&sortdir=desc">
                  <img src="{$conf.images_html}/down_arrow.gif" /></a>
              </th>
            {/section}
            <th>
              <a href="{$conf.html}/results_table.php?sid={$survey.sid}&page={$data.page}&sortcol={$data.datecol}&sortdir=asc">
                <img src="{$conf.images_html}/up_arrow.gif" /></a>
              Datetime
              <a href="{$conf.html}/results_table.php?sid={$survey.sid}&page={$data.page}&sortcol={$data.datecol}&sortdir=desc">
                <img src="{$conf.images_html}/down_arrow.gif" /></a>
            </th>
          </tr>
          {section name=x loop=$data.answers show=TRUE}
            <tr>
              <td style="text-align:center"><input type="checkbox" name="delete[]" value="{$data.answers[x][0]}" /></td>
              {section name=a loop=$data.answers[x] show=TRUE}
                <td>{$data.answers[x][a]}</td>
              {/section}
            </tr>
          {/section}
          <tr>
            <td colspan="{$data.num_columns}">
              <select name="action" size="1">
                <option value="delete">Delete Selected Records</option>
              </select>
              <input type="submit" name="submit" value="Go" />
            </td>
          </tr>
        </table>
        </form>
      </div>

      <div style="text-align:left;font-size:xx-small;margin-left:25px;margin-right:25px;margin-top:0px;margin-bottom:0px">
        {$data.prev_next_text}
      </div>

      <div style="text-align:right">
        <form method="GET" action="{$conf.html}/results_table.php">
          <input type="hidden" name="sid" value="{$survey.sid}" />
          Results per page:
          <select name="num_table_results" size="1">
            <option value="5"{$data.num_table_results.selected[5]}>5</option>
            <option value="10"{$data.num_table_results.selected[10]}>10</option>
            <option value="15"{$data.num_table_results.selected[15]}>15</option>
            <option value="20"{$data.num_table_results.selected[20]}>20</option>
            <option value="25"{$data.num_table_results.selected[25]}>25</option>
            <option value="50"{$data.num_table_results.selected[50]}>50</option>
            <option value="75"{$data.num_table_results.selected[75]}>75</option>
            <option value="100"{$data.num_table_results.selected[100]}>100</option>
          </select>
          <input type="submit" name="submit" value="Go" />
       </form>
      </div>

      <div style="text-align:center">
        [ <a href="{$conf.html}/index.php">Main</a> ]
        &nbsp;&nbsp;
        [ <a href="{$conf.html}/results.php?sid={$survey.sid}">Graphic Results</a> ]
        &nbsp;&nbsp;
        [ <a href="{$conf.html}/results_csv.php?sid={$survey.sid}">Export Results to CSV</a> ]
      </div>

    </td>
  </tr>
</table>