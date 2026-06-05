<?php
declare(strict_types=1);
use MongoDB\BSON\ObjectId;
$javascriptArray = [];
foreach ($medias as $media) {
    if($media['state'] != 'APPROVED') continue;
    $id = $media['_id'] instanceof ObjectId ? $media['_id']->__toString() : (string)$media['_id'];
    $type = $media['type'] ?? 'Inconnu';
    $name = $media['name'] ?? 'Non Nommé';
    $mediaTags = $media['tags'] ?? [];
    $state = $media['state'] ?? 'Inconnu';
    $spent_tickets = $media['spent_tickets'] ?? 0;
    $creatorName = $media['created_by']['username'] ?? 'Inconnu';
    $creatorPFP = $media['created_by']['twitchPFP'] ?? 'https://i.pinimg.com/170x/1d/ec/e2/1dece2c8357bdd7cee3b15036344faf5.jpg';
    $javascriptArray[] = [
        'id' => $id,
        'type' => $type,
        'name' => $name,
        'tags' => $mediaTags,
        'state' => $state,
        'spent_tickets' => $spent_tickets,
        'creatorUsername' => $creatorName,
        'creatorPFP' => $creatorPFP,
    ];
}
?>
<div id="feedback-container"></div>
<div id="overlay" onclick="closeOverlay()"></div>
<div class="options">
    <div class="search-filters">
        <input type="text" id="search-input" placeholder="Rechercher" oninput="search()">
        <button onclick="toggleTagsVisibility()">Filtres <i class="fa-solid fa-filter"></i></button>
        <button onclick="deleteFilters()" class="reinit">Réinitialiser <i class="fa-solid fa-rectangle-xmark"></i></button>
    </div>
    <div class="tagArray">
<?php
    foreach($tags as $tag){
        $altTag = str_replace(' ', '_', $tag);
        echo "<button class='tag tagToggle' id='$altTag' onclick='toggleTag(\"$altTag\")'>$tag</button>";
    }
?>
    </div>
    <div class="sort-options">
        <button onclick="sortBy('date')" class="sort date">Date</button>
        <button onclick="sortBy('name')" class="sort name">Nom</button>
        <button onclick="sortBy('popu')" class="sort popu">Popularité</button>
    </div>
</div>
<div class="media-container"></div>
</div>
<script>
    const medias = <?= json_encode($javascriptArray) ?>;
    const mediaFolder = "<?= $mediaFolder ?>";
</script>