<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="https://www.pierreshow.fr/cameleon%20rond.png" type="image/png" />
  <title>ScreenShow Display</title>
  
  
  <meta property="og:locale" content="fr_FR" />
  <meta property="og:title" content="ScreenShow > Display" />
  <meta property="og:site_name" content="ScreenShow" />
  <meta property="og:description" content="ScreenShow est un projet qui a pour but de rajouter de l'interactivité dans les points de chaine de PierreShow." />
  <meta property="og:image" content="https://screenshow.pierreshow.fr/display/approved/PLACEHOLDER%20SCREENSHOW.png" />
  <meta property="og:url" content="https://screenshow.pierreshow.fr/" />
  <meta property="og:type" content="website" />
  <meta name="twitter:card" content="summary_large_image" />


  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *  { color: #fff; }
    body {
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      background-color: #222;
      overflow: hidden;
    }
    iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
    #container {
      width: 80vw;
      height: 45vw; /* Format 16:9 */
      max-width: 142vh;
      max-height: 80vh;
      background: #000;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    #container.fullscreen {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      max-width: 100vw;
      max-height: 100vh;
      z-index: 999;
      border-radius: 0;
    }
    img, video {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    #fullscreen-btn {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 1001;
      background: #111;
      border: none;
      border-radius: 50%;
      width: 56px;
      height: 56px;
      box-shadow: 0 2px 8px #0008;
      font-size: 2em;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: opacity 0.3s;
    }
    #home-btn {
      position: fixed;
      bottom: 24px;
      left: 24px;
      z-index: 1000;
      background: #111;
      border: none;
      border-radius: 50%;
      width: 56px;
      height: 56px;
      box-shadow: 0 2px 8px #0008;
      font-size: 2em;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: opacity 0.3s;
    }
    @media screen and (max-width: 768px) {
      #fullscreen-btn {
        display: none !important;
      }
      #home-btn {
        width: 80px;
        height: 80px;
      }
    }
  </style>
</head>
<body>
  <!-- au final, il affiche l'overlay sur son OBS -->
  <!-- <iframe allow="autoplay" src="http://screenshow.pierreshow.fr:3008/"></iframe> -->
  <div id="container">
  </div>
  <button id="fullscreen-btn"><i class="fa-solid fa-expand" id="fullscreen-icon"></i></button>
  <button id="home-btn" title="Accueil"><i class="fa-solid fa-house"></i></span></button>
  <div id="brightness-control" style="position:fixed;top:24px;right:24px;z-index:1100;background:#222b;padding:12px 18px;border-radius:18px;box-shadow:0 2px 8px #0008;display:flex;align-items:center;gap:10px;">
    <i class="fa-solid fa-sun"></i>
    <input type="range" id="brightness-slider" min="30" max="150" value="100" style="width:120px;">
    <span id="brightness-value">100%</span>
  </div>

  <script>
    const container = document.getElementById('container');
    let connexionAttempts = 0;
    function connectToWebSocket() {
      connexionAttempts++;
      const ws = new WebSocket("<?=$data['queueServerWS']?>");
      ws.onopen = () => {
        console.log("Connecté au serveur WebSocket");
        connexionAttempts = 0;
      };
      ws.onmessage = (event) => {
        info = JSON.parse(event.data);
        switch (info.type) {
          case 'stateUpdate':
            const item = info.currentItem;
            item.url = 'resources/medias/' + (item.type === 'image' ? item.id + '.png' : item.id + '.mp4');
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
  </script>
</body>
</html>