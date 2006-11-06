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
      <input type="hidden" name="mode" value="{$data.mode_invite}">
      <input type="hidden" name="sid" value="{$data.sid}">

      <div class="whitebox">Import Invitees</div>

      <div class="indented_cell">
        Click the Browse button to upload a file and import invitee information. Click <a href="">here</a> for a sample CSV file format. The imported file must match this format.
        <br />
        <input type="checkbox" name="ignore_uid" value="1"> Ignore "uid" <strong>(All lines in file will be loaded as new invitees into this survey! Use this for exported files from other surveys.)</strong>
        <br />
        <input type="file" name="invite_file" /><input type="submit" name="invite_import" value="Import Invitees" />
      </div>

      <div class="whitebox">Export Invitees</div>

      <div class="indented_cell">
        Clicking the Export button will allow you to download a CSV file filled populated with your current invitees. This can then be imported into another survey or edited and re-imported back into this one.
        <br />
        <input type="submit" name="invite_export" value="Export Invitees" />
      </div>

        <div class="whitebox" style="margin-top:10px">Invitation Code Type <a href="{$conf.html}/docs/index.html#ac_invite_code">[?]</a></div>
        <div class="indented_cell">
          <p style="margin-top:1px; margin-bottom:1px">
            <input type="radio" id="numeric" name="invite_code_type" value="numeric"{$data.invite_code_type.numeric}>
            <label for="numeric">Numeric</label>
            <input type="text" name="invite_numcode_length" value="{$data.invite_numcode_length}" size="3" maxlength="2"> digits
            <em>(i.e. 1234 or 45643, etc., max {$data.numeric.maxlength} digits, default {$data.numeric.defaultlength} digits)</em>
          </p>

          <p style="margin-top:1px; margin-bottom:1px">
            <input type="radio" id="alpha" name="invite_code_type" value="alpha"{$data.invite_code_type.alpha}>
            <label for="alpha">Alphabetic</label>
            <input type="text" name="invite_alphacode_length" value="{$data.invite_alphacode_length}" size="3" maxlength="2"> letters
            <em>(i.e. abce or eftg, etc., max {$data.alpha.maxlength} letters, default {$data.alpha.defaultlength} letters)</em>
          </p>

          <p style="magin-top:1px; margin-bottom:1px">
            <input type="radio" id="alphanumeric" name="invite_code_type" value="alphanumeric"{$data.invite_code_type.alphanumeric}>
            <label for="alphanumeric">Alphanumeric</label>
            <input type="text" name="invite_alphanumericcode_length" value="{$data.invite_alphanumericcode_length}" size="3" maxlength="2"> characters
            <em>(i.e &quot;5ta2ST7aE2&quot; or &quot;2jiW72sut97Y&quot;, max {$data.alphanumeric.maxlength} characters, default {$data.alphanumeric.defaultlength} characters)</em>
          </p>

          <p style="margin-top:1px; margin-bottom:1px">
            <input type="radio" id="words" name="invite_code_type" value="words"{$data.invite_code_type.words}>
            <label for="words">Words</label> <em>(i.e &quot;buffalo-candy&quot; or &quot;interesting-something&quot;)</em>
          </p>
        </div>
        <div class="whitebox">Invitees <a href="{$conf.html}/docs/index.html#ac_invitee_list">[?]</a></div>
        <div class="indented_cell">
          <table border="1" cellspacing="0" cellpadding="3">
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Invited</th>
              <th>Invite Code</th>
              <th>Completed</th>
              {section name="view_results" loop=1 show=$data.show.results_priv}
                <th>View Results</th>
              {/section}
              <th bgcolor="#DDDDDD">
                Action<input type="checkbox" name="invitee_all" onclick="doStuff('invite',this.checked);">
              </th>
            </tr>
            {section name="i" loop=$data.invite show=TRUE}
              <tr{section name="erruid" loop=1 show=$data.invite[i].erruid} style="background-color:red"{/section}>
                <td><input type="text" name="invite_name[{$data.invite[i].uid}]" size="20" maxlength="50" value="{$data.invite[i].name}"></td>
                <td><input type="text" name="invite_email[{$data.invite[i].uid}]" size="20" maxlength="100" value="{$data.invite[i].email}"></td>
                <td align="center">{$data.invite[i].status_date}</td>
                <td align="center">{$data.invite[i].invite_code}</td>
                <td align="center">{$data.invite[i].completed} ({$data.invite[i].num_completed})</td>
                {section name="view_results" loop=1 show=$data.show.results_priv}
                  <td align="center"><input type="checkbox" name="invite_results_priv[{$data.invite[i].uid}]" value="1"{$data.invite[i].results_priv}></td>
                {/section}
                <td align="center" bgcolor="#DDDDDD"><input type="checkbox" name="invite_checkbox[{$data.invite[i].uid}]" value="1"></td>
              </tr>
            {/section}
            <tr>
              <td colspan="2">(Be sure to save invitees before sending invite codes.)</td>
              <td colspan="{$data.inviteactioncolspan}" align="right" bgcolor="#DDDDDD">
                Action:
                <select name="invite_selection" size="1">
                  <option value="saveall">Save All Invitees</option>
                  <option value="delete">Delete Selected Invitees</option>
                  <option value="invite">Send Invitation Code to Selected</option>
                  <option value="movetousers">Move Selected to Users List</option>
                </select>
                <input type="submit" name="invite_go" value="Go">
              </td>
            </tr>
          </table>
        </div>
    </form>