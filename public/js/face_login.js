// public/js/face_login.js

document.addEventListener('DOMContentLoaded', async () => {
    const video = document.getElementById('video');
    const startBtn = document.getElementById('startCamera');
    const btnLoginFace = document.getElementById('btnLoginFace');
    const status = document.getElementById('status');
    const canvas = document.getElementById('snapshot');

    let modelsLoaded = false;
    let stream = null;

    // Afficher le statut
    status.textContent = "⏳ Chargement des modèles...";

    try {
        // Charger les modèles depuis le dossier public
        await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
        await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
        await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
        
        modelsLoaded = true;
        status.textContent = "✅ Modèles chargés - Cliquez sur 'Activer ma caméra'";
    } catch (error) {
        console.error('Erreur chargement modèles:', error);
        status.textContent = "❌ Erreur chargement modèles. Vérifiez que les fichiers sont dans /public/models/";
        return;
    }

    // Activer la caméra
    startBtn.addEventListener('click', async () => {
        try {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }

            const constraints = {
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                }
            };
            
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            
            // Attendre que la vidéo soit prête
            video.onloadedmetadata = () => {
                video.play();
                btnLoginFace.style.display = 'inline-block';
                status.textContent = "✅ Caméra activée - Cliquez sur 'Se connecter'";
            };
        } catch (e) {
            console.error('Erreur caméra:', e);
            status.textContent = "❌ Accès caméra refusé. Vérifiez les permissions.";
        }
    });

    // Se connecter avec le visage
    btnLoginFace.addEventListener('click', async () => {
        if (!modelsLoaded) {
            alert('Les modèles ne sont pas encore chargés.');
            return;
        }

        if (!video.srcObject) {
            alert('Activez d\'abord la caméra.');
            return;
        }

        btnLoginFace.disabled = true;
        status.textContent = "⏳ Analyse du visage...";

        try {
            // Options de détection
            const options = new faceapi.TinyFaceDetectorOptions({
                inputSize: 320,
                scoreThreshold: 0.3
            });

            // Détecter le visage
            const detection = await faceapi
                .detectSingleFace(video, options)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (!detection) {
                throw new Error('Aucun visage détecté. Assurez-vous d\'être bien éclairé et de faire face à la caméra.');
            }

            status.textContent = "✅ Visage détecté, connexion en cours...";

            // Convertir le descripteur en tableau
            const descriptorArray = Array.from(detection.descriptor);

            // Envoyer au serveur
            const response = await fetch('/login/face', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ descriptor: descriptorArray })
            });

            const result = await response.json();

            if (result.success) {
                status.textContent = "✅ Connexion réussie! Redirection...";
                window.location.href = '/profile';
            } else {
                throw new Error(result.message || 'Échec de la connexion');
            }
        } catch (err) {
            console.error('Erreur:', err);
            status.textContent = "❌ " + err.message;
        } finally {
            btnLoginFace.disabled = false;
        }
    });
});