    <form method="POST" action="{$conf.html}/edit_survey.php">
    <input type="hidden" name="mode" value="{$mode}">
    <input type="hidden" name="sid" value="{$property.sid}">

      <div class="whitebox">Survey Name</div>

      <div class="indented_cell">
        <input type="text" name="name" value="{$property.name}" size="50">
      </div>

      <div class="whitebox">Survey Template</div>

      <div class="indented_cell">
        <select name="template" size="1">
          {section name="tem" loop=$property.templates show=TRUE}
            <option value="{$property.templates[tem]}"{$property.selected_template[tem]}>{$property.templates[tem]}</option>
          {/section}
        </select>
      </div>

      <div class="whitebox">Welcome Message</div>

      <div class="indented_cell">
        <textarea rows="6" cols="50" wrap="physical" name="welcome_text">{$property.welcome_text}</textarea>
      </div>

      <div class="whitebox">Thank You Message</div>

      <div class="indented_cell">
        <textarea rows="6" cols="50" wrap="physical" name="thank_you_text">{$property.thank_you_text}</textarea>
      </div>

      <div class="whitebox">Start Date</div>

      <div class="indented_cell">
        If Start and End dates are given, they will override the Active/Inactive Status setting.
        <br />
        If Start and End dates are blank, then the Active/Inactive Status will control the survey.
        <br />
        <input type="text" name="start" size="11" maxlength="10" value="{$property.start}"> (dd-mm-yyyy)
      </div>

      <div class="whitebox">End Date</div>

      <div class="indented_cell">
        <input type="text" name="end" size="11" maxlength="10" value="{$property.end}"> (dd-mm-yyyy)
      </div>

      <div class="whitebox">Status</div>

      <div class="indented_cell">
        <input type="radio" name="active" value="1" {$property.active_selected}>Active
        &nbsp;
        <input type="radio" name="active" value="0" {$property.inactive_selected}>Inactive
      </div>

      <div class="whitebox">Survey Access</div>

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

      <div class="whitebox">Edit Password</div>

      <div class="indented_cell">
        <input type="text" name="edit_password" value="{$property.edit_password}">
      </div>

      <div class="whitebox">Clear Results</div>

      <div class="indented_cell">
        <input type="checkbox" name="clear_answers" value="1">
        Check this box to clear current answers to this survey.
        Answers will be cleared when you press Save Changes below.
      </div>

      <div class="whitebox">Delete Survey</div>

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