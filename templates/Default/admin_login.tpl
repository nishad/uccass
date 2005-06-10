<table width="70%" align="center" cellpadding="0" cellspacing="0">
  <tr class="grayboxheader">
    <td width="14"><img src="{$conf.images_html}/box_left.gif" border="0" width="14"></td>
    <td background="{$conf.images_html}/box_bg.gif">Administration Login</td>
    <td width="14"><img src="{$conf.images_html}/box_right.gif" border="0" width="14"></td>
  </tr>
</table>

<table width="70%" align="center" class="bordered_table">
  {section name="message" show=$message}
    <tr><td class="error">{$message}</td></tr>
  {/section}
  <tr>
    <td align="center">
      <form method="POST" action="{$conf.html}/admin.php" name="login_form">
        Please enter the administrator password:
        <br>
        <input type="password" name="admin_password" size="15">
        <br>
        <input type="submit" value="Enter">
      </form>
    </td>
  </tr>
</table>
<script language="JavaScript">
document.login_form.admin_password.focus();
</script>