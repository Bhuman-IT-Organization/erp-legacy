<?php
defined('BASEPATH') or exit('No direct script access allowed');

render_datatable(
    array(
        _l('id'),
        [
            'name'  => _l('quality_code'),
            'th_attrs' => [
                'style' => 'min-width:150px',
            ],
        ],
        [
            'name'  => _l('quality_name'),
            'th_attrs' => [
                'style' => 'min-width:200px',
            ],
        ],

        _l('quality_item'),
        [
            'name'  => _l('quality_operation'),
            'th_attrs' => [
                'style' => 'min-width:100px',
            ],
        ],
        [
            'name'  => _l('quality_control_per'),
            'th_attrs' => [
                'style' => 'min-width:150px',
            ],
        ],
        [
            'name'  => _l('quality_percentage'),
            'th_attrs' => [
                'style' => 'min-width:50px',
            ],
        ],
        _l('quality_control_point'),
        _l('quality_type'),
        _l('quality_team'),
        _l('quality_status'),
        _l('quality_created_by'),
        _l('quality_datecreated'),
    ),'quality_check_table'
);
?>