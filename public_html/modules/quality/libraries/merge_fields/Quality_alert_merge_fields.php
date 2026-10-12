<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quality_alert_merge_fields extends App_merge_fields
{
    public function build()
    {

        return [
                [
                    'name'      => _l('quality_alert_by_staff'),
                    'key'       => '{alert_by_staff}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_code'),
                    'key'       => '{quality_alert_number}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_alert_link'),
                    'key'       => '{quality_alert_link}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_name'),
                    'key'       => '{quality_name}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_operation'),
                    'key'       => '{operation_type}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],
                
                [
                    'name'      => _l('quality_status'),
                    'key'       => '{quality_status}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_description'),
                    'key'       => '{quality_description}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_root_cause'),
                    'key'       => '{quality_root_cause}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],
                [
                    'name'      => _l('quality_priority'),
                    'key'       => '{quality_priority}',
                    'available' => [
                    ],
                    'templates' => [
                        'quality-alert-send-to-staff',
                    ],
                ],    
                
            ];
    }

    /**
     * Merge fields for quality alert
     * @param  mixed $quality alert _id quality_alert id
     * @return array
     */
    public function format($quality_alert)
    {
        $fields = [];
        if(is_numeric($quality_alert)){
            $this->ci->db->where('id', $quality_alert);
            $quality_alert = $this->ci->db->get(db_prefix().'quality_alerts')->row();
        }

        if (!$quality_alert) {
            return $fields;
        }

        $alert_by_staff = '';
        $quality_team = get_quality_team_name($quality_alert->team_id, false);
        if($quality_team){
            $alert_by_staff = get_staff_full_name($quality_team->team_member);
        }

        $quality_alert_url         = admin_url('quality/quality_alert_detail/' . $quality_alert->id . '?tab=detail/' . $quality_alert->hash);
        if($quality_alert->operation == 'receipts'){
            $operation_id = $quality_alert->goods_receipt_id;
        } elseif($quality_alert->operation == 'internal_transfers'){
            $operation_id = $quality_alert->internal_delivery_id;
        } elseif($quality_alert->operation == 'delivery_orders'){
            $operation_id = $quality_alert->goods_delivery_id;
        } elseif($quality_alert->operation == 'manufacturing'){
            $operation_id = $quality_alert->work_order_id;
        }
        $operation_detail = quality_get_operation_detail($quality_alert->operation, $operation_id);

        $fields['{alert_by_staff}'] = $alert_by_staff;
        $fields['{quality_alert_number}']       = format_quality_alert_number($quality_alert->id);
        $fields['{quality_alert_link}']       = '<a href='.$quality_alert_url.' target="_blank">'.format_quality_alert_number($quality_alert->id).'</a>';
        $fields['{quality_name}'] = $quality_alert->name;
        $fields['{operation_type}']         = _l('quality_'.$quality_alert->operation).': '.$operation_detail;
        $fields['{quality_status}']          = render_quality_alert_status_html($quality_alert->id, '', $quality_alert->status);
        $fields['{quality_description}']          = $quality_alert->description;
        $fields['{quality_root_cause}']          = $quality_alert->root_cause;
        $fields['{quality_priority}']          = $quality_alert->priority;


        return hooks()->apply_filters('quality_alert_merge_fields', $fields, [
        'id'       => $quality_alert->id,
        'quality_alert' => $quality_alert,
     ]);
    }
}
