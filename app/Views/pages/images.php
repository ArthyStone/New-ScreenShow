<?php
declare(strict_types=1);
use MongoDB\BSON\ObjectId;
//var_dump($data['medias']);
$javascriptArray = [];
foreach ($data['medias'] as $media) {
    if($media['state'] != 'APPROVED') continue;
    $id = $media['_id'] instanceof ObjectId ? $media['_id']->__toString() : (string)$media['_id'];
    $type = $media['type'] ?? 'Inconnu';
    $name = $media['name'] ?? 'Non Nommé';
    $tags = $media['tags'] ?? [];
    $state = $media['state'] ?? 'Inconnu';
    $spent_tickets = $media['spent_tickets'] ?? 0;
    $creatorName = $media['created_by']['username'] ?? 'Inconnu';
    $creatorPFP = $media['created_by']['twitchPFP'] ?? 'https://i.pinimg.com/170x/1d/ec/e2/1dece2c8357bdd7cee3b15036344faf5.jpg';
    $javascriptArray[] = [
        'id' => $id,
        'type' => $type,
        'name' => $name,
        'tags' => $tags,
        'state' => $state,
        'spent_tickets' => $spent_tickets,
        'creatorUsername' => $creatorName,
        'creatorPFP' => $creatorPFP,
    ];
}
?>
<div id="overlay" onclick="closeOverlay()"></div>
<div class="options">
    <div class="search-filters">
        <input type="text" id="search-input" placeholder="Rechercher" oninput="search()">
        <button onclick="toggleTagsVisibility()">Filtres <i class="fa-solid fa-filter"></i></button>
        <button onclick="deleteFilters()" class="reinit">Réinitialiser <i class="fa-solid fa-rectangle-xmark"></i></button>
    </div>
    <div class="tagArray">
<?php
//var_dump($data['tags']);
foreach($data['tags'] as $tag){
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
sortedMedias = [...medias];
let sortCriteria = JSON.parse(localStorage.getItem('sortCriteria')) ?? {criteria: 'date', order: 'desc'};
if (sortCriteria.criteria === 'date') {
    sortedMedias.sort((a, b) => b.id.localeCompare(a.id));
} else if (sortCriteria.criteria === 'name') {
    sortedMedias.sort((a, b) => a.name.localeCompare(b.name));
} else if (sortCriteria.criteria === 'popu') {
    sortedMedias.sort((a, b) => (b.spent_tickets || 0) - (a.spent_tickets || 0));
}
if(sortCriteria.order === 'asc') {
    sortedMedias.reverse();
}
document.querySelector(`.sort.${sortCriteria.criteria}`).innerHTML += sortCriteria.order === 'asc' ? '<i class="fa-solid fa-sort-up"></i>' : '<i class="fa-solid fa-sort-down"></i>';
document.querySelector(`.sort.${sortCriteria.criteria}`).classList.add('active');
function displayMedias() {
    const container = document.querySelector('.media-container');
    container.innerHTML = '';
    sortedMedias.forEach(async (media, index) => {
        const mediaElement = document.createElement('div');
        mediaElement.classList.add('media-item');
        // mediaElement.innerHTML = `
        //     <h3>${media.name}</h3>
        //     <p>Type: ${media.type}</p>
        //     <p>Tags: ${media.tags.join(', ')}</p>
        //     <p>State: ${media.state}</p>
        //     <p>Created by: ${media.created_by}</p>
        // `;
        mediaElement.innerHTML = `
        <${media.type === "image" ? "img" : "video"}
            ${media.type === "image" ? "loading='lazy'" : "muted autoplay loop"}
            src="https://screenshow.pierreshow.fr/display/approved/${media.id}.${media.type === "image" ? "png" : "mp4"}" alt="${media.name}"
            onclick="openOverlay('${media.id}')"
            class="adaptativeMedia"
            ${media.type === "image" ? ">" : "></video>"}
        <p class="caption">${media.name}</p>
        `;
        container.appendChild(mediaElement);
    });
    document.querySelectorAll('.adaptativeMedia').forEach(media => {
        if (media.tagName.toLowerCase() === 'img') {
            adaptMedia(media);
        } else {
            adaptMedia(media);
        }
    });
}
displayMedias();
function filterMedias() {
    sortedMedias = [];
    const nameFilter = document.querySelector('#search-input').value;
    let tagsFilter = [];
    document.querySelectorAll('.tag.tagToggle.enabled').forEach(button => {
        tagsFilter.push(button.id.replace('_', ' '));
    })
    medias.forEach(media => {
        if(!media['name'].toLowerCase().includes(nameFilter.toLowerCase())) return;
        let hasAllTheTags = true;
        tagsFilter.forEach(tag => {
            if(!media['tags'].includes(tag)) return hasAllTheTags = false;
        })
        if(!hasAllTheTags) return;
        sortedMedias.push(media);
    })
    let sortCriteria = JSON.parse(localStorage.getItem('sortCriteria')) ?? {criteria: 'date', order: 'desc'};
    if (sortCriteria.criteria === 'date') {
        sortedMedias.sort((a, b) => b.id.localeCompare(a.id));
    } else if (sortCriteria.criteria === 'name') {
        sortedMedias.sort((a, b) => a.name.localeCompare(b.name));
    } else if (sortCriteria.criteria === 'popu') {
        sortedMedias.sort((a, b) => (b.spent_tickets || 0) - (a.spent_tickets || 0));
    }
    if(sortCriteria.order === 'asc') {
        sortedMedias.reverse();
    }
    displayMedias();
}
let timeout;
function search() {
    clearTimeout(timeout);
    timeout = setTimeout( () => {
        filterMedias();    
    }, 500)
}

document.querySelector('#search-input').addEventListener('keyup', event => {
    if(event['key'] == 'Enter') {
        clearTimeout(timeout);
        filterMedias();
    }
})
function toggleTagsVisibility() {
    document.querySelector('.tagArray').classList.toggle('visible');
}
function toggleTag(tag) {
    document.querySelector("#"+tag).classList.toggle('enabled');
    filterMedias();
}
function adaptMedia(media) {
    if(media.tagName.toLowerCase() === 'img') {
        media.onload = () => {
            if (media.naturalHeight > media.naturalWidth) {
                media.classList.add('vertical');
            } else {
                media.classList.add('horizontal');
            }
        };
    } else {
        media.onloadedmetadata = () => {
            if (media.videoHeight > media.videoWidth) {
                media.classList.add('vertical');
            } else {
                media.classList.add('horizontal');
            }
        }
    }
}
function sortBy(criteria) {
    let sortCriteria = JSON.parse(localStorage.getItem('sortCriteria')) ?? {criteria: 'date', order: 'desc'};
    localStorage.setItem('sortCriteria', JSON.stringify({criteria, order: criteria === sortCriteria?.criteria ? (sortCriteria?.order === 'asc' ? 'desc' : 'asc') : sortCriteria?.order}));
    sortCriteria = JSON.parse(localStorage.getItem('sortCriteria'));
    if (criteria === 'date') {
        sortedMedias.sort((a, b) => b.id.localeCompare(a.id));
    } else if (criteria === 'name') {
        sortedMedias.sort((a, b) => a.name.localeCompare(b.name));
    } else if (criteria === 'popu') {
        sortedMedias.sort((a, b) => (b.spent_tickets || 0) - (a.spent_tickets || 0));
    }
    if(sortCriteria.order === 'asc') {
        sortedMedias.reverse();
    }
    document.querySelectorAll('.sort').forEach(button => {
        button.innerHTML = button.innerHTML.replace('<i class="fa-solid fa-sort-up"></i>', '').replace('<i class="fa-solid fa-sort-down"></i>', '')
        button.classList.remove('active');
    });
    document.querySelector(`.sort.${criteria}`).innerHTML += sortCriteria.order === 'asc' ? '<i class="fa-solid fa-sort-up"></i>' : '<i class="fa-solid fa-sort-down"></i>';
    document.querySelector(`.sort.${criteria}`).classList.add('active');
    displayMedias();
}
function deleteFilters() {
    sortedMedias = medias;
    document.querySelectorAll(".tag.tagToggle").forEach(button => {
        button.classList.remove('enabled');
    })
    document.querySelector('#search-input').value = "";
    filterMedias();
}



function navigateMedia(e, direction) {
    e.stopPropagation();
    const overlay = document.querySelector('#overlay');
    const currentId = overlay.querySelector('.overlay-media').getAttribute('src').split('/').pop().split('.').shift();
    const currentIndex = sortedMedias.findIndex(m => m.id === currentId);
    let newIndex = currentIndex + direction;
    if (newIndex < 0) newIndex = sortedMedias.length - 1;
    if (newIndex >= sortedMedias.length) newIndex = 0;
    openOverlay(sortedMedias[newIndex].id);
}

function openOverlay(id) {
    const media = sortedMedias.find(m => m.id === id);
    const overlay = document.querySelector('#overlay');
    const time = localStorage.getItem('timeSelect') ?? 30;
    overlay.classList.add('active');
    overlay.innerHTML = `
        <div class="tags" onclick="event.stopPropagation()">${media.tags.map(tag => `<span class="tag">#${tag}</span>`).join('')}</div>
        <div class="creator-info" onclick="event.stopPropagation()">
            <img src="${media.creatorPFP}" alt="Photo de profil de ${media.creatorUsername}" class="creator-pfp">
            <span class="creator-username">${media.creatorUsername}</span>
        </div>
        <button class="close-button" onclick="closeOverlay()">×</button>
        ${sortedMedias.length > 1 ? `
        <div class="left-arrow" onclick="navigateMedia(event, -1)">❮</div>
        <div class="right-arrow" onclick="navigateMedia(event, 1)">❯</div>
        ` : ""}
        <div class="media-wrapper">
            <${media.type === "image" ? "img" : "video"}
            onclick="event.stopPropagation()"
            ${media.type === "image" ? "loading='lazy'" : "muted autoplay loop"}
            src="https://screenshow.pierreshow.fr/display/approved/${media.id}.${media.type === "image" ? "png" : "mp4"}" alt="${media.name}"
            class="overlay-media"
            ${media.type === "image" ? ">" : "></video>"}
        </div>
        <div class="actions-container" onclick="event.stopPropagation()">
            <select name="duration" onchange="selectDuration(event)">
                <option value="10"${time == 10 ? " selected" : ""}>10 secondes</option>
                <option value="20"${time == 20 ? " selected" : ""}>20 secondes</option>
                <option value="30"${time == 30 ? " selected" : ""}>30 secondes</option>
                <option value="60"${time == 60 ? " selected" : ""}>1 minute</option>
                <option value="120"${time == 120 ? " selected" : ""}>2 minutes</option>
                <option value="300"${time == 300 ? " selected" : ""}>5 minutes</option>
                <option value="600"${time == 600 ? " selected" : ""}>10 minutes</option>
                <option value="1800"${time == 1800 ? " selected" : ""}>30 minutes</option>
            </select>
            <button class="add-btn" onclick="addToQueue(${media.id})">Ajouter</button>
        </div>
        <div class="ticketCost" onclick="event.stopPropagation()">
            <div id="innerTicketCost">
                <p>coût: ${time} <i class="fa-solid fa-ticket"></i></p>
            </div>
        </div>
    `;
}
function closeOverlay() {
    const overlay = document.querySelector('#overlay');
    overlay.classList.remove('active');
    overlay.innerHTML = '';
}
function selectDuration(e) {
    localStorage.setItem('timeSelect', e.target.value);
    
}
function addToQueue(mediaId) {
    const duration = localStorage.getItem('timeSelect') ?? 30;
    fetch("/api/queue/add", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ mediaId, duration, "priority": "none"})
    })
    // .then(res => res.json())
    .then(data => {
        console.log(data);
    })
    .catch(err => {
        console.log(err);
    });
}
</script>