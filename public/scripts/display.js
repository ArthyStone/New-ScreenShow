const container = document.getElementById('container');
let connexionAttempts = 0;
function connectToWebSocket() {
    connexionAttempts++;
    const ws = new WebSocket(queueServerWS);
    ws.onopen = () => {
    console.log("Connecté au serveur WebSocket");
    connexionAttempts = 0;
    };
    ws.onmessage = (event) => {
    info = JSON.parse(event.data);
    switch (info.type) {
        case 'stateUpdate':
        const item = info.currentItem;
        item.url = mediaFolder + (item.id === '0' ? '/' : '/APPROVED/') + item.id + (item.type === 'image' ?  '.png' : '.mp4');
        container.innerHTML = item.type === 'image' ? `<img src="${item.url}">` : `<video src="${item.url}" autoplay loop muted></video>`;
        break;
        default:
        console.warn("Type de message inconnu:", info.type);
        console.warn("Message complet:", info);
    }
    };
    ws.onerror = (error) => {
    console.error("Erreur WebSocket:", error);
    ws.close();
    };
    ws.onclose = () => {
    console.log("Déconnecté du serveur WebSocket, tentative de reconnexion n°" + connexionAttempts);
    if(connexionAttempts > 10) container.innerHTML = `<img src="resources/DOWN_SERVER_ERR.png">`;
    else container.innerHTML = `<img src="resources/NOT_RESPONDING_SERVER_ERR.png">`;
    setTimeout(connectToWebSocket, 1000);
    };
}
connectToWebSocket();
// Contrôle de la luminosité
const brightnessSlider = document.getElementById('brightness-slider');
const brightnessValue = document.getElementById('brightness-value');
// Restaure la valeur sauvegardée
const savedBrightness = localStorage.getItem('screenshow_brightness');
if (savedBrightness) {
    brightnessSlider.value = savedBrightness;
    container.style.filter = `brightness(${savedBrightness}%)`;
    brightnessValue.textContent = savedBrightness + '%';
} else {
    container.style.filter = 'brightness(100%)';
}
brightnessSlider.addEventListener('input', function() {
    const val = brightnessSlider.value;
    container.style.filter = `brightness(${val}%)`;
    brightnessValue.textContent = val + '%';
    localStorage.setItem('screenshow_brightness', val);
});


const fullscreenBtn = document.getElementById('fullscreen-btn');
const fullscreenIcon = document.getElementById('fullscreen-icon');
const brightnessControl = document.getElementById('brightness-control')
const homeBtn = document.getElementById('home-btn');

let isContainerFullscreen = false;
let hideSlide = () => {
    brightnessControl.style.opacity = '0';
    brightnessControl.style.pointerEvents = 'none';
};
let showSlide = () => {
    brightnessControl.style.opacity = '1';
    brightnessControl.style.pointerEvents = 'auto';
};
let hideBtn = () => {
    fullscreenBtn.style.opacity = '0';
    fullscreenBtn.style.pointerEvents = 'none';
};
let showBtn = () => {
    fullscreenBtn.style.opacity = '1';
    fullscreenBtn.style.pointerEvents = 'auto';
};
function hideHomeBtn() {
    homeBtn.style.opacity = '0';
    homeBtn.style.pointerEvents = 'none';
}
function showHomeBtn() {
    homeBtn.style.opacity = '1';
    homeBtn.style.pointerEvents = 'auto';
}
fullscreenBtn.addEventListener('click', () => {
    isContainerFullscreen = !isContainerFullscreen;
    if (isContainerFullscreen) {
    container.classList.add('fullscreen');
    hideSlide();
    fullscreenIcon.classList.remove('fa-expand');
    fullscreenIcon.classList.add('fa-compress');
    // on fait quand même un check au cas où la personne laisse sa souris sur le bouton
    const margin = 100;
    const x = window.innerWidth - e.clientX;
    const y = window.innerHeight - e.clientY;
    if (x < margin && y < margin) {
        showBtn();
    } else {
        hideBtn();
    }
    // Affiche le bouton home si la souris est dans le coin bas gauche
    const xLeft = e.clientX;
    const yBottom = window.innerHeight - e.clientY;
    if (xLeft < margin && yBottom < margin) {
        showHomeBtn();
    } else {
        hideHomeBtn();
    }
    } else {
    container.classList.remove('fullscreen');
    showSlide();
    showBtn();
    showHomeBtn();
    fullscreenIcon.classList.remove('fa-compress');
    fullscreenIcon.classList.add('fa-expand');
    }
});
homeBtn.addEventListener('click', () => {
    window.location.href = '/infos';
});
// Zone de détection (marge de 80px autour du coin bas droit)
document.addEventListener('mousemove', (e) => {
    if (isContainerFullscreen) {
    const margin = 100;
    const x = window.innerWidth - e.clientX;
    const y = window.innerHeight - e.clientY;
    if (x < margin && y < margin) {
        showBtn();
    } else {
        hideBtn();
    }
    // Affiche le bouton home si la souris est dans le coin bas gauche
    const xLeft = e.clientX;
    const yBottom = window.innerHeight - e.clientY;
    if (xLeft < margin && yBottom < margin) {
        showHomeBtn();
    } else {
        hideHomeBtn();
    }
    }
});
const params = new URLSearchParams(window.location.search);
const isDisplayed = params.get("displayed") === "true";
if (isDisplayed) {
    isContainerFullscreen = true;
    container.classList.add('fullscreen');
    hideSlide();
    hideBtn();
    hideHomeBtn();
    fullscreenIcon.classList.remove('fa-expand');
    fullscreenIcon.classList.add('fa-compress');
}