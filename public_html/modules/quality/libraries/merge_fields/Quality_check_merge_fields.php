<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quality_check_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => _l('quality_check_by_staff'),
                    'key'       => '{check_by_staff}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_code'),
                    'key'       => '{quality_check_number}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_check_link'),
                    'key'       => '{quality_check_link}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_name'),
                    'key'       => '{quality_name}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_operation'),
                    'key'       => '{operation_type}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_control_per'),
                    'key'       => '{quality_control_per}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_partial_transfer_test'),
                    'key'       => '{quality_partial_transfer_test}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_type'),
                    'key'       => '{quality_type}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_status'),
                    'key'       => '{quality_status}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_instructions'),
                    'key'       => '{quality_instructions}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-check-send-to-staff',
                    ],
                ],
                
                
            ];
    }

    /**
     * Merge fields for quality check
     * @param  mixed $quality check _id quality_check id
     * @return array
     */
    public function format($quality_check)
    {
        $fields = [];
        $check_by_staff = '';
        if(is_numeric($quality_check)){
            $this->ci->db->where('id', $quality_check);
            $quality_check = $this->ci->db->get(db_prefix().'quality_checks')->row();

        }

        if (!$quality_check) {
            return $fields;
        }
        $quality_team = get_quality_team_name($quality_check->team_id, false);
        if($quality_team){
            $check_by_staff = get_staff_full_name($quality_team->team_member);
        }

        $quality_check_url         = admin_url('quality/quality_check_detail/' . $quality_check->id . '?tab=detail/' . $quality_check->hash);
        if($quality_check->operation == 'receipts'){
            $operation_id = $quality_check->goods_receipt_id;
        } elseif($quality_check->operation == 'internal_transfers'){
            $operation_id = $quality_check->internal_delivery_id;
        } elseif($quality_check->operation == 'delivery_orders'){
            $operation_id = $quality_check->goods_delivery_id;
        } elseif($quality_check->operation == 'manufacturing'){
            $operation_id = $quality_check->work_order_id;
        }
        $operation_detail = quality_get_operation_detail($quality_check->operation, $operation_id);

        $fields['{check_by_staff}'] = $check_by_staff;
        $fields['{quality_check_number}']       = format_quality_check_number($quality_check->id);
        $fields['{quality_check_link}']       = '<a href='.$quality_check_url.' target="_blank">'.format_quality_check_number($quality_check->id).'</a>';
        $fields['{quality_name}'] = $quality_check->name;
        $fields['{operation_type}']         = _l('quality_'.$quality_check->operation).': '.$operation_detail;
        $fields['{quality_control_per}']       = _l('quality_'.$quality_check->control_per);
        if($quality_check->control_per == 'quantity' && $quality_check->partial_transfer_test == 1 ){
            $fields['{quality_partial_transfer_test}']          = $quality_check->percentage;
        }else{
            $fields['{quality_partial_transfer_test}']          = '';
        }

        $fields['{quality_type}']          = _l('quality_'.$quality_check->type);
        $fields['{quality_status}']          = render_quality_check_status_html($quality_check->id, '', $quality_check->status);
        $fields['{quality_instructions}']          = $quality_check->instructions;


        return hooks()->apply_filters('quality_check_merge_fields', $fields, [
        'id'       => $quality_check->id,
        'quality_check' => $quality_check,
     ]);
    }
}
