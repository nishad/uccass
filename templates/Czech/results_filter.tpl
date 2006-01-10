        <br />

        {section name="filter_text" loop=1 show=$survey.filter_text}
          <br><span class="message">Notice: This result page shows the results filtered by the following questions:</span><br>
          <span style="font-size:x-small">{$survey.filter_text}</span>
        {/section}

        <br />

        <div>
          <select name="action" size="1">
            <option value="filter">Filter On Checked Questions</option>
            {section name="clear_filter" loop=1 show=$survey.show.clear_filter}
              <option value="clear_filter">Clear Filter</option>
            {/section}

            {section name="hide_show_questions" loop=1 show=$survey.hide_show_questions}
              <option value="hide_questions">Hide Checked Questions</option>
              <option value="show_questions">Show Only Checked Questions</option>
            {/section}

            {section name="show_all_questions" loop=1 show=$survey.show_all_questions}
              <option value="show_all_questions">Show All Questions</option>
            {/section}
          </select>
          <input type="submit" name="results_action" value="Go">
          <a href="{$conf.html}/docs/index.html#filter_results">[?]</a>
          <span style="margin-left:20px">
            <select name="report_id" size="1">
              {section name="r" loop=$survey.report_id}
                <option value="{$survey.report_id[r]}">{$survey.report_name[r]}</option>
              {/section}
            </select>
            <input type="submit" name="report_submit" value="Load Custom Report">
          </span>
        </div>