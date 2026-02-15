const video = document.getElementById('video');
const button = document.getElementById('captureFace'); // id corrigé
const status = document.getElementById('status');

async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
    } catch (e) {
        status.textContent = "❌ Accès caméra refusé";
    }
}

async function loadModels() {
    const MODEL_URL = '/models';
    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
}

button.addEventListener('click', async () => {
    button.disabled = true;
    status.textContent = "⏳ Analyse du visage...";

    const detections = await faceapi
        .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptors();

    if (detections.length === 0) {
        status.textContent = "❌ Aucun visage détecté";
        button.disabled = false;
        return;
    }

    if (detections.length > 1) {
        status.textContent = "❌ Plusieurs visages détectés";
        button.disabled = false;
        return;
    }

    const descriptor = Array.from(detections[0].descriptor);

    const response = await fetch('/profil/securite/visage/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ descriptor })
    });

    const result = await response.json();

    if (result.success) {
        status.textContent = "✅ Visage enregistré avec succès";
    } else {
        status.textContent = "❌ " + result.message;
    }

    button.disabled = false;
});

(async () => {
    await loadModels();
    await startCamera();
})();
