/**
 * Utility Functions - Helper functions for math, collision, and rendering
 */

class Utils {
  /**
   * Calculate distance between two points
   * @param {number} x1 - First x coordinate
   * @param {number} y1 - First y coordinate
   * @param {number} x2 - Second x coordinate
   * @param {number} y2 - Second y coordinate
   * @returns {number} Distance between points
   */
  static distance(x1, y1, x2, y2) {
    const dx = x2 - x1;
    const dy = y2 - y1;
    return Math.sqrt(dx * dx + dy * dy);
  }

  /**
   * Check if two circles collide
   * @param {number} x1 - First circle x
   * @param {number} y1 - First circle y
   * @param {number} r1 - First circle radius
   * @param {number} x2 - Second circle x
   * @param {number} y2 - Second circle y
   * @param {number} r2 - Second circle radius
   * @returns {boolean} Whether circles collide
   */
  static circleCollide(x1, y1, r1, x2, y2, r2) {
    return this.distance(x1, y1, x2, y2) < r1 + r2;
  }

  /**
   * Check if two rectangles collide (AABB)
   * @param {Rect} rect1 - First rectangle
   * @param {Rect} rect2 - Second rectangle
   * @returns {boolean} Whether rectangles collide
   */
  static rectCollide(rect1, rect2) {
    return rect1.x < rect2.x + rect2.width &&
           rect1.x + rect1.width > rect2.x &&
           rect1.y < rect2.y + rect2.height &&
           rect1.y + rect1.height > rect2.y;
  }

  /**
   * Calculate damage with stat scaling
   * @param {number} baseDamage - Base damage value
   * @param {number} attackStat - Attacker's attack stat
   * @param {number} defenseStat - Defender's defense stat
   * @returns {number} Calculated damage
   */
  static calculateDamage(baseDamage, attackStat, defenseStat) {
    const damageMultiplier = 1 + (attackStat / 100);
    const baseCalculated = baseDamage * damageMultiplier;
    const defenseReduction = defenseStat / (defenseStat + 50);
    return Math.max(1, Math.floor(baseCalculated * (1 - defenseReduction * 0.5)));
  }

  /**
   * Clamp a value between min and max
   * @param {number} value - Value to clamp
   * @param {number} min - Minimum value
   * @param {number} max - Maximum value
   * @returns {number} Clamped value
   */
  static clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  /**
   * Get random integer between min and max (inclusive)
   * @param {number} min - Minimum value
   * @param {number} max - Maximum value
   * @returns {number} Random integer
   */
  static randInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  }

  /**
   * Get random float between min and max
   * @param {number} min - Minimum value
   * @param {number} max - Maximum value
   * @returns {number} Random float
   */
  static randFloat(min, max) {
    return Math.random() * (max - min) + min;
  }

  /**
   * Get random element from array
   * @param {Array} array - Array to choose from
   * @returns {*} Random element
   */
  static choice(array) {
    return array[Math.floor(Math.random() * array.length)];
  }

  /**
   * Shuffle an array (Fisher-Yates)
   * @param {Array} array - Array to shuffle
   * @returns {Array} Shuffled array
   */
  static shuffle(array) {
    const arr = [...array];
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }

  /**
   * Interpolate between two values
   * @param {number} start - Start value
   * @param {number} end - End value
   * @param {number} t - Interpolation factor (0-1)
   * @returns {number} Interpolated value
   */
  static lerp(start, end, t) {
    return start + (end - start) * t;
  }

  /**
   * Easy-out animation function
   * @param {number} t - Time factor (0-1)
   * @returns {number} Eased value
   */
  static easeOutQuad(t) {
    return 1 - (1 - t) * (1 - t);
  }

  /**
   * Perlin-like noise for terrain (seeded randomness)
   * @param {number} x - X coordinate
   * @param {number} y - Y coordinate
   * @param {number} seed - Seed value
   * @returns {number} Noise value (0-1)
   */
  static noise(x, y, seed = 0) {
    const n = Math.sin(x * 12.9898 + y * 78.233 + seed) * 43758.5453;
    return n - Math.floor(n);
  }

  /**
   * Smoother noise blending
   * @param {number} x - X coordinate
   * @param {number} y - Y coordinate
   * @param {number} scale - Noise scale
   * @returns {number} Smoothed noise value
   */
  static smoothNoise(x, y, scale = 10) {
    const xi = Math.floor(x / scale);
    const yi = Math.floor(y / scale);
    const xf = (x % scale) / scale;
    const yf = (y % scale) / scale;

    const n00 = this.noise(xi, yi);
    const n10 = this.noise(xi + 1, yi);
    const n01 = this.noise(xi, yi + 1);
    const n11 = this.noise(xi + 1, yi + 1);

    const u = this.easeOutQuad(xf);
    const v = this.easeOutQuad(yf);

    const nx0 = this.lerp(n00, n10, u);
    const nx1 = this.lerp(n01, n11, u);
    return this.lerp(nx0, nx1, v);
  }

  /**
   * Draw circle with anti-aliasing effect
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} x - Center x
   * @param {number} y - Center y
   * @param {number} radius - Circle radius
   * @param {string} color - Fill color
   * @param {boolean} stroke - Whether to stroke
   */
  static drawCircle(ctx, x, y, radius, color, stroke = false) {
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.arc(x, y, radius, 0, Math.PI * 2);
    ctx.fill();
    if (stroke) {
      ctx.stroke();
    }
  }

  /**
   * Draw rotated rectangle
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} x - Center x
   * @param {number} y - Center y
   * @param {number} width - Width
   * @param {number} height - Height
   * @param {number} angle - Rotation angle in radians
   * @param {string} color - Fill color
   */
  static drawRotatedRect(ctx, x, y, width, height, angle, color) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.fillStyle = color;
    ctx.fillRect(-width / 2, -height / 2, width, height);
    ctx.restore();
  }

  /**
   * Get angle between two points
   * @param {number} x1 - First x
   * @param {number} y1 - First y
   * @param {number} x2 - Second x
   * @param {number} y2 - Second y
   * @returns {number} Angle in radians
   */
  static getAngle(x1, y1, x2, y2) {
    return Math.atan2(y2 - y1, x2 - x1);
  }

  /**
   * Get velocity vector from angle and speed
   * @param {number} angle - Angle in radians
   * @param {number} speed - Speed magnitude
   * @returns {Object} Object with vx and vy
   */
  static getVelocity(angle, speed) {
    return {
      vx: Math.cos(angle) * speed,
      vy: Math.sin(angle) * speed
    };
  }
}
