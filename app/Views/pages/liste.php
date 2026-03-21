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
    function giveItem(item, state) {
        return `
        <div class="item">
            <div class="media-box">
                ${
                    item.type === 'image'
                    ? `<img   src="resources/medias/${item.id}.png">`
                    : `<video src="resources/medias/${item.id}.mp4" muted autoplay loop></video>`
                }
                
            </div>
            <div class="info-box">
                <div class="visual-info">
                    <span class="state ${state}"><i class="fa-solid fa-${state === 'direct' ? 'tower-broadcast' : 'hourglass'}"></i>${state === 'direct' ? 'Direct' : 'En attente'}</span>
                    <div class="user-info">
                        <span class="username">${item.username}</span>
                        <img class="avatar" src="${item.user_pfp}">
                    </div>
                </div>
                <div class="additional-info">
                <span class="title">${item.name}</span>
                <span class="time">${item.duration != 0 ? `Durée : ${Math.floor(item.duration / 1000 / 60)}m${item.duration / 1000 % 60}s` : 'Durée indéterminée'}</span>
                </div>
            </div>
        </div>
        `;
    }
    const list = document.querySelector('.list');
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
            list.innerHTML = giveItem(info.currentItem, "direct");
            info.queue.forEach((item, index) => {
                let itemElement = document.createElement('div');
                itemElement.classList.add('item');
                itemElement.innerHTML = giveItem(item, "Waiting");
                list.appendChild(itemElement);
            })
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