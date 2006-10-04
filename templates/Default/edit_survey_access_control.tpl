    <form enctype="multipart/form-data" method="POST" name="user_invitee_form" action="{$conf.html}/access_control.php">
      <input type="hidden" name="mode" value="{$data.mode}">
      <input type="hidden" name="sid" value="{$data.sid}">

      <div class="whitebox">Survey Access Control <a href="{$conf.html}/docs/index.html#ac_type">[?]</a></div>

      <div class="indented_cell">
        <select name="access_control" size="1">
          <option value="0"{$data.acs.none}>None - Public Survey</option>
          <option value="1"{$data.acs.cookie}>Cookies</option>
          <option value="2"{$data.acs.ip}>IP Address</option>
          <option value="3"{$data.acs.usernamepassword}>Username and Password</option>
          <option value="4"{$data.acs.invitation}>Invitation Only (Email)</option>
        </select>
        [ <a href="{$conf.html}/access_control.php?sid={$data.sid}&mode={$data.mode_user}">Manage Users</a>
          {section name="invite_link" show=$data.show.invite}
            &nbsp;|&nbsp;
            <a href="{$conf.html}/access_control.php?sid={$data.sid}&mode={$data.mode_invite}">Manage Invitees</a>
          {/section} ]
        {section name="invite_link" show=$data.show.invite}
          <br />
          <input type="checkbox" name="manual_codes" value="1"{$data.manual_codes_checked}/>Manually add/edit invitation codes
        {/section}
      </div>

      <div class="whitebox">Hide Survey <a href="{$conf.html}/docs/index.html#ac_hidden">[?]</a></div>

      <div class="indented_cell">
        <input type="checkbox" name="hidden" value="1"{$data.hidden_checked}>
        Survey will not be shown anywhere on main page and will need to be directly linked
        to using the following links. <br />
        [ <a href="{$conf.html}/survey.php?sid={$data.sid}">Take Survey</a>
          &nbsp;|&nbsp;
          <a href="{$conf.html}/results.php?sid={$data.sid}">Survey Results</a>
          &nbsp;|&nbsp;
          <a href="{$conf.html}/edit_survey.php?sid={$data.sid}">Edit Survey</a> ]
      </div>

      <div class="whitebox">Public Survey Results <a href="{$conf.html}/docs/index.html#ac_public_results">[?]</a></div>

      <div class="indented_cell">
        <input type="checkbox" name="public_results" value="1"{$data.public_results_checked}> Check this box to make the results of the survey
        public. If this box is not checked, access to the results will be controlled by the permissions
        you set below.
      </div>

      {section name="survey_limit" loop=1 show=$data.show.survey_limit}
        <div class="whitebox">Survey Limit <a href="{$conf.html}/docs/index.html#ac_survey_limit">[?]</a></div>

        <div class="indented_cell">
          Allow users to take survey <input type="text" name="survey_limit_times" size="3" value="{$data.survey_limit_times}">
          time(s) every <input type="text" name="survey_limit_number" size="5" value="{$data.survey_limit_number}">
          <select name="survey_limit_unit" size="1">
            <option value="0"{$data.survey_limit_unit[0]}>minute(s)</option>
            <option value="1"{$data.survey_limit_unit[1]}>hour(s)</option>
            <option value="2"{$data.survey_limit_unit[2]}>day(s)</option>
            <option value="3"{$data.survey_limit_unit[3]}>ever</option>
          </select>
          <p class="example" style="margin:1px">Sets a limit for how many times users can complete a survey over
          a given time span, such as &quot;Allow users to take survey <strong>1</strong> time every <strong>7</strong>
          <strong>days</strong>&quot; or &quot;Allow users to take survey <strong>2</strong> times <strong>ever</strong>&quot; (second number is ignored in
          this case). Leave set at zero for no limit.</p>
        </div>
      {/section}

      {section name="clear_completed" loop=1 show=$data.show.clear_completed}
        <div class="whitebox">Reset Completed Surveys <a href="{$conf.html}/docs/index.html#ac_clear_completed">[?]</a></div>

        <div class="indented_cell">
          <input type="checkbox" name="clear_completed" value="1">Check this box to reset the completed surveys number for all users. This will not
          remove the actual answers the users gave, but simply reset to zero the number of times the system thinks they have completed the survey.
        </div>
      {/section}

      <div class="indented_cell">
        <input type="submit" name="update_access_control" value="Update Access Control">
      </div>
    </form>