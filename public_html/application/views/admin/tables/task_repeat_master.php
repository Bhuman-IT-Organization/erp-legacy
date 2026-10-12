<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'name',
    'repeat_every',
    'recurring_type',
    'sort_order',
    'active',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'task_repeat_master';

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], []);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $dataAttrs = _attributes_to_string([
        'onclick'             => 'edit_repeat_option(this,' . e($aRow['id']) . '); return false',
        'data-name'           => e($aRow['name']),
        'data-repeat-every'   => e($aRow['repeat_every']),
        'data-recurring-type' => e($aRow['recurring_type']),
        'data-sort-order'     => e($aRow['sort_order']),
        'data-active'         => e($aRow['active']),
    ]);

    $row[] = $aRow['id'];

    $row[] = '<a href="#" class="tw-font-medium" ' . $dataAttrs . '>' . e($aRow['name']) . '</a>';

    $row[] = e($aRow['repeat_every']);

    $row[] = e(ucfirst($aRow['recurring_type']));

    $row[] = e($aRow['sort_order']);

    $row[] = $aRow['active'] == 1
        ? '<span class="label label-success">' . _l('active') . '</span>'
        : '<span class="label label-default">' . _l('inactive') . '</span>';

    $options = '<div class="tw-flex tw-items-center tw-space-x-2">';

    $options .= '<a href="#" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700" ' . $dataAttrs . '>
        <i class="fa-regular fa-pen-to-square fa-lg"></i>
    </a>';

    $options .= '<a href="' . admin_url('task_masters/delete_repeat/' . $aRow['id']) . '"
        class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
        <i class="fa-regular fa-trash-can fa-lg"></i>
    </a>';

    $options .= '</div>';

    $row[] = $options;

    $output['aaData'][] = $row;
}
