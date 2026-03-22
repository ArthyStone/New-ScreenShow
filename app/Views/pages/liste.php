<div class="list">
    <div class="item">
        <div class="media-box">
            <img src="resources/medias/0.png">
        </div>
        <div class="info-box">
            <div class="visual-info">
                <span class="state direct"><i class="fa-solid fa-tower-broadcast"></i>Direct</span>
                <div class="user-info">
                    <span class="username">PierreShow</span>
                    <img class="avatar" src="https://static-cdn.jtvnw.net/jtv_user_pictures/753e553e-3aaa-40bc-9be0-8e63c2cd1d56-profile_image-70x70.png">
                </div>
            </div>
            <div class="additional-info">
                <span class="title">Placeholder</span>
                <span class="time">Durée indéterminée</span>
            </div>
        </div>
    </div>
</div>
<script>
    function formatDuration(ms) {
        const totalSeconds = Math.floor(ms / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = Math.floor(totalSeconds % 60);
        return minutes > 0 ? (minutes > 1 ? `${minutes} minutes` : `1 minute` ) : (seconds > 1 ? `${seconds} secondes` : `1 seconde`);
    }

    function giveItem(item, state, waitMs = 0) {
        let stateLabel;
        if (state === 'direct') {
            stateLabel = 'Direct';
        } else if (waitMs > 0) {
            stateLabel = `${formatDuration(waitMs)}`;
        } else {
            stateLabel = 'En attente';
        }
        return `
            <div class="media-box">
                ${
                    item.type === 'image'
                    ? `<img src="resources/medias/${item.id}.png">`
                    : `<video src="resources/medias/${item.id}.mp4" muted autoplay loop></video>`
                }
            </div>
            <div class="info-box">
                <div class="visual-info">
                    <span class="state ${state}"><i class="fa-solid fa-${state === 'direct' ? 'tower-broadcast' : 'hourglass'}"></i>${stateLabel}</span>
                    <div class="user-info">
                        <span class="username">${item.username}</span>
                        <img class="avatar" src="${item.user_pfp}">
                    </div>
                </div>
                <div class="additional-info">
                    <span class="title">${item.name}</span>
                    <span class="time">${item.duration != 0 ? formatDuration(item.duration) : 'Durée indéterminée'}</span>
                </div>
            </div>
        `;
    }

    function refreshTimes() {
        if (!currentState) return;
        const elapsed = Date.now() - currentStateReceivedAt;
        const remainingCurrent = Math.max(0, currentState.currentItem.duration - elapsed);

        const stateSpans = list.querySelectorAll('.state');
        const timeSpans  = list.querySelectorAll('.time');

        // Item en direct : mettre à jour le temps restant dans .time
        if (currentState.currentItem.duration != 0) {
            timeSpans[0].textContent = remainingCurrent > 0
                ? formatDuration(remainingCurrent)
                : 'Terminé';
        }

        // Items en attente : recalculer les délais
        let accumulatedWait = remainingCurrent;
        currentState.queue.forEach((item, index) => {
            const span = stateSpans[index + 1];
            if (span) {
                span.innerHTML = `<i class="fa-solid fa-hourglass"></i>${accumulatedWait > 0 ? `${formatDuration(accumulatedWait)}` : 'Imminent'}`;
            }
            accumulatedWait += item.duration;
        });
    }

    const list = document.querySelector('.list');
    let currentState = null;
    let currentStateReceivedAt = 0;
    let tickInterval = null;
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
            console.log(info);
            switch (info.type) {
                case 'stateUpdate':
                    currentState = info;
                    currentStateReceivedAt = Date.now();

                    list.innerHTML = "";
                    let itemElement = document.createElement('div');
                    itemElement.classList.add('item');
                    itemElement.innerHTML = giveItem(info.currentItem, "direct");
                    list.appendChild(itemElement);

                    let accumulatedWait = info.currentItem.duration;
                    info.queue.forEach((item) => {
                        let itemElement = document.createElement('div');
                        itemElement.classList.add('item');
                        itemElement.innerHTML = giveItem(item, "waiting", accumulatedWait);
                        list.appendChild(itemElement);
                        accumulatedWait += item.duration;
                    });

                    if (tickInterval) clearInterval(tickInterval);
                    tickInterval = setInterval(refreshTimes, 1000);
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
            if (tickInterval) clearInterval(tickInterval);
            currentState = null;
            list.innerHTML = `
            <div class="item">
                <div class="media-box">
                    <img src="resources/medias/0.png">
                </div>
                <div class="info-box">
                    <div class="visual-info">
                        <span class="state direct"><i class="fa-solid fa-tower-broadcast"></i>Direct</span>
                        <div class="user-info">
                            <span class="username">Zzz</span>
                            <img class="avatar" src="https://i.pinimg.com/736x/07/7d/39/077d39fcb76025474b6600f70f0d195a.jpg">
                        </div>
                    </div>
                    <div class="additional-info">
                        <span class="title">Il semblerait que le serveur ne réponde pas</span>
                        <span class="time">Durée indéterminée</span>
                    </div>
                </div>
            </div>
            `;
            setTimeout(connectToWebSocket, 1000);
        };
    }
    connectToWebSocket();
</script>