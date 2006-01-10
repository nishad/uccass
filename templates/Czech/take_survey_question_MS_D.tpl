<div class="whitebox">
  {$q.question_num}. {$q.required_text} {$q.question}
</div>

<div class="indented_cell">
  {section name="req_label" loop=1 show=$q.req_label}
    <div class="example">
      (
      	{if $q.num_required eq 1}
			{$q.num_required} odpověď požadována
		{elseif $q.num_required lte 4}
			{$q.num_required} odpovědi požadovány
		{else}
			{$q.num_required} odpovědí požadováno
		{/if}
		)
    </div>
  {/section}

  {section name="label" loop=1 show=$q.label}
    <div class="example">{$q.label}</div>
  {/section}

  {section name="na" loop=$q.num_answers show=TRUE}
    <select name="answer[{$q.qid}][{$smarty.section.na.index}]" size="1">
      <option value=""></option>
      {section name="ms" loop=$q.num_values show=TRUE}
        <option value="{$q.avid[ms]}"{$q.selected[na][ms]}>{$q.value[ms]}</option>
      {/section}
    </select>
  {/section}
</div>