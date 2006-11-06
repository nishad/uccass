  {section name="show_current_page" loop=1 show=$show.current_page}
    Page {$data.page}
    {section name="show_total_pages" loop=1 show=$show.total_pages}
      of {$data.total_pages}
    {/section}
  {/section}
  {section name="show_record_count" loop=1 show=$show.record_count}
    (Record{section name="plural" loop=1 show=$data.end_record}s {/section}
    {$data.start_record}
    {section name="end_record" loop=1 show=$data.end_record}
      - {$data.end_record}
    {/section}
    of {$data.total_records})
  {/section}

  [
  {section name="show_first_last" loop=1 show=$show.first}
    <a href="{$conf.html}/{$data.link_page}?sid={$data.sid}&page=1">First</a>
  {sectionelse}
    First
  {/section}
  ]

  [
  {section name="show_prev_next" loop=1 show=$show.prev}
    <a href="{$conf.html}/{$data.link_page}?sid={$data.sid}&page={$data.prev_page}">Prev</a>
  {sectionelse}
    Prev
  {/section}
  ]

  {section name="prev_elipse" loop=1 show=$show.prev_elipse}
    ...
  {/section}

  {section name="link" loop=$data.display_count show=TRUE}
    [
    {section name="url" loop=1 show=$data.url[link]}
      <a href="{$conf.html}/{$data.link_page}?sid={$data.sid}&page={$data.page_num[link]}">{$data.page_num[link]}</a>
    {sectionelse}
      <strong>{$data.page_num[link]}</strong>
    {/section}
    ]
  {/section}

  {section name="next_elipse" loop=1 show=$show.next_elipse}
    ...
  {/section}

  [
  {section name="show_prev_next" loop=1 show=$show.next}
    <a href="{$conf.html}/{$data.link_page}?sid={$data.sid}&page={$data.next_page}">Next</a>
  {sectionelse}
    Next
  {/section}
  ]

  [
  {section name="show_first_last" loop=1 show=$show.last}
    <a href="{$conf.html}/{$data.link_page}?sid={$data.sid}&page={$data.total_pages}">Last</a>
  {sectionelse}
    Last
  {/section}
  ]