const ALLOWED_EXT = ['jpeg', 'jpg', 'svg', 'png', 'gif', 'mp4'];
const MAX_SIZE = 100 * 1024 * 1024, MAX_FILES = 20;
let files = [];

const grid     = document.getElementById('grid');
const dropZone = document.getElementById('dropZone');
const fileInput= document.getElementById('fileInput');
const errorMsg = document.getElementById('errorMsg');
const counter  = document.getElementById('counter');

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('dragover'); handleFiles(e.dataTransfer.files); });
fileInput.addEventListener('change', () => { handleFiles(fileInput.files); fileInput.value = ''; });

// ── Tag drag & drop ──────────────────────────────────────────
let draggedTag = null;

function buildTagPalette() {
    const existing = document.getElementById('tagPalette');
    if (existing) existing.remove();

    if (!AVAILABLE_TAGS || !AVAILABLE_TAGS.length) return;

    const palette = document.createElement('div');
    palette.id = 'tagPalette';
    palette.innerHTML = `
        <span class="palette-label">Tags</span>
        ${AVAILABLE_TAGS.map(t => `
            <span class="palette-tag" draggable="true" data-tag="${t}">${t}</span>
        `).join('')}
    `;

    grid.parentElement.insertBefore(palette, grid);

    palette.querySelectorAll('.palette-tag').forEach(el => {
        el.addEventListener('dragstart', e => {
            draggedTag = el.dataset.tag;
            el.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'copy';
        });
        el.addEventListener('dragend', () => {
            el.classList.remove('dragging');
            draggedTag = null;
        });
    });
}

buildTagPalette();

// ── Upload ───────────────────────────────────────────────────

const uploadBar = document.getElementById('uploadBar');

async function uploadFiles() {
    if (!files.length) return;

    // Lire les noms depuis les inputs avant l'envoi
    Array.from(grid.querySelectorAll('.file-cell')).forEach((cell, idx) => {
        const input = cell.querySelector('.file-name-input');
        if (input) files[idx].name = input.value.trim() || files[idx].name;
    });

    setUploadState(true);
    errorMsg.innerHTML = '';

    let done = 0;
    const total = files.length;
    updateProgress(0, total);

    const results = await Promise.allSettled(files.map(async (entry) => {
        const fd = new FormData();
        fd.append('file', entry.file, entry.name + entry.ext);
        fd.append('name', entry.name);
        fd.append('tags', JSON.stringify(entry.tags));
        fd.append('type', entry.ext === '.mp4' ? 'video' : 'image');

        const res = await fetch('/api/medias/add', { method: 'POST', body: fd });
        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            throw new Error(body.error ?? `Erreur ${res.status} pour « ${entry.name} »`);
        }
        done++;
        updateProgress(done, total);
        return entry.name;
    }));

    const errors = results
        .filter(r => r.status === 'rejected')
        .map(r => r.reason.message);

    const successes = results.filter(r => r.status === 'fulfilled');

    // Supprimer les fichiers réussis
    if (successes.length) {
        const successNames = new Set(successes.map(r => r.value));
        files = files.filter(f => !successNames.has(f.name));
    }

    if (errors.length) {
        errorMsg.innerHTML = errors.map(e =>
            `<div class="error-msg"><i class="fa-solid fa-circle-exclamation" style="margin-right:4px"></i>${e}</div>`
        ).join('');
    }

    setUploadState(false);
    render();
}

function updateProgress(done, total) {
    if (!uploadBar) return;
    const pct = total ? Math.round((done / total) * 100) : 0;
    uploadBar.style.width = pct + '%';
    uploadBar.closest('.upload-progress')?.classList.toggle('visible', pct > 0 && pct < 100);
}

function setUploadState(loading) {
    const btn = document.getElementById('uploadBtn');
    if (!btn) return;
    btn.disabled = loading;
    btn.innerHTML = loading
        ? `<i class="fa-solid fa-spinner fa-spin"></i> Envoi en cours…`
        : `<i class="fa-solid fa-cloud-arrow-up"></i> Envoyer les médias`;
}

// ────────────────────────────────────────────────────────────

function handleFiles(incoming) {
    errorMsg.innerHTML = "";
    let errors = [];
    for (const f of incoming) {
        if (files.length >= MAX_FILES) { errors.push(`Limite de ${MAX_FILES} fichiers atteinte.`); break; }
        const ext = f.name.split('.').pop().toLowerCase();
        if (!ALLOWED_EXT.includes(ext)) { errors.push(`"${f.name}" : format non accepté.`); continue; }
        if (f.size > MAX_SIZE) { errors.push(`"${f.name}" : trop lourd (max 100 Mo).`); continue; }
        files.push({ file: f, name: f.name.replace(/\.[^.]+$/, ''), ext: '.' + ext, tags: [], url: URL.createObjectURL(f) });
    }
    errorMsg.innerHTML = errors.map(e => `<div class="error-msg"> <i class="fa-solid fa-circle-exclamation" style="margin-right:4px"></i>${e} </div>`).join('');
    render();
}

function toggleTag(idx, tag) {
    const entry = files[idx];
    if (entry.tags.includes(tag)) {
        entry.tags = entry.tags.filter(t => t !== tag);
    } else {
        entry.tags.push(tag);
    }
    const cell = grid.querySelectorAll('.file-cell')[idx];
    if (cell) {
        cell.querySelectorAll('.mobile-tag-btn').forEach(btn => {
            btn.classList.toggle('selected', entry.tags.includes(btn.dataset.tag));
        });
        const tagsWrap = cell.querySelector('.tags-wrap');
        if (tagsWrap) {
            tagsWrap.innerHTML = entry.tags.map(t => `
                <span class="tag">${t}
                    <button onclick="removeTag(${idx},'${t.replace(/'/g,"\\'")}')">
                        <i class="fa-solid fa-xmark" style="font-size:9px"></i>
                    </button>
                </span>
            `).join('');
        }
    }
}

function removeTag(idx, t) {
    files[idx].tags = files[idx].tags.filter(x => x !== t);
    render();
}
function removeFile(idx) { URL.revokeObjectURL(files[idx].url); files.splice(idx, 1); render(); }

function getThumb(entry) {
    const ext = entry.ext.replace('.', '');
    if (ext === 'mp4') return `<video src="${entry.url}" muted playsinline></video>`;
    if (['jpeg','jpg','png','gif'].includes(ext)) return `<img src="${entry.url}" alt="" />`;
    return `<i class="fa-solid fa-file-image file-icon" aria-hidden="true"></i>`;
}

function render() {
    dropZone.classList.toggle('compact', files.length > 0);

    Array.from(grid.querySelectorAll('.file-cell')).forEach(el => el.remove());
    dropZone.remove();

    files.forEach((entry, idx) => {
        const cell = document.createElement('div');
        cell.className = 'file-cell';

        cell.innerHTML = `
            <button class="remove-btn" onclick="removeFile(${idx})" aria-label="Supprimer">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="file-thumb-full">${getThumb(entry)}</div>
            <div class="file-footer">
                <div style="display:flex;align-items:center;gap:4px">
                    <input class="file-name-input" type="text" value="${entry.name.replace(/"/g,'&quot;')}" />
                    <span class="file-ext">${entry.ext}</span>
                </div>
                <div class="tags-wrap">
                    ${entry.tags.map(t => `
                        <span class="tag">${t}
                            <button onclick="removeTag(${idx},'${t.replace(/'/g,"\\'")}')">
                                <i class="fa-solid fa-xmark" style="font-size:9px"></i>
                            </button>
                        </span>
                    `).join('')}
                </div>
                ${AVAILABLE_TAGS && AVAILABLE_TAGS.length ? `
                <div class="mobile-tag-buttons">
                    ${AVAILABLE_TAGS.map(t => `
                        <button class="mobile-tag-btn${entry.tags.includes(t) ? ' selected' : ''}" data-tag="${t.replace(/"/g,'&quot;')}">${t}</button>
                    `).join('')}
                </div>` : ''}
                <span class="file-size">${(entry.file.size / 1024 / 1024).toFixed(2)} Mo</span>
            </div>
        `;

        cell.querySelectorAll('.mobile-tag-btn').forEach(btn => {
            btn.addEventListener('click', () => toggleTag(idx, btn.dataset.tag));
        });

        cell.addEventListener('dragover', e => {
            if (!draggedTag) return;
            e.preventDefault();
            cell.classList.add('tag-drop-target');
        });
        cell.addEventListener('dragleave', () => cell.classList.remove('tag-drop-target'));
        cell.addEventListener('drop', e => {
            cell.classList.remove('tag-drop-target');
            if (!draggedTag) return;
            e.preventDefault();
            toggleTag(idx, draggedTag);
        });

        grid.appendChild(cell);
    });

    grid.appendChild(dropZone);

    counter.textContent = files.length
        ? `${files.length} / ${MAX_FILES} fichier${files.length > 1 ? 's' : ''} sélectionné${files.length > 1 ? 's' : ''}`
        : '';

    // Bouton d'envoi
    let uploadBtn = document.getElementById('uploadBtn');
    if (files.length > 0) {
        if (!uploadBtn) {
            const wrapper = document.createElement('div');
            wrapper.className = 'upload-wrapper';
            wrapper.innerHTML = `
                <div class="upload-progress"><div class="upload-bar" id="uploadBar"></div></div>
                <button id="uploadBtn" class="upload-btn" onclick="uploadFiles()">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Envoyer les médias
                </button>
            `;
            grid.parentElement.appendChild(wrapper);
        }
    } else {
        document.querySelector('.upload-wrapper')?.remove();
    }
}