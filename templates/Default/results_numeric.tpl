              {section name="a" loop=$qdata.answer.value}
                <tr>
                  <td>{$qdata.answer.value[a]} ({$qdata.answer.numeric_value[a]})</td>
                  <td> - </td>
                  <td>{$qdata.count[a]}</td>
                  <td>{$qdata.percent[a]}%</td>
                </tr>
              {/section}