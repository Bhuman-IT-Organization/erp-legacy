<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin Face Registration Controller
 * 
 * Allows administrators to register staff faces via webcam from desktop/laptop.
 * Uses face-api.js (browser-side) for face detection and embedding generation.
 * Embedding + optional reference photo are sent to server.
 */
class Face_registration extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('face_model');
        $this->load->model('staff_model');
    }

    /**
     * Face registration page for a specific staff member
     * URL: /admin/face_registration/register/{staff_id}
     */
    public function register($staff_id = '')
    {
        if (!is_admin()) {
            access_denied('face_registration');
        }

        if (empty($staff_id) || !is_numeric($staff_id)) {
            redirect(admin_url('staff'));
        }

        $staff = $this->staff_model->get($staff_id);
        if (!$staff) {
            set_alert('danger', 'Staff member not found.');
            redirect(admin_url('staff'));
        }

        $data['staff_member'] = $staff;
        $data['face_template'] = $this->face_model->get_template($staff_id);
        $data['title'] = 'Face Registration - ' . $staff->firstname . ' ' . $staff->lastname;

        $this->load->view('admin/face_registration/register', $data);
    }

    /**
     * AJAX: Save face embedding + photo from web interface
     * POST: staff_id, embedding (JSON string), photo (base64)
     */
    public function save()
    {
        header('Content-Type: application/json');

        try {
            if (!is_admin()) {
                echo json_encode(['status' => false, 'message' => 'Access denied.']);
                die;
            }

            $staff_id = $this->input->post('staff_id');
            $embedding_json = $this->input->post('embedding');
            $photo_base64 = $this->input->post('photo');

            if (empty($staff_id) || empty($embedding_json)) {
                echo json_encode(['status' => false, 'message' => 'Staff ID and embedding are required. Received staff_id=' . ($staff_id ?: 'empty') . ', embedding_length=' . strlen($embedding_json ?: '')]);
                die;
            }

            $embedding = json_decode($embedding_json, true);

            if (!is_array($embedding) || count($embedding) < 64) {
                echo json_encode(['status' => false, 'message' => 'Invalid face embedding data. Decoded type: ' . gettype($embedding)]);
                die;
            }

            // Save reference photo if provided (non-critical, skip on failure)
            $photo_path = null;
            if (!empty($photo_base64)) {
                try {
                    $photo_path = $this->save_face_photo($staff_id, $photo_base64);
                } catch (\Exception $e) {
                    // Photo save failed, continue without photo
                    $photo_path = null;
                }
            }

            // Get old template to clean up old photo
            $old_photo_path = null;
            try {
                $old_template = $this->face_model->get_template($staff_id);
                $old_photo_path = ($old_template && isset($old_template->photo_path) && !empty($old_template->photo_path)) ? $old_template->photo_path : null;
            } catch (\Exception $e) {
                $old_photo_path = null;
            }

            // Register the face
            $result = $this->face_model->register($staff_id, $embedding, 'web-admin', [
                'photo_path'     => $photo_path,
                'registered_by'  => get_staff_user_id(),
                'source'         => 'web',
                'old_photo_path' => $old_photo_path,
            ]);

            if ($result) {
                log_activity('Face Registered via Web [Staff ID: ' . $staff_id . ', By: ' . get_staff_user_id() . ']');
                echo json_encode([
                    'status'  => true,
                    'message' => 'Face registered successfully.',
                    'data'    => [
                        'template_id' => $result,
                        'photo_url'   => $photo_path ? base_url($photo_path) : null,
                    ],
                ]);
            } else {
                // Get DB error for debugging
                $db_error = $this->db->error();
                echo json_encode(['status' => false, 'message' => 'Failed to save face data. DB: ' . ($db_error['message'] ?? 'unknown')]);
            }
        } catch (\Exception $e) {
            echo json_encode(['status' => false, 'message' => 'Server exception: ' . $e->getMessage()]);
        } catch (\Error $e) {
            echo json_encode(['status' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
        die;
    }

    /**
     * AJAX: Delete face template
     */
    public function delete()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!is_admin()) {
            echo json_encode(['status' => false, 'message' => 'Access denied.']);
            die;
        }

        $staff_id = $this->input->post('staff_id');

        // Get template to delete photo file
        $template = $this->face_model->get_template($staff_id);
        if ($template && !empty($template->photo_path)) {
            $full_path = FCPATH . $template->photo_path;
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
        }

        $deleted = $this->face_model->delete_template($staff_id);

        log_activity('Face Deleted via Web [Staff ID: ' . $staff_id . ', By: ' . get_staff_user_id() . ']');

        echo json_encode([
            'status'  => true,
            'message' => $deleted ? 'Face template deleted.' : 'No face template found.',
        ]);
        die;
    }

    /**
     * View all staff with face registration status
     * URL: /admin/face_registration
     */
    public function index()
    {
        if (!is_admin()) {
            access_denied('face_registration');
        }

        $staff_members = $this->staff_model->get('', ['active' => 1]);

        // Add face registration status to each
        foreach ($staff_members as &$member) {
            $member['has_face'] = $this->face_model->has_face($member['staffid']);
        }

        $data['staff_members'] = $staff_members;
        $data['title'] = 'Face Registration Management';

        $this->load->view('admin/face_registration/index', $data);
    }

    /**
     * Delete face and redirect back to staff member page
     * URL: /admin/face_registration/delete_redirect/{staff_id}
     */
    public function delete_redirect($staff_id = '')
    {
        if (!is_admin()) {
            access_denied('face_registration');
        }

        if (empty($staff_id) || !is_numeric($staff_id)) {
            redirect(admin_url('staff'));
        }

        // Get template to delete photo file
        $template = $this->face_model->get_template($staff_id);
        if ($template && !empty($template->photo_path)) {
            $full_path = FCPATH . $template->photo_path;
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
        }

        $this->face_model->delete_template($staff_id);
        log_activity('Face Deleted via Admin [Staff ID: ' . $staff_id . ', By: ' . get_staff_user_id() . ']');

        set_alert('success', 'Face data deleted successfully.');
        redirect(admin_url('staff/member/' . $staff_id));
    }

    /**
     * Save base64 photo to file
     *
     * @param int    $staff_id
     * @param string $base64
     * @return string|null Relative path
     */
    private function save_face_photo($staff_id, $base64)
    {
        // Remove data URI prefix if present
        if (strpos($base64, ',') !== false) {
            $base64 = explode(',', $base64)[1];
        }

        $image_data = base64_decode($base64);
        if (!$image_data) {
            return null;
        }

        // Create directory - use writable uploads path
        $dir = 'uploads/face_photos/' . $staff_id;
        $full_dir = FCPATH . $dir;

        if (!is_dir($full_dir)) {
            @mkdir($full_dir, 0755, true);
            // If mkdir fails, try without recursive
            if (!is_dir($full_dir)) {
                $parent = FCPATH . 'uploads/face_photos';
                if (!is_dir($parent)) {
                    @mkdir($parent, 0755);
                }
                @mkdir($full_dir, 0755);
            }
            // Still failed? Skip photo
            if (!is_dir($full_dir)) {
                return null;
            }
        }

        $filename = 'face_' . time() . '.jpg';
        $filepath = $full_dir . '/' . $filename;

        if (@file_put_contents($filepath, $image_data)) {
            @chmod($filepath, 0644);
            return $dir . '/' . $filename;
        }

        return null;
    }
}
