<table width="70%" align="center" cellpadding="0" cellspacing="0">
  <tr class="grayboxheader">
    <td width="14"><img src="{$conf.images_html}/box_left.gif" border="0" width="14"></td>
    <td background="{$conf.images_html}/box_bg.gif">Edit Survey</td>
    <td width="14"><img src="{$conf.images_html}/box_right.gif" border="0" width="14"></td>
  </tr>
</table>
<table width="70%" align="center" class="bordered_table">

{$show.links}

{* ERROR MESSAGE *}
  {section name="error" loop=1 show=$show.error}
  <tr>
    <td class="error">Error: {$show.error}</td>
  </tr>
  {/section}
{* / ERROR MESSAGE *}

{* NOTICE MESSAGE *}
  {section name="notice" loop=1 show=$show.notice}
  <tr>
    <td class="message">{$show.notice}</td>
  </tr>
  {/section}
{* / NOTICE MESSAGE *}

  <tr>
    <td>
      {$show.content}
    </td>
  </tr>

  <tr>
    <td align="center">
      <br />
      [ <a href="{$conf.html}/index.php">Return to Main</a>
      &nbsp;|&nbsp;
      <a href="{$conf.html}/admin.php">Admin</a> ]
    </td>
  </tr>
</table>