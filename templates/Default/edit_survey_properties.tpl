    <form method="POST" action="{$conf.html}/edit_survey.php">
    <input type="hidden" name="mode" value="{$mode}">
    <input type="hidden" name="sid" value="{$property.sid}">

      <div class="whitebox">Survey Name <a href="{$conf.html}/docs/index.html#ep_name">[?]</a></div>

      <div class="indented_cell">
        <input type="text" name="name" value="{$property.name}" size="50">
      </div>

      <div class="whitebox">Creation Date: {$property.created}</div>

      <div class="whitebox">Status <a href="{$conf.html}/docs/index.html#ep_active">[?]</a></div>

      <div class="indented_cell">
        <input type="radio" name="active" value="1" {$property.active_selected}>Active
        &nbsp;
        <input type="radio" name="active" value="0" {$property.inactive_selected}>Inactive
      </div>

      <div class="whitebox">Start Date <a href="{$conf.html}/docs/index.html#ep_dates">[?]</a></div>

      <div class="indented_cell">
        If Start and End dates are given, they will override the Active/Inactive Status setting.
        <br />
        If Start and End dates are blank, then the Active/Inactive Status will control the survey.
        <br />
        <input type="text" name="start" size="11" maxlength="10" value="{$property.start_date}"> (yyyy-mm-dd)
      </div>

      <div class="whitebox">End Date</div>

      <div class="indented_cell">
        <input type="text" name="end" size="11" maxlength="10" value="{$property.end_date}"> (yyyy-mm-dd)
      </div>

      <div class="whitebox">Survey Template <a href="{$conf.html}/docs/index.html#ep_template">[?]</a></div>

      <div class="indented_cell">
        <select name="template" size="1">
          {section name="tem" loop=$property.templates show=TRUE}
            <option value="{$property.templates[tem]}"{$property.selected_template[tem]}>{$property.templates[tem]}</option>
          {/section}
        </select>
      </div>

      <div class="whitebox">Text Modes <a href="{$conf.html}/docs/index.html#ep_text_mode">[?]</a></div>

      <div class="indented_cell">
        These settings control the text modes for survey data (questions and answer values) and user data (answers
        supplied by users taking the survey).
        {section name="fullhtml_warning" loop=1 show=$show.fullhtmlwarning}
          Notice: Allowing Full HTML is a security risk. Malicious users can include HTML that will mess up the
          page design and possibly introduce vulernabilities to those who view the HTML they create. It is recommended
          that Full HTML mode not be used for the user_text_mode and only used for survey_text_mode under controlled circumstances.
        {/section}
        <br />
        Survey Text Mode:
          <select name="survey_text_mode" size="1">
            {section name="stm" loop=$property.survey_text_mode_options show=TRUE}
              <option value="{$property.survey_text_mode_options[stm]}"{$property.survey_text_mode_selected[stm]}>{$property.survey_text_mode_values[stm]}</option>
            {/section}
          </select>
        <br />
        User Text Mode:
          <select name="user_text_mode" size="1">
            {section name="utm" loop=$property.user_text_mode_options show=TRUE}
              <option value="{$property.user_text_mode_options[utm]}"{$property.user_text_mode_selected[utm]}>{$property.user_text_mode_values[utm]}</option>
            {/section}
          </select>
      </div>
      <div class="whitebox">Completion Redirect Page <a href="{$conf.html}/docs/index.html#ep_redirect">[?]</a></div>
      <div class="indented_cell">
        This is the page users will be sent to after they complete the survey.<br />
        <input type="radio" name="redirect_page" value="index"{$property.redirect_index}> Main Survey Page <span class="example">(index.php)</span><br />
        <input type="radio" name="redirect_page" value="results"{$property.redirect_results}> Survey Results Page <span class="example">(Results Access should be Public)</span><br />
        <input type="radio" name="redirect_page" value="custom"{$property.redirect_custom}> Custom URL <span class="example">(Provide complete URL including http:// or https://)</span><br />
        <div style="margin-left:20px">
          URL: <input type="text" name="redirect_page_text" value="{$property.redirect_page_text}" size="30" maxlength="255">
        </div>
      </div>

      <div class="whitebox">Survey Access <a href="{$conf.html}/docs/index.html#ep_access">[?]</a></div>

      <div class="indented_cell">
        <input type="radio" name="survey_access" value="public" {$property.survey_public}>Public (Anyone can take survey)
        <br>
        <input type="radio" name="survey_access" value="private" {$property.survey_private}>Private
        &nbsp;
        Password: <input type="text" name="survey_password" value="{$property.survey_password}">
      </div>

      <div class="whitebox">Results Access</div>

      <div class="indented_cell">
        <input type="radio" name="results_access" value="public" {$property.results_public}>Public (Anyone can view results)
        <br>
        <input type="radio" name="results_access" value="private" {$property.results_private}>Private
        &nbsp;
        Password: <input type="text" name="results_password" value="{$property.results_password}">
      </div>

      <div class="whitebox">Edit Password <a href="{$conf.html}/docs/index.html#ep_password">[?]</a></div>

      <div class="indented_cell">
        <input type="text" name="edit_password" value="{$property.edit_password}">
      </div>

      <div class="whitebox">Results Date Format <a href="{$conf.html}/docs/index.html#ep_results_date_format">[?]</a></div>

      <div class="indented_cell">
        Format used for Table Results and CSV Export. Must match specifications given for PHP
        <a href="http://www.php.net/date" target="_blank">date()</a> function.<br />
        <input type="text" name="date_format" size="20" value="{$property.date_format}">
      </div>

      <div class="whitebox">Time Limit <a href="{$conf.html}/docs/index.html#ep_time_limit">[?]</a></div>

      <div class="indented_cell">
        Optional time limit to take survey in minutes. Leave blank or zero for no time limit. Time limit begins from
        the time the first question is viewed. Only pages submitted before the time limit is up are saved in the
        results. If the time limit is 60 minutes and page 8 is submitted after 60 minutes, only pages 1 through 7
        are saved.<br />
        <input type="text" name="time_limit" size="5" value="{$property.time_limit}"> (minutes)
      </div>

      <div class="whitebox">Clear Results <a href="{$conf.html}/docs/index.html#ep_clear">[?]</a></div>

      <div class="indented_cell">
        <input type="checkbox" name="clear_answers" value="1">
        Check this box to clear current answers to this survey.
        Answers will be cleared when you press Save Changes below.
      </div>

      <div class="whitebox">Delete Survey <a href="{$conf.html}/docs/index.html#ep_delete">[?]</a></div>

      <div class="indented_cell">
        <input type="checkbox" name="delete_survey" value="1">
        Check this box to Delete the Survey. All questions and answers associated with
        this survey will be erased. There is no way to 'undelete' this information. The
        survey will be deleted when you click Save Changes below.
      </div>

      <br />

      <div style="text-align:center">
        <input type="submit" name="edit_survey_submit" value="Save Changes">
      </div>
    </form>