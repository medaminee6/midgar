const realVideo = document.getElementById('realVideo');
const canvas = document.getElementById('canvas');
const startCameraBtn = document.getElementById('startCamera');
const captureFaceBtn = document.getElementById('captureFace');
const status = document.getElementById('status');

// Charger les modèles face-api
Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
    faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
    faceapi.nets.faceRecognitionNet.loadFromUri('/models')
]).then(() => {
    status.innerText = 'Modèles chargés';
});

startCameraBtn.addEventListener('click', async () => {
    startCameraBtn.style.display = 'none';
    captureFaceBtn.style.display = 'inline';

    // Démarrer la caméra réelle en arrière-plan
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    realVideo.srcObject = stream;

    status.innerText = 'Caméra activée. Veuillez regarder la vidéo...';
});

// Fonction pour capturer et envoyer le visage
captureFaceBtn.addEventListener('click', async () => {
    status.innerText = 'Analyse du visage en cours...';

    // Détection faciale sur la caméra réelle (cachée)
    const detections = await faceapi.detectSingleFace(realVideo, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();

    if (!detections) {
        status.innerText = 'Aucun visage détecté, essayez à nouveau.';
        return;
    }

    const descriptor = Array.from(detections.descriptor);

    // Envoi au backend
    fetch('/profil/securite/visage/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ descriptor })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            status.innerText = 'Visage enregistré avec succès !';
        } else {
            status.innerText = 'Erreur lors de l\'enregistrement : ' + data.message;
        }
    })
    .catch(err => {
        status.innerText = 'Erreur serveur';
        console.error(err);
    });
});
