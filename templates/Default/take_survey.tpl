<form method="POST" action="survey.php">
  <input type="hidden" name="sid" value="{$survey.sid}">

  <table width="70%" align="center" cellpadding="0" cellspacing="0">
    <tr class="grayboxheader">
      <td width="14"><img src="{$conf.images_html}/box_left.gif" border="0" width="14"></td>
      <td background="{$conf.images_html}/box_bg.gif">Survey #{$conf.sid}: {$conf.survey_name}</td>
      <td width="14"><img src="{$conf.images_html}/box_right.gif" border="0" width="14"></td>
    </tr>
  </table>

  <table width="70%" align="center" class="bordered_table">
    <tr>
      <td>

        {*MESSAGE*}
        {section name="message" loop=1 show=$message}
          <div class="message">{$message}</div>
        {/section}

        {*ERROR*}
        {section name="error" loop=1 show=$error}
          <div class="error">{$error}</div>
        {/section}

        {*NUMBER OF PAGES*}
        {section name="page" loop=1 show=$show.page_num}
          <div>
            Page {$survey.page} of {$survey.total_pages}
          </div>
        {/section}

        {*TIME LIMIT*}
        {section name="time_limit" loop=1 show=$survey.time_limit}
          <div>
            Time Limit: {$survey.time_limit} minutes. Approximate Elapsed Time: {$survey.elapsed_minutes}:{$survey.elapsed_seconds}
          </div>
        {/section}

        <br />

        {*QUESTIONS*}
        {section name="question" loop=1 show=$show.question}
          <div>{$question_text}</div>
        {/section}

        {*QUIT SURVEY MESSAGE*}
        {section name="quit" loop=1 show=$show.quit}
          <div>
            You have quit answering this survey. Your answers were not saved.
          </div>
        {/section}

        {*MAIN LINK*}
        {section name="main_url" loop=1 show=$show.main_url}
          <div style="text-align:center">
            <br />
            [ <a href="{$conf.html}/index.php">Return to Main Menu</a> ]
          </div>
        {/section}

        {*BUTTONS*}
          <div style="text-align:right">
            {section name="quit" loop=1 show=$show.quit_button}
              <input type="submit" name="quit" value="{$button.quit|default:"Quit Survey - Do not save answers"}">
            {/section}

            {section name="previous" loop=1 show=$show.previous_button}
              &nbsp;
              <input type="submit" name="previous" value="{$button.previous|default:"&lt;&lt;&nbsp;Previous Page"}">
            {/section}

            {section name="next" loop=1 show=$show.next_button}
              &nbsp;
              <input type="submit" name="next" value="{$button.next|default:"Next Page&nbsp;&gt;&gt;"}">
            {/section}
          </div>
      </td>
    </tr>
  </table>
</form>
