<table width="70%" align="center" cellpadding="0" cellspacing="0">
  <tr class="grayboxheader">
    <td width="14"><img src="{$conf.images_html}/box_left.gif" border="0" width="14"></td>
    <td background="{$conf.images_html}/box_bg.gif">SPSS Export File Parameters</td>
    <td width="14"><img src="{$conf.images_html}/box_right.gif" border="0" width="14"></td>
  </tr>
</table>
<table width="70%" align="center" class="bordered_table">
  <tr>
    <td>
		<div class="whitebox">
			Exporting Results for Survey #{$survey.sid}: {$survey.name}
		</div>
		<form method="GET" action="results_spss.php">
			<input type="hidden" name="sid" value="{$survey.sid}">
			<input type="hidden" name="export_type" value="{$export_type}">
			<div class="indented_cell">
				Separator: <input type="textbox" name="sep" value="{$parameters.separator|escape}" size="2">
			</div>
			<div class="indented_cell">
				Text delimiter: <input type="textbox" name="del" value="{$parameters.delimiter|escape}" size="2" maxlength="1">
			</div>
			<div class="indented_cell">
				Alternative text delimiter: <input type="textbox" name="altdel" value="{$parameters.altdelimiter|escape}" size="2" maxlength="1">
			</div>
			<div class="indented_cell">
				Do you want to export text fields: <input type="radio" name="text" value="1" checked>yes <input type="radio" name="text" value="0">no
			</div>
			<div class="indented_cell">
				Substitute new line caracters in text with: <input type="textbox" name="altcr" value="<BR />" size="10" maxlength="10">
			</div>
			<div class="indented_cell">
				Order by: <input type="radio" name="order" value="1" checked>Question sequence order &nbsp;<input type="radio" name="order" value="2">Question creation order (ID)
			</div>

			<div align="center">
				<input type="submit" value="Export">
			</div>
		</form>
		<div style="text-align:center">
			[ <a href="{$conf.html}/index.php">Main</a> ]
		</div>
    </td>
  </tr>
</table>