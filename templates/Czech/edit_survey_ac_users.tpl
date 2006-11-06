      {literal}
      <script type="text/JavaScript">
      function doStuff(el,a)
      {
          var cbs=document.user_invitee_form.getElementsByTagName('input');
          var l=cbs.length;
          for (var i=0; i<l; i++)
          {
              if (cbs[i].type=='checkbox')
              {
                  var daName=cbs[i].name;

                  regex = new RegExp(el+'_checkbox\\[x?([0-9]+)\\]');
                  if (regex.test(daName)) cbs[i].checked=a;
              }
          }
      }
      </script>
      {/literal}
    <form enctype="multipart/form-data" method="POST" name="user_invitee_form" action="{$conf.html}/access_control.php">
      <input type="hidden" name="mode" value="{$data.mode_user}">
      <input type="hidden" name="sid" value="{$data.sid}">

      <div class="whitebox">Import Users</div>

      <div class="indented_cell">
        Click the Browse button to upload a file and import user information. Click <a href="">here</a> for a sample CSV file format. The imported file must match this format.
        <br />
        <input type="checkbox" name="ignore_uid" value="1"> Ignore "uid" <strong>(All lines in file will be loaded as new users into this survey! Use this for exported files from other surveys.)</strong>
        <br />
        <input type="file" name="user_file" /><input type="submit" name="user_import" value="Import Users" />
        <br />
      </div>

      <div class="whitebox">Export Users</div>

      <div class="indented_cell">
        Clicking the Export button will allow you to download a CSV file filled populated with your current users. This can then be imported into another survey or edited and re-imported back into this one.
        <br />
        <input type="submit" name="user_export" value="Export Users" />
      </div>

      <div class="whitebox">Users <a href="{$conf.html}/docs/index.html#ac_user_list">[?]</a></div>

      <div class="indented_cell">
        <strong>Be sure to click the &quot;Update Access Control&quot; button if any changes were made above before you
        edit the users below.</strong>
        <table border="1" cellspacing="0" cellpadding="3">
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Username</th>
            <th>Password</th>
            <th>Sent Login</th>
            {section name="take_survey" loop=1 show=$data.show.take_priv}
              <th>Completed</th>
              <th>Take Survey</th>
            {/section}
            {section name="view_results" loop=1 show=$data.show.results_priv}
              <th>View Results</th>
            {/section}
            <th>Edit Survey</th>
            <th bgcolor="#DDDDDD">
              Action<input type="checkbox" name="users_all" onclick="doStuff('users',this.checked);">
            </th>
          </tr>
          {section name="u" loop=$data.users show=TRUE}
            <tr{section name="erruid" loop=1 show=$data.users[u].erruid} style="background-color:red"{/section}>
              <td><input type="text" name="name[{$data.users[u].uid}]" size="20" maxlength="50" value="{$data.users[u].name}"></td>
              <td><input type="text" name="email[{$data.users[u].uid}]" size="20" maxlength="100" value="{$data.users[u].email}"></td>
              <td><input type="text" name="username[{$data.users[u].uid}]" size="10" maxlength="50" value="{$data.users[u].username}"></td>
              <td><input type="text" name="password[{$data.users[u].uid}]" size="10" maxlength="50" value="{$data.users[u].password}"></td>
              <td align="center">{$data.users[u].status_date}</td>
              {section name="take_survey" loop=1 show=$data.show.take_priv}
                <td align="center">{$data.users[u].completed} ({$data.users[u].num_completed})</td>
                <td align="center"><input type="checkbox" name="take_priv[{$data.users[u].uid}]" value="1"{$data.users[u].take_priv}></td>
              {/section}
              {section name="view_results" loop=1 show=$data.show.results_priv}
                <td align="center"><input type="checkbox" name="results_priv[{$data.users[u].uid}]" value="1"{$data.users[u].results_priv}></td>
              {/section}
              <td align="center"><input type="checkbox" name="edit_priv[{$data.users[u].uid}]" value="1"{$data.users[u].edit_priv}></td>
              <td align="center" bgcolor="#DDDDDD"><input type="checkbox" name="users_checkbox[{$data.users[u].uid}]" value="1"></td>
            </tr>
          {/section}
          <tr>
            <td colspan="2">(Be sure to save users before sending login information)</td>
            <td colspan="{$data.actioncolspan}" align="right" bgcolor="#DDDDDD">
              Action:
              <select name="users_selection" size="1">
                <option value="saveall">Save All Users</option>
                <option value="delete">Delete Selected</option>
                <option value="remind">Send Login Info to Selected</option>
                {section name="invite" loop=1 show=$data.show.invite}
                  <option value="movetoinvite">Move Selected to Invitee List</option>
                {/section}
              </select>
              <input type="submit" name="users_go" value="Go">
            </td>
          </tr>
        </table>
      </div>
    </form>