<table border="1" width="50%" align="center" cellpadding="2">
  <tr>
    <td class="message">{$message}</td>
  </tr>
  <tr>
    <td align="center">
      <a href="{$conf.html}/index.php">Main</a>
      {section name="admin" loop=1 show=$smarty.session.admin_logged_in}
        &nbsp;&nbsp;<a href="{$conf.html}/admin.php">Admin</a>
      {/section}
    </td>
  </tr>
</table>
<br>
