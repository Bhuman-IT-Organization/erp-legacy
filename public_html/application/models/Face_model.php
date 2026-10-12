<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Face Template Model
 * Handles face embedding storage and cosine similarity matching
 */
class Face_model extends App_Model
{
    /**
     * How close the runner-up may be before a 1:N identification is treated as
     * ambiguous. Two people separated by less than this are, in practice,
     * indistinguishable to the model.
     */
    const AMBIGUOUS_MARGIN = 0.02;

    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'face_templates';
    }

    /**
     * Register a face template (embedding) for a staff member
     *
     * @param int    $staff_id
     * @param array  $embedding     128-float array from MobileFaceNet/face-api.js
     * @param string $device_id     Device identifier
     * @param array  $extra_data    Optional: photo_path, registered_by, source
     * @return int|false Insert ID or false
     */
    public function register($staff_id, $embedding, $device_id = '', $extra_data = [])
    {
        // Remove any existing templates for this user (1 face per user)
        $this->db->where('staff_id', $staff_id);
        $this->db->delete($this->table);

        // If there was a previous photo, try to remove it
        if (isset($extra_data['old_photo_path']) && !empty($extra_data['old_photo_path'])) {
            $full_path = FCPATH . $extra_data['old_photo_path'];
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
            unset($extra_data['old_photo_path']);
        }

        $data = [
            'staff_id'       => $staff_id,
            'embedding'      => json_encode($embedding),
            'photo_path'     => isset($extra_data['photo_path']) ? $extra_data['photo_path'] : null,
            'device_id'      => $device_id,
            'registered_by'  => isset($extra_data['registered_by']) ? $extra_data['registered_by'] : null,
            'source'         => isset($extra_data['source']) ? $extra_data['source'] : 'mobile',
            'registered_at'  => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        $this->db->insert($this->table, $data);
        return $this->db->insert_id() ?: false;
    }

    /**
     * Get the stored face template for a staff member
     *
     * @param int $staff_id
     * @return object|null
     */
    public function get_template($staff_id)
    {
        $this->db->where('staff_id', $staff_id);
        $row = $this->db->get($this->table)->row();

        if ($row) {
            $row->embedding = json_decode($row->embedding, true);
        }

        return $row;
    }

    /**
     * Check if a staff member has a registered face
     *
     * @param int $staff_id
     * @return bool
     */
    public function has_face($staff_id)
    {
        $this->db->where('staff_id', $staff_id);
        return $this->db->count_all_results($this->table) > 0;
    }

    /**
     * Delete face template for a staff member
     *
     * @param int $staff_id
     * @return bool
     */
    public function delete_template($staff_id)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Verify a face embedding against stored template using cosine similarity
     *
     * @param int   $staff_id
     * @param array $embedding  128-float array to compare
     * @param float $threshold  Similarity threshold (0.0 - 1.0), default 0.6
     * @return array ['matched' => bool, 'similarity' => float]
     */
    public function verify($staff_id, $embedding, $threshold = 0.6)
    {
        $template = $this->get_template($staff_id);

        if (!$template || empty($template->embedding)) {
            return ['matched' => false, 'similarity' => 0.0, 'error' => 'No face registered'];
        }

        $stored = $template->embedding;

        // Normalise incoming values to float (JSON may deliver them as strings)
        $embedding = array_values(array_map('floatval', $embedding));
        $stored    = array_values(array_map('floatval', $stored));

        // Dimension mismatch means the two embeddings came from different models.
        // Comparing them is meaningless, so report it instead of returning 0.
        if (count($stored) !== count($embedding)) {
            return [
                'matched'          => false,
                'similarity'       => 0.0,
                'stored_dimension' => count($stored),
                'sent_dimension'   => count($embedding),
                'error'            => 'Embedding dimension mismatch. The registered face was created with a different model ('
                    . count($stored) . '-dim) than the one sent (' . count($embedding) . '-dim). '
                    . 'Both registration and verification must use the same face model.',
            ];
        }

        $similarity = $this->cosine_similarity($stored, $embedding);
        $distance   = $this->euclidean_distance($stored, $embedding);

        return [
            'matched'          => $similarity >= $threshold,
            'similarity'       => round($similarity, 4),
            'distance'         => round($distance, 4),
            'stored_dimension' => count($stored),
            'sent_dimension'   => count($embedding),
            'source'           => $template->source ?? null,
        ];
    }

    /**
     * Identify a face against every registered template (1:N search).
     *
     * verify() answers "is this a particular person?" - it needs the staff id up
     * front. This answers "who is this?", which is what a shared device has to ask,
     * because nobody has identified themselves yet.
     *
     * Only active staff are searched: a leaver keeps their template row, but they must
     * not be identifiable at a door or a kiosk.
     *
     * Templates whose embedding has a different dimension to the probe are skipped
     * rather than scored 0. A dimension mismatch means the two came from different
     * face models, so any comparison between them is meaningless, and scoring it 0
     * would quietly hide a misconfigured app behind "no match".
     *
     * SCALE - every embedding is loaded and compared in PHP. At 128 floats a template
     * that is a few hundred KB for a few hundred staff, which is fine. If this ever
     * has to serve thousands of employees per punch, the comparison belongs in SQL or
     * a vector index rather than here.
     *
     * @param array $embedding Probe embedding
     * @param float $threshold Minimum cosine similarity to count as a match
     *
     * @return array
     */
    public function identify($embedding, $threshold = 0.6)
    {
        $probe = array_values(array_map('floatval', $embedding));
        $probe_dimension = count($probe);

        $result = [
            'matched'           => false,
            'staff_id'          => null,
            'similarity'        => 0.0,
            'distance'          => null,
            'threshold'         => (float) $threshold,
            'margin'            => null,
            'ambiguous'         => false,
            'runner_up'         => null,
            'probe_dimension'   => $probe_dimension,
            'total_templates'   => 0,
            'compared'          => 0,
            'skipped_dimension' => 0,
            'source'            => null,
            'registered_at'     => null,
        ];

        if ($probe_dimension === 0) {
            $result['error'] = 'Empty embedding.';

            return $result;
        }

        $staff = db_prefix() . 'staff';

        $this->db->select(
            $this->table . '.staff_id, ' . $this->table . '.embedding, '
            . $this->table . '.source, ' . $this->table . '.registered_at'
        );
        $this->db->join($staff, $staff . '.staffid = ' . $this->table . '.staff_id', 'inner');
        $this->db->where($staff . '.active', 1);
        $rows = $this->db->get($this->table)->result();

        $result['total_templates'] = count($rows);

        if (count($rows) === 0) {
            $result['error'] = 'No faces are registered yet.';

            return $result;
        }

        $scores = [];

        foreach ($rows as $row) {
            $stored = json_decode($row->embedding, true);

            if (!is_array($stored) || count($stored) === 0) {
                continue;
            }

            $stored = array_values(array_map('floatval', $stored));

            if (count($stored) !== $probe_dimension) {
                $result['skipped_dimension']++;

                continue;
            }

            $result['compared']++;

            $scores[] = [
                'staff_id'      => (int) $row->staff_id,
                'similarity'    => $this->cosine_similarity($stored, $probe),
                'distance'      => $this->euclidean_distance($stored, $probe),
                'source'        => $row->source,
                'registered_at' => $row->registered_at,
            ];
        }

        if ($result['compared'] === 0) {
            $result['error'] = 'No comparable face templates. ' . $result['skipped_dimension']
                . ' registered template(s) were created with a different face model than the '
                . $probe_dimension . '-dimension embedding sent. Registration and identification '
                . 'must use the same model.';

            return $result;
        }

        // Highest similarity first.
        usort($scores, function ($a, $b) {
            if ($a['similarity'] == $b['similarity']) {
                return 0;
            }

            return $a['similarity'] < $b['similarity'] ? 1 : -1;
        });

        $best = $scores[0];

        $result['similarity']    = round($best['similarity'], 4);
        $result['distance']      = round($best['distance'], 4);
        $result['matched']       = $best['similarity'] >= $threshold;
        $result['source']        = $best['source'];
        $result['registered_at'] = $best['registered_at'];

        if ($result['matched']) {
            $result['staff_id'] = $best['staff_id'];
        }

        if (isset($scores[1])) {
            $second = $scores[1];

            $result['margin'] = round($best['similarity'] - $second['similarity'], 4);
            $result['runner_up'] = [
                'staff_id'   => $second['staff_id'],
                'similarity' => round($second['similarity'], 4),
            ];

            // Two different people both clearing the threshold, with almost nothing
            // between them, is not an identification - it is a coin toss. Reported so
            // the caller can refuse rather than silently punch in the wrong person.
            if ($result['matched']
                && $second['similarity'] >= $threshold
                && $result['margin'] < self::AMBIGUOUS_MARGIN) {
                $result['ambiguous'] = true;
            }
        }

        return $result;
    }

    /**
     * Euclidean distance between two vectors.
     * face-api.js uses this metric natively (match when distance < 0.6).
     *
     * @param array $a
     * @param array $b
     * @return float
     */
    private function euclidean_distance($a, $b)
    {
        if (count($a) !== count($b) || count($a) === 0) {
            return 999.0;
        }

        $sum = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $diff = $a[$i] - $b[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Calculate cosine similarity between two vectors
     *
     * @param array $a First vector
     * @param array $b Second vector
     * @return float Similarity score (0.0 to 1.0)
     */
    private function cosine_similarity($a, $b)
    {
        if (count($a) !== count($b) || count($a) === 0) {
            return 0.0;
        }

        $dot_product = 0.0;
        $norm_a = 0.0;
        $norm_b = 0.0;

        for ($i = 0; $i < count($a); $i++) {
            $dot_product += $a[$i] * $b[$i];
            $norm_a += $a[$i] * $a[$i];
            $norm_b += $b[$i] * $b[$i];
        }

        $norm_a = sqrt($norm_a);
        $norm_b = sqrt($norm_b);

        if ($norm_a == 0 || $norm_b == 0) {
            return 0.0;
        }

        return $dot_product / ($norm_a * $norm_b);
    }
}
