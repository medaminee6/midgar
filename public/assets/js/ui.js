/**
 * Battle UI Module
 * Renders turn-based battle interface using website design system
 */

class BattleUI {
  constructor(container, gameInstance) {
    this.container = container;
    this.game = gameInstance;
    this.battle = null;
  }

  /**
   * Initialize battle UI
   */
  initialize(battle) {
    this.battle = battle;
    this.render();
  }

  /**
   * Render the complete battle interface
   */
  render() {
    const state = this.battle.getState();
    
    this.container.innerHTML = `
      <div class="battle-container">
        <div class="battle-header">
          <h2>Battle: ${state.player.name} vs ${state.enemy.name}</h2>
        </div>

        <div class="battle-field">
          <!-- Player Side -->
          <div class="battle-side player-side">
            <div class="character-card">
              <div class="character-name">${state.player.name}</div>
              <div class="health-bar">
                <div class="health-fill" style="width: ${(state.player.hp / state.player.maxHp) * 100}%"></div>
              </div>
              <div class="health-text">${state.player.hp}/${state.player.maxHp} HP</div>
              <div class="stats-grid">
                <div class="stat">
                  <span class="stat-label">ATK</span>
                  <span class="stat-value">${state.player.stats.attack || 10}</span>
                </div>
                <div class="stat">
                  <span class="stat-label">DEF</span>
                  <span class="stat-value">${state.player.stats.defense || 5}</span>
                </div>
                <div class="stat">
                  <span class="stat-label">MAG</span>
                  <span class="stat-value">${state.player.stats.magic || 0}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Enemy Side -->
          <div class="battle-side enemy-side">
            <div class="character-card enemy-card">
              <div class="character-name">${state.enemy.name}</div>
              <div class="health-bar">
                <div class="health-fill enemy" style="width: ${(state.enemy.hp / state.enemy.maxHp) * 100}%"></div>
              </div>
              <div class="health-text">${state.enemy.hp}/${state.enemy.maxHp} HP</div>
              <div class="stats-grid">
                <div class="stat">
                  <span class="stat-label">ATK</span>
                  <span class="stat-value">${state.enemy.stats.attack || 10}</span>
                </div>
                <div class="stat">
                  <span class="stat-label">DEF</span>
                  <span class="stat-value">${state.enemy.stats.defense || 5}</span>
                </div>
                <div class="stat">
                  <span class="stat-label">MAG</span>
                  <span class="stat-value">${state.enemy.stats.magic || 0}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Battle Log -->
        <div class="battle-log">
          ${this.renderBattleLog(state.log)}
        </div>

        <!-- Actions -->
        <div class="battle-actions">
          ${this.renderActions(state)}
        </div>

        ${state.isOver ? `
          <div class="battle-result">
            ${state.winner === 'player' 
              ? `<div class="result-victory">🎉 Victory!</div>` 
              : `<div class="result-defeat">💀 Defeat!</div>`}
          </div>
        ` : ''}
      </div>
    `;

    // Attach event listeners
    this.attachEventListeners(state);
  }

  /**
   * Render battle log entries
   */
  renderBattleLog(log) {
    if (log.length === 0) {
      return '<p class="log-message">Battle start! Choose your action.</p>';
    }

    return log
      .slice(-5) // Show last 5 messages
      .map(entry => `<p class="log-message">${entry.message}</p>`)
      .join('');
  }

  /**
   * Render action buttons based on current state
   */
  renderActions(state) {
    if (state.isOver) {
      return `
        <button class="btn btn-primary" id="endBattle">Continue</button>
      `;
    }

    if (state.currentActor !== 'player') {
      return `
        <div class="action-waiting">
          <p>${state.enemy.name} is taking action...</p>
          <button class="btn btn-secondary" id="nextAction">Next Turn</button>
        </div>
      `;
    }

    return `
      <div class="action-buttons">
        <button class="btn btn-primary action-btn" data-action="attack" title="Physical attack">
          ⚔️ Attack
        </button>
        <button class="btn btn-primary action-btn" data-action="skill" title="Magic skill">
          ✨ Skill
        </button>
        <button class="btn btn-secondary action-btn" data-action="defend" title="Reduce damage">
          🛡️ Defend
        </button>
        <button class="btn btn-secondary action-btn" data-action="dodge" title="Evade next attack">
          🏃 Dodge
        </button>
      </div>
    `;
  }

  /**
   * Attach event listeners to action buttons
   */
  attachEventListeners(state) {
    // Action buttons for player
    document.querySelectorAll('.action-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const action = btn.dataset.action;
        this.game.handlePlayerAction(action);
      });
    });

    // Next turn button
    const nextBtn = document.getElementById('nextAction');
    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        this.game.processTurn();
      });
    }

    // End battle button
    const endBtn = document.getElementById('endBattle');
    if (endBtn) {
      endBtn.addEventListener('click', () => {
        this.game.endBattle();
      });
    }
  }

  /**
   * Update battle display after action
   */
  update() {
    this.render();
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = BattleUI;
}
