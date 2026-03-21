const WebSocket = require("ws");
const { createClient } = require("redis");
const http = require("http");

const server = http.createServer(async (req, res) => {
    res.setHeader("Access-Control-Allow-Origin", "*");
    res.setHeader("Access-Control-Allow-Headers", "Content-Type");
    res.setHeader("Access-Control-Allow-Methods", "POST, GET, OPTIONS");
    if (req.method === "POST") {

        let body = "";

        req.on("data", chunk => {
            body += chunk;
        });

        req.on("end", async () => {

            try {
                console.log("RAW BODY:", body);
                const data = JSON.parse(body);
                console.log("Parsed data:", data);

                switch (req.url) {

                    case "/add":
                        await addToQueue(data);
                        break;

                    case "/skip":
                        await skipCurrent();
                        break;

                    case "/pause":
                        pauseQueue();
                        break;

                    case "/resume":
                        resumeQueue();
                        break;

                    default:
                        res.writeHead(404);
                        return res.end("Not found");
                }

                res.writeHead(200, { "Content-Type": "application/json" });
                res.end(JSON.stringify({ success: true }));

            } catch (err) {
                res.writeHead(400);
                res.end("Invalid JSON");
            }
        });

    } else {
        res.writeHead(200);
        res.end("Queue server running");
    }
});

const wss = new WebSocket.Server({ server });

server.listen(8080, () => {
  console.log("HTTP + WebSocket running on port 8080");
});
const redis = createClient();

redis.connect();

const QUEUE_KEY = "mediaQueue";

let currentItem = null;
let currentTimeout = null;
let remainingTime = 0;
let startTimestamp = null;
let isPaused = false;

// LOGIQUE QUEUE

async function addToQueue(data) {

    const {
        id,
        name,
        duration,
        type,
        username,
        user_pfp,
        priority = false
    } = data;

    if (!name || !duration || !type || !username || !user_pfp) {
        return;
    }

    const item = {
        id,
        name,
        duration,
        createdAt: Date.now(),
        type,
        username,
        user_pfp
    };

    if (priority) {

        if (currentItem) {

            // Si le média n'est pas déjà en pause, on calcule le temps restant
            if (!isPaused) {
                const elapsed = Date.now() - startTimestamp;
                remainingTime -= elapsed;
            }

            // Sécurité : éviter durée négative
            if (remainingTime < 0) remainingTime = 0;

            clearTimeout(currentTimeout);

            // Mettre à jour la durée restante
            currentItem.duration = remainingTime;

            // Réinsérer l'ancien média en tête
            await redis.lPush(QUEUE_KEY, JSON.stringify(currentItem));

            // Reset état courant proprement
            currentItem = null;
            currentTimeout = null;
            startTimestamp = null;
            remainingTime = 0;
        }

        // Ajouter le nouveau média prioritaire
        await redis.lPush(QUEUE_KEY, JSON.stringify(item));

        // Lancer uniquement si pas en pause
        if (!isPaused) {
            await playNext();
        }

    } else {

        await redis.rPush(QUEUE_KEY, JSON.stringify(item));

        if (!currentItem && !isPaused) {
            await playNext();
        }
    }

    await publishState();
}

async function getNextItem() {

    const item = await redis.lPop(QUEUE_KEY);
    return item ? JSON.parse(item) : null;
}

async function playNext() {
    currentItem = await getNextItem();

    if (!currentItem) {
        clearTimeout(currentTimeout);
        currentTimeout = null;
        remainingTime = 0;
        await publishState();
        return;
    }

    remainingTime = currentItem.duration;
    startTimestamp = Date.now();

    startTimer();
    await publishState();
}

function startTimer() {
    clearTimeout(currentTimeout);

    currentTimeout = setTimeout(async () => {
        currentItem = null;
        await playNext();
    }, remainingTime);
}

function pauseQueue() {
    if (!currentItem || isPaused) return;

    isPaused = true;
    clearTimeout(currentTimeout);

    const elapsed = Date.now() - startTimestamp;
    remainingTime -= elapsed;

    publishState();
}

function resumeQueue() {
    if (!currentItem || !isPaused) return;

    isPaused = false;
    startTimestamp = Date.now();
    startTimer();

    publishState();
}

async function skipCurrent() {
    clearTimeout(currentTimeout);
    currentItem = null;
    remainingTime = 0;
    await playNext();
}

// STATE PUSH

async function getFullQueue() {
    const normal = await redis.lRange(QUEUE_KEY, 0, -1);

    return [
        ...normal.map(JSON.parse)
    ];
}

async function publishState() {
    const queue = await getFullQueue();

    let shownCurrent;

    if (isPaused) {
        shownCurrent = {
            id: "PAUSED_QUEUE",
            name: "Queue en pause",
            duration: 0,
            createdAt: 0,
            type: "image"
        };
    } else if (currentItem) {
        shownCurrent = currentItem;
    } else {
        shownCurrent = {
            id: "0",
            name: "Placeholder",
            duration: 0,
            createdAt: 0,
            type: "image",
            username: "PierreShow",
            user_pfp: "https://static-cdn.jtvnw.net/jtv_user_pictures/753e553e-3aaa-40bc-9be0-8e63c2cd1d56-profile_image-70x70.png",
        };
    }

    const payload = JSON.stringify({
        type: "stateUpdate",
        currentItem: shownCurrent,
        remainingTime,
        paused: isPaused,
        queue
    });

    wss.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(payload);
        }
    });
}

// WEBSOCKET API

wss.on("connection", (ws) => {
    publishState();
});

console.log("Server running on port 8080");