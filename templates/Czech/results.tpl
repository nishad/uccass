<table width="70%" align="center" cellpadding="0" cellspacing="0">
  <tr class="grayboxheader">
    <td width="14"><img src="{$conf.images_html}/box_left.gif" border="0" width="14"></td>
    <td background="{$conf.images_html}/box_bg.gif">Survey Results</td>
    <td width="14"><img src="{$conf.images_html}/box_right.gif" border="0" width="14"></td>
  </tr>
</table>
<table width="70%" align="center" class="bordered_table">
  <tr>
    <td>

      <div style="text-align:center">
        [ <a href="{$conf.html}/index.php">Main</a> ]
        &nbsp;&nbsp;
        [ <a href="{$conf.html}/results_table.php?sid={$survey.sid}">Results as Table</a>
          <a href="{$conf.html}/docs/index.html#table_results">[?]</a> ]
        &nbsp;&nbsp;
        [ Export Results to CSV as
          <a href="{$conf.html}/results_csv.php?sid={$survey.sid}&export_type={$survey.export_csv_text}">Text</a> or
          <a href="{$conf.html}/results_csv.php?sid={$survey.sid}&export_type={$survey.export_csv_numeric}">Numeric</a> Values
          <a href="{$conf.html}/docs/index.html#csv_export">[?]</a> ]
      </div>

      <div class="whitebox">
        Results for Survey #{$conf.sid}: {$conf.survey_name}
      </div>

      <form method="GET" action="results.php">
        <input type="hidden" name="sid" value="{$survey.sid}">

        <span class="example">
          Questions marked with a {$survey.required} were required.
        </span>

        {$output.filter}

        <br />

        <div class="whitebox">
          Survey Time Stats
        </div>
        <div class="indented_cell">
          Average Completion Time: {$survey.avgtime.minutes}min {$survey.avgtime.seconds}sec
          (Min: {$survey.mintime.minutes}min {$survey.mintime.seconds}sec, Max: {$survey.maxtime.minutes}min {$survey.maxtime.seconds}sec)
          <br />
          Average Time before Quit: {$survey.quittime.minutes}min {$survey.quittime.seconds}sec
        </div>

        {section name="o" loop=$output.question}
          {$output.question[o]}
          <div>
            <table border="0" cellpadding="2" cellspacing="2" style="font-size:xx-small;margin-left:25px;margin-top:10px;margin-bottom:10px">
              {$output.bar_graph[o]}
              {$output.total_ans[o]}
              {$output.average[o]}
              {$output.text[o]}
            </table>
          </div>
        {/section}
      </form>

      <div style="text-align:center">
        [ <a href="{$conf.html}/index.php">Main</a> ]
      </div>

    </td>
  </tr>
</table>