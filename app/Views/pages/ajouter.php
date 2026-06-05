<?php // var_dump($tags); ?>
<h2>Déposez vos images ou vidéos ici</h2>
<h3>On n'accepte que les fichiers jpeg / jpg / svg / png / gif / mp4 de moins de 100Mo</h3>
<h3>pas plus de 20 fichiers à la fois</h3>

<div id="errorMsg"></div>
<div id="counter"></div>
<div id="grid">
    <div id="dropZone">
        <div class="drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
        <h3 class="ephemeralDropZoneText">Glissez-déposez vos fichiers ici !</h3>
        <h4 class="ephemeralDropZoneText">ou cliquez pour charger un fichier</h4>
        <input type="file" id="fileInput" multiple accept=".jpeg,.jpg,.svg,.png,.gif,.mp4" />
    </div>
</div>
<script>
    const AVAILABLE_TAGS = <?= json_encode($tags) ?>;
</script>