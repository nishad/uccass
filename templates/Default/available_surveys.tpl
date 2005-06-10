<table width="70%" align="center" cellpadding="0" cellspacing="0">
  <tr class="grayboxheader">
    <td width="14"><img src="{$conf.images_html}/box_left.gif" border="0" width="14"></td>
    <td background="{$conf.images_html}/box_bg.gif">Survey System</td>
    <td width="14"><img src="{$conf.images_html}/box_right.gif" border="0" width="14"></td>
  </tr>
</table>
<table width="70%" align="center" class="bordered_table">
  <tr>
    <td class="whitebox">Public Surveys</td>
  </tr>
  <tr>
    <td>
      <div style="font-weight:bold;text-align:center">
        The following surveys are publicly available. Click on a link to begin taking the survey.
      </div>

      <div class="indented_cell">
      {section name="s" loop=$survey show=TRUE}
        {$smarty.section.s.iteration}. <a href="{$conf.html}/survey.php?sid={$survey[s].sid}">{$survey[s].display}</a>
        {section name="r" loop=1 show=$results[s]}
          &nbsp;&nbsp;<a href="{$conf.html}/results.php?sid={$survey[s].sid}">[ View Results ]</a>
        {/section}
        <br />
      {sectionelse}
        There are no surveys available at this time.
      {/section}
      </div>
    </td>
  </tr>
  {section name="priv_surveys" loop=1 show=$priv_survey}
    <tr>
      <td class="whitebox">Private Surveys</td>
    </tr>
    <tr>
      <td>
        <form class="indented_cell" method="POST" action="{$conf.html}/survey.php">
          Survey:&nbsp;
          <select name="sid" size="1">
            {section name="ps" loop=$priv_survey.sid}
              <option value="{$priv_survey.sid[ps]}">{$priv_survey.display[ps]}</option>
            {/section}
          </select><br>
          Password:&nbsp;
          <input type="password" value="" name="password">
          &nbsp;<input type="submit" name="submit" value="Take Survey">
        </form>
      </td>
    </tr>
  {/section}
  {section name="priv_results" loop=1 show=$priv_results}
    <tr>
      <td class="whitebox">Private Results</td>
    </tr>
    <tr>
      <td>
        <form class="indented_cell" method="POST" action="{$conf.html}/results.php">
          Survey:&nbsp;
          <select name="sid" size="1">
            {section name="pr" loop=$priv_results.sid}
              <option value="{$priv_results.sid[pr]}">{$priv_results.display[pr]}</option>
            {/section}
          </select><br>
          Password:&nbsp;
          <input type="password" value="" name="password">
          &nbsp;<input type="submit" name="submit" value="View Results">
        </form>
      </td>
    </tr>
  {/section}
  <tr>
    <td class="whitebox">Edit Surveys</td>
  </tr>
  <tr>
    <td>
      <form class="indented_cell" method="POST" action="{$conf.html}/edit_survey.php">
        Survey:&nbsp;
        <select name="sid" size="1">
          {section name="as" loop=$all_surveys.sid}
            <option value="{$all_surveys.sid[as]}">{$all_surveys.name[as]}</option>
          {/section}
        </select><br>
        Password:&nbsp;
        <input type="password" value="" name="edit_survey_password">
        &nbsp;<input type="submit" name="submit" value="Edit Survey">
      </form>
    </td>
  </tr>
  <tr>
    <td style="text-align:center">
      <br />
      [ <a href="{$conf.html}/new_survey.php">Create New Survey</a>
      &nbsp;|&nbsp;
      <a href="{$conf.html}/admin.php">Admin</a>
      &nbsp;|&nbsp;
      {section name="logout" loop=1 show=$show.logout}
        <a href="{$conf.html}/index.php?action=logout">Logout</a>
        &nbsp;|&nbsp;
      {/section}
      <a href="{$conf.html}/docs/index.html">Documentation</a> ]
    </td>
  </tr>
</table>