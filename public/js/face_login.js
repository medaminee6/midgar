async function startVideo() {
    const video = document.getElementById('video');
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
}

async function loadModels() {
    await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
    await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
    await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
}

async function captureAndLogin() {
    const video = document.getElementById('video');

    const detection = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

    if(!detection) {
        alert('Aucun visage détecté.');
        return;
    }

    const descriptorArray = Array.from(detection.descriptor);

    const response = await fetch('/login/face', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ descriptor: descriptorArray })
    });

    const result = await response.json();
    if(result.success) {
        window.location.href = '/dashboard';
    } else {
        alert(result.message);
    }
}

document.getElementById('btnLoginFace').addEventListener('click', captureAndLogin);
loadModels().then(startVideo);
