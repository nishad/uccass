<div class="indented_cell">
  <table border="0" width="100%">
    <tr>
      <td>
        <table border="0" cellspacing="0" cellpadding="5">
          <tr>
            <td width="150">&nbsp;</td>
            {section name="mh" loop=$q.num_values show=TRUE}
              <td width="150" align="center">{$q.value[mh]}</td>
            {/section}
          </tr>