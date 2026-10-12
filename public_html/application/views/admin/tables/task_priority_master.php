<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'name',
    'color',
    'sort_order',
    'active',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'task_priority_master';

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], []);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $dataAttrs = _attributes_to_string([
        'onclick'         => 'edit_priority_option(this,' . e($aRow['id']) . '); return false',
        'data-name'       => e($aRow['name']),
        'data-color'      => e($aRow['color']),
        'data-sort-order' => e($aRow['sort_order']),
        'data-active'     => e($aRow['active']),
    ]);

    $row[] = $aRow['id'];

    $row[] = '<a href="#" class="tw-font-medium" ' . $dataAttrs . '>' . e($aRow['name']) . '</a>';

    // Preview the colour exactly as the task list renders it
    $row[] = '<span class="label" style="color:' . e($aRow['color']) . ';border:1px solid ' . e($aRow['color']) . ';">'
        . e($aRow['name']) . '</span> <span class="tw-text-neutral-500">' . e($aRow['color']) . '</span>';

    $row[] = e($aRow['sort_order']);

    $row[] = $aRow['active'] == 1
        ? '<span class="label label-success">' . _l('active') . '</span>'
        : '<span class="label label-default">' . _l('inactive') . '</span>';

    $options = '<div class="tw-flex tw-items-center tw-space-x-2">';

    $options .= '<a href="#" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" ' . $dataAttrs . '>
        <i class="fa-regular fa-pen-to-square fa-lg"></i>
    </a>';

    $options .= '<a href="' . admin_url('task_masters/delete_priority/' . $aRow['id']) . '"
        class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
        <i class="fa-regular fa-trash-can fa-lg"></i>
    </a>';

    $options .= '</div>';

    $row[] = $options;

    $output['aaData'][] = $row;
}
