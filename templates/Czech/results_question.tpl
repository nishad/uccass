          <div class="whitebox">
            {section name="box" loop=1 show=$survey.hide_show_questions}
              <input type="checkbox" name="select_qid[]" value="{$qdata.qid}">&nbsp;
            {/section}

            {section name="qn" loop=1 show=$qdata.question_num}
              {$qdata.question_num}.&nbsp;
            {/section}

            {section name="req" loop=1 show=$qdata.num_required}
              {$data.survey.required}
            {/section}

            {$qdata.question}
          </div>