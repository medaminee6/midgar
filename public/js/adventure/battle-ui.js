/**
 * Battle UI Module
 * Handles all UI rendering, animations, and visual effects
 * Uses the site's existing color scheme: #DDF247 (accent), #161616 (dark bg)
 */

(function() {
    'use strict';
    
    class BattleUI {
        constructor(battle) {
            this.battle = battle;
            this.container = null;
            this.attackMeter = null;
            this.attackIndicator = null;
            
            // Sound effects
            this.sounds = {
                bgMusic: new Audio('/assets/adventure/music/normal enemy.mp3'),
                bossMusic: new Audio('/assets/adventure/music/boss music.mp3'),
                victoryMusic: new Audio('/assets/adventure/music/victory music.mp3'),
                button: new Audio('/assets/adventure/sounds/bip.ogg'),
                heal: new Audio('/assets/adventure/sounds/healing.ogg'),
                attack: new Audio('/assets/adventure/sounds/slice.ogg'),
                damage: new Audio('/assets/adventure/sounds/hurt.ogg')
            };
            
            // Configure looping music
            this.sounds.bgMusic.loop = true;
            this.sounds.bossMusic.loop = true;
            this.sounds.victoryMusic.loop = false;
            this.sounds.bgMusic.volume = 0.5;
            this.sounds.bossMusic.volume = 0.55;
            this.sounds.victoryMusic.volume = 0.65;
        }
        
        /**
         * Play a sound effect
         */
        playSound(soundName) {
            const sound = this.sounds[soundName];
            if (sound) {
                sound.currentTime = 0;
                sound.play().catch(() => {}); // Ignore autoplay errors
            }
        }
        
        /**
         * Start background music
         */
        startMusic() {
            if (this.battle?.state?.enemy?.isBoss) {
                this.sounds.bossMusic.currentTime = 0;
                this.sounds.bossMusic.play().catch(() => {});
            } else {
                this.sounds.bgMusic.currentTime = 0;
                this.sounds.bgMusic.play().catch(() => {});
            }
        }
        
        /**
         * Stop background music
         */
        stopMusic() {
            this.sounds.bgMusic.pause();
            this.sounds.bgMusic.currentTime = 0;
            this.sounds.bossMusic.pause();
            this.sounds.bossMusic.currentTime = 0;
        }
        
        /**
         * Get enemy sprite HTML based on enemy type
         */
        getEnemySpriteHtml(enemy) {
            // Use sprite from enemy data if available (from overworld)
            if (enemy.sprite) {
                const spritePath = `/assets/adventure/enemies/${enemy.sprite}.png`;
                return `<img src="${spritePath}" alt="${enemy.name}" class="enemy-img">`;
            }
            
            // Fallback to name-based mapping
            const name = enemy.name?.toLowerCase() || '';
            const type = enemy.type || 'monster';
            
            let spritePath = '';
            
            // Map enemy to sprite
            if (enemy.isBoss) {
                spritePath = '/assets/adventure/enemies/boss.png';
            } else if (name.includes('toxic') || name.includes('frog') || type === 'enemy1') {
                spritePath = '/assets/adventure/enemies/enemy1.png';
            } else if (name.includes('ghost') || type === 'enemy2') {
                spritePath = '/assets/adventure/enemies/enemy2.png';
            } else if (name.includes('carrot') || name.includes('haunted') || type === 'enemy3') {
                spritePath = '/assets/adventure/enemies/enemy3.png';
            } else {
                // Default fallback
                spritePath = '/assets/adventure/enemies/enemy1.png';
            }
            
            return `<img src="${spritePath}" alt="${enemy.name}" class="enemy-img">`;
        }
        
        /**
         * Show slice animation on enemy
         */
        showSliceAnimation() {
            const sliceContainer = document.getElementById('slice-animation');
            if (!sliceContainer) return;
            
            const slices = [
                '/assets/adventure/img/slice1.png',
                '/assets/adventure/img/slice2.png',
                '/assets/adventure/img/slice3.png',
                '/assets/adventure/img/slice4.png',
                '/assets/adventure/img/slice5.png',
                '/assets/adventure/img/slice6.png'
            ];
            
            // Create slice elements with animation
            let delay = 0;
            slices.forEach((slice, index) => {
                setTimeout(() => {
                    const sliceImg = document.createElement('img');
                    sliceImg.src = slice;
                    sliceImg.className = 'slice-img';
                    sliceContainer.appendChild(sliceImg);
                    
                    // Remove after animation
                    setTimeout(() => sliceImg.remove(), 200);
                }, delay);
                delay += 80;
            });
        }
        
        /**
         * Show miss overlay for ghost (immune to physical attacks)
         */
        showMissOverlay() {
            const overlay = document.getElementById('enemy-miss-overlay');
            if (!overlay) return;
            
            overlay.innerHTML = '<img src="/assets/adventure/img/miss.png" class="miss-img">';
            overlay.classList.add('visible');
            
            // Show message
            this.showMessage('Oops! You cannot stab what\'s not there!', 2000);
            
            // Hide after delay
            setTimeout(() => {
                overlay.classList.remove('visible');
                setTimeout(() => overlay.innerHTML = '', 300);
            }, 1500);
        }
        
        /**
         * Initialize the battle UI
         */
        init(player, enemy, universe) {
            // Determine level from enemy ID (e.g., L2_3 -> level 2) or use enemy.level, or use universe ID
            let level = 1;
            if (enemy?.id) {
                // Extract level from enemy ID format: L{level}_{number}
                const idMatch = enemy.id.match(/^L(\d+)_/);
                if (idMatch) {
                    level = parseInt(idMatch[1], 10);
                }
            }
            if (!level || level < 1) {
                level = enemy?.level || universe?.id || 1;
            }
            console.log('Battle UI init - Enemy ID:', enemy?.id, 'Parsed level:', level);
            
            // Create main container
            this.container = document.createElement('div');
            this.container.id = 'battle-container';
            this.container.innerHTML = this.createHTML(player, enemy, universe, level);
            
            // Insert into page
            document.body.appendChild(this.container);
            
            // Cache elements
            this.cacheElements();
            
            // Add styles
            this.addStyles();
            
            // Start background music for all encounters (boss uses boss theme)
            this.startMusic();
        }
        
        /**
         * Create HTML structure
         */
        createHTML(player, enemy, universe, level = 1) {
            const portraitUrl = player.portrait
                ? (player.portrait.startsWith('data:') ? player.portrait : 'data:image/png;base64,' + player.portrait)
                : null;

            // Determine video background based on level or boss status
            // Level 1 = Desert, Level 2 = Forest, Boss = Ocean
            const isBoss = enemy?.isBoss || enemy?.difficulty === 'boss';
            // Use the level parameter first (which we parsed from enemy ID), then fall back
            const effectiveLevel = level || enemy?.level || 1;
            const finalLevel = isBoss ? 3 : effectiveLevel;
            const biomeTheme = finalLevel === 1 ? 'desert' : (finalLevel === 2 ? 'forest' : 'ocean');
            const biomeIcon = finalLevel === 1 ? '🏜️' : (finalLevel === 2 ? '🌿' : '🌊');
            
            let videoBackground = '';
            const videoPath = '/assets/adventure/maps/';
            
            if (finalLevel === 1) {
                videoBackground = `<video class="battle-video" autoplay muted loop playsinline>
                    <source src="${videoPath}DESERT.mp4" type="video/mp4">
                </video>`;
            } else if (finalLevel === 2) {
                videoBackground = `<video class="battle-video" autoplay muted loop playsinline>
                    <source src="${videoPath}forest.mp4" type="video/mp4">
                </video>`;
            } else {
                // Level 3 (boss) = Ocean
                videoBackground = `<video class="battle-video" autoplay muted loop playsinline>
                    <source src="${videoPath}ocean.mp4" type="video/mp4">
                </video>`;
            }
            
            console.log('Battle background - Level:', finalLevel, 'isBoss:', isBoss, 'video:', videoPath + (finalLevel === 1 ? 'DESERT.mp4' : finalLevel === 2 ? 'forest.mp4' : 'ocean.mp4'));
            
            return `
                <!-- Battle Background -->
                <div id="battle-background" class="battle-background">
                    ${videoBackground}
                    <div class="battle-video-overlay"></div>
                    <div class="universe-banner" style="background-image: url(${universe.bannerImage ? 'data:image/png;base64,' + universe.bannerImage : ''})"></div>
                    <div class="battle-vignette"></div>
                </div>
                
                <!-- Battle Arena -->
                <div id="battle-arena" class="battle-arena theme-${biomeTheme}">
                    
                    <!-- Top: Enemy Area -->
                    <div id="enemy-area" class="enemy-area">
                        <div class="enemy-sprite ${enemy.isBoss ? 'boss' : ''}" id="enemy-sprite">
                            ${this.getEnemySpriteHtml(enemy)}
                        </div>
                        <!-- Slice animation container -->
                        <div id="slice-animation" class="slice-animation"></div>
                        <div class="enemy-miss-overlay" id="enemy-miss-overlay"></div>
                        <div class="enemy-name">${enemy.name}</div>

                        <div class="enemy-hp-bar">
                            <div class="hp-fill" style="width: 100%; background: ${enemy.color || '#ff4444'}"></div>
                        </div>
                        <div class="enemy-stats">
                            <span>HP: <span id="enemy-hp">${enemy.currentHp}</span>/${enemy.maxHp}</span>
                        </div>
                    </div>

                    <!-- Middle: Action Area -->
                    <div id="action-area" class="action-area">
                        <!-- Decision Phase Menu -->
                        <div id="decision-menu" class="decision-menu">
                            <div class="menu-title">${biomeIcon} CHOOSE ACTION ${biomeIcon}</div>
                            <button class="battle-btn attack-btn" data-action="ATTACK">
                                <span class="btn-icon">🗡️</span>
                                <span class="btn-text">ATTACK</span>
                            </button>
                            <button class="battle-btn magic-btn" data-action="MAGIC">
                                <span class="btn-icon">🔮</span>
                                <span class="btn-text">MAGIC</span>
                                <span class="btn-cost">10 MP</span>
                            </button>
                            <button class="battle-btn item-btn" data-action="ITEM">
                                <span class="btn-icon">🧪</span>
                                <span class="btn-text">ITEM</span>
                            </button>
                            <button class="battle-btn flee-btn" data-action="FLEE">
                                <span class="btn-icon">💨</span>
                                <span class="btn-text">FLEE</span>
                            </button>
                        </div>
                        
                        <!-- Attack Phase -->
                        <div id="attack-phase" class="attack-phase hidden">
                            <div class="attack-instructions">Press SPACE at the right moment!</div>
                            <div class="attack-multiplier" id="attack-multiplier"></div>
                            <div class="attack-meter">
                                <div class="meter-track">
                                    <div class="meter-zones">
                                        <div class="zone perfect">PERFECT</div>
                                        <div class="zone good">GOOD</div>
                                        <div class="zone ok">OK</div>
                                    </div>
                                    <div id="attack-indicator" class="meter-indicator"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bullet Phase -->
                        <div id="bullet-phase" class="bullet-phase hidden">
                            <div id="bullet-arena" class="bullet-arena">
                                <div id="player-hitbox" class="player-hitbox${portraitUrl ? ' portrait' : ''}" style="background-image: url('${portraitUrl || '/assets/adventure/playablecharachter/a.png'}'); background-size: cover; background-position: center; background-repeat: no-repeat;"></div>
                            </div>
                            <div class="bullet-timer">
                                <div id="bullet-timer-bar" class="timer-fill"></div>
                            </div>
                        </div>
                         
                        <!-- Message Display in player area - hidden, using battle log instead -->
                        <div id="battle-message" class="battle-message hidden" style="display: none;"></div>
                    </div>
                    
                    <!-- Bottom: Player Area -->
                    <div id="player-area" class="player-area">
                        <div class="player-portrait">
                            ${portraitUrl ? `<img src="${portraitUrl}" alt="${player.name}">` :
                            `<div class="portrait-placeholder">🧙</div>`}
                        </div>
                        <div class="player-details">
                            <div class="player-name">${player.name}</div>
                            <div class="player-class">${player.classRole || 'Warrior'}</div>
                            <div class="player-bars">
                                <div class="hp-bar-container">
                                    <span class="bar-label">HP</span>
                                    <div class="hp-bar">
                                        <div id="player-hp-fill" class="bar-fill hp-fill" style="width: 100%"></div>
                                    </div>
                                    <span id="player-hp-text" class="bar-text">${player.currentHp}/${player.maxHp}</span>
                                </div>
                                <div class="mp-bar-container">
                                    <span class="bar-label">MP</span>
                                    <div class="mp-bar">
                                        <div id="player-mp-fill" class="bar-fill mp-fill" style="width: 100%"></div>
                                    </div>
                                    <span id="player-mp-text" class="bar-text">${player.currentMp}/${player.maxMp}</span>
                                </div>
                            </div>
                            <div class="player-stats">
                                <span>ATK: <span id="player-atk">${player.attack}</span></span>
                                <span>DEF: <span id="player-def">${player.defense}</span></span>
                                <span>MAG: <span id="player-mag">${player.magic}</span></span>
                                <span>AGI: <span id="player-agi">${player.agility}</span></span>
                            </div>
                        </div>
                        <!-- Battle Log - scrolls independently -->
                        <div class="battle-log-container">
                            <div class="battle-log" id="battle-log">
                                <div class="log-entry system">⚔️ Battle started!</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Damage Numbers Container -->
                <div id="damage-numbers" class="damage-numbers"></div>
                
                <!-- Screen Effects -->
                <div id="screen-effects" class="screen-effects"></div>
                
                <!-- Victory/Defeat Overlay -->
                <div id="battle-result" class="battle-result hidden">
                    <div class="result-content">
                        <h1 id="result-title"></h1>
                        <div id="result-details" class="result-details"></div>
                        <button class="battle-btn restart-btn">Play Again</button>
                    </div>
                </div>
            `;
        }
        
        /**
         * Cache DOM elements
         */
        cacheElements() {
            this.decisionMenu = document.getElementById('decision-menu');
            this.attackPhase = document.getElementById('attack-phase');
            this.attackIndicator = document.getElementById('attack-indicator');
            this.attackMeter = document.querySelector('.attack-meter');
            this.attackMultiplier = document.getElementById('attack-multiplier');
            this.bulletPhase = document.getElementById('bullet-phase');
            this.bulletArena = document.getElementById('bullet-arena');
            this.playerHitbox = document.getElementById('player-hitbox');
            this.battleMessage = document.getElementById('battle-message');
            this.damageNumbers = document.getElementById('damage-numbers');
            this.screenEffects = document.getElementById('screen-effects');
            this.battleResult = document.getElementById('battle-result');
            
            // Bind menu buttons
            document.querySelectorAll('.battle-btn[data-action]').forEach(btn => {
                btn.addEventListener('click', () => {
                    this.playSound('button');
                    this.battle.selectAction(btn.dataset.action);
                });
            });
            
            // Bind restart button
            document.querySelector('.restart-btn')?.addEventListener('click', () => {
                location.reload();
            });
        }
        
        /**
         * Add CSS styles
         */
        addStyles() {
            const style = document.createElement('style');
            style.textContent = `
                /* Battle Container */
                #battle-container {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    z-index: 999999;
                    background: #0a0a0a;
                    font-family: '8bitoperator', 'Courier New', monospace;
                    image-rendering: pixelated;
                }
                
                .battle-background {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 1;
                    background: linear-gradient(180deg, #1a1a2e 0%, #161616 100%);
                }
                
                .universe-banner {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 60%;
                    background-size: cover;
                    background-position: center;
                    opacity: 0.3;
                    filter: blur(2px);
                }
                
                .battle-vignette {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,0.8) 100%);
                }
                
                /* Video Background */
                .battle-video {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    z-index: 1;
                    opacity: 0;
                    animation: videoFadeIn 0.8s ease-out forwards;
                }
                
                @keyframes videoFadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                .battle-video-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.4);
                    z-index: 2;
                }
                
                /* Battle Arena */
                .battle-arena {
                    --battle-accent: #DDF247;
                    --battle-accent-soft: #f5ffb1;
                    --battle-glow: rgba(221, 242, 71, 0.28);
                    --battle-panel-bg: rgba(22, 22, 22, 0.9);
                    --battle-btn-bg: rgba(24, 24, 24, 0.9);
                    --battle-btn-hover: rgba(38, 38, 38, 0.95);
                    position: relative;
                    width: 100%;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    padding: 0;
                    box-sizing: border-box;
                    z-index: 10;
                }

                .battle-arena.theme-desert {
                    --battle-accent: #ffb347;
                    --battle-accent-soft: #ffe38a;
                    --battle-glow: rgba(255, 179, 71, 0.35);
                    --battle-panel-bg: rgba(42, 26, 8, 0.84);
                    --battle-btn-bg: rgba(64, 36, 9, 0.88);
                    --battle-btn-hover: rgba(82, 48, 12, 0.94);
                }

                .battle-arena.theme-forest {
                    --battle-accent: #6ee37b;
                    --battle-accent-soft: #b5ffbf;
                    --battle-glow: rgba(110, 227, 123, 0.35);
                    --battle-panel-bg: rgba(8, 34, 20, 0.84);
                    --battle-btn-bg: rgba(12, 52, 30, 0.88);
                    --battle-btn-hover: rgba(16, 66, 39, 0.94);
                }

                .battle-arena.theme-ocean {
                    --battle-accent: #66c7ff;
                    --battle-accent-soft: #9fdbff;
                    --battle-glow: rgba(102, 199, 255, 0.35);
                    --battle-panel-bg: rgba(8, 20, 42, 0.84);
                    --battle-btn-bg: rgba(10, 30, 66, 0.88);
                    --battle-btn-hover: rgba(15, 42, 88, 0.94);
                }
                
                /* Enemy Area */
                .enemy-area {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    padding: 20px;
                    flex-shrink: 0;
                    position: relative;
                }
                
                .enemy-sprite {
                    width: 120px;
                    height: 120px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .enemy-sprite.boss {
                    width: 180px;
                    height: 180px;
                }
                
                .enemy-sprite img {
                    max-width: 100%;
                    max-height: 100%;
                    image-rendering: pixelated;
                }
                
                /* Slice Animation */
                .slice-animation {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    pointer-events: none;
                    z-index: 100;
                }
                
                .slice-img {
                    position: absolute;
                    width: 80px;
                    height: 80px;
                    animation: sliceFly 0.2s ease-out forwards;
                }
                
                @keyframes sliceFly {
                    0% { opacity: 1; transform: scale(0.5) rotate(0deg); }
                    100% { opacity: 0; transform: scale(1.5) rotate(180deg); }
                }
                
                /* Miss Overlay (for ghost) */
                .enemy-miss-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    transition: opacity 0.3s;
                    pointer-events: none;
                }
                
                .enemy-miss-overlay.visible {
                    opacity: 1;
                }
                
                .miss-img {
                    width: 100px;
                    height: 100px;
                    animation: missPulse 0.5s ease-in-out infinite;
                }
                
                @keyframes missPulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
                
                .enemy-placeholder {
                    width: 80px;
                    height: 80px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                    font-weight: bold;
                    color: #fff;
                    border-radius: 8px;
                }
                
                .enemy-name {
                    font-size: 18px;
                    color: #fff;
                    margin-top: 10px;
                    text-shadow: 2px 2px 0 #000;
                }
                
                .enemy-hp-bar {
                    width: 200px;
                    height: 12px;
                    background: #333;
                    border: 2px solid #555;
                    border-radius: 6px;
                    overflow: hidden;
                    margin-top: 8px;
                }
                
                .enemy-hp-bar .hp-fill {
                    height: 100%;
                    transition: width 0.3s ease;
                }
                
                .enemy-stats {
                    font-size: 12px;
                    color: #888;
                    margin-top: 5px;
                }
                
                /* Action Area */
                .action-area {
                    flex: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    position: relative;
                    min-height: 150px;
                }
                
                /* Decision Menu */
                .decision-menu {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    padding: 20px;
                    background: var(--battle-panel-bg);
                    border: 3px solid var(--battle-accent);
                    border-radius: 12px;
                    box-shadow: 0 0 30px var(--battle-glow);
                }
                
                .menu-title {
                    text-align: center;
                    font-size: 16px;
                    color: var(--battle-accent-soft);
                    margin-bottom: 10px;
                    text-shadow: 0 0 10px var(--battle-glow);
                }
                
                .battle-btn {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 24px;
                    background: var(--battle-btn-bg);
                    border: 2px solid #444;
                    border-radius: 8px;
                    color: #fff;
                    font-family: inherit;
                    font-size: 14px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                
                .battle-btn:hover {
                    background: var(--battle-btn-hover);
                    border-color: var(--battle-accent);
                    transform: translateX(5px);
                    box-shadow: 0 0 15px var(--battle-glow);
                }
                
                .battle-btn:active {
                    transform: translateX(2px);
                }
                
                .btn-icon {
                    font-size: 20px;
                }
                
                .btn-cost {
                    font-size: 10px;
                    color: var(--battle-accent-soft);
                    margin-left: auto;
                }
                
                /* Attack Phase */
                .attack-phase {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 20px;
                }
                
                .attack-instructions {
                    font-size: 18px;
                    color: #DDF247;
                    text-shadow: 0 0 10px rgba(221, 242, 71, 0.5);
                    animation: pulse 1s infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.7; }
                }
                
                .attack-meter {
                    width: 300px;
                }
                
                .attack-multiplier {
                    font-size: 24px;
                    font-weight: bold;
                    text-align: center;
                    margin-bottom: 10px;
                    min-height: 30px;
                    text-shadow: 0 0 10px currentColor;
                }
                
                .attack-multiplier.perfect {
                    color: #ffd700;
                    animation: pulse 0.3s ease-out;
                }
                
                .attack-multiplier.good {
                    color: #00ff88;
                }
                
                .attack-multiplier.ok {
                    color: #88ccff;
                }
                
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.2); }
                    100% { transform: scale(1); }
                }
                
                .meter-track {
                    position: relative;
                    height: 40px;
                    background: #1a1a1a;
                    border: 3px solid #444;
                    border-radius: 8px;
                    overflow: hidden;
                }
                
                .meter-zones {
                    display: flex;
                    height: 100%;
                }
                
                .zone {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    color: #000;
                    font-weight: bold;
                }
                
                .zone.perfect { 
                    width: 20%; 
                    background: #00ff00;
                    animation: zonePulse 0.5s infinite;
                }
                .zone.good { width: 25%; background: #88ff00; }
                .zone.ok { width: 55%; background: #ffff00; }
                
                @keyframes zonePulse {
                    0%, 100% { box-shadow: inset 0 0 10px rgba(0,255,0,0.5); }
                    50% { box-shadow: inset 0 0 20px rgba(0,255,0,0.8); }
                }
                
                .meter-indicator {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 4px;
                    height: 100%;
                    background: #fff;
                    box-shadow: 0 0 10px #fff;
                }
                
                /* Bullet Phase */
                .bullet-phase {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 15px;
                }
                
                .bullet-arena {
                    width: 300px;
                    height: 300px;
                    background: rgba(0, 0, 0, 0.8);
                    border: 3px solid #DDF247;
                    border-radius: 50%;
                    position: relative;
                    overflow: hidden;
                    box-shadow: 0 0 30px rgba(221, 242, 71, 0.3);
                }
                
                .player-hitbox {
                    position: absolute;
                    width: 24px;
                    height: 24px;
                    background: url('/assets/adventure/playablecharachter/a.png') center/cover no-repeat;
                    transform: translate(-50%, -50%);
                    animation: none;
                    border-radius: 6px;
                    box-shadow: 0 0 8px rgba(221, 242, 71, 0.6);
                    border: 2px solid rgba(221, 242, 71, 0.8);
                }

                .player-hitbox.portrait {
                    animation: none;
                    border-radius: 50%;
                    border-width: 3px;
                    box-shadow: 0 0 12px rgba(221, 242, 71, 0.8);
                }
                
                @keyframes playerIdle {
                    0% { background-position: 0 0; }
                    100% { background-position: -48px 0; }
                }
                
                .bullet-timer {
                    width: 200px;
                    height: 8px;
                    background: #333;
                    border-radius: 4px;
                    overflow: hidden;
                }
                
                .timer-fill {
                    height: 100%;
                    width: 100%;
                    background: linear-gradient(90deg, #00ff00, #ffff00, #ff0000);
                    transform-origin: left;
                    transition: transform 0.1s linear;
                }
                
                /* Player Area */
                .player-area {
                    display: flex;
                    align-items: flex-start;
                    justify-content: center;
                    gap: 20px;
                    padding: 10px 30px;
                    background: rgba(22, 22, 22, 0.95);
                    border-top: 3px solid #DDF247;
                    width: 100%;
                    min-width: 100%;
                    box-sizing: border-box;
                    height: 120px;
                    flex-shrink: 0;
                }
                
                .player-portrait {
                    width: 60px;
                    height: 60px;
                    border: 2px solid #DDF247;
                    border-radius: 8px;
                    overflow: hidden;
                    background: #1a1a1a;
                    flex-shrink: 0;
                }
                
                .player-portrait img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                
                .portrait-placeholder {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    color: #DDF247;
                }
                
                .player-info {
                    flex: 1;
                    min-width: 0;
                }
                
                .player-details {
                    flex-shrink: 0;
                    min-width: 180px;
                }
                
                .battle-log-container {
                    flex: 1;
                    min-width: 0;
                    overflow-y: auto;
                    max-height: 60px;
                }
                
                .player-name {
                    font-size: 16px;
                    color: #fff;
                    font-weight: bold;
                }
                
                .player-class {
                    font-size: 12px;
                    color: #888;
                    margin-bottom: 6px;
                }
                
                .player-bars {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 6px;
                }
                
                .hp-bar-container, .mp-bar-container {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                
                .bar-label {
                    font-size: 10px;
                    color: #888;
                    width: 20px;
                }
                
                .hp-bar, .mp-bar {
                    width: 100px;
                    height: 12px;
                    background: #333;
                    border-radius: 6px;
                    overflow: hidden;
                }
                
                .bar-fill {
                    height: 100%;
                    transition: width 0.3s ease;
                }
                
                .hp-fill { background: linear-gradient(90deg, #ff4444, #ff8888); }
                .mp-fill { background: linear-gradient(90deg, #4488ff, #88ccff); }
                
                .bar-text {
                    font-size: 10px;
                    color: #fff;
                    min-width: 50px;
                }
                
                .player-stats {
                    display: flex;
                    gap: 12px;
                    font-size: 10px;
                    color: #888;
                }
                
                /* Battle Log */
                .battle-log-container {
                    flex: 1;
                    min-width: 0;
                    overflow-y: auto;
                    max-height: 150px;
                }
                
                .battle-log {
                    padding: 8px;
                    background: rgba(0, 0, 0, 0.4);
                    border-radius: 8px;
                    border: 1px solid rgba(221, 242, 71, 0.2);
                    font-size: 12px;
                }
                
                .battle-log .log-entry {
                    padding: 3px 0;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                    animation: logFadeIn 0.3s ease;
                }
                
                .battle-log .log-entry:last-child {
                    border-bottom: none;
                    font-weight: bold;
                    color: #DDF247;
                }
                
                .battle-log .log-entry.damage { color: #ff6b6b; }
                .battle-log .log-entry.heal { color: #6bff6b; }
                .battle-log .log-entry.turn { color: #6bb6ff; }
                .battle-log .log-entry.system { color: #aaa; font-style: italic; }
                
                @keyframes logFadeIn {
                    from { opacity: 0; transform: translateX(-10px); }
                    to { opacity: 1; transform: translateX(0); }
                }
                
                .battle-log::-webkit-scrollbar { width: 4px; }
                .battle-log::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); }
                .battle-log::-webkit-scrollbar-thumb { background: #DDF247; border-radius: 2px; }
                
                /* Damage Numbers */
                .damage-numbers {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: 1000;
                }
                
                .damage-number {
                    position: absolute;
                    font-size: 32px;
                    font-weight: bold;
                    text-shadow: 2px 2px 0 #000;
                    animation: damageFloat 1s ease-out forwards;
                }
                
                .damage-number.perfect { color: #00ff00; font-size: 48px; }
                .damage-number.good { color: #88ff00; }
                .damage-number.ok { color: #ffff00; }
                .damage-number.miss { color: #ff4444; font-size: 24px; }
                
                @keyframes damageFloat {
                    0% { transform: translateY(0) scale(0.5); opacity: 1; }
                    50% { transform: translateY(-30px) scale(1.2); }
                    100% { transform: translateY(-60px) scale(1); opacity: 0; }
                }
                
                /* Screen Effects */
                .screen-effects {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: 999;
                }
                
                .screen-flash {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    animation: flash 0.2s ease-out;
                }
                
                @keyframes flash {
                    0% { background: rgba(255,255,255,0.8); }
                    100% { background: transparent; }
                }
                
                .screen-shake {
                    animation: shake 0.3s ease-out;
                }
                
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-10px); }
                    75% { transform: translateX(10px); }
                }
                
                .screen-tint {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    transition: background 0.3s;
                }
                
                /* Battle Result */
                .battle-result {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                }
                
                .result-content {
                    text-align: center;
                    padding: 40px;
                }
                
                #result-title {
                    font-size: 48px;
                    margin-bottom: 20px;
                    text-shadow: 0 0 20px currentColor;
                }
                
                .victory #result-title { color: #DDF247; }
                .defeat #result-title { color: #ff4444; }
                
                .result-details {
                    font-size: 18px;
                    color: #fff;
                    margin-bottom: 30px;
                }
                
                .result-details div {
                    margin: 10px 0;
                }

                .stat-choice {
                    margin-top: 20px;
                }

                .stat-title {
                    color: #DDF247;
                    font-size: 18px;
                    margin-bottom: 12px;
                    text-shadow: 0 0 10px rgba(221, 242, 71, 0.4);
                }

                .stat-buttons {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(140px, 1fr));
                    gap: 10px;
                    justify-content: center;
                    margin-bottom: 10px;
                }

                .stat-btn.selected {
                    border-color: #DDF247;
                    box-shadow: 0 0 12px rgba(221, 242, 71, 0.6);
                }

                .stat-btn.disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                .stat-confirm {
                    color: #aaa;
                    font-size: 14px;
                }

                .stat-choice {
                    margin-top: 20px;
                }

                .stat-title {
                    color: #DDF247;
                    font-size: 16px;
                    margin-bottom: 12px;
                }

                .stat-buttons {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    justify-content: center;
                }

                .stat-btn {
                    background: #232340;
                    border: 2px solid #444;
                }

                .stat-btn.selected {
                    border-color: #DDF247;
                    box-shadow: 0 0 12px rgba(221, 242, 71, 0.4);
                }

                .stat-btn.disabled {
                    opacity: 0.6;
                    cursor: default;
                }

                .stat-confirm {
                    margin-top: 12px;
                    font-size: 14px;
                    color: #aaa;
                }
                
                /* Item Menu */
                .item-menu {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: #1a1a2e;
                    border: 3px solid #DDF247;
                    border-radius: 12px;
                    padding: 20px;
                    min-width: 280px;
                    z-index: 100;
                }
                
                .item-menu-title {
                    color: #DDF247;
                    font-size: 20px;
                    text-align: center;
                    margin-bottom: 15px;
                    text-shadow: 0 0 10px rgba(221, 242, 71, 0.5);
                }
                
                .item-btn {
                    width: 100%;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 16px;
                    background: #232340;
                    border: 2px solid #444;
                    border-radius: 8px;
                    color: #fff;
                    font-family: inherit;
                    font-size: 14px;
                    cursor: pointer;
                    margin-bottom: 10px;
                    transition: all 0.2s;
                }
                
                .item-btn:hover {
                    background: #2a2a4a;
                    border-color: #DDF247;
                }

                .item-btn.disabled {
                    opacity: 0.45;
                    cursor: not-allowed;
                    border-color: #555;
                }
                
                .item-icon {
                    font-size: 24px;
                }
                
                .item-name {
                    flex: 1;
                    text-align: left;
                }
                
                .item-desc {
                    color: #88ff88;
                    font-size: 12px;
                }
                
                .back-btn {
                    width: 100%;
                    padding: 10px;
                    background: transparent;
                    border: 2px solid #666;
                    border-radius: 8px;
                    color: #aaa;
                    font-family: inherit;
                    font-size: 14px;
                    cursor: pointer;
                    margin-top: 10px;
                }
                
                .back-btn:hover {
                    border-color: #fff;
                    color: #fff;
                }
                
                /* Hidden */
                .hidden {
                    display: none !important;
                }
            `;
            document.head.appendChild(style);
        }
        
        /**
         * Show decision phase
         */
        showDecisionPhase() {
            this.decisionMenu.classList.remove('hidden');
            this.attackPhase.classList.add('hidden');
            this.bulletPhase.classList.add('hidden');
            this.battleMessage.classList.add('hidden');
        }
        
        /**
         * Show attack phase
         */
        showAttackPhase() {
            this.decisionMenu.classList.add('hidden');
            this.attackPhase.classList.remove('hidden');
            
            // Start attack meter animation
            this.animateAttackMeter();
            
            // Listen for attack input
            this.attackKeyHandler = (e) => {
                if (e.code === 'Space' || e.keyCode === 32) {
                    e.preventDefault();
                    
                    // Stop the animation immediately
                    if (this.attackAnimationFrame) {
                        cancelAnimationFrame(this.attackAnimationFrame);
                        this.attackAnimationFrame = null;
                    }
                    
                    const timing = this.getAttackTiming();
                    this.battle.handleAttackHit(timing);
                    document.removeEventListener('keydown', this.attackKeyHandler);
                }
            };
            document.addEventListener('keydown', this.attackKeyHandler);
        }
        
        /**
         * Show item menu
         */
        showItemMenu() {
            this.decisionMenu.classList.add('hidden');
            
            // Create item menu
            const itemMenu = document.createElement('div');
            itemMenu.id = 'item-menu';
            itemMenu.className = 'item-menu';
            itemMenu.innerHTML = `
                <div class="item-menu-content">
                    <div class="item-menu-title">🎒 ITEMS</div>
                    <button class="item-btn" data-item="potion">
                        <span class="item-icon">🧪</span>
                        <span class="item-name">Health Potion</span>
                        <span class="item-desc">+30 HP</span>
                    </button>
                    <button class="item-btn" data-item="mana">
                        <span class="item-icon">💧</span>
                        <span class="item-name">Mana Potion</span>
                        <span class="item-desc">+20 MP</span>
                    </button>
                    <button class="back-btn">← Back</button>
                </div>
            `;
            
            document.getElementById('battle-arena').appendChild(itemMenu);
            
            // Bind item buttons
            itemMenu.querySelectorAll('.item-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const itemId = btn.dataset.item;
                    itemMenu.remove();
                    this.battle.useItem(itemId);
                });
            });
            
            itemMenu.querySelector('.back-btn').addEventListener('click', () => {
                itemMenu.remove();
                this.decisionMenu.classList.remove('hidden');
            });
        }

        showMagicMenu(spells, currentMp) {
            this.decisionMenu.classList.add('hidden');

            const magicMenu = document.createElement('div');
            magicMenu.id = 'magic-menu';
            magicMenu.className = 'item-menu';

            const spellButtons = spells.map(spell => {
                const mpText = `MP ${spell.mpCost}`;
                const effectText = spell.type === 'heal' ? `Heal ${spell.power} HP` : `${spell.power} DMG`;
                const disabledClass = currentMp < spell.mpCost ? 'disabled' : '';
                return `
                    <button class="item-btn ${disabledClass}" data-spell="${spell.id}" ${currentMp < spell.mpCost ? 'disabled' : ''}>
                        <span class="item-icon">${spell.type === 'heal' ? '💚' : '✨'}</span>
                        <span class="item-name">${spell.name}</span>
                        <span class="item-desc">${effectText} • ${mpText}</span>
                    </button>
                `;
            }).join('');

            magicMenu.innerHTML = `
                <div class="item-menu-content">
                    <div class="item-menu-title">✨ MAGIC</div>
                    ${spellButtons}
                    <button class="back-btn">← Back</button>
                </div>
            `;

            document.getElementById('battle-arena').appendChild(magicMenu);

            return new Promise(resolve => {
                magicMenu.querySelectorAll('.item-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (btn.disabled) return;
                        const selected = spells.find(s => s.id === btn.dataset.spell);
                        magicMenu.remove();
                        resolve(selected || null);
                    });
                });

                magicMenu.querySelector('.back-btn').addEventListener('click', () => {
                    magicMenu.remove();
                    this.decisionMenu.classList.remove('hidden');
                    resolve(null);
                });
            });
        }
        
        /**
         * Animate attack meter
         */
        animateAttackMeter() {
            const indicator = this.attackIndicator;
            const meter = this.attackMeter;
            let position = 0;
            let direction = 1;
            const speed = 2; // Slower for easier gameplay
            
            const animate = () => {
                if (this.battle.state.phase !== 'ATTACK') return;
                
                position += speed * direction;
                if (position >= 100 || position <= 0) {
                    direction *= -1;
                }
                
                indicator.style.left = position + '%';
                
                this.attackAnimationFrame = requestAnimationFrame(animate);
            };
            
            animate();
        }
        
        /**
         * Get attack timing value
         */
        getAttackTiming() {
            const indicator = this.attackIndicator;
            const meterWidth = this.attackMeter.offsetWidth;
            const indicatorPosition = parseFloat(indicator.style.left) / 100 * meterWidth;
            
            // DEBUG: Log timing calculation
            console.log('[DEBUG] Indicator position:', indicatorPosition, 'px', '(', (indicatorPosition/meterWidth*100).toFixed(1), '%)');
            
            // Calculate position-based zone (rhythm game style)
            // The meter is 0-100%, zones are:
            // - PERFECT: 0-20% (center at 10%)
            // - GOOD: 20-45%
            // - OK: 45-100%
            const indicatorPercent = indicatorPosition / meterWidth * 100;
            
            // Determine zone based on position
            let zone = 'MISS';
            let quality = 'MISS';
            
            if (indicatorPercent <= 20) {
                zone = 'PERFECT';
                quality = 'PERFECT';
            } else if (indicatorPercent <= 45) {
                zone = 'GOOD';
                quality = 'GOOD';
            } else if (indicatorPercent <= 100) {
                zone = 'OK';
                quality = 'OK';
            }
            
            // Calculate "timing" for backwards compatibility (in milliseconds)
            // Perfect = 0ms, further = more ms
            let timing = 0;
            if (quality === 'PERFECT') {
                // Very close to perfect center (10%)
                const distanceFromCenter = Math.abs(indicatorPercent - 10);
                timing = distanceFromCenter * 5; // Max ~50ms for perfect
            } else if (quality === 'GOOD') {
                // In good zone
                const distanceFromGoodStart = indicatorPercent - 20;
                timing = 50 + distanceFromGoodStart * 3; // 50-125ms
            } else {
                // OK zone
                const distanceFromOkStart = indicatorPercent - 45;
                timing = 150 + distanceFromOkStart * 2; // 150-250ms
            }
            
            console.log('[DEBUG] Zone:', zone, 'Quality:', quality, 'Timing:', timing, 'ms');
            
            // Return timing for backwards compatibility
            return timing;
        }
        
        /**
         * Get attack quality based on indicator position (new zone-based method)
         */
        getAttackQuality() {
            const indicator = this.attackIndicator;
            const meterWidth = this.attackMeter.offsetWidth;
            const indicatorPosition = parseFloat(indicator.style.left) / 100 * meterWidth;
            const indicatorPercent = indicatorPosition / meterWidth * 100;
            
            // Zone-based detection
            if (indicatorPercent <= 20) {
                return 'PERFECT';
            } else if (indicatorPercent <= 45) {
                return 'GOOD';
            } else if (indicatorPercent <= 100) {
                return 'OK';
            }
            return 'MISS';
        }
        
        /**
         * Show bullet phase
         */
        showBulletPhase(duration) {
            this.decisionMenu.classList.add('hidden');
            this.attackPhase.classList.add('hidden');
            this.bulletPhase.classList.remove('hidden');
            
            // Position player hitbox in center (in pixels for 300x300 arena)
            this.playerHitbox.style.left = '150px';
            this.playerHitbox.style.top = '150px';
            
            // Start timer
            const timerBar = document.getElementById('bullet-timer-bar');
            timerBar.style.transition = `transform ${duration}ms linear`;
            timerBar.style.transform = 'scaleX(0)';
            
            // Setup keyboard controls
            this.setupBulletControls();
        }
        
        /**
         * Setup bullet phase keyboard controls
         */
        setupBulletControls() {
            // Clean up any existing bullet controls first
            this.cleanupBulletControls();
            
            const hitbox = this.playerHitbox;
            const arena = this.bulletArena || document.getElementById('bullet-arena');
            
            // Initialize position from current hitbox position or use center
            const currentLeft = parseInt(hitbox.style.left) || 150;
            const currentTop = parseInt(hitbox.style.top) || 150;

            const arenaSize = arena ? Math.min(arena.clientWidth, arena.clientHeight) : 300;
            const arenaCenter = arenaSize / 2;
            const hitboxRadius = Math.max(10, Math.ceil((hitbox.offsetWidth || 24) / 2));
            const borderPadding = 4;
            const maxDistance = Math.max(20, arenaCenter - hitboxRadius - borderPadding);

            const clampToArenaCircle = (x, y) => {
                const dx = x - arenaCenter;
                const dy = y - arenaCenter;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist <= maxDistance || dist === 0) {
                    return { x, y };
                }

                const scale = maxDistance / dist;
                return {
                    x: arenaCenter + dx * scale,
                    y: arenaCenter + dy * scale
                };
            };
            
            // Use instance properties to persist position across bullet phases
            const initialPos = clampToArenaCircle(currentLeft, currentTop);
            this.bulletPosX = initialPos.x;
            this.bulletPosY = initialPos.y;
            const agility = Math.max(0, Number(this.battle?.state?.player?.agility) || 0);
            const baseBulletSpeed = 4;
            const agilityBonus = Math.min(1.5, Math.sqrt(agility) * 0.15);
            this.bulletSpeed = Math.min(5.5, baseBulletSpeed + agilityBonus);
            
            this.bulletKeyState = {
                up: false,
                down: false,
                left: false,
                right: false
            };
            
            this.bulletKeyDown = (e) => {
                switch(e.key) {
                    case 'ArrowUp': case 'w': case 'W': this.bulletKeyState.up = true; break;
                    case 'ArrowDown': case 's': case 'S': this.bulletKeyState.down = true; break;
                    case 'ArrowLeft': case 'a': case 'A': this.bulletKeyState.left = true; break;
                    case 'ArrowRight': case 'd': case 'D': this.bulletKeyState.right = true; break;
                }
            };
            
            this.bulletKeyUp = (e) => {
                switch(e.key) {
                    case 'ArrowUp': case 'w': case 'W': this.bulletKeyState.up = false; break;
                    case 'ArrowDown': case 's': case 'S': this.bulletKeyState.down = false; break;
                    case 'ArrowLeft': case 'a': case 'A': this.bulletKeyState.left = false; break;
                    case 'ArrowRight': case 'd': case 'D': this.bulletKeyState.right = false; break;
                }
            };
            
            document.addEventListener('keydown', this.bulletKeyDown);
            document.addEventListener('keyup', this.bulletKeyUp);

            hitbox.style.left = this.bulletPosX + 'px';
            hitbox.style.top = this.bulletPosY + 'px';
            
            this.bulletMoveInterval = setInterval(() => {
                let nextX = this.bulletPosX;
                let nextY = this.bulletPosY;

                if (this.bulletKeyState.up) nextY -= this.bulletSpeed;
                if (this.bulletKeyState.down) nextY += this.bulletSpeed;
                if (this.bulletKeyState.left) nextX -= this.bulletSpeed;
                if (this.bulletKeyState.right) nextX += this.bulletSpeed;

                const constrained = clampToArenaCircle(nextX, nextY);
                this.bulletPosX = constrained.x;
                this.bulletPosY = constrained.y;
                
                hitbox.style.left = this.bulletPosX + 'px';
                hitbox.style.top = this.bulletPosY + 'px';
            }, 16);
        }
        
        /**
         * Clean up bullet phase controls
         */
        cleanupBulletControls() {
            // Remove event listeners
            if (this.bulletKeyDown) {
                document.removeEventListener('keydown', this.bulletKeyDown);
            }
            if (this.bulletKeyUp) {
                document.removeEventListener('keyup', this.bulletKeyUp);
            }
            
            // Clear movement interval
            if (this.bulletMoveInterval) {
                clearInterval(this.bulletMoveInterval);
                this.bulletMoveInterval = null;
            }
            
            // Reset key state
            this.bulletKeyState = null;
        }
        
        /**
         * Update stats display
         */
        updateStats() {
            const player = this.battle.state.player;
            const enemy = this.battle.state.enemy;
            
            // Player HP
            const hpPercent = (player.currentHp / player.maxHp) * 100;
            document.getElementById('player-hp-fill').style.width = hpPercent + '%';
            document.getElementById('player-hp-text').textContent = `${player.currentHp}/${player.maxHp}`;
            
            // Player MP
            const mpPercent = (player.currentMp / player.maxMp) * 100;
            document.getElementById('player-mp-fill').style.width = mpPercent + '%';
            document.getElementById('player-mp-text').textContent = `${player.currentMp}/${player.maxMp}`;
            
            // Enemy HP
            const enemyHpPercent = (enemy.currentHp / enemy.maxHp) * 100;
            document.querySelector('.enemy-hp-bar .hp-fill').style.width = enemyHpPercent + '%';
            document.getElementById('enemy-hp').textContent = enemy.currentHp;
        }

        updatePlayerStats(player) {
            const atk = document.getElementById('player-atk');
            const def = document.getElementById('player-def');
            const mag = document.getElementById('player-mag');
            const agi = document.getElementById('player-agi');

            if (atk) atk.textContent = player.attack;
            if (def) def.textContent = player.defense;
            if (mag) mag.textContent = player.magic;
            if (agi) agi.textContent = player.agility;
        }
        
        /**
         * Add entry to battle log
         */
        addLogEntry(message, type = 'system') {
            const log = document.getElementById('battle-log');
            if (!log) return;
            
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.textContent = message;
            
            log.appendChild(entry);
            
            // Auto-scroll to bottom (newest message) - force immediate scroll
            requestAnimationFrame(() => {
                log.scrollTop = log.scrollHeight;
            });
            
            // Keep only last 20 entries
            while (log.children.length > 20) {
                log.removeChild(log.firstChild);
            }
        }
        
        /**
         * Show damage number
         */
        showDamageNumber(damage, quality) {
            const container = this.damageNumbers;
            const el = document.createElement('div');
            el.className = `damage-number ${quality.toLowerCase()}`;
            el.textContent = damage;
            
            // Position near enemy
            const enemyArea = document.getElementById('enemy-area');
            const rect = enemyArea.getBoundingClientRect();
            el.style.left = (rect.left + rect.width / 2) + 'px';
            el.style.top = (rect.top + 50) + 'px';
            
            container.appendChild(el);
            
            // Remove after animation
            setTimeout(() => el.remove(), 1000);
        }
        
        /**
         * Show attack multiplier on attack bar
         */
        showAttackMultiplier(quality) {
            if (!this.attackMultiplier) return;
            
            let multiplierText = '';
            let multiplierClass = '';
            
            switch(quality) {
                case 'PERFECT':
                    multiplierText = '2x ATTACK!';
                    multiplierClass = 'perfect';
                    break;
                case 'GOOD':
                    multiplierText = '1.25x ATTACK';
                    multiplierClass = 'good';
                    break;
                case 'OK':
                    multiplierText = '1x ATTACK';
                    multiplierClass = 'ok';
                    break;
                default:
                    return;
            }
            
            this.attackMultiplier.textContent = multiplierText;
            this.attackMultiplier.className = 'attack-multiplier ' + multiplierClass;
            
            // Clear after a short delay
            setTimeout(() => {
                this.attackMultiplier.textContent = '';
                this.attackMultiplier.className = 'attack-multiplier';
            }, 1500);
        }
        
        /**
         * Show critical hit effect
         */
        showCriticalHit() {
            const container = this.container;
            container.classList.add('screen-shake');
            
            const flash = document.createElement('div');
            flash.className = 'screen-flash';
            this.screenEffects.appendChild(flash);
            
            setTimeout(() => {
                container.classList.remove('screen-shake');
                flash.remove();
            }, 300);
        }
        
        /**
         * Show message
         */
        showMessage(text) {
            this.battleMessage.textContent = text;
            this.battleMessage.classList.remove('hidden');
            
            setTimeout(() => {
                this.battleMessage.classList.add('hidden');
            }, 2000);
        }
        
        /**
         * Show enemy attack animation
         */
        async showEnemyAttack(damage) {
            // Flash the player area
            this.showMessage(`${this.battle.state.enemy.name} attacks!`);
            await this.battle.delay(500);
            
            const playerArea = document.getElementById('player-area');
            playerArea.style.background = 'rgba(255, 0, 0, 0.5)';
            
            this.showDamageNumber(damage, 'miss');
            
            await this.battle.delay(500);
            playerArea.style.background = '';
        }
        
        /**
         * Show victory screen
         */
        showVictory(loot, rewardPreview = { restorePercent: 25, statGain: 5 }) {
            this.battleResult.classList.remove('hidden');
            this.stopMusic();
            this.sounds.victoryMusic.currentTime = 0;
            this.sounds.victoryMusic.play().catch(() => {});
            
            this.battleResult.innerHTML = `
                <div class="result-content victory">
                    <h1 id="result-title">🎉 VICTORY! 🎉</h1>
                    <div class="result-details">
                        <div>You defeated ${this.battle.state.enemy.name}!</div>
                    </div>
                    <div class="stat-choice">
                        <div class="stat-title">Choose one reward</div>
                        <div class="stat-buttons">
                            <button class="battle-btn stat-btn" data-choice="restore-hp">Restore HP (+${rewardPreview.restorePercent}%)</button>
                            <button class="battle-btn stat-btn" data-choice="restore-mp">Restore MP (+${rewardPreview.restorePercent}%)</button>
                            <button class="battle-btn stat-btn" data-choice="attack">+${rewardPreview.statGain} ATK</button>
                            <button class="battle-btn stat-btn" data-choice="defense">+${rewardPreview.statGain} DEF</button>
                            <button class="battle-btn stat-btn" data-choice="magic">+${rewardPreview.statGain} MAG</button>
                            <button class="battle-btn stat-btn" data-choice="agility">+${rewardPreview.statGain} AGI</button>
                        </div>
                        <div class="stat-confirm" id="stat-confirm">Pick a reward to continue.</div>
                    </div>
                </div>
            `;

            return new Promise(resolve => {
                const buttons = this.battleResult.querySelectorAll('.stat-btn');
                const confirm = this.battleResult.querySelector('#stat-confirm');
                let selected = false;

                buttons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (selected) return;
                        selected = true;
                        buttons.forEach(other => {
                            other.disabled = true;
                            other.classList.add('disabled');
                        });
                        btn.classList.add('selected');
                        if (confirm) {
                            confirm.textContent = 'Reward applied! Returning to overworld...';
                        }
                        resolve(btn.dataset.choice);
                    });
                });
            });
        }
        
        /**
         * Show defeat screen
         */
        showDefeat() {
            this.battleResult.classList.remove('hidden');
            
            this.battleResult.innerHTML = `
                <div class="result-content defeat">
                    <h1 id="result-title">💀 DEFEATED 💀</h1>
                    <div class="result-details">
                        <div>${this.battle.state.enemy.name} was too strong...</div>
                        <div>Choose your fate.</div>
                    </div>
                    <button class="battle-btn retry-half-btn" style="margin-top: 10px; background: #4a4;">Try Again (50% HP/MP)</button>
                    <button class="battle-btn give-up-btn" style="margin-top: 10px; background: #a33;">Give Up (Bad Ending)</button>
                </div>
            `;

            return new Promise(resolve => {
                let selected = false;

                document.querySelector('.retry-half-btn')?.addEventListener('click', () => {
                    if (selected) return;
                    selected = true;
                    resolve('retry-half');
                });

                document.querySelector('.give-up-btn')?.addEventListener('click', () => {
                    if (selected) return;
                    selected = true;
                    resolve('give-up');
                });
            });
        }
        
        /**
         * Cleanup
         */
        destroy() {
            this.stopMusic();
            this.sounds.victoryMusic.pause();
            this.sounds.victoryMusic.currentTime = 0;
            if (this.attackAnimationFrame) {
                cancelAnimationFrame(this.attackAnimationFrame);
            }
            if (this.attackKeyHandler) {
                document.removeEventListener('keydown', this.attackKeyHandler);
            }
            if (this.bulletKeyDown) {
                document.removeEventListener('keydown', this.bulletKeyDown);
                document.removeEventListener('keyup', this.bulletKeyUp);
            }
            if (this.bulletMoveInterval) {
                clearInterval(this.bulletMoveInterval);
            }
            if (this.container) {
                this.container.remove();
            }
        }
    }
    
    // Export to global
    window.BattleUI = BattleUI;
    
})();
