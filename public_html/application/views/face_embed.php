<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Face Descriptor Generator</title>
    <style>
        body { font-family: -apple-system, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 16px;
               background: #f7f7f8; color: #222; font-size: 14px; }
        .card { background: #fff; border: 1px solid #e3e3e6; border-radius: 8px;
                padding: 16px; max-width: 640px; margin: 0 auto 16px; }
        h1 { font-size: 17px; margin: 0 0 4px; }
        .muted { color: #6b6b70; font-size: 12px; }
        #status { padding: 10px 12px; border-radius: 6px; margin: 12px 0; font-weight: 600; }
        .s-wait { background: #fff6e0; color: #7a5600; }
        .s-ok   { background: #e6f6ea; color: #17632c; }
        .s-err  { background: #fdeaea; color: #8a1c1c; }
        #preview { max-width: 260px; border-radius: 6px; display: none; margin-top: 12px; }
        pre { background: #1f2430; color: #d6deeb; padding: 12px; border-radius: 6px;
              overflow-x: auto; font-size: 11px; max-height: 220px; }
        code { background: #eef0f3; padding: 1px 5px; border-radius: 3px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 5px 0; border-bottom: 1px solid #eee; font-size: 13px; }
        td:first-child { color: #6b6b70; width: 45%; }
    </style>
</head>
<body>

<div class="card">
    <h1>Face Descriptor Generator</h1>
    <div class="muted">
        Produces 128-dimension face-api.js descriptors &mdash; the same model the
        admin registration page uses, so embeddings from here will match faces
        registered on the web.
    </div>

    <div id="status" class="s-wait">Loading face recognition models&hellip;</div>

    <div id="selftest" style="display:none;">
        <label class="muted">Self-test &mdash; pick a photo containing one face:</label><br>
        <input type="file" id="file" accept="image/*" style="margin-top:6px;">
        <img id="preview" alt="">
        <div id="result"></div>
    </div>
</div>

<div class="card">
    <h1>Bridge contract</h1>
    <div class="muted">How the mobile app drives this page.</div>
    <p><strong>Call from Flutter:</strong></p>
    <pre>generateEmbedding("data:image/jpeg;base64,/9j/4AAQ...")</pre>
    <p><strong>Messages posted back</strong> on channel <code>FaceChannel</code>:</p>
    <pre>{"event":"ready"}

{"event":"result","success":true,
 "embedding":[-0.12,0.04, ...],  // 128 floats
 "dimension":128,"score":0.997}

{"event":"result","success":false,"error":"NO_FACE",
 "message":"No face detected in the image."}</pre>
    <p class="muted">
        Error codes: <code>NOT_READY</code>, <code>BAD_IMAGE</code>,
        <code>NO_FACE</code>, <code>INFERENCE_FAILED</code>.
        Wait for <code>ready</code> before calling.
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
// Same CDN weights the admin registration page uses. Both sides MUST stay on
// this model or descriptors will not be comparable.
const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';

let modelsReady = false;

/**
 * Post a message back to the host app.
 * Supports flutter_inappwebview and webview_flutter, and no-ops in a browser.
 */
function notify(payload) {
    const json = JSON.stringify(payload);

    try {
        if (window.flutter_inappwebview && window.flutter_inappwebview.callHandler) {
            window.flutter_inappwebview.callHandler('FaceChannel', json);
            return;
        }
        if (window.FaceChannel && window.FaceChannel.postMessage) {
            window.FaceChannel.postMessage(json);
            return;
        }
    } catch (e) {
        console.error('Bridge post failed:', e);
    }

    console.log('[FaceChannel]', json);
}

function setStatus(text, cls) {
    const el = document.getElementById('status');
    el.className = cls;
    el.innerHTML = text;
}

async function loadModels() {
    try {
        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

        modelsReady = true;
        window.faceEmbedReady = true;

        setStatus('Ready. Models loaded.', 's-ok');
        document.getElementById('selftest').style.display = 'block';
        notify({ event: 'ready' });
    } catch (e) {
        setStatus('Failed to load models: ' + e.message, 's-err');
        notify({ event: 'error', error: 'MODEL_LOAD_FAILED', message: e.message });
    }
}

/**
 * Convert a face image into a 128-dim descriptor.
 *
 * @param {string} imageDataUrl  data URI or bare base64 JPEG/PNG
 * @returns {Promise<object>}    the same payload posted on the channel
 */
async function generateEmbedding(imageDataUrl) {
    if (!modelsReady) {
        const r = { event: 'result', success: false, error: 'NOT_READY',
                    message: 'Models are still loading. Wait for the ready event.' };
        notify(r);
        return r;
    }

    if (!imageDataUrl || typeof imageDataUrl !== 'string') {
        const r = { event: 'result', success: false, error: 'BAD_IMAGE',
                    message: 'No image data supplied.' };
        notify(r);
        return r;
    }

    // Accept a bare base64 string as well as a full data URI
    if (imageDataUrl.indexOf('data:') !== 0) {
        imageDataUrl = 'data:image/jpeg;base64,' + imageDataUrl;
    }

    try {
        const img = await faceapi.fetchImage(imageDataUrl);

        const detection = await faceapi
            .detectSingleFace(img, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            const r = { event: 'result', success: false, error: 'NO_FACE',
                        message: 'No face detected in the image.' };
            notify(r);
            return r;
        }

        const embedding = Array.from(detection.descriptor);

        const r = {
            event: 'result',
            success: true,
            embedding: embedding,
            dimension: embedding.length,
            score: Math.round(detection.detection.score * 1000) / 1000
        };
        notify(r);
        return r;
    } catch (e) {
        const r = { event: 'result', success: false, error: 'INFERENCE_FAILED',
                    message: e.message };
        notify(r);
        return r;
    }
}

// Expose for WebView evaluation
window.generateEmbedding = generateEmbedding;

// ---- Browser self-test ----------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    loadModels();

    document.getElementById('file').addEventListener('change', function (ev) {
        const file = ev.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = async function (e) {
            const dataUrl = e.target.result;

            const preview = document.getElementById('preview');
            preview.src = dataUrl;
            preview.style.display = 'block';

            setStatus('Generating descriptor&hellip;', 's-wait');
            const res = await generateEmbedding(dataUrl);
            const out = document.getElementById('result');

            if (!res.success) {
                setStatus(res.error + ': ' + res.message, 's-err');
                out.innerHTML = '';
                return;
            }

            setStatus('Descriptor generated.', 's-ok');
            out.innerHTML =
                '<table>' +
                '<tr><td>Dimension</td><td>' + res.dimension + '</td></tr>' +
                '<tr><td>Detection confidence</td><td>' + (res.score * 100).toFixed(1) + '%</td></tr>' +
                '<tr><td>Value range</td><td>' +
                    Math.min.apply(null, res.embedding).toFixed(4) + ' to ' +
                    Math.max.apply(null, res.embedding).toFixed(4) + '</td></tr>' +
                '</table>' +
                '<p class="muted" style="margin-top:10px;">Paste this into the ' +
                '<code>embedding</code> field of your API request:</p>' +
                '<pre>' + JSON.stringify(res.embedding) + '</pre>';
        };
        reader.readAsDataURL(file);
    });
});
</script>

</body>
</html>
