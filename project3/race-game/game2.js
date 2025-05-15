    const elements = {
        gameWrapper: document.querySelector('.game-wrapper'),
        player1: document.getElementById('player1'),
        player2: document.getElementById('player2'),
        p1Speed: document.getElementById('p1-speed'),
        p1Lap: document.getElementById('p1-lap'),
        p1MaxLap: document.getElementById('p1-max-lap'),
        p1Penalty: document.getElementById('p1-penalty'),
        p2Speed: document.getElementById('p2-speed'),
        p2Lap: document.getElementById('p2-lap'),
        p2MaxLap: document.getElementById('p2-max-lap'),
        p2Penalty: document.getElementById('p2-penalty'),
        startScreen: document.getElementById('startScreen'),
        status: document.getElementById('status'),
        startBtn: document.getElementById('startBtn'),
        lapsSelect: document.getElementById('lapsSelect'),
        debugMask: document.getElementById('debugMask'),
        debugToggle: document.getElementById('debugToggle'),
        maskOpacity: document.getElementById('maskOpacity'),
        showFinishLine: document.getElementById('showFinishLine')
    };

    // Размеры игры
    const gameSize = { width: 800, height: 600 };
    let trackImgSize = { width: 0, height: 0 };
    let maskImgSize = { width: 0, height: 0 };

    // Состояние игры
    const gameState = {
        started: false,
        lapsRequired: 3,
        startTime: 0,
        lastPenaltyTime: 0,
        penaltyCooldown: 2000,
        players: {
            player1: {
                x: 370,
                y: 150,
                width: 60,
                height: 100,
                speed: 0,
                maxSpeed: 5,
                acceleration: 0.1,
                deceleration: 0.2,
                turnSpeed: 0.05,
                angle: Math.PI/2,
                lap: 0,
                penalty: 0,
                finished: false,
                penaltyTimer: 0
            },
            player2: {
                x: 400,
                y: 150,
                width: 60,
                height: 100,
                speed: 0,
                maxSpeed: 5,
                acceleration: 0.1,
                deceleration: 0.2,
                turnSpeed: 0.05,
                angle: Math.PI/2,
                lap: 0,
                penalty: 0,
                finished: false,
                penaltyTimer: 0
            }
        },
        keys: {
            ArrowUp: false,
            ArrowDown: false,
            ArrowLeft: false,
            ArrowRight: false,
            w: false,
            a: false,
            s: false,
            d: false
        }
    };

    // Canvas для маски трассы
    const trackMask = document.createElement('canvas');
    trackMask.width = 800;
    trackMask.height = 600;
    const maskCtx = trackMask.getContext('2d');

    // Режим отладки
    let debugMode = false;

    // Инициализация игры
    document.addEventListener('DOMContentLoaded', init);

    function init() {
      const trackImg = new Image();
      trackImg.onload = function() {
          trackImgSize.width = this.naturalWidth;
          trackImgSize.height = this.naturalHeight;
          
          // Показываем трассу на странице
          const trackElement = document.createElement('img');
          trackElement.src = 'photo/race1.png';
          trackElement.style.width = '100%';
          trackElement.style.height = '100%';
          elements.gameWrapper.appendChild(trackElement);
  
          // Теперь грузим маску
          const maskImg = new Image();
          maskImg.onload = function() {
              maskImgSize.width = this.naturalWidth;
              maskImgSize.height = this.naturalHeight;
  
              trackMask.width = maskImgSize.width;
              trackMask.height = maskImgSize.height;
              maskCtx.drawImage(maskImg, 0, 0, maskImgSize.width, maskImgSize.height);
  
              const maskImageData = maskCtx.getImageData(0, 0, trackMask.width, trackMask.height);
              gameState.trackMaskData = maskImageData.data;
  
              setupGameScaling();
              setupControls();
              setupEventListeners();
              console.log("Game initialized");
          };
          maskImg.src = 'photo/race1_mask.png';
      };
      trackImg.src = 'photo/race1.png';
  }
  function setupGameScaling() {
    // Вычисляем соотношение размеров
    const widthRatio = gameSize.width / trackImgSize.width;
    const heightRatio = gameSize.height / trackImgSize.height;
    const scale = Math.min(widthRatio, heightRatio);
    
    // Применяем масштабирование к трассе
    elements.gameWrapper.style.width = `${trackImgSize.width * scale}px`;
    elements.gameWrapper.style.height = `${trackImgSize.height * scale}px`;

    // Накладываем маску тоже
    elements.debugMask.style.width = `${trackImgSize.width * scale}px`;
    elements.debugMask.style.height = `${trackImgSize.height * scale}px`;
    elements.debugMask.style.position = 'absolute';
    elements.debugMask.style.left = '0';
    elements.debugMask.style.top = '0';
    elements.debugMask.style.pointerEvents = 'none'; // чтобы не мешала кликам
}


    function setupGameScaling() {
        // Вычисляем соотношение размеров
        const widthRatio = gameSize.width / trackImgSize.width;
        const heightRatio = gameSize.height / trackImgSize.height;
        const scale = Math.min(widthRatio, heightRatio);
        
        // Применяем масштабирование
        elements.gameWrapper.style.width = `${trackImgSize.width * scale}px`;
        elements.gameWrapper.style.height = `${trackImgSize.height * scale}px`;
    }

    function setupControls() {
        // Управление клавишами
        document.addEventListener('keydown', (e) => {
            if (gameState.keys.hasOwnProperty(e.key)) {
                gameState.keys[e.key] = true;
            }
        });
        
        document.addEventListener('keyup', (e) => {
            if (gameState.keys.hasOwnProperty(e.key)) {
                gameState.keys[e.key] = false;
            }
        });
    }

    function setupEventListeners() {
        elements.startBtn.addEventListener('click', startGame);
        elements.debugToggle.addEventListener('click', toggleDebugMode);
        elements.maskOpacity.addEventListener('input', updateDebugMask);
        elements.showFinishLine.addEventListener('change', updateDebugMask);
    }

    function startGame() {
        gameState.started = true;
        gameState.lapsRequired = parseInt(elements.lapsSelect.value) || 3;
        gameState.startTime = Date.now();
        elements.startScreen.style.display = 'none';
        
        // Сброс игроков
        resetPlayer('player1', 370, 150);
        resetPlayer('player2', 400, 150);
        
        // Обновление UI
        elements.p1MaxLap.textContent = gameState.lapsRequired;
        elements.p2MaxLap.textContent = gameState.lapsRequired;
        
        // Запуск игрового цикла
        gameLoop();
    }

    function resetPlayer(playerId, x, y) {
        const player = gameState.players[playerId];
        player.x = x;
        player.y = y;
        player.speed = 0;
        player.angle = Math.PI/2;
        player.lap = 0;
        player.penalty = 0;
        player.finished = false;
        player.penaltyTimer = 0;
        updatePlayerPosition(playerId);
    }

    function gameLoop() {
        if (!gameState.started) return;
        
        // Движение игроков
        movePlayer('player1', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight');
        movePlayer('player2', 'w', 's', 'a', 'd');
        
        // Проверка столкновений
        checkCarToCarCollision();
        
        // Обновление UI
        updateUI();
        
        // Отладка
        if (debugMode) {
            drawDebugPoints('player1');
            drawDebugPoints('player2');
        }
        
        requestAnimationFrame(gameLoop);
    }

    function movePlayer(playerId, upKey, downKey, leftKey, rightKey) {
        const player = gameState.players[playerId];
        if (player.finished) return;

        // Управление
        const accel = (gameState.keys[downKey] - gameState.keys[upKey]);
        const steer = (gameState.keys[rightKey] - gameState.keys[leftKey]);

        // Физика движения
        player.angle += steer * player.turnSpeed;
        player.speed += accel * (accel > 0 ? player.acceleration : player.deceleration);
        player.speed = Math.max(Math.min(player.speed, player.maxSpeed), -player.maxSpeed/2);
        player.speed *= 0.98;
        if (Math.abs(player.speed) < 0.1) player.speed = 0;

        // Новые координаты
        const moveX = Math.cos(player.angle) * player.speed;
        const moveY = Math.sin(player.angle) * player.speed;
        const newX = player.x + moveX;
        const newY = player.y + moveY;

        // Проверка коллизий с маской
        if (checkCarCollision(player, newX, newY)) {
            player.x = newX;
            player.y = newY;
        } else {
            // Штраф за выезд с трассы
            player.speed *= 0.7;
            player.penaltyTimer = 2.0;
        }

        updatePlayerPosition(playerId);
    }

    // Проверка коллизий машины с трассой (5 точек)
    function checkCarCollision(player, x, y) {
      const rad = player.angle;
      const cos = Math.cos(rad);
      const sin = Math.sin(rad);
      
      const points = [
          [x, y], // центр
          [x + cos * 30, y + sin * 30], // перед
          [x - cos * 20, y - sin * 20], // зад
          [x - sin * 15, y + cos * 15], // лево
          [x + sin * 15, y - cos * 15]  // право
      ];
      
      // Проверяем все точки - если хотя бы одна не на дороге, возвращаем false
      return points.every(([px, py]) => isOnTrack(px, py));
  }

    function isOnTrack(x, y) {
      // Переводим игровые координаты в координаты маски
      const maskX = Math.floor((x / gameSize.width) * maskImgSize.width);
      const maskY = Math.floor((y / gameSize.height) * maskImgSize.height);
      
      // Проверка границ
      if (maskX < 0 || maskX >= maskImgSize.width || maskY < 0 || maskY >= maskImgSize.height) {
          return false;
      }
      
      // Получаем цвет пикселя
      const pixel = maskCtx.getImageData(maskX, maskY, 1, 1).data;
      
      // Белый (#FFFFFF) - дорога, всё остальное - вне трассы
      return pixel[0] === 255 && pixel[1] === 255 && pixel[2] === 255;
  }

  function updateDebugMask() {
    if (!debugMode) return;
    
    const debugCanvas = document.createElement('canvas');
    debugCanvas.width = maskImgSize.width;
    debugCanvas.height = maskImgSize.height;
    const ctx = debugCanvas.getContext('2d');
    
    // Копируем оригинальную маску
    ctx.drawImage(trackMask, 0, 0);
    
    // Подсвечиваем зоны для наглядности
    const imageData = ctx.getImageData(0, 0, debugCanvas.width, debugCanvas.height);
    const data = imageData.data;
    
    for (let i = 0; i < data.length; i += 4) {
        // Дорога (белая → зелёная)
        if (data[i] === 255 && data[i+1] === 255 && data[i+2] === 255) {
            data[i] = 0; data[i+1] = 255; data[i+2] = 0;
        }
        // Всё остальное (не дорога → красная подсветка)
        else {
            data[i] = 255; data[i+1] = 0; data[i+2] = 0;
            data[i+3] = 100;
        }
    }
    
    ctx.putImageData(imageData, 0, 0);
    elements.debugMask.src = debugCanvas.toDataURL();
    elements.debugMask.style.opacity = elements.maskOpacity.value / 100;
}

    // Проверка столкновений между машинами
    function checkCarToCarCollision() {
        const p1 = gameState.players.player1;
        const p2 = gameState.players.player2;
        
        const dx = p2.x - p1.x;
        const dy = p2.y - p1.y;
        const dist = Math.sqrt(dx*dx + dy*dy);
        
        if (dist < 40) {
            const angle = Math.atan2(dy, dx);
            const force = (p1.speed + p2.speed) * 0.5;
            
            p1.x -= Math.cos(angle) * force * 0.1;
            p1.y -= Math.sin(angle) * force * 0.1;
            p2.x += Math.cos(angle) * force * 0.1;
            p2.y += Math.sin(angle) * force * 0.1;
            
            p1.speed *= -0.5;
            p2.speed *= -0.5;
            p1.penaltyTimer = 1.5;
            p2.penaltyTimer = 1.5;
        }
    }

    function updatePlayerPosition(playerId) {
        const player = gameState.players[playerId];
        const element = document.getElementById(playerId);
        
        element.style.left = `${player.x - player.width/2}px`;
        element.style.top = `${player.y - player.height/2}px`;
        element.style.transform = `rotate(${player.angle}rad)`;
    }

    function updateUI() {
        // Игрок 1
        const p1 = gameState.players.player1;
        elements.p1Speed.textContent = Math.abs(Math.round(p1.speed * 20));
        elements.p1Lap.textContent = p1.lap;
        elements.p1Penalty.textContent = p1.penalty;
        
        // Игрок 2
        const p2 = gameState.players.player2;
        elements.p2Speed.textContent = Math.abs(Math.round(p2.speed * 20));
        elements.p2Lap.textContent = p2.lap;
        elements.p2Penalty.textContent = p2.penalty;
        
        // Отображение штрафов
        showPenalties();
    }

    function showPenalties() {
        const p1 = gameState.players.player1;
        const p2 = gameState.players.player2;
        
        if (p1.penaltyTimer > 0) {
            showPenaltyMessage('PLAYER 1: OFF TRACK!');
        }
        
        if (p2.penaltyTimer > 0) {
            showPenaltyMessage('PLAYER 2: OFF TRACK!');
        }
    }

    function showPenaltyMessage(message) {
        const penaltyDiv = document.createElement('div');
        penaltyDiv.className = 'penalty-message';
        penaltyDiv.textContent = message;
        document.body.appendChild(penaltyDiv);
        
        setTimeout(() => {
            penaltyDiv.remove();
        }, 2000);
    }

    // Режим отладки
    function toggleDebugMode() {
        debugMode = !debugMode;
        elements.debugMask.style.display = debugMode ? 'block' : 'none';
        updateDebugMask();
    }

    function updateDebugMask() {
        if (!debugMode) return;
        
        const opacity = elements.maskOpacity.value / 100;
        const showFinish = elements.showFinishLine.checked;
        
        const debugCanvas = document.createElement('canvas');
        debugCanvas.width = maskImgSize.width;
        debugCanvas.height = maskImgSize.height;
        const ctx = debugCanvas.getContext('2d');
        
        // Копируем маску
        ctx.drawImage(trackMask, 0, 0);
        
        if (showFinish) {
            // Подсвечиваем финишную линию
            const imageData = ctx.getImageData(0, 0, maskImgSize.width, maskImgSize.height);
            const data = imageData.data;
            
            for (let i = 0; i < data.length; i += 4) {
                if (data[i] > 200 && data[i+1] < 50 && data[i+2] < 50) {
                    data[i] = 255; data[i+1] = 0; data[i+2] = 0;
                }
            }
            ctx.putImageData(imageData, 0, 0);
        }
        
        elements.debugMask.src = debugCanvas.toDataURL();
        elements.debugMask.style.opacity = opacity;
    }

    function drawDebugPoints(playerId) {
        const player = gameState.players[playerId];
        const element = document.getElementById(playerId);
        
        // Удаляем старые точки
        const oldPoints = element.querySelectorAll('.debug-point');
        oldPoints.forEach(p => p.remove());
        
        if (!debugMode) return;
        
        // Точки для проверки
        const points = [
            { x: 0, y: 0 }, // центр
            { x: 25, y: 40 }, { x: -25, y: 40 }, // перед
            { x: 25, y: -30 }, { x: -25, y: -30 } // зад
        ];
        
        points.forEach(point => {
            const rotatedX = point.x * Math.cos(player.angle) - point.y * Math.sin(player.angle);
            const rotatedY = point.x * Math.sin(player.angle) + point.y * Math.cos(player.angle);
            
            const debugPoint = document.createElement('div');
            debugPoint.className = 'debug-point';
            debugPoint.style.position = 'absolute';
            debugPoint.style.width = '8px';
            debugPoint.style.height = '8px';
            debugPoint.style.borderRadius = '50%';
            debugPoint.style.backgroundColor = isOnTrack(
                player.x + rotatedX, 
                player.y + rotatedY
            ) ? 'rgba(0,255,0,0.7)' : 'rgba(255,0,0,0.7)';
            debugPoint.style.transform = `translate(${rotatedX-4}px, ${rotatedY-4}px)`;
            element.appendChild(debugPoint);
        });
    }
