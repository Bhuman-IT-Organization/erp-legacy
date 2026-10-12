<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Face Embedding Generator
 *
 * Serves a self-contained page that runs face-api.js in the browser (or in a
 * mobile WebView) and converts a face image into a 128-dimension descriptor.
 *
 * Purpose: the admin registration page generates descriptors with face-api.js.
 * Descriptors from a different model (e.g. TFLite MobileFaceNet) live in an
 * unrelated vector space and will never match. By having the mobile app call
 * this page, both registration and verification use the exact same model.
 *
 * The page holds no data and touches no database - it is a compute helper only.
 * Images are processed in memory on the client and never stored or uploaded here.
 */
class Face_embed extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /face/embed
     *
     * Renders the descriptor generator. Open it in a desktop browser to
     * self-test with a file picker, or load it in a WebView and drive it
     * via the documented JavaScript bridge.
     */
    public function index()
    {
        $this->load->view('face_embed');
    }
}
