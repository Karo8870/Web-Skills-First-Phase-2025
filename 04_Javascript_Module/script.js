const STAGES = {
  SEEDLING: { icon: '🌱', name: 'seedling' },
  SPROUT: { icon: '🌿', name: 'sprout' },
  GROWN: { icon: '🌳', name: 'full-grown plant' },
  WILTING: { icon: '🥀', name: 'wilting' },
  DEAD: { icon: '💀', name: 'dead' }
};

let gameState = {
  stage: STAGES.SEEDLING,
  day: 1,
  hour: 0,
  sunToday: false,
  waterToday: false,
  careStreak: 0,
  missingCareStreak: 0,
  gameOver: false
};

const startButton = document.getElementById('start');
const giveSunlightButton = document.getElementById('give-sunlight');
const waterPlantButton = document.getElementById('water-plant');
const plantDisplay = document.getElementById('plant-display');
const plantStatus = document.getElementById('plant-status');
const dayCounter = document.getElementById('day-counter');
const messageElement = document.getElementById('message');

function initGame() {
  updateDisplay();

  setInterval(updateHour, (15 * 1000) / 24);
}

function updateHour() {
  if (gameState.gameOver) return;

  gameState.hour++;

  if (gameState.hour >= 24) {
    gameState.hour = 0;
    nextDay();
  }

  updateDisplay();
}

function updateDisplay() {
  plantDisplay.textContent = gameState.stage.icon;
  plantStatus.textContent = `Your plant is a ${gameState.stage.name}.`;

  const formattedHour = String(gameState.hour).padStart(2, '0');
  dayCounter.textContent = `Day: ${gameState.day} - Hour: ${formattedHour}:00`;
}

function nextDay() {
  if (gameState.gameOver) return;

  gameState.day++;

  if (gameState.sunToday && gameState.waterToday) {
    gameState.careStreak++;
    gameState.missingCareStreak = 0;
    showMessage('Your plant is happy and growing!', 'success');

    if (gameState.careStreak >= 1) {
      growPlant();
      gameState.careStreak = 0;
    }
  } else {
    gameState.missingCareStreak++;
    gameState.careStreak = 0;

    if (gameState.missingCareStreak >= 2) {
      wiltPlant();
    } else {
      showMessage(
        'Your plant needs both water and sunlight to grow!',
        'warning'
      );
    }
  }

  gameState.sunToday = false;
  gameState.waterToday = false;

  updateDisplay();
  updateNeeds();
}

function growPlant() {
  if (gameState.stage === STAGES.SEEDLING) {
    gameState.stage = STAGES.SPROUT;
    showMessage('Your seedling has grown into a sprout!', 'success');
  } else if (gameState.stage === STAGES.SPROUT) {
    gameState.stage = STAGES.GROWN;
    showMessage('Your plant is fully grown! You win!', 'success');
    endGame(true);
  }
}

function wiltPlant() {
  if (gameState.stage === STAGES.WILTING) {
    gameState.stage = STAGES.DEAD;
    showMessage('Your plant has died! Game over.', 'failure');
    endGame(false);
  } else if (gameState.stage !== STAGES.DEAD) {
    gameState.stage = STAGES.WILTING;
    showMessage('Your plant is wilting! It needs care quickly!', 'failure');
  }
}

function endGame(success) {
  gameState.gameOver = true;

  const buttons = document.querySelectorAll('.buttons button');
  buttons.forEach((button) => (button.disabled = true));

  if (success) {
    plantStatus.textContent =
      "Congratulations! You've successfully grown a healthy plant!";
  } else {
    plantStatus.textContent = 'Game over. Your plant has died.';
  }

  alert(plantStatus.textContent);
}

function giveSun() {
  if (gameState.gameOver) return;

  if (gameState.sunToday) {
    showMessage('Your plant already has enough sunlight today!', 'warning');
  } else {
    gameState.sunToday = true;
    showMessage("You've given your plant some sunlight! ☀️", 'info');
    updateNeeds();
  }
}

function giveWater() {
  if (gameState.gameOver) return;

  if (gameState.waterToday) {
    showMessage('Your plant already has enough water today!', 'warning');
  } else {
    gameState.waterToday = true;
    showMessage("You've watered your plant! 💧", 'info');
    updateNeeds();
  }
}

function showMessage(text, type) {
  messageElement.textContent = text;
  messageElement.className = type || '';

  setTimeout(() => {
    messageElement.textContent = '';
    messageElement.className = '';
  }, 5000);
}

function updateNeeds() {
  let needs = [];

  if (!gameState.sunToday) {
    needs.push('sunlight');
  }

  if (!gameState.waterToday) {
    needs.push('water');
  }

  if (needs.length > 0) {
    plantStatus.textContent = `Your plant is a ${gameState.stage.name}. It needs ${needs.join(' and ')}.`;
  } else {
    plantStatus.textContent = `Your plant is a ${gameState.stage.name}. It's well taken care of today!`;
  }
}

function startGame() {
    console.log(123);
    
    startButton.style.display = 'none';
    giveSunlightButton.style.display = 'block';
    waterPlantButton.style.display = 'block';

    initGame();
}
