


/**
 * Bullet System Module
 * Undertale-style dodge phase with various bullet patterns
 */

(function() {
    'use strict';
    
    class BulletSystem {
        constructor(battle) {
            this.battle = battle;
            this.arena = null;
            this.playerHitbox = null;
            this.bullets = [];
            this.animationId = null;
            this.startTime = 0;
            this.duration = 0;
            this.isRunning = false;
            this.damageTaken = 0;
            this.bulletIdCounter = 0;
            // Scale bullet damage based on enemy attack stat
            this.bulletDamage = 0;
        }
        
        /**
         * Start bullet phase
         */
        start(duration) {
            this.duration = duration;
            this.isRunning = true;
            this.startTime = Date.now();
            this.bullets = [];
            // Calculate bullet damage based on enemy attack (threatening but fair)
            const enemyAttack = this.battle.state?.enemy?.attack || 10;
            const isBossBattle = !!this.battle.state?.enemy?.isBoss;
            const damageScale = isBossBattle ? 0.36 : 0.3;
            const minDamage = isBossBattle ? 4 : 3;
            this.bulletDamage = Math.max(minDamage, Math.floor(enemyAttack * damageScale));
            this.damageTaken = 0;
            this.hitCount = 0;  // Track consecutive hits during this bullet phase
            
            // Get DOM elements
            this.arena = document.getElementById('bullet-arena');
            this.playerHitbox = document.getElementById('player-hitbox');
            
            // Clear existing bullets
            this.arena.querySelectorAll('.bullet').forEach(b => b.remove());
            
            // Start spawning bullets based on enemy type
            this.scheduleBulletPatterns();
            
            // Start game loop
            this.gameLoop();
            
            // End after duration
            setTimeout(() => this.end(), duration);
        }
        
        /**
         * Schedule bullet patterns based on enemy
         */
        scheduleBulletPatterns() {
            const enemy = this.battle.state.enemy;
            const isBoss = enemy.isBoss;
            const behavior = enemy.behavior || 'normal';

            // Ghost-specific pattern: slow closing circles + sequential seekers
            if (enemy.sprite === 'enemy2') {
                this.ghostPattern();
                return;
            }
            
            // Different patterns based on enemy type
            switch(behavior) {
                case 'aggressive':
                    this.aggressivePattern();
                    break;
                case 'defensive':
                    this.defensivePattern();
                    break;
                case 'tricky':
                    this.trickyPattern();
                    break;
                case 'boss':
                    this.bossPattern();
                    break;
                default:
                    this.normalPattern();
            }
            
            // Add extra bullets for boss
            if (isBoss) {
                setTimeout(() => this.spawnBulletRing(150, 150, 80, -2, 0), this.duration * 0.5);
            }
        }

        /**
         * Ghost pattern - circles close in slowly + seekers follow one by one
         */
        ghostPattern() {
            let phase = 0;
            const cycleInterval = 1800;

            const ghostCycle = setInterval(() => {
                if (!this.isRunning) {
                    clearInterval(ghostCycle);
                    return;
                }

                const activeBullets = this.bullets.filter(b => b.active).length;
                if (activeBullets > 24) {
                    return;
                }

                switch (phase % 3) {
                    case 0:
                        this.spawnClosingRingAroundPlayer(110, 1.1, 8);
                        break;
                    case 1:
                        this.spawnSequentialSeekers(2, 420, 2.0, 'seeker');
                        break;
                    case 2:
                        for (let i = 0; i < 3; i++) {
                            setTimeout(() => {
                                if (this.isRunning) {
                                    this.spawnTargeted(2.6);
                                }
                            }, i * 260);
                        }
                        break;
                }

                phase++;
            }, cycleInterval);
        }
        
        /**
         * Normal bullet pattern - targeted at player with varying speeds
         */
        normalPattern() {
            // Spawn bullets at intervals - all targeted at player
            const spawnInterval = 600;
            let elapsed = 0;
            
            const spawner = setInterval(() => {
                if (!this.isRunning) {
                    clearInterval(spawner);
                    return;
                }
                
                elapsed += spawnInterval;
                
                // Vary speed randomly between 2.5 and 4.5
                const speed = 2.5 + Math.random() * 2;
                
                // Target player's current position
                this.spawnTargeted(speed);
                
            }, spawnInterval);
        }
        
        /**
         * Aggressive pattern - fast bullets
         */
        aggressivePattern() {
            const spawnInterval = 500;
            
            const spawner = setInterval(() => {
                if (!this.isRunning) {
                    clearInterval(spawner);
                    return;
                }
                
                // Spawn from random position toward center
                this.spawnTargeted(5);
                
            }, spawnInterval);
        }
        
        /**
         * Defensive pattern - spiral and patterns
         */
        defensivePattern() {
            // Spawn spiral
            let angle = 0;
            const spawnInterval = 200;
            
            const spawner = setInterval(() => {
                if (!this.isRunning) {
                    clearInterval(spawner);
                    return;
                }
                
                const rad = angle * Math.PI / 180;
                const x = 150 + Math.cos(rad) * 120;
                const y = 150 + Math.sin(rad) * 120;
                
                this.createBullet(x, y, -Math.cos(rad) * 3, -Math.sin(rad) * 3, 'spiral');
                
                angle += 15;
                
            }, spawnInterval);
        }
        
        /**
         * Tricky pattern - mixed patterns
         */
        trickyPattern() {
            // Alternate between patterns
            let pattern = 0;
            
            const switcher = setInterval(() => {
                if (!this.isRunning) {
                    clearInterval(switcher);
                    return;
                }
                
                switch(pattern % 3) {
                    case 0:
                        this.spawnFromEdge(0, 4);
                        break;
                    case 1:
                        this.spawnBulletRing(150, 150, 100, 2, 0);
                        break;
                    case 2:
                        this.spawnSequentialSeekers(2, 180, 2.2);
                        break;
                }
                
                pattern++;
                
            }, 1000);
        }

        spawnClosingRingAroundPlayer(radius, speed, numBullets) {
            const playerPos = this.getPlayerPosition();
            const gapSize = Math.max(1, Math.floor(numBullets * 0.2));
            const gapStart = Math.floor(Math.random() * numBullets);
            const oppositeGapStart = (gapStart + Math.floor(numBullets / 2)) % numBullets;

            const inGap = (index, start, size, total) => {
                const distance = (index - start + total) % total;
                return distance < size;
            };

            for (let i = 0; i < numBullets; i++) {
                if (inGap(i, gapStart, gapSize, numBullets) || inGap(i, oppositeGapStart, gapSize, numBullets)) {
                    continue;
                }
                const angle = (i / numBullets) * Math.PI * 2;
                const x = playerPos.x + Math.cos(angle) * radius;
                const y = playerPos.y + Math.sin(angle) * radius;
                const vx = -Math.cos(angle) * speed;
                const vy = -Math.sin(angle) * speed;
                this.createBullet(x, y, vx, vy, 'ghost-ring');
            }
        }

        spawnSequentialSeekers(count, intervalMs, speed, type = 'seeker') {
            for (let i = 0; i < count; i++) {
                setTimeout(() => {
                    if (!this.isRunning) return;
                    this.spawnGhostSeeker(speed, type);
                }, i * intervalMs);
            }
        }

        spawnGhostSeeker(speed, type = 'seeker') {
            const playerPos = this.getPlayerPosition();
            const startPos = this.getRandomEdgePosition();
            const dx = playerPos.x - startPos.x;
            const dy = playerPos.y - startPos.y;
            const dist = Math.max(1, Math.sqrt(dx * dx + dy * dy));
            const vx = (dx / dist) * speed;
            const vy = (dy / dist) * speed;

            this.createBullet(startPos.x, startPos.y, vx, vy, type);
        }
        
        /**
         * Boss pattern - intense
         */
        bossPattern() {
            let cycle = 0;

            const phaseManager = setInterval(() => {
                if (!this.isRunning) {
                    clearInterval(phaseManager);
                    return;
                }

                const enemy = this.battle?.state?.enemy;
                const hp = enemy?.currentHp ?? 1000;
                const activeBullets = this.bullets.filter(b => b.active).length;

                let phase = 1;
                if (hp <= 400) {
                    phase = 3;
                } else if (hp <= 650) {
                    phase = 2;
                }

                const maxActive = phase === 1 ? 24 : phase === 2 ? 28 : 32;
                if (activeBullets > maxActive) {
                    return;
                }

                switch (phase) {
                    case 1:
                        // P1 (>650 HP): readable and dodgeable openings
                        if (cycle % 3 === 0) {
                            this.spawnClosingRingAroundPlayer(115, 1.25, 10);
                        } else if (cycle % 3 === 1) {
                            this.spawnSequentialSeekers(2, 300, 2.8, 'boss-seeker');
                        } else {
                            for (let i = 0; i < 3; i++) {
                                setTimeout(() => this.spawnTargeted(3.3), i * 150);
                            }
                        }
                        break;
                    case 2:
                        // P2 (<=650 HP): tighter mixes, still fair spacing
                        if (cycle % 3 === 0) {
                            this.spawnClosingRingAroundPlayer(108, 1.4, 11);
                            setTimeout(() => {
                                if (this.isRunning) this.spawnSequentialSeekers(2, 280, 2.95, 'boss-seeker');
                            }, 300);
                        } else if (cycle % 3 === 1) {
                            for (let i = 0; i < 4; i++) {
                                setTimeout(() => this.spawnTargeted(3.7), i * 130);
                            }
                        } else {
                            this.spawnWall();
                            this.spawnSequentialSeekers(1, 320, 2.95, 'boss-seeker');
                        }
                        break;
                    case 3:
                        // P3 (<=400 HP): hardest phase, difficult but dodgeable
                        if (cycle % 3 === 0) {
                            this.spawnClosingRingAroundPlayer(102, 1.55, 12);
                            this.spawnSequentialSeekers(2, 230, 3.1, 'boss-seeker');
                        } else if (cycle % 3 === 1) {
                            for (let i = 0; i < 5; i++) {
                                setTimeout(() => this.spawnTargeted(3.95), i * 110);
                            }
                        } else {
                            this.spawnWall();
                            setTimeout(() => {
                                if (this.isRunning) this.spawnClosingRingAroundPlayer(112, 1.4, 9);
                            }, 360);
                            this.spawnSequentialSeekers(2, 280, 3.0, 'boss-seeker');
                        }
                        break;
                }

                cycle++;

            }, 1650);
        }
        
        /**
         * Spawn bullet from edge
         */
        spawnFromEdge(side, speed) {
            let x, y, vx, vy;
            
            switch(side) {
                case 0: // Top
                    x = Math.random() * 300;
                    y = -10;
                    vx = (Math.random() - 0.5) * 2;
                    vy = speed;
                    break;
                case 1: // Right
                    x = 310;
                    y = Math.random() * 300;
                    vx = -speed;
                    vy = (Math.random() - 0.5) * 2;
                    break;
                case 2: // Bottom
                    x = Math.random() * 300;
                    y = 310;
                    vx = (Math.random() - 0.5) * 2;
                    vy = -speed;
                    break;
                case 3: // Left
                    x = -10;
                    y = Math.random() * 300;
                    vx = speed;
                    vy = (Math.random() - 0.5) * 2;
                    break;
            }
            
            this.createBullet(x, y, vx, vy, 'normal');
        }
        
        /**
         * Spawn targeted bullet - targets player position at spawn, then travels in straight line
         */
        spawnTargeted(speed) {
            const playerPos = this.getPlayerPosition();
            const startPos = this.getRandomEdgePosition();
            
            // Calculate direction towards player's current position
            const dx = playerPos.x - startPos.x;
            const dy = playerPos.y - startPos.y;
            const dist = Math.max(1, Math.sqrt(dx * dx + dy * dy));
            
            // Set velocity towards player position (non-homing)
            const vx = (dx / dist) * speed;
            const vy = (dy / dist) * speed;
            
            this.createBullet(startPos.x, startPos.y, vx, vy, 'targeted');
        }
        
        /**
         * Spawn bullet ring
         */
        spawnBulletRing(cx, cy, radius, speed, offsetAngle) {
            const numBullets = 12;
            
            for (let i = 0; i < numBullets; i++) {
                const angle = (i / numBullets) * Math.PI * 2 + (offsetAngle * Math.PI / 180);
                const x = cx + Math.cos(angle) * radius;
                const y = cy + Math.sin(angle) * radius;
                const vx = Math.cos(angle) * speed;
                const vy = Math.sin(angle) * speed;
                
                this.createBullet(x, y, vx, vy, 'ring');
            }
        }
        
        /**
         * Start spiral pattern
         */
        startSpiral(duration) {
            let angle = 0;
            
            const spiral = setInterval(() => {
                if (!this.isRunning) {
                    clearInterval(spiral);
                    return;
                }
                
                const rad = angle * Math.PI / 180;
                const x = 150 + Math.cos(rad) * 100;
                const y = 150 + Math.sin(rad) * 100;
                
                this.createBullet(x, y, Math.cos(rad) * 3, Math.sin(rad) * 3, 'spiral');
                
                angle += 20;
                
            }, 100);
            
            setTimeout(() => clearInterval(spiral), duration * 1000);
        }
        
        /**
         * Spawn wall of bullets
         */
        spawnWall() {
            for (let i = 0; i < 8; i++) {
                this.createBullet(i * 35 + 20, -10, 0, 3, 'wall');
            }
        }
        
        /**
         * Create a bullet
         */
        createBullet(x, y, vx, vy, type) {
            if (this.bullets.length >= 50) return; // Max bullets limit
            
            // Get enemy sprite to determine bullet image
            const enemySprite = this.battle.state?.enemy?.sprite || 'enemy1';
            console.log('[BULLETS] Enemy sprite:', enemySprite);
            
            // Map enemy sprites to their attack images (relative paths from public folder)
            const enemyAttackImages = {
                'enemy1': '/assets/adventure/enemies/enemy1 attack.png',
                'enemy2': '/assets/adventure/enemies/enemy2 attack.png',
                'enemy3': '/assets/adventure/enemies/enemy3 attack.png',
                'boss': '/assets/adventure/enemies/Boss attack.png'
            };
            
            // Get the attack image for this enemy (default to enemy1 if not found)
            const attackImage = enemyAttackImages[enemySprite] || enemyAttackImages['enemy1'];
            console.log('[BULLETS] Using image:', attackImage);
            
            // Calculate rotation angle based on velocity direction (point towards movement direction)
            const angle = Math.atan2(vy, vx) * (180 / Math.PI);
            
            // Random color for fallback and effects
            const colors = ['#ff6b6b', '#ffd93d', '#6bcb77', '#4d96ff', '#9b59b6', '#ff9f43', '#00d2d3', '#ff6b81'];
            const randomColor = colors[Math.floor(Math.random() * colors.length)];
            
            const bullet = document.createElement('div');
            bullet.className = `bullet ${type}`;
            
            // Use enemy attack image as background, rotated to point towards movement direction
            // Only show the image, no colored fallback
            bullet.style.cssText = `
                position: absolute;
                left: ${x}px;
                top: ${y}px;
                width: 24px;
                height: 24px;
                background: url("${attackImage}") center/contain no-repeat;
                transform: translate(-50%, -50%) rotate(${angle}deg);
            `;
            
            this.arena.appendChild(bullet);
            
            this.bullets.push({
                id: this.bulletIdCounter++,
                element: bullet,
                x: x,
                y: y,
                vx: vx,
                vy: vy,
                type: type,
                color: randomColor,
                createdAt: Date.now(),
                active: true
            });
        }
        
        /**
         * Get bullet color based on type (for compatibility)
         */
        getBulletColor(type) {
            const colors = {
                normal: '#ff6b6b',      // Red
                targeted: '#ffd93d',    // Yellow
                spiral: '#6bcb77',      // Green  
                ring: '#4d96ff',        // Blue
                homing: '#9b59b6',      // Purple
                wall: '#ff6b6b'        // Red
            };
            return colors[type] || '#ff6b6b';
        }
        
        /**
         * Get player position in arena
         */
        getPlayerPosition() {
            const hitbox = this.playerHitbox;
            const arena = this.arena;
            
            // Safety check
            if (!hitbox || !arena) {
                return { x: 150, y: 150 }; // Default center position
            }
            
            // Use offsetLeft/offsetTop for more stable positioning
            // This is less affected by browser reflows
            const arenaRect = arena.getBoundingClientRect();
            const hitboxRect = hitbox.getBoundingClientRect();
            
            // Calculate position relative to arena
            let x = hitboxRect.left - arenaRect.left + hitboxRect.width / 2;
            let y = hitboxRect.top - arenaRect.top + hitboxRect.height / 2;
            
            // Clamp to arena bounds
            x = Math.max(10, Math.min(290, x));
            y = Math.max(10, Math.min(290, y));
            
            return { x, y };
        }
        
        /**
         * Get random edge position
         */
        getRandomEdgePosition() {
            const side = Math.floor(Math.random() * 4);
            
            switch(side) {
                case 0: return { x: Math.random() * 300, y: -10 };
                case 1: return { x: 310, y: Math.random() * 300 };
                case 2: return { x: Math.random() * 300, y: 310 };
                case 3: return { x: -10, y: Math.random() * 300 };
            }
        }
        
        /**
         * Main game loop
         */
        gameLoop() {
            if (!this.isRunning) return;
            
            const playerPos = this.getPlayerPosition();
            const playerRadius = 8; // Smaller hitbox for easier dodging
            
            // Update bullets
            for (let i = this.bullets.length - 1; i >= 0; i--) {
                const bullet = this.bullets[i];
                
                if (!bullet.active) continue;
                
                // Ghost seekers slightly home in over time
                if (bullet.type === 'seeker' || bullet.type === 'boss-seeker') {
                    const bulletAge = Date.now() - (bullet.createdAt || 0);
                    if (bulletAge <= 2000) {
                        const targetDx = playerPos.x - bullet.x;
                        const targetDy = playerPos.y - bullet.y;
                        const targetDist = Math.max(1, Math.sqrt(targetDx * targetDx + targetDy * targetDy));
                        const homingSpeed = bullet.type === 'boss-seeker' ? 3.8 : 2.4;
                        const homingStrength = bullet.type === 'boss-seeker' ? 0.1 : 0.06;
                        const desiredVx = (targetDx / targetDist) * homingSpeed;
                        const desiredVy = (targetDy / targetDist) * homingSpeed;
                        bullet.vx += (desiredVx - bullet.vx) * homingStrength;
                        bullet.vy += (desiredVy - bullet.vy) * homingStrength;
                    }
                }
                
                // Move bullet
                bullet.x += bullet.vx;
                bullet.y += bullet.vy;
                
                // Update DOM
                bullet.element.style.left = bullet.x + 'px';
                bullet.element.style.top = bullet.y + 'px';
                
                // Check collision with player
                const dx = bullet.x - playerPos.x;
                const dy = bullet.y - playerPos.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                // Use generous collision radius for better gameplay feel
                if (dist < playerRadius + 6) { // 6 is half bullet size + tolerance
                    // Hit! Normal enemies ramp damage with each consecutive hit.
                    // Bosses keep flat damage for readability/fairness.
                    this.hitCount++;
                    const player = this.battle.state.player;
                    const rawMitigation = ((player.defense || 0) * 0.1) + ((player.agility || 0) * 0.04);
                    const isBossBattle = !!this.battle.state?.enemy?.isBoss;
                    const baseDamage = Math.max(1, this.bulletDamage || 2);
                    let scaledDamage = baseDamage;

                    if (!isBossBattle) {
                        const scalingPerHit = Math.max(1, Math.floor(baseDamage * 0.3));
                        const streakBonus = (this.hitCount - 1) * scalingPerHit;
                        const maxStreakBonus = Math.ceil(baseDamage * 1.6);
                        scaledDamage = baseDamage + Math.min(streakBonus, maxStreakBonus);
                    }

                    const mitigationCap = scaledDamage * (isBossBattle ? 0.35 : 0.5);
                    const effectiveMitigation = Math.min(rawMitigation, mitigationCap);
                    const currentDamage = Math.max(1, Math.floor(scaledDamage - effectiveMitigation));
                    this.damageTaken += currentDamage;
                    this.showHitEffect(bullet.x, bullet.y);
                    
                    // Update player HP in real-time
                    player.currentHp = Math.max(0, player.currentHp - currentDamage);
                    this.battle.ui.updateStats();
                    
                    // Play damage sound
                    if (this.battle.ui && this.battle.ui.playSound) {
                        this.battle.ui.playSound('damage');
                    }
                    
                    // Check for defeat during bullet phase
                    if (player.currentHp <= 0) {
                        this.end(false);
                        this.battle.handleDefeat();
                        return;
                    }
                    
                    // Remove bullet
                    bullet.element.remove();
                    bullet.active = false;
                    this.bullets.splice(i, 1);
                    continue;
                }
                
                // Remove if out of bounds
                if (bullet.x < -20 || bullet.x > 320 || bullet.y < -20 || bullet.y > 320) {
                    bullet.element.remove();
                    bullet.active = false;
                    this.bullets.splice(i, 1);
                }
            }
            
            // Continue loop
            this.animationId = requestAnimationFrame(() => this.gameLoop());
        }
        
        /**
         * Show hit effect
         */
        showHitEffect(x, y) {
            const effect = document.createElement('div');
            effect.style.cssText = `
                position: absolute;
                left: ${x}px;
                top: ${y}px;
                width: 30px;
                height: 30px;
                background: rgba(255, 0, 0, 0.5);
                border-radius: 50%;
                transform: translate(-50%, -50%);
                animation: hitEffect 0.3s ease-out forwards;
            `;
            
            this.arena.appendChild(effect);
            
            setTimeout(() => effect.remove(), 300);
        }
        
        /**
         * End bullet phase
         */
        end(notifyBattle = true) {
            this.isRunning = false;
            
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
            }
            
            // Clear all bullets
            this.bullets.forEach(b => {
                if (b.element) b.element.remove();
            });
            this.bullets = [];
            
            // Notify battle controller
            if (notifyBattle) {
                this.battle.handleBulletPhaseEnd(this.damageTaken);
            }
        }
        
        /**
         * Stop (emergency stop)
         */
        stop() {
            this.end();
        }
    }
    
    // Export to global
    window.BulletSystem = BulletSystem;
    
})();
