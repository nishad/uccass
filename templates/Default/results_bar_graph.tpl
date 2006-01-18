              {section name="a" loop=$qdata.answer.value}
                <tr>
                  <td>{$qdata.answer.value[a]} ({$qdata.answer.numeric_value[a]})</td>
                  <td> - </td>
                  <td>{$qdata.count[a]}</td>
                  <td>
                    {section name="left_right_img" loop=1 show=$qdata.show.left_right_image[a]}
                      <img src="{$conf.images_html}/{$qdata.answer.left_image[a]}" alt=""><img src="{$conf.images_html}/{$qdata.answer.image[a]}" height="{$qdata.height[a]}" width="{$qdata.width[a]}" alt="{$qdata.percent[a]}%"><img src="{$conf.images_html}/{$qdata.answer.right_image[a]}" alt="">
                    {/section}

                    {section name="left_img" loop=1 show=$qdata.show.left_image[a]}
                      <img src="{$conf.images_html}/{$qdata.answer.left_image[a]}" alt=""><img src="{$conf.images_html}/{$qdata.answer.image[a]}" height="{$qdata.height[a]}" width="{$qdata.width[a]}" alt="{$qdata.percent[a]}%">
                    {/section}

                    {section name="right_img" loop=1 show=$qdata.show.right_image[a]}
                      <img src="{$conf.images_html}/{$qdata.answer.image[a]}" height="{$qdata.height[a]}" width="{$qdata.width[a]}" alt="{$qdata.percent[a]}%"><img src="{$conf.images_html}/{$qdata.answer.right_image[a]}" alt="">
                    {/section}

                    {section name="middle_img" loop=1 show=$qdata.show.middle_image[a]}
                      <img src="{$conf.images_html}/{$qdata.answer.image[a]}" height="{$qdata.height[a]}" width="{$qdata.width[a]}" alt="{$qdata.percent[a]}%">
                    {/section}
                    &nbsp;{$qdata.percent[a]}%
                  </td>
                </tr>
              {/section}