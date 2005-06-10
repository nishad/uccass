      <form method="POST" action="{$conf.html}/edit_answer.php">
        <input type="hidden" name="aid" value="{$answer.aid}">
        <input type="hidden" name="sid" value="{$answer.sid}">

        {* SUCCESS MESSAGE *}
          {section name="success" loop=1 show=$show.success}
            <div class="message">
              Answer successfully edited.
            </div>
          {/section}
        {* / SUCCESS MESSAGE *}


        {* WARNING MESSAGE *}
          {section name="warning" loop=1 show=$show.warning}
            <div class="error">
              WARNING: This answer type is being used for {$num_usedanswers} question{$usedanswers_plural} in this survey. Changing
              the values will affect all questions using this answer type. Changing the answer type will
              DELETE all existing results for the old type! Deleting an answer value from the list
              below will DELETE all results using that value! Use extreme caution when editing answer types that are in use to prevent
              data loss.
            </div>
          {/section}
        {* / WARNING MESSAGE *}

        {section name="delete" loop=1 show=$show.delete}
          <div class="whitebox">
            Delete Answer
          </div>

          <div class="indented_cell">
            Check box and press button to delete answer.
            <br />
            <input type="checkbox" name="delete" value="1">
            &nbsp;&nbsp;
            <input type="submit" name="delete_submit" value="Delete Answer">
          </div>
        {/section}

        <div class="whitebox">
          Answer Name
        </div>

        <div class="indented_cell">
          The Answer Name will appear in the drop
          downs used to select the type of answer you want. It should be short
          and describe the possible answers for this type. The Label
          field is a longer text area where you can give a description of this question
          and possibly explain how to answer (i.e. <em>Check all that apply</em>) The
          Label will be visible to users when they take the survey. Use it
          to explain the question or answers, otherwise leave it blank.

          <br />

          <input type="text" name="name" size="40" value="{$answer.name}">
        </div>

        <div class="whitebox">
          Label
        </div>

        <div class="indented_cell">
          <input type="text" name="label" size="60" value="{$answer.label}">
        </div>

        <div class="whitebox">
          Answer Type
        </div>

        <div class="indented_cell">
          <select name="type" size="1">
            <option value="T" {$answer.selected.T}>T - Textbox, large</option>
            <option value="S" {$answer.selected.S}>S - Textbox, small</option>
            <option value="MS" {$answer.selected.MS}>MS - Multiple Choice, Single Answer</option>
            <option value="MM" {$answer.selected.MM}>MM - Multiple Choice, Multiple Answers</option>
            <!-- <option value="NUM" {$answer.selected.NUM}>NUM - Numeric Answer</option>
            <option value="DATE" {$answer.selected.DATE}>DATE - Date/Time Answer</option> -->
            <option value="N" {$answer.selected.N}>N - No Answer Values</option>
          </select>

          <ul>
            <li>T = Large text area, unlimited answer size.</li>
            <li>S = Sentence text box, 255 characters max.</li>
            <li>MS = Multiple choice, one possible answer can be chosen.</li>
            <li>MM = Multiple choice, more than one possible answer can be chosen.</li>
            <!-- <li>NUM = Numeric answer only. Use the Label above to tell users about any range that may be required.</li>
            <li>DATE = Date and/or Time answer only. Use the Label above to tell the users about any range that may be required and the format of the date and/or time that's required.</li> -->
            <li>N = No answer choices. Instead of a question, this will be more of a label with no choices below it. Useful
                for setting up a sequence of questions, for example: "<em>For the following 5 questions, choose the most likely answer:</em>"</li>
          </ul>
        </div>

        <div style="text-align:center">
          <input type="submit" name="submit" value="Edit Answer">
        </div>

        <div class="whitebox">
          Answer Values (MS and MM Answer Types only)
        </div>

        <div class="indented_cell">
          You must supply a list of possible answers if you selected MS or MM for an Answer Type.
          List one answer per text box in the boxes below. Use the button at the bottom of the boxes to add more
          boxes for more answers. The order you list the answers here is the order they will be presented in the
          surveys.

          <br />

          You can use the Group column to group certain answers together when viewing results.
          For example, if you have <em>Strongly Agree</em> and <em>Agree</em> as possible answers and you want all of
          the questions answered with those two options to be added together, you would give them the same group number.
          You will still have the option to view results without the answers grouped, also. If left blank, Group
          will be assigned automatically. You can use a number between 1 and 99 to designate groups.
        </div>


        <table border="0" cellspacing="0" width="100%">
          <tr class="whitebox" style="text-align:center">
            <td>Num</td>
            <td>Answer Value</td>
            <td>Group</td>
            <td>Bar Graph Image</td>
          </tr>
          {section name="i" loop=$answer.num_answers show=TRUE}
            <tr style="background-color:{cycle values="#F9F9F9,#FFFFFF"};text-align:center">
              <td>{$smarty.section.i.iteration}.</td>
              <td><input type="text" name="value[{$answer.avid[i]}]" value="{$answer.value[i]}" size="40" maxlength="255"></td>
              <td><input type="text" name="group_id[{$answer.avid[i]}]" value="{$answer.group_id[i]}" size="3" maxlength="2"></td>
              <td>
                <select name="image[{$answer.avid[i]}]" size="1">
                  {section name="img" loop=$answer.allowable_images show=TRUE}
                    <option value="{$answer.allowable_images[img]}"{$answer.image_selected[i][img]}>{$answer.allowable_images[img]}</option>
                  {/section}
                </select>
              </td>
            </tr>
          {/section}
        </table>

        <div>
          {section name="add_answer" loop=1 show=$answer.show_add_answers}
            Add
            <select name="add_answer_num" size="1">
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="5">5</option>
              <option value="10">10</option>
              <option value="20">20</option>
            </select>
            more answer and group boxes.
            <input type="submit" name="add_answers_submit" value="Add">
            <input type="hidden" name="num_answers" value="{$answer.num_answers}">
          {/section}
          <br />
        </div>
<!--
        <div class="whitebox">
          Numeric Range (NUM Answer Type only)
        </div>

        <div class="indented_cell">
          You can optionally choose to include decimal point numbers such as 5.25 by checking the following box, otherwise
          only whole numbers, 1, 2, 3, etc will be accepted. You can also optionally set a range of numbers to be accepted.
          You can leave both ranges blank for no range at all or leave one of the ranges blank for no upper or lower range.
          <br />
          <input type="checkbox" name="allow_decimal" value="1"> Allow Decimals.
          Minimum Allowed Value: <input type="text" name="min_num" value="" size="5">
          Maximum Allowed Value: <input type="text" name="max_num" value="" size="5">
          <br />
        </div>

        <div class="whitebox">
          Date Range (DATE Answer Type Only)
        </div>

        <div class="indented_cell">
          You can optionally choose to require a time entry along with the date by checking the following box. If this box
          is not checked, only a date will be required. Click here for allowable date and time formats. You can also choose
          to specify a minimum and maximum allowed date. You can leave both date ranges blank for no range at all or leave one
          of the ranges blank for no upper or lower range.
          <br />
          <input type="checkbox" name="require_time" value="1"> Require Time
          Minimum Allowed Date: <input type="text" name="min_date" value="" size="12">
          Maximum Allowed Date: <input type="text" name="max_date" value="" size="12">
        </div>
-->
        <br />

        <div style="text-align:center">
          <input type="submit" name="submit" value="Edit Answer">
        </div>
      </form>