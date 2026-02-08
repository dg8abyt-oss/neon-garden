<?php
// Server-side logic for initial time sync
date_default_timezone_set('UTC');
$serverTime = time();
$hour = date('H');
$greeting = "Good Morning";
if ($hour >= 12 && $hour < 18) {
    $greeting = "Good Afternoon";
} elseif ($hour >= 18) {
    $greeting = "Good Evening";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neon Zen Garden</title>
    <style>
        :root {
            --bg: #0d1117;
            --card: #161b22;
            --text: #c9d1d9;
            --accent: #2ea043;
            --glow: #3fb950;
            --water: #58a6ff;
            --danger: #f85149;
        }
        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Courier New', monospace;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
            user-select: none;
        }
        .container {
            text-align: center;
            background: var(--card);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            width: 300px;
            border: 1px solid #30363d;
        }
        h1 { margin-top: 0; font-size: 1.2rem; opacity: 0.7; }
        .plant-stage {
            font-size: 80px;
            margin: 20px 0;
            filter: drop-shadow(0 0 15px var(--glow));
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }
        .plant-stage:active { transform: scale(0.9) translateY(5px); }
        .shake { animation: shake 0.5s; }
        @keyframes shake {
            0% { transform: translate(1px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1deg); }
            20% { transform: translate(-3px, 0px) rotate(1deg); }
            30% { transform: translate(3px, 2px) rotate(0deg); }
            40% { transform: translate(1px, -1px) rotate(1deg); }
            50% { transform: translate(-1px, 2px) rotate(-1deg); }
            60% { transform: translate(-3px, 1px) rotate(0deg); }
            70% { transform: translate(3px, 1px) rotate(-1deg); }
            80% { transform: translate(-1px, -1px) rotate(1deg); }
            90% { transform: translate(1px, 2px) rotate(0deg); }
            100% { transform: translate(1px, -2px) rotate(-1deg); }
        }
        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .bar-container {
            background: #21262d;
            height: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            overflow: hidden;
            position: relative;
        }
        .bar-fill {
            background: var(--accent);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            box-shadow: 0 0 10px var(--accent);
        }
        .controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        button {
            background: #21262d;
            border: 1px solid #30363d;
            color: var(--text);
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        button:hover:not(:disabled) {
            background: #30363d;
            transform: translateY(-2px);
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        button.btn-water {
            grid-column: span 2;
            background: rgba(88, 166, 255, 0.1);
            border-color: var(--water);
            color: var(--water);
            font-weight: bold;
        }
        button.btn-water:hover:not(:disabled) {
            background: var(--water);
            color: #fff;
            box-shadow: 0 0 15px var(--water);
        }
        .toast {
            position: fixed;
            bottom: 20px;
            background: var(--card);
            border: 1px solid var(--accent);
            padding: 10px 20px;
            border-radius: 8px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
        .info-panel {
            margin-top: 15px;
            font-size: 0.8rem;
            color: #8b949e;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><?php echo $greeting; ?>, Gardener.</h1>
    
    <div class="stats">
        <span>Level: <span id="level-display">1</span></span>
        <span>💧 Drops: <span id="currency-display">0</span></span>
    </div>

    <div class="plant-stage" id="plant" onclick="petPlant()">🌰</div>

    <div class="bar-container">
        <div class="bar-fill" id="xp-bar"></div>
    </div>
    
    <div style="font-size: 0.8rem; margin-bottom: 5px; color: #8b949e;">Hydration Level</div>
    <div class="bar-container">
        <div class="bar-fill" id="water-bar" style="background: var(--water); width: 100%;"></div>
    </div>

    <div class="controls">
        <button class="btn-water" id="btn-water" onclick="waterPlant()">💧 Water Plant</button>
        <button onclick="singToPlant()">🎵 Sing</button>
        <button onclick="buyFertilizer()">🧪 Fertilizer (50💧)</button>
    </div>
    
    <div class="info-panel">
        <span id="status-text">The seed is dormant.</span>
    </div>
</div>

<div id="toast" class="toast">Event Happened!</div>

<script>
    // Game State
    let state = {
        xp: 0,
        maxXp: 10,
        level: 1,
        drops: 0,
        hydration: 100,
        lastSave: <?php echo $serverTime; ?>,
        fertilizerActive: false
    };

    // Visual Assets
    const stages = ["🌰", "🌱", "🌿", "🪴", "🌳", "🍎", "⛲", "🌌"];
    const statusMessages = [
        "Waiting for water...",
        "Photosynthesis in progress...",
        "Roots are deepening...",
        "Leaves are rustling...",
        "The tree hums with energy."
    ];

    // DOM Elements
    const plantEl = document.getElementById('plant');
    const xpBar = document.getElementById('xp-bar');
    const waterBar = document.getElementById('water-bar');
    const levelDisplay = document.getElementById('level-display');
    const currencyDisplay = document.getElementById('currency-display');
    const statusText = document.getElementById('status-text');
    const btnWater = document.getElementById('btn-water');

    // Init
    loadGame();
    updateUI();
    
    // Game Loop (Hydration Decay)
    setInterval(() => {
        if (state.hydration > 0) {
            state.hydration -= 1; 
            if (state.hydration < 0) state.hydration = 0;
            updateUI();
        }
        saveGame();
    }, 1000); 

    function waterPlant() {
        if (state.hydration >= 100) {
            showToast("It's not thirsty!");
            return;
        }

        state.hydration = 100;
        
        // XP Gain Logic
        let gain = state.fertilizerActive ? 4 : 2;
        addXp(gain);
        
        // Visuals
        plantEl.classList.add('shake');
        setTimeout(() => plantEl.classList.remove('shake'), 500);
        showToast("Glug glug glug... +"+gain+" XP");
        
        updateUI();
    }

    function petPlant() {
        if (Math.random() > 0.7) {
            state.drops += 1;
            showToast("You found a water drop! +1 💧");
        }
        plantEl.classList.add('shake');
        setTimeout(() => plantEl.classList.remove('shake'), 500);
        updateUI();
    }

    function singToPlant() {
        addXp(5);
        showToast("You hum a melody... The plant likes it.");
        state.hydration -= 10;
        updateUI();
    }

    function buyFertilizer() {
        if (state.drops >= 50) {
            state.drops -= 50;
            state.fertilizerActive = true;
            showToast("Fertilizer applied! 2x XP for next waters.");
            updateUI();
        } else {
            showToast("Not enough drops!");
        }
    }

    function addXp(amount) {
        state.xp += amount;
        if (state.xp >= state.maxXp) {
            levelUp();
        }
        updateUI();
    }

    function levelUp() {
        state.xp = 0;
        state.maxXp = Math.floor(state.maxXp * 1.5);
        state.level++;
        state.drops += 10;
        showToast("LEVEL UP! The plant evolves.");
        state.fertilizerActive = false; 
    }

    function updateUI() {
        let stageIndex = Math.min(state.level - 1, stages.length - 1);
        plantEl.innerText = stages[stageIndex];
        
        xpBar.style.width = (state.xp / state.maxXp * 100) + "%";
        waterBar.style.width = state.hydration + "%";
        
        if (state.hydration < 30) waterBar.style.background = "#f85149";
        else waterBar.style.background = "#58a6ff";

        levelDisplay.innerText = state.level;
        currencyDisplay.innerText = state.drops;
        
        if (Math.random() > 0.9) {
            statusText.innerText = statusMessages[Math.floor(Math.random() * statusMessages.length)];
        }
        
        btnWater.disabled = state.hydration >= 100;
        if(state.hydration >= 100) btnWater.innerText = "Fully Hydrated";
        else btnWater.innerText = "💧 Water Plant";
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        t.innerText = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function saveGame() {
        localStorage.setItem('neon_garden_save', JSON.stringify(state));
    }

    function loadGame() {
        const saved = localStorage.getItem('neon_garden_save');
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                state = { ...state, ...parsed };
                
                // Offline progress
                const now = Math.floor(Date.now() / 1000);
                const diff = now - state.lastSave; 
                const decay = Math.floor(diff / 60); 
                state.hydration -= decay;
                if(state.hydration < 0) state.hydration = 0;
                
                if (decay > 5) {
                    showToast(`You were gone for ${Math.floor(diff/60)} mins. The plant got thirsty.`);
                }
                state.lastSave = now;
            } catch (e) {
                console.error("Save corrupted");
            }
        }
    }
    
    window.onbeforeunload = function() {
        state.lastSave = Math.floor(Date.now() / 1000);
        saveGame();
    };
</script>
</body>
</html>