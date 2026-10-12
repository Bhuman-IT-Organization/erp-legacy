<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cost_centers extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cost_centers_model');
    }

    public function index()
    {
        $data['title'] = _l('cost_centers');
        $data['cost_centers'] = $this->cost_centers_model->get();
        $this->load->view('admin/cost_centers/manage', $data);
    }

    public function create()
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $id = $this->cost_centers_model->add($data);
            if ($id) {
                set_alert('success', _l('added_successfully', _l('cost_center')));
            }
            redirect(admin_url('cost_centers'));
        }
        $data['title'] = _l('add_cost_center');
        $this->load->view('admin/cost_centers/form', $data);
    }

    public function edit($id)
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $success = $this->cost_centers_model->update($id, $data);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('cost_center')));
            }
            redirect(admin_url('cost_centers'));
        }
        $data['title'] = _l('edit_cost_center');
        $data['cost_center'] = $this->cost_centers_model->get($id);
        if (!$data['cost_center']) {
            show_404();
        }
        $this->load->view('admin/cost_centers/form', $data);
    }

    public function delete($id)
    {
        $success = $this->cost_centers_model->delete($id);
        if ($success) {
            set_alert('success', _l('deleted', _l('cost_center')));
        }
        redirect(admin_url('cost_centers'));
    }
}
