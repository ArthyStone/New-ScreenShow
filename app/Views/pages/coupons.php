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