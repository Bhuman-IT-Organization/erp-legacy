<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quality_alert_send_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $quality_alert;
    
    protected $staff;

    public $slug = 'quality-alert-send-to-staff';

    public function __construct($quality_alert, $staff, $cc = '')
    {
        parent::__construct();

        $this->quality_alert = $quality_alert;
        $this->staff = $staff;
        $this->cc      = $cc;

        // For SMS and merge fields for email
        $this->set_merge_fields('quality_alert_merge_fields', $this->quality_alert);
        if($this->staff){
            $this->set_merge_fields('staff_merge_fields', $this->staff->staffid);
        }
    }

    public function build()
    {
        $this->to($this->staff->email);
    }
}
