<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                    <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                        <i class="fa fa-user-circle"></i> <?= $title; ?>
                    </h4>
                    <a href="<?= admin_url('face_registration'); ?>" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Staff Info -->
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body text-center">
                        <?= staff_profile_image($staff_member->staffid, ['img', 'img-responsive', 'img-circle'], 'thumb'); ?>
                        <h4 class="tw-font-semibold tw-mt-3">
                            <?= $staff_member->firstname . ' ' . $staff_member->lastname; ?>
                        </h4>
                        <p class="text-muted"><?= $staff_member->email; ?></p>

                        <?php if ($face_template) { ?>
                            <div class="alert alert-success mtop15">
                                <i class="fa fa-check-circle"></i> Face Registered
                                <br><small>Registered: <?= $face_template->registered_at; ?></small>
                                <br><small>Source: <?= ucfirst($face_template->source ?? 'mobile'); ?></small>
                            </div>
                            <?php if (!empty($face_template->photo_path)) { ?>
                                <div class="mtop10">
                                    <label>Current Reference Photo:</label><br>
                                    <img src="<?= base_url($face_template->photo_path); ?>" 
                                         class="img-responsive img-thumbnail" style="max-width:200px;margin:0 auto;">
                                </div>
                            <?php } ?>
                            <button class="btn btn-danger btn-sm mtop10" id="btn-delete-face" onclick="deleteFace()">
                                <i class="fa fa-trash"></i> Delete Face Data
                            </button>
                        <?php } else { ?>
                            <div class="alert alert-warning mtop15">
                                <i class="fa fa-exclamation-triangle"></i> No face registered
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Webcam & Registration -->
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="tw-font-semibold tw-mb-3">
                            <i class="fa fa-camera"></i> Capture Face from Webcam
                        </h5>

                        <!-- Status Messages -->
                        <div id="status-message" class="alert alert-info">
                            <i class="fa fa-spinner fa-spin"></i> Loading face detection models...
                        </div>

                        <!-- Webcam Container -->
                        <div class="text-center" id="webcam-container" style="display:none;">
                            <div style="position:relative;display:inline-block;">
                                <video id="video" width="480" height="360" autoplay muted 
                                       style="border-radius:8px;border:2px solid #ddd;"></video>
                                <canvas id="overlay" width="480" height="360" 
                                        style="position:absolute;top:0;left:0;"></canvas>
                            </div>
                            <canvas id="snapshot-canvas" width="480" height="360" style="display:none;"></canvas>

                            <div class="mtop15">
                                <button class="btn btn-primary btn-lg" id="btn-capture" onclick="captureFace()" disabled>
                                    <i class="fa fa-camera"></i> Capture & Register Face
                                </button>
                                <button class="btn btn-default" id="btn-retake" onclick="retake()" style="display:none;">
                                    <i class="fa fa-refresh"></i> Retake
                                </button>
                            </div>

                            <div class="mtop10">
                                <small class="text-muted">
                                    Position the face clearly in front of the camera. Ensure good lighting.
                                    A green box will appear when a face is detected.
                                </small>
                            </div>
                        </div>

                        <!-- Captured Preview -->
                        <div id="captured-preview" class="text-center mtop15" style="display:none;">
                            <img id="captured-image" class="img-responsive img-thumbnail" 
                                 style="max-width:320px;margin:0 auto;">
                            <div class="mtop10">
                                <span class="label label-success" id="confidence-label"></span>
                            </div>
                        </div>

                        <!-- Processing -->
                        <div id="processing" class="text-center mtop15" style="display:none;">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="mtop10">Processing face and generating embedding...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<!-- face-api.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<script>
const STAFF_ID = <?= $staff_member->staffid; ?>;
const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';

let video, overlay, ctx;
let modelsLoaded = false;
let detecting = false;
let stream = null;

document.addEventListener('DOMContentLoaded', async function() {
    video = document.getElementById('video');
    overlay = document.getElementById('overlay');
    ctx = overlay.getContext('2d');

    await loadModels();
});

async function loadModels() {
    try {
        updateStatus('Loading face detection models...', 'info');

        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

        modelsLoaded = true;
        updateStatus('Models loaded. Starting camera...', 'success');
        await startCamera();
    } catch(e) {
        updateStatus('Failed to load face models: ' + e.message, 'danger');
        console.error(e);
    }
}

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 480, height: 360, facingMode: 'user' }
        });
        video.srcObject = stream;

        video.addEventListener('playing', () => {
            document.getElementById('webcam-container').style.display = 'block';
            document.getElementById('btn-capture').disabled = false;
            updateStatus('Camera active. Position face in front of the camera and click Capture.', 'success');
            startDetection();
        });
    } catch(e) {
        updateStatus('Camera access denied: ' + e.message + '. Please allow camera permissions.', 'danger');
    }
}

async function startDetection() {
    if (!modelsLoaded || detecting) return;
    detecting = true;

    const detectLoop = async () => {
        if (!detecting) return;

        const detections = await faceapi.detectAllFaces(video)
            .withFaceLandmarks();

        ctx.clearRect(0, 0, overlay.width, overlay.height);

        if (detections.length > 0) {
            const dims = faceapi.matchDimensions(overlay, video, true);
            const resized = faceapi.resizeResults(detections, dims);

            resized.forEach(det => {
                const box = det.detection.box;
                ctx.strokeStyle = '#00ff00';
                ctx.lineWidth = 2;
                ctx.strokeRect(box.x, box.y, box.width, box.height);
            });
        }

        requestAnimationFrame(detectLoop);
    };

    detectLoop();
}

async function captureFace() {
    if (!modelsLoaded) return;

    document.getElementById('btn-capture').disabled = true;
    document.getElementById('processing').style.display = 'block';
    updateStatus('Detecting face and generating embedding...', 'info');

    try {
        // Detect face with descriptors (128-dim embedding)
        const detection = await faceapi.detectSingleFace(video)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            updateStatus('No face detected. Please position your face clearly and try again.', 'warning');
            document.getElementById('btn-capture').disabled = false;
            document.getElementById('processing').style.display = 'none';
            return;
        }

        // Get the 128-dim embedding
        const embedding = Array.from(detection.descriptor);
        const confidence = detection.detection.score;

        // Capture photo from video
        const canvas = document.getElementById('snapshot-canvas');
        const snapCtx = canvas.getContext('2d');
        snapCtx.drawImage(video, 0, 0, 480, 360);
        const photoBase64 = canvas.toDataURL('image/jpeg', 0.5);

        // Show preview
        document.getElementById('captured-image').src = photoBase64;
        document.getElementById('confidence-label').textContent = 
            'Detection Confidence: ' + (confidence * 100).toFixed(1) + '%';
        document.getElementById('captured-preview').style.display = 'block';

        // Stop detection overlay
        detecting = false;
        ctx.clearRect(0, 0, overlay.width, overlay.height);

        // Send to server
        await saveFaceToServer(embedding, photoBase64, confidence);

    } catch(e) {
        updateStatus('Error: ' + e.message, 'danger');
        document.getElementById('btn-capture').disabled = false;
        document.getElementById('processing').style.display = 'none';
        console.error(e);
    }
}

async function saveFaceToServer(embedding, photoBase64, confidence) {
    try {
        const response = await fetch('<?= admin_url("face_registration/save"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                staff_id: STAFF_ID,
                embedding: JSON.stringify(embedding),
                photo: photoBase64,
                '<?= $this->security->get_csrf_token_name(); ?>': '<?= $this->security->get_csrf_hash(); ?>'
            })
        });

        const responseText = await response.text();
        document.getElementById('processing').style.display = 'none';

        let result;
        try {
            result = JSON.parse(responseText);
        } catch(parseErr) {
            console.error('Server response:', responseText);
            updateStatus('Server returned invalid response. Check server error logs. Response: ' + responseText.substring(0, 200), 'danger');
            document.getElementById('btn-capture').disabled = false;
            return;
        }

        if (result.status) {
            updateStatus(
                '<i class="fa fa-check-circle"></i> ' + result.message + 
                ' (Embedding: 128-dim, Confidence: ' + (confidence * 100).toFixed(1) + '%)',
                'success'
            );
            document.getElementById('btn-capture').style.display = 'none';
            document.getElementById('btn-retake').style.display = 'inline-block';

            // Stop camera
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        } else {
            updateStatus('Error: ' + result.message, 'danger');
            document.getElementById('btn-capture').disabled = false;
        }
    } catch(e) {
        document.getElementById('processing').style.display = 'none';
        updateStatus('Network error: ' + e.message, 'danger');
        document.getElementById('btn-capture').disabled = false;
    }
}

function retake() {
    document.getElementById('captured-preview').style.display = 'none';
    document.getElementById('btn-retake').style.display = 'none';
    document.getElementById('btn-capture').style.display = 'inline-block';
    document.getElementById('btn-capture').disabled = false;

    startCamera();
}

function deleteFace() {
    if (!confirm('Are you sure you want to delete the face data for this staff member?')) return;

    fetch('<?= admin_url("face_registration/delete"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            staff_id: STAFF_ID,
            '<?= $this->security->get_csrf_token_name(); ?>': '<?= $this->security->get_csrf_hash(); ?>'
        })
    })
    .then(r => r.json())
    .then(result => {
        if (result.status) {
            alert_float('success', result.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert_float('danger', result.message);
        }
    });
}

function updateStatus(message, type) {
    const el = document.getElementById('status-message');
    el.className = 'alert alert-' + type;
    el.innerHTML = message;
}
</script>
</body>
</html>
