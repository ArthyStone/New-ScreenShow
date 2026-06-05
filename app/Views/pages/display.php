<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="/css/display.css">
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
    const queueServerWS = "<?= $queueServerWS ?>";
    const mediaFolder = <?= json_encode($mediaFolder) ?>;
  </script>
  <script src="scripts/display.js"></script>
</body>
</html>