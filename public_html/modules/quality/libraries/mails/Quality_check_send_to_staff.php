<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quality_check_send_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $quality_check;
    
    protected $staff;

    public $slug = 'quality-check-send-to-staff';

    public function __construct($quality_check, $staff, $cc = '')
    {
        parent::__construct();

        $this->quality_check = $quality_check;
        $this->staff = $staff;
        $this->cc      = $cc;

        // For SMS and merge fields for email
        $this->set_merge_fields('quality_check_merge_fields', $this->quality_check);
        if($this->staff){
            $this->set_merge_fields('staff_merge_fields', $this->staff->staffid);
        }
    }

    public function build()
    {
        $this->to($this->staff->email);
    }
}
