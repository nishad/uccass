<?php

//======================================================
// Copyright (C) 2004 John W. Holmes, All Rights Reserved
//
// This file is part of the Unit Command Climate
// Assessment and Survey System (UCCASS)
//
// UCCASS is free software; you can redistribute it and/or
// modify it under the terms of the Affero General Public License as
// published by Affero, Inc.; either version 1 of the License, or
// (at your option) any later version.
//
// http://www.affero.org/oagpl.html
//
// UCCASS is distributed in the hope that it will be
// useful, but WITHOUT ANY WARRANTY; without even the implied warranty
// of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// Affero General Public License for more details.
//======================================================

class UCCASS_Pager extends UCCASS_Main {

    var $show = array();
    var $data = array();

    function UCCASS_Pager($page=1, $total_pages=1, $before_after, $link_page='') {

        $this->load_configuration();

        // Set defaults
        $this->show['current_page'] = TRUE;
        $this->show['total_pages'] =TRUE;
        $this->show['record_count'] = FALSE;
        $this->show['first'] = TRUE;
        $this->show['last'] = TRUE;
        $this->show['prev'] = TRUE;
        $this->show['next'] = TRUE;
        $this->show['prev_elipse'] = TRUE;
        $this->show['next_elipse'] = TRUE;

        $total_pages = (int)$total_pages;
        if($total_pages <= 0) {
            $total_pages = 1;
        }
        $this->data['total_pages'] = $total_pages;

        $page = (int)$page;
        if($page <= 0) {
            $page = 1;
        } elseif($page > $total_pages) {
            $page = $total_pages;
        }
        $this->data['page'] = $page;

        $this->data['link_page'] = $link_page;

        $before_after = (int)$before_after;
        $this->data['before_after'] = $before_after;

        return TRUE;
    }

    function getPager() {

        if($this->show['prev'] && $this->data['page'] == 1) {
            $this->show['prev'] = FALSE;
            $this->show['first'] = FALSE;
        }

        if($this->show['next'] && $this->data['page'] == $this->data['total_pages']) {
            $this->show['next'] = FALSE;
            $this->show['last'] = FALSE;
        }

        $start_page = max(1, $this->data['page'] - $this->data['before_after']);
        $end_page = min($this->data['total_pages'], $this->data['page'] + $this->data['before_after']);
        $this->data['display_count'] = $end_page - $start_page + 1;
        $this->data['prev_page'] = max(1, $this->data['page'] - 1);
        $this->data['next_page'] = min($this->data['total_pages'], $this->data['page'] + 1);

        if($this->show['prev_elipse'] && $start_page == 1) {
            $this->show['prev_elipse'] = FALSE;
        }

        if($this->show['next_elipse'] && $end_page == $this->data['total_pages']) {
            $this->show['next_elipse'] = FALSE;
        }

        $counter = 0;
        for($x = $start_page; $x <= $end_page; $x++) {
            if($x != $this->data['page']) {
                $this->data['url'][$counter] = TRUE;
            }
            $this->data['page_num'][$counter] = $x;
            $counter++;
        }

        $this->smarty->assign_by_ref('data',$this->data);
        $this->smarty->assign_by_ref('show',$this->show);
        return $this->smarty->Fetch($this->CONF['template'].'/pager.tpl');
    }

    function showCurrentPage($bool) {
        $this->show['current_page'] = $bool;
    }

    function showTotalPages($bool) {
        $this->show['total_pages'] = $bool;
    }

    function showFirst($bool) {
        $this->show['first'] = $bool;
    }

    function showLast($bool) {
        $this->show['last'] = $bool;
    }

    function showPrev($bool) {
        $this->show['prev'] = $bool;
    }

    function showNext($bool) {
        $this->show['next'] = $bool;
    }

    function showPrevElipse($bool) {
        $this->show['prev_elipse'] = $bool;
    }

    function showNextElipse($bool) {
        $this->show['next_elipse'] = $bool;
    }

    function showRecordCount($bool, $per_page=0, $total_records=0) {
        if($bool) {
            $per_page = (int)$per_page;

            $this->show['record_count'] = TRUE;
            $this->data['total_records'] = (int)$total_records;

            $this->data['start_record'] = ($this->data['page'] - 1) * $per_page + 1;

            $this->data['end_record'] = $this->data['start_record'] + $per_page - 1;
            if($this->data['end_record'] >= $this->data['total_records']) {
                $remainder = $this->data['total_records'] % $per_page;
                if($remainder > 1) {
                    $this->data['end_record'] = $this->data['start_record'] + $remainder - 1;
                } else {
                    $this->data['end_record'] = 0;
                }
            }
        } else {
            $this->show['record_count'] = FALSE;
        }
    }


    function setData($key, $value) {
        if(!empty($key) && !empty($value)) {
            $this->data[$key] = $value;
            return TRUE;
        }
        return FALSE;
    }

}

?>