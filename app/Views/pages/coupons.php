<div class="container">
    <div class="section">
        <p class="section-title">Actions</p>
        <div class="grid-2">

            <div class="card">
                <p class="card-title">Créer un coupon</p>
                <div class="form-group">
                    <label>Valeur (tickets)</label>
                    <input type="number" id="value" placeholder="ex: 50" min="1">
                </div>
                <div class="form-group">
                    <label>Utilisations max</label>
                    <input type="number" id="uses" placeholder="ex: 10" min="1">
                </div>
                <div class="form-group">
                    <label>Code personnalisé <span>(optionnel)</span></label>
                    <input type="text" id="code" placeholder="laissez vide pour auto">
                </div>
                <div class="form-group">
                    <label>Validité (jours)</label>
                    <input type="number" id="days" placeholder="14" min="1" value="14">
                </div>
                <button class="btn btn-primary" id="create-btn" onclick="createCoupon()">Créer le coupon</button>
                <div class="feedback" id="feedback"></div>
            </div>

            <div class="card">
                <p class="card-title">Utiliser un coupon</p>
                <p class="card-desc">Entrez un code pour créditer vos tickets. Vous ne pouvez pas utiliser vos propres coupons.</p>
                <div class="form-group">
                    <label>Code du coupon</label>
                    <input type="text" id="u-code" placeholder="ex: a3f8b2c1d4">
                </div>
                <button class="btn btn-primary" id="consume-btn" onclick="consumeCoupon()">Utiliser le coupon</button>
                <div class="feedback" id="u-feedback"></div>
            </div>

        </div>
    </div>

    <div class="section">
        <p class="section-title">Mes coupons créés</p>
        <div class="card">
            <div id="coupon-list">
                <div id="coupon-empty" class="empty">Aucun coupon créé pour l'instant.</div>
            </div>
        </div>
    </div>
</div>
<script>
    // ── Rendu de la liste ──────────────────────────────────────────
 
    function getStatus(expiresAt, remainingUses) {
        // expiresAt vient de MongoDB : objet { $date: { $numberLong: "..." } } ou timestamp ms
        const exp = expiresAt?.$date?.$numberLong
            ? new Date(parseInt(expiresAt.$date.$numberLong))
            : new Date(expiresAt);
        if (exp < new Date()) return { label: 'expiré', cls: 'badge-danger' };
        if (remainingUses === 0) return { label: 'épuisé', cls: 'badge-warn' };
        return { label: 'actif', cls: 'badge-success' };
    }
 
    function parseDate(expiresAt) {
        return expiresAt?.$date?.$numberLong
            ? new Date(parseInt(expiresAt.$date.$numberLong))
            : new Date(expiresAt);
    }
 
    function renderList(coupons) {
        const list  = document.getElementById('coupon-list');
        const empty = document.getElementById('coupon-empty');
 
        if (!coupons || coupons.length === 0) {
            list.innerHTML = '<div class="empty">Aucun coupon créé pour l\'instant.</div>';
            return;
        }
 
        const rows = coupons.map(c => {
            const expiresAt = parseDate(c.expiresAt);
            const status    = getStatus(c.expiresAt, c.remainingUses);
            const dateStr   = expiresAt.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
            return `<div class="coupon-row">
                <span class="code-badge">${c.code}</span>
                <div class="coupon-meta">
                    <p>${c.remainingUses} util. · expire le ${dateStr}</p>
                </div>
                <span class="value-chip">+${c.value} tkts</span>
                <span class="badge ${status.cls}">${status.label}</span>
                <button class="btn btn-link" onclick="deleteCoupon('${c.code}')">Supprimer</button>
            </div>`;
        }).join('');
 
        list.innerHTML = rows;
    }
 
    // ── Feedback ───────────────────────────────────────────────────
 
    function setFeedback(id, msg, ok) {
        const el = document.getElementById(id);
        el.textContent = msg;
        el.className = 'feedback ' + (ok ? 'ok' : 'ko');
    }
 
    function clearFeedback(id) {
        document.getElementById(id).className = 'feedback';
    }
 
    function setLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        btn.disabled = loading;
        btn.textContent = loading
            ? (btnId === 'create-btn' ? 'Création…' : 'Envoi…')
            : (btnId === 'create-btn' ? 'Créer le coupon' : 'Utiliser le coupon');
    }
 
    // ── Créer un coupon → POST /api/coupons/create ─────────────────
    // Controller retourne : { success, coupon, newTicketsCount } | { success, reason }
 
    function createCoupon() {
        const value = parseInt(document.getElementById('value').value);
        const uses  = parseInt(document.getElementById('uses').value);
        const code  = document.getElementById('code').value.trim() || null;
        const days  = parseInt(document.getElementById('days').value) || 14;
 
        if (!value || !uses)
            return setFeedback('feedback', "Veuillez renseigner la valeur et le nombre d'utilisations.", false);
 
        clearFeedback('feedback');
        setLoading('create-btn', true);
 
        fetch('/api/coupons/create', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ value, uses, code, daysValid: days }),
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                setFeedback('feedback', data.reason ?? 'Une erreur est survenue.', false);
                return;
            }
            console.log(data);
            const couponCode = data.coupon?.code ?? code ?? '?';
            setFeedback('feedback',
                `Coupon "${couponCode}" créé. Il vous reste ${data.newTicketsCount} tickets.`, true);
 
            document.getElementById('value').value = '';
            document.getElementById('uses').value  = '';
            document.getElementById('code').value  = '';
            document.getElementById('days').value  = '14';
 
            loadCoupons();
            document.querySelector('.user-info .user-details .tickets').innerHTML = data.newTicketsCount + '<i class="fa-solid fa-ticket"></i>';
        })
        .catch((err) => {
             console.error('Error consuming coupon:', err);
             const error = err.status === 500
                ? "Impossible de contacter le serveur."
                : "Ce code de coupon n'existe pas, est expiré, épuisé ou vous l'avez déjà utilisé.";
            setFeedback('u-feedback', error, false);
        })
        .finally(() => setLoading('create-btn', false));
    }
 
    // ── Utiliser un coupon → POST /api/coupons/consume ─────────────
    // Controller retourne : { success, newTicketsCount } | { success, reason }
 
    function consumeCoupon() {
        const code = document.getElementById('u-code').value.trim();
        if (!code) return setFeedback('u-feedback', 'Veuillez entrer un code.', false);
 
        clearFeedback('u-feedback');
        setLoading('consume-btn', true);
 
        fetch('/api/coupons/consume', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ code }),
        })
        .then(res => res.json())
        .then(data => {
            console.log(data);
            if (!data.success) {
                setFeedback('u-feedback', data.reason ?? 'Une erreur est survenue.', false);
                return;
            }
 
            setFeedback('u-feedback', `Coupon utilisé avec succès ! Vous avez maintenant ${data.newTicketsCount} tickets.`, true);
            document.getElementById('u-code').value = '';
            document.querySelector('.user-info .user-details .tickets').innerHTML = data.newTicketsCount + '<i class="fa-solid fa-ticket"></i>';
        })
        .catch((err) => {
             console.error('Error consuming coupon:', err);
             const error = err.status === 500
                ? "Impossible de contacter le serveur."
                : "Ce code de coupon n'existe pas, est expiré, épuisé ou vous l'avez déjà utilisé.";
            setFeedback('u-feedback', error, false);
        })
        .finally(() => setLoading('consume-btn', false));
    }

    function deleteCoupon(code) {
        if (!confirm(`Êtes-vous sûr de vouloir supprimer le coupon "${code}" ?`)) return;
 
        fetch('/api/coupons/delete', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ code }),
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.reason ?? 'Une erreur est survenue.');
                return;
            }
            loadCoupons();
            document.querySelector('.user-info .user-details .tickets').innerHTML = data.newTicketsCount + '<i class="fa-solid fa-ticket"></i>';
        })
        .catch(() => alert('Impossible de contacter le serveur.'))
    }
 
    // ── Chargement initial de la liste → GET /api/coupons/list ─────
 
    function loadCoupons() {
        fetch('/api/coupons/list')
        .then(res => res.json())
        .then(data => renderList(data.coupons ?? []))
        .catch(err => console.error('Impossible de charger les coupons :', err));
    }
 
    loadCoupons();
</script>