<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * CRUD for the Task "Repeat Every" and "Related To" master lists.
 *
 * @property-read Task_masters_model $task_masters_model
 */
class Task_masters extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('task_masters_model');

        if (!is_admin()) {
            access_denied('Task Masters');
        }
    }

    public function index()
    {
        redirect(admin_url('task_masters/repeat'));
    }

    // =========================================================
    // Repeat Every
    // =========================================================

    public function repeat()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('task_repeat_master');
        }

        $data['title'] = _l('task_repeat_master');
        $this->load->view('admin/task_masters/manage_repeat', $data);
    }

    public function repeat_option($id = '')
    {
        if (!$this->input->post()) {
            return;
        }

        $data    = $this->input->post();
        $message = '';
        $success = false;

        if (!$this->input->post('id')) {
            $insert_id = $this->task_masters_model->add_repeat($data);
            if ($insert_id) {
                $success = true;
                $message = _l('added_successfully', _l('task_repeat_option'));
            }
        } else {
            $id = $data['id'];
            unset($data['id']);
            $success = $this->task_masters_model->update_repeat($data, $id);
            if ($success) {
                $message = _l('updated_successfully', _l('task_repeat_option'));
            }
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
        die;
    }

    public function delete_repeat($id)
    {
        if (!$id) {
            redirect(admin_url('task_masters/repeat'));
        }

        $response = $this->task_masters_model->delete_repeat($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('task_repeat_option')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('task_repeat_option')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('task_repeat_option')));
        }

        redirect(admin_url('task_masters/repeat'));
    }

    // =========================================================
    // Related To
    // =========================================================

    public function related()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('task_related_master');
        }

        $supported = task_supported_relation_types();

        // Keys already stored — these can't be added again (one row per type)
        $used = array_column($this->task_masters_model->get_related(), 'key_name');

        $data['supported_types'] = $supported;
        $data['used_keys']       = $used;
        $data['available_count'] = count(array_diff(array_keys($supported), $used));
        $data['title']           = _l('task_related_master');

        $this->load->view('admin/task_masters/manage_related', $data);
    }

    public function related_option($id = '')
    {
        if (!$this->input->post()) {
            return;
        }

        $data    = $this->input->post();
        $message = '';
        $success = false;

        $postedId = $this->input->post('id');
        $keyName  = $this->input->post('key_name');

        // Guard: key must be one the application can actually resolve
        if (!array_key_exists($keyName, task_supported_relation_types())) {
            echo json_encode([
                'success' => false,
                'message' => _l('task_related_key_not_supported'),
            ]);
            die;
        }

        // Guard: one row per relation type — avoids hitting the UNIQUE index
        if ($this->task_masters_model->key_name_exists($keyName, $postedId)) {
            echo json_encode([
                'success' => false,
                'message' => _l('task_related_key_already_exists'),
            ]);
            die;
        }

        if (!$postedId) {
            $insert_id = $this->task_masters_model->add_related($data);
            if ($insert_id) {
                $success = true;
                $message = _l('added_successfully', _l('task_related_option'));
            }
        } else {
            $id = $postedId;
            unset($data['id']);
            $success = $this->task_masters_model->update_related($data, $id);
            if ($success) {
                $message = _l('updated_successfully', _l('task_related_option'));
            } else {
                // Nothing changed — still a successful outcome for the user
                $success = true;
                $message = _l('updated_successfully', _l('task_related_option'));
            }
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
        die;
    }

    public function delete_related($id)
    {
        if (!$id) {
            redirect(admin_url('task_masters/related'));
        }

        $response = $this->task_masters_model->delete_related($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('task_related_option')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('task_related_option')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('task_related_option')));
        }

        redirect(admin_url('task_masters/related'));
    }

    // =========================================================
    // Priority
    // =========================================================

    public function priority()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('task_priority_master');
        }

        $data['title'] = _l('task_priority_master');
        $this->load->view('admin/task_masters/manage_priority', $data);
    }

    public function priority_option($id = '')
    {
        if (!$this->input->post()) {
            return;
        }

        $data     = $this->input->post();
        $message  = '';
        $success  = false;
        $postedId = $this->input->post('id');

        // Guard: UNIQUE index on name would otherwise raise a raw DB error
        if ($this->task_masters_model->priority_name_exists($this->input->post('name'), $postedId)) {
            echo json_encode([
                'success' => false,
                'message' => _l('task_priority_name_already_exists'),
            ]);
            die;
        }

        if (!$postedId) {
            $insert_id = $this->task_masters_model->add_priority($data);
            if ($insert_id) {
                $success = true;
                $message = _l('added_successfully', _l('task_priority_label'));
            }
        } else {
            unset($data['id']);
            $success = $this->task_masters_model->update_priority($data, $postedId);
            if ($success) {
                $message = _l('updated_successfully', _l('task_priority_label'));
            } else {
                // Nothing actually changed — still a success from the user's view
                $success = true;
                $message = _l('updated_successfully', _l('task_priority_label'));
            }
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
        die;
    }

    public function delete_priority($id)
    {
        if (!$id) {
            redirect(admin_url('task_masters/priority'));
        }

        $response = $this->task_masters_model->delete_priority($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('task_priority_label')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('task_priority_label')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('task_priority_label')));
        }

        redirect(admin_url('task_masters/priority'));
    }

    /**
     * Remote validation — key_name must be unique
     */
    public function key_name_exists()
    {
        $exists = $this->task_masters_model->key_name_exists(
            $this->input->post('key_name'),
            $this->input->post('id')
        );

        echo $exists ? 'false' : 'true';
    }
}
