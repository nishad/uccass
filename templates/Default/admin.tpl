<table width="70%" align="center" cellpadding="0" cellspacing="0">
  <tr class="grayboxheader">
    <td width="14"><img src="{$conf.images_html}/box_left.gif" border="0" width="14"></td>
    <td background="{$conf.images_html}/box_bg.gif">Administration System</td>
    <td width="14"><img src="{$conf.images_html}/box_right.gif" border="0" width="14"></td>
  </tr>
</table>

<table width="70%" align="center" class="bordered_table">
  {section name="message" loop=1 show=$message}
    <tr>
      <td class="message">{$message}</td>
    </tr>
  {/section}

  <tr>
    <td class="whitebox">Edit Survey</td>
  </tr>
  <tr>
    <td>
      <form method="GET" action="{$conf.html}/edit_survey.php" class="indented_cell">
        <select name="sid">
          {section name=s loop=$survey.sid}
            <option value="{$survey.sid[s]}">{$survey.name[s]}</option>
          {/section}
        </select>
        <input type="submit" value="Edit Survey">
      </form>
    </td>
  </tr>
  <tr>
    <td align="center">
      <br />
      [ <a href="{$conf.html}/index.php">Return to Main</a> ]
    </td>
  </tr>
</table>