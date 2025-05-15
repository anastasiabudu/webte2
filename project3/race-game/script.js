document.addEventListener('DOMContentLoaded', function() {
  const canvas = document.getElementById('gameCanvas');
  const startMenu = document.getElementById('startMenu');
  const gameContainer = document.getElementById('gameContainer');
  const startButton = document.getElementById('startButton');
  const statusBar = document.getElementById('statusBar'); // Добавляем эту строку
  const finishLine = { x: 489, y: 758, width: 38, height: 165 };
  if (!canvas || !statusBar) {
    console.error('Canvas or status bar element not found!');
    return;
}
  
  const ctx = canvas.getContext('2d');
  const trackImage = new Image();
  const maskImage = new Image();
  const blueCarImage = new Image();
  const yellowCarImage = new Image();
  
  trackImage.src = "photo/race.png";
  maskImage.src = "photo/race_mask1.png";
  blueCarImage.src = "photo/blue.png";
  yellowCarImage.src = "photo/yellow.png";

  const maskCanvas = document.createElement('canvas');
  const maskCtx = maskCanvas.getContext('2d');

  // Game parameters
  let lapsRequired = 3;
  let gameRunning = false;
  let gameStartTime = 0;
  let playerId = null;
  let controls = null;

  // WebSocket connection
  const ws = new WebSocket('wss://node36.webte.fei.stuba.sk:8082');
  ws.onopen = () => {
      console.log('Connected to server');
  };
  
  ws.onerror = (error) => {
      console.error('WebSocket error:', error);
      alert('Connection error. Please try again later.');
  };
  
  ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      switch (data.type) {
          case 'init':
            playerId = data.playerId;
            controls = data.controls;
            players[playerId].color = playerId === 0 ? "#2196F3" : "#FFD700";
            players[playerId].image = playerId === 0 ? blueCarImage : yellowCarImage;
            document.getElementById(`player${playerId + 1}Status`).style.display = 'block';
            break;
               
          case 'start':
            statusBar.textContent = '🏁 Race Started!';
      statusBar.style.animation = '';  // Убираем анимацию
      setTimeout(() => statusBar.style.display = 'none', 2000);  // Скрыть статус через 2 секунды
              startGame();
              break;
              
          case 'state':
              if (gameRunning && data.playerId !== playerId) {
                  players[data.playerId] = { ...players[data.playerId], ...data.state };
              }
              break;
              
              case 'finished':
                if (gameRunning) {
                    players[data.playerId].finished = true;
                    players[data.playerId].time = data.time;
                    checkGameEnd();
                }
                break;
            
              
          case 'gameOver':
              gameRunning = false;
              const winner = data.results[0].time < data.results[1].time ? 
                  "Player 1 (Blue)" : "Player 2 (Yellow)";
              showWinnerModal(winner, data.results[0].time, data.results[1].time);
              break;
              case 'gameState':
                if (gameRunning) {
                    data.state.players.forEach((state) => {
                        if (state.id !== playerId) {
                            players[state.id] = { ...players[state.id], ...state };
                        }
                    });
                }
                break;
               
          
                case 'restarted':
            console.log("Game restarted!");
            statusBar.style.display = 'block';
            statusBar.innerHTML = '🔄 Game Restarted';
            setTimeout(() => statusBar.style.display = 'none', 2000);
            
            // Сбрасываем состояние игры на клиенте
            gameRunning = false;
            players.forEach(player => {
                player.ready = false;
            });
            break;

          case 'error':
              alert(data.message);
              break;
      }
  };

    // Players
    const players = [
        {
          id: 0, // Добавьте это
            x: 100, 
            y: 400, 
            angle: Math.PI,
            speed: 0,
            color: "#2196F3", 
            laps: 0, 
            penalty: 0, 
            finished: false,
            width: 40, 
            height: 70,
            lastFinishCross: 0,
            image: blueCarImage
        },
        {
          id: 1, // Добавьте это

            x: 130, 
            y: 400, 
            angle: Math.PI,
            speed: 0,
            color: "#FFD700", 
            laps: 0, 
            penalty: 0, 
            finished: false,
            width: 40, 
            height: 70,
            lastFinishCross: 0,
            image: yellowCarImage
        }
    ];

    // Image loading
    let imagesLoaded = 0;
    const totalImages = 4;
    
    function imageLoaded() {
        if (++imagesLoaded === totalImages) initGame();
    }

    trackImage.onload = imageLoaded;
    maskImage.onload = imageLoaded;
    blueCarImage.onload = imageLoaded;
    yellowCarImage.onload = imageLoaded;

    function initGame() {
        canvas.width = trackImage.width;
        canvas.height = trackImage.height;
        maskCanvas.width = trackImage.width;
        maskCanvas.height = trackImage.height;
        maskCtx.drawImage(maskImage, 0, 0);
    }

    function drawCar(player) {
        ctx.save();
        ctx.translate(player.x, player.y);
        ctx.rotate(player.angle);
        if (player.penalty > 2 && Date.now() % 200 < 100) {
            ctx.filter = 'brightness(1.5) saturate(3)';
        }
        if (player.image.complete) {
            ctx.drawImage(player.image, -player.width/2, -player.height/2, player.width, player.height);
        } else {
            ctx.fillStyle = player.color;
            ctx.fillRect(-player.width/2, -player.height/2, player.width, player.height);
        }
        ctx.restore();
    }
    
    function isOnTrack(x, y) {
        if (x < 0 || y < 0 || x >= maskCanvas.width || y >= maskCanvas.height) return false;
        const pixel = maskCtx.getImageData(Math.floor(x), Math.floor(y), 1, 1).data;
        return pixel[0] > 200 || pixel[1] > 200 || pixel[2] > 200;
    }

    function updatePlayer(player) {
      if (player.finished) return;
  
      // Управление только для текущего игрока
      if (player.id === playerId){
        if (keys[controls.up]) {
              player.speed = Math.min(player.speed + 0.1, 5);
          }
          if (keys[controls.down]) {
              player.speed = Math.max(player.speed - 0.1, -2);
          }
          if (keys[controls.left]) {
              player.angle -= 0.05 * (1 + Math.abs(player.speed)/5);
          }
          if (keys[controls.right]) {
              player.angle += 0.05 * (1 + Math.abs(player.speed)/5);
          }
      }
  
      const moveX = Math.sin(player.angle) * player.speed;
      const moveY = -Math.cos(player.angle) * player.speed;
      const newX = player.x + moveX;
      const newY = player.y + moveY;
  
      if (isOnTrack(newX, newY)) {
          player.x = newX;
          player.y = newY;
      } else {
          player.speed *= -0.7;
          player.penalty += 1.0;
          player.x -= moveX * 0.5;
          player.y -= moveY * 0.5;
          player.speed *= 0.8;
      }
  
      // Проверка столкновений между машинами
      players.forEach(otherPlayer => {
          if (otherPlayer !== player) {
              const dx = player.x - otherPlayer.x;
              const dy = player.y - otherPlayer.y;
              const distance = Math.sqrt(dx*dx + dy*dy);
              
              if (distance < 50) {
                  if (Math.abs(player.speed) > Math.abs(otherPlayer.speed)) {
                      player.penalty += 0.5;
                  }
                  
                  const force = 0.5;
                  player.x += dx * force / distance;
                  player.y += dy * force / distance;
                  otherPlayer.x -= dx * force / distance;
                  otherPlayer.y -= dy * force / distance;
              }
          }
      });
  
      player.speed *= 0.96;
      if (Math.abs(player.speed) < 0.05) player.speed = 0;
      

      if (players.length === 2 && players.every(p => p.ready)) {
        gameState.gameRunning = true;
        gameState.startTime = Date.now();
        broadcast({ 
            type: 'start', 
            lapsRequired: gameState.lapsRequired 
        });
        console.log('Game started!'); // Добавьте для отладки
    }
      // Отправка обновлений на сервер (ДОБАВЬТЕ ЭТОТ БЛОК)
      if (player.id === playerId && ws.readyState === WebSocket.OPEN) {
          ws.send(JSON.stringify({
              type: 'update',
              state: {
                  x: player.x,
                  y: player.y,
                  angle: player.angle,
                  speed: player.speed,
                  laps: player.laps,
                  penalty: player.penalty,
                  finished: player.finished
              }
          }));
      }
  
      checkLap(player); // Оставляем эту строку последней в функции
  }

    function checkLap(player) {
        if (player.x > finishLine.x && 
            player.x < finishLine.x + finishLine.width &&
            player.y > finishLine.y && 
            player.y < finishLine.y + finishLine.height) {
            
            const now = Date.now();
            if (now - player.lastFinishCross > 1000) {
                player.laps++;
                player.lastFinishCross = now;
                
                if (player.laps < lapsRequired) {
                    createLapEffect(player.x, player.y, player.color);
                }
                
                if (player.laps >= lapsRequired && !player.finished) {
                    player.finished = true;
                    player.time = now - gameStartTime;
                    createFinishEffect(player.x, player.y, player.color);
                    ws.send(JSON.stringify({
                        type: 'finished',
                        time: player.time
                    }));
                }
            }
        }
    }

    function createLapEffect(x, y, color) {
        for (let i = 0; i < 20; i++) {
            setTimeout(() => {
                const particle = document.createElement('div');
                particle.className = 'confetti-particle';
                particle.style.left = `${x + Math.random() * 30 - 15}px`;
                particle.style.top = `${y + Math.random() * 30 - 15}px`;
                particle.style.backgroundColor = color;
                particle.style.width = `${Math.random() * 8 + 4}px`;
                particle.style.height = particle.style.width;
                particle.style.animationDuration = `${Math.random() * 1 + 0.5}s`;
                document.body.appendChild(particle);
                
                setTimeout(() => {
                    particle.remove();
                }, 1000);
            }, i * 50);
        }
    }

    function createFinishEffect(x, y, color) {
        for (let i = 0; i < 100; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = `${x + Math.random() * 100 - 50}px`;
                confetti.style.top = `${y + Math.random() * 100 - 50}px`;
                confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 100%, 50%)`;
                confetti.style.width = `${Math.random() * 10 + 5}px`;
                confetti.style.height = `${Math.random() * 10 + 5}px`;
                confetti.style.animationDuration = `${Math.random() * 2 + 1}s`;
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 3000);
            }, i * 30);
        }
    }

    function showWinnerModal(winner, time1, time2) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        
        const title = document.createElement('h2');
        title.className = 'modal-title';
        title.textContent = `WINNER: ${winner}`;
        
        const results = document.createElement('div');
        results.className = 'modal-results';
        results.innerHTML = `
            <p>Blue: ${(time1/1000).toFixed(1)}s</p>
            <p>Yellow: ${(time2/1000).toFixed(1)}s</p>
        `;
        
        const restartBtn = document.createElement('button');
        restartBtn.className = 'modal-button';
        restartBtn.textContent = 'RESTART RACE';
        restartBtn.onclick = restartGame; // ← Важное изменение!

        restartBtn.onclick = function() {
            document.body.removeChild(modal);
            location.reload();
        };
        
        modalContent.appendChild(title);
        modalContent.appendChild(results);
        modalContent.appendChild(restartBtn);
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        createFinishEffect(window.innerWidth/2, window.innerHeight/2, winner.includes('Blue') ? '#2196F3' : '#FFD700');
    }


    function checkGameEnd() {
        if (players.every(p => p.finished)) {
            const winner = players[0].time < players[1].time ? "Player 1 (Blue)" : "Player 2 (Yellow)";
            showWinnerModal(winner, players[0].time, players[1].time);
        }
    }

      // ... (предыдущий код остается без изменений до gameLoop)

function gameLoop() {
  if (!gameRunning) return;
  
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  
  // Центрирование камеры на текущем игроке
  const currentPlayer = players[playerId];
  const viewX = currentPlayer.x - canvas.width/2;
  const viewY = currentPlayer.y - canvas.height/2;
  
  // Отрисовка трека с учетом смещения камеры
  ctx.save();
  ctx.translate(-viewX, -viewY);
  ctx.drawImage(trackImage, 0, 0);
  
  // Обновление и отрисовка всех игроков
  players.forEach(player => {
      // Обновляем только текущего игрока локально
      if (player.id === playerId) {
          updatePlayer(player);
      }
      drawCar(player);
      
      // Обновление HUD
      updatePlayerHUD(player);
  });
  
  ctx.restore();
  requestAnimationFrame(gameLoop);
}

function updatePlayerHUD(player) {
  const playerStatus = document.getElementById(`player${player.id + 1}Status`);
  if (playerStatus) {
      const penaltyColor = player.penalty > 3 ? 'red' : 'white';
      playerStatus.innerHTML = `
          <h3>Player ${player.id + 1} (${player.id === 0 ? "Blue" : "Yellow"})</h3>
          <p>Lap: ${player.laps}/${lapsRequired}</p>
          <p>Speed: ${Math.abs(player.speed).toFixed(1)}</p>
          <p style="color: ${penaltyColor}">Penalty: ${player.penalty.toFixed(1)}</p>
          <p>Time: ${player.finished ? (player.time/1000).toFixed(1) : ((Date.now()-gameStartTime)/1000).toFixed(1)}s</p>
      `;
  }
}

    
    function startGame() {
        // Hide start menu and show game
        startMenu.style.display = 'none';
        gameContainer.style.display = 'block';
        
        gameStartTime = Date.now();
        gameRunning = true;
        gameLoop();
    }
    
    function updatePlayerStatus() {
        const player1Status = document.getElementById('player1Status');
        const player2Status = document.getElementById('player2Status');
        
        players.forEach((p, i) => {
            const playerStatus = i === 0 ? player1Status : player2Status;
            const penaltyColor = p.penalty > 3 ? 'red' : 'white';
            
            playerStatus.innerHTML = `
                <h3>Player ${i+1} (${p.color === "#2196F3" ? "Blue" : "Yellow"})</h3>
                <p>Lap: ${p.laps}/${lapsRequired}</p>
                <p>Speed: ${Math.abs(p.speed).toFixed(1)}</p>
                <p style="color: ${penaltyColor}">Penalty: ${p.penalty.toFixed(1)}</p>
                <p>Time: ${p.finished ? (p.time/1000).toFixed(1) : ((Date.now()-gameStartTime)/1000).toFixed(1)}s</p>
            `;
        });
    }

    // Keyboard controls
    const keys = {};
    window.addEventListener('keydown', e => {
      if (["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", "Space"].includes(e.code)) {
          e.preventDefault();
      }
      keys[e.code] = true;
  });
  
  window.addEventListener('keyup', e => {
      if (["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", "Space"].includes(e.code)) {
          e.preventDefault();
      }
      keys[e.code] = false;
  });
  
    const element = document.querySelector('#status');
    // Start button
    startButton.addEventListener('click', () => {
      lapsRequired = parseInt(document.getElementById('lapsInput').value) || 3;
      ws.send(JSON.stringify({ type: 'ready', laps: lapsRequired }));
      
      // Show the status bar
      statusBar.style.display = 'block';
      statusBar.textContent = '⏳ Waiting for opponent...';
      
      // Add animation
      statusBar.style.animation = 'pulse 2s infinite alternate';    
    });
});

