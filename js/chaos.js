/**
 * WXXXC CHAOS ENGINE
 * MAXIMUM CYBERPUNK INSANITY - NO LIMITS
 * ═══════════════════════════════════════════════════════════════════
 */

(function() {
    'use strict';

    const CONFIG = {
        particleCount: 200,
        lightningInterval: 3000,
        glitchIntensity: 1.5,
        chromaticStrength: 3,
        scanlineOpacity: 0.15,
        neonPulseSpeed: 2,
        matrixDensity: 1.5,
        explosionParticles: 50
    };

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ CYBER LIGHTNING STORM                                             ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class LightningStorm {
        constructor() {
            this.canvas = document.createElement('canvas');
            this.canvas.className = 'lightning-canvas';
            this.canvas.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 9995;
                opacity: 0.8;
            `;
            document.body.appendChild(this.canvas);
            this.ctx = this.canvas.getContext('2d');
            this.resize();
            this.strike();
            window.addEventListener('resize', () => this.resize());
        }

        resize() {
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
        }

        strike() {
            setInterval(() => {
                if (Math.random() > 0.7) {
                    this.drawLightning();
                }
            }, CONFIG.lightningInterval);
        }

        drawLightning() {
            const ctx = this.ctx;
            const startX = Math.random() * this.canvas.width;
            let x = startX;
            let y = 0;

            ctx.strokeStyle = Math.random() > 0.5 ? '#00FAFF' : '#9B1DFF';
            ctx.lineWidth = 2;
            ctx.shadowBlur = 20;
            ctx.shadowColor = ctx.strokeStyle;

            ctx.beginPath();
            ctx.moveTo(x, y);

            while (y < this.canvas.height) {
                x += (Math.random() - 0.5) * 100;
                y += Math.random() * 50 + 20;
                ctx.lineTo(x, y);

                // Branch
                if (Math.random() > 0.8) {
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(x, y);
                    this.drawBranch(ctx, x, y, Math.random() > 0.5 ? 1 : -1);
                    ctx.beginPath();
                    ctx.moveTo(x, y);
                }
            }
            ctx.stroke();

            // Flash effect
            document.body.style.filter = 'brightness(1.5)';
            setTimeout(() => {
                document.body.style.filter = '';
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            }, 100);
        }

        drawBranch(ctx, x, y, direction) {
            let bx = x;
            let by = y;
            const length = Math.random() * 100 + 50;

            ctx.beginPath();
            ctx.moveTo(x, y);

            for (let i = 0; i < 5; i++) {
                bx += direction * (Math.random() * 30 + 10);
                by += Math.random() * 30 + 10;
                ctx.lineTo(bx, by);
                if (by - y > length) break;
            }
            ctx.stroke();
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ HOLOGRAPHIC GLITCH OVERLAY                                        ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class HolographicGlitch {
        constructor() {
            this.overlay = document.createElement('div');
            this.overlay.className = 'holo-glitch';
            this.overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 9994;
                mix-blend-mode: screen;
                opacity: 0;
                transition: opacity 0.1s;
            `;
            document.body.appendChild(this.overlay);
            this.glitch();
        }

        glitch() {
            setInterval(() => {
                if (Math.random() > 0.95) {
                    this.triggerGlitch();
                }
            }, 100);
        }

        triggerGlitch() {
            const intensity = Math.random() * CONFIG.glitchIntensity;

            this.overlay.style.opacity = '0.3';
            this.overlay.style.background = `
                repeating-linear-gradient(
                    ${Math.random() * 180}deg,
                    transparent,
                    rgba(155, 29, 255, ${intensity * 0.3}) ${Math.random() * 2}px,
                    transparent ${Math.random() * 4}px
                ),
                repeating-linear-gradient(
                    ${Math.random() * 180}deg,
                    transparent,
                    rgba(0, 250, 255, ${intensity * 0.3}) ${Math.random() * 2}px,
                    transparent ${Math.random() * 4}px
                )
            `;

            // Random displacement
            document.body.style.transform = `translate(${(Math.random() - 0.5) * 5}px, ${(Math.random() - 0.5) * 5}px)`;

            setTimeout(() => {
                this.overlay.style.opacity = '0';
                document.body.style.transform = '';
            }, 50 + Math.random() * 100);
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ NEON PLASMA FIELD                                                 ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class PlasmaField {
        constructor() {
            this.canvas = document.createElement('canvas');
            this.canvas.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 0;
                opacity: 0.15;
            `;
            document.body.insertBefore(this.canvas, document.body.firstChild);
            this.ctx = this.canvas.getContext('2d');
            this.time = 0;
            this.resize();
            this.animate();
            window.addEventListener('resize', () => this.resize());
        }

        resize() {
            this.canvas.width = window.innerWidth / 4;
            this.canvas.height = window.innerHeight / 4;
            this.canvas.style.imageRendering = 'pixelated';
        }

        animate() {
            const ctx = this.ctx;
            const w = this.canvas.width;
            const h = this.canvas.height;
            const imageData = ctx.createImageData(w, h);
            const data = imageData.data;

            this.time += 0.02;

            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const i = (y * w + x) * 4;

                    const v1 = Math.sin(x * 0.05 + this.time);
                    const v2 = Math.sin((y * 0.05 + this.time) * 0.5);
                    const v3 = Math.sin((x * 0.05 + y * 0.05 + this.time) * 0.5);
                    const v4 = Math.sin(Math.sqrt(x * x + y * y) * 0.05 - this.time);

                    const v = (v1 + v2 + v3 + v4) / 4;

                    // Cyberpunk colors
                    data[i] = Math.floor((Math.sin(v * Math.PI) * 0.5 + 0.5) * 155); // R
                    data[i + 1] = Math.floor((Math.sin(v * Math.PI + 2) * 0.5 + 0.5) * 50); // G
                    data[i + 2] = Math.floor((Math.sin(v * Math.PI + 4) * 0.5 + 0.5) * 255); // B
                    data[i + 3] = 255; // A
                }
            }

            ctx.putImageData(imageData, 0, 0);
            requestAnimationFrame(() => this.animate());
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ CYBER RAIN (ADVANCED MATRIX)                                      ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class CyberRain {
        constructor() {
            this.canvas = document.createElement('canvas');
            this.canvas.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 1;
                opacity: 0.4;
            `;
            document.body.insertBefore(this.canvas, document.body.firstChild);
            this.ctx = this.canvas.getContext('2d');

            this.chars = 'WXXXC01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン!@#$%^&*()ネオンサイバー反逆者'.split('');
            this.fontSize = 14;
            this.drops = [];

            this.resize();
            this.animate();
            window.addEventListener('resize', () => this.resize());
        }

        resize() {
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
            this.columns = Math.floor(this.canvas.width / this.fontSize);
            this.drops = Array(this.columns).fill(0).map(() => Math.random() * -100);
        }

        animate() {
            const ctx = this.ctx;

            ctx.fillStyle = 'rgba(5, 6, 10, 0.05)';
            ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

            for (let i = 0; i < this.drops.length; i++) {
                const char = this.chars[Math.floor(Math.random() * this.chars.length)];
                const x = i * this.fontSize;
                const y = this.drops[i] * this.fontSize;

                // Gradient color based on position
                const gradient = ctx.createLinearGradient(x, y - 100, x, y);
                gradient.addColorStop(0, 'rgba(155, 29, 255, 0)');
                gradient.addColorStop(0.5, 'rgba(155, 29, 255, 1)');
                gradient.addColorStop(1, 'rgba(0, 250, 255, 1)');

                ctx.fillStyle = gradient;
                ctx.font = `${this.fontSize}px monospace`;
                ctx.fillText(char, x, y);

                // Head glow
                ctx.shadowBlur = 10;
                ctx.shadowColor = '#00FAFF';
                ctx.fillText(char, x, y);
                ctx.shadowBlur = 0;

                if (y > this.canvas.height && Math.random() > 0.98) {
                    this.drops[i] = 0;
                }
                this.drops[i] += CONFIG.matrixDensity;
            }

            requestAnimationFrame(() => this.animate());
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ PARTICLE EXPLOSION ON CLICK                                       ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class ClickExplosion {
        constructor() {
            document.addEventListener('click', (e) => this.explode(e.clientX, e.clientY));
        }

        explode(x, y) {
            const colors = ['#9B1DFF', '#00FAFF', '#39FF14', '#FF1F3D', '#C0C4CC'];

            for (let i = 0; i < CONFIG.explosionParticles; i++) {
                const particle = document.createElement('div');
                const angle = (Math.PI * 2 * i) / CONFIG.explosionParticles;
                const velocity = 5 + Math.random() * 10;
                const size = 4 + Math.random() * 8;

                particle.style.cssText = `
                    position: fixed;
                    left: ${x}px;
                    top: ${y}px;
                    width: ${size}px;
                    height: ${size}px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 99999;
                    box-shadow: 0 0 ${size * 2}px currentColor;
                `;

                document.body.appendChild(particle);

                const vx = Math.cos(angle) * velocity;
                const vy = Math.sin(angle) * velocity;
                let px = x;
                let py = y;
                let opacity = 1;
                let scale = 1;

                const animate = () => {
                    px += vx;
                    py += vy + 2; // gravity
                    opacity -= 0.02;
                    scale -= 0.01;

                    particle.style.left = px + 'px';
                    particle.style.top = py + 'px';
                    particle.style.opacity = opacity;
                    particle.style.transform = `scale(${scale})`;

                    if (opacity > 0) {
                        requestAnimationFrame(animate);
                    } else {
                        particle.remove();
                    }
                };

                requestAnimationFrame(animate);
            }
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ CYBERPUNK HUD OVERLAY                                             ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class CyberHUD {
        constructor() {
            this.hud = document.createElement('div');
            this.hud.className = 'cyber-hud';
            this.hud.innerHTML = `
                <div class="hud-corner hud-tl">
                    <div class="hud-bracket"></div>
                    <div class="hud-text">SYS//ONLINE</div>
                </div>
                <div class="hud-corner hud-tr">
                    <div class="hud-bracket"></div>
                    <div class="hud-text" id="hud-time">00:00:00</div>
                </div>
                <div class="hud-corner hud-bl">
                    <div class="hud-bracket"></div>
                    <div class="hud-text">WXXXC.NET</div>
                </div>
                <div class="hud-corner hud-br">
                    <div class="hud-bracket"></div>
                    <div class="hud-text" id="hud-coords">X:0 Y:0</div>
                </div>
                <div class="hud-center">
                    <svg class="hud-reticle" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="40" fill="none" stroke="#9B1DFF" stroke-width="0.5" stroke-dasharray="5,5"/>
                        <circle cx="50" cy="50" r="30" fill="none" stroke="#00FAFF" stroke-width="0.5"/>
                        <line x1="50" y1="10" x2="50" y2="25" stroke="#9B1DFF" stroke-width="1"/>
                        <line x1="50" y1="75" x2="50" y2="90" stroke="#9B1DFF" stroke-width="1"/>
                        <line x1="10" y1="50" x2="25" y2="50" stroke="#9B1DFF" stroke-width="1"/>
                        <line x1="75" y1="50" x2="90" y2="50" stroke="#9B1DFF" stroke-width="1"/>
                    </svg>
                </div>
            `;

            const style = document.createElement('style');
            style.textContent = `
                .cyber-hud {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: 9990;
                    font-family: 'Orbitron', monospace;
                }
                .hud-corner {
                    position: absolute;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .hud-tl { top: 100px; left: 20px; }
                .hud-tr { top: 100px; right: 20px; flex-direction: row-reverse; }
                .hud-bl { bottom: 20px; left: 20px; }
                .hud-br { bottom: 20px; right: 20px; flex-direction: row-reverse; }
                .hud-bracket {
                    width: 20px;
                    height: 20px;
                    border: 1px solid #9B1DFF;
                    opacity: 0.5;
                }
                .hud-tl .hud-bracket { border-right: none; border-bottom: none; }
                .hud-tr .hud-bracket { border-left: none; border-bottom: none; }
                .hud-bl .hud-bracket { border-right: none; border-top: none; }
                .hud-br .hud-bracket { border-left: none; border-top: none; }
                .hud-text {
                    font-size: 10px;
                    letter-spacing: 2px;
                    color: #9B1DFF;
                    text-shadow: 0 0 10px #9B1DFF;
                    opacity: 0.7;
                }
                .hud-center {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                .cyber-hud:hover .hud-center {
                    opacity: 0.3;
                }
                .hud-reticle {
                    width: 100px;
                    height: 100px;
                    animation: reticleRotate 10s linear infinite;
                }
                @keyframes reticleRotate {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                @media (max-width: 768px) {
                    .cyber-hud { display: none; }
                }
            `;
            document.head.appendChild(style);
            document.body.appendChild(this.hud);

            this.updateTime();
            this.trackMouse();
        }

        updateTime() {
            setInterval(() => {
                const now = new Date();
                document.getElementById('hud-time').textContent =
                    now.toLocaleTimeString('en-US', { hour12: false });
            }, 1000);
        }

        trackMouse() {
            document.addEventListener('mousemove', (e) => {
                document.getElementById('hud-coords').textContent =
                    `X:${e.clientX} Y:${e.clientY}`;
            });
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ BASS DROP EFFECT                                                  ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class BassDropEffect {
        constructor() {
            this.ring = document.createElement('div');
            this.ring.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                border: 3px solid #9B1DFF;
                border-radius: 50%;
                transform: translate(-50%, -50%);
                pointer-events: none;
                z-index: 9999;
                opacity: 0;
                box-shadow: 0 0 30px #9B1DFF, inset 0 0 30px #00FAFF;
            `;
            document.body.appendChild(this.ring);

            // Trigger on scroll past certain points
            let lastTrigger = 0;
            window.addEventListener('scroll', () => {
                const scrollY = window.scrollY;
                if (scrollY - lastTrigger > 500) {
                    this.drop();
                    lastTrigger = scrollY;
                }
            });
        }

        drop() {
            this.ring.style.width = '0';
            this.ring.style.height = '0';
            this.ring.style.opacity = '1';

            let size = 0;
            const maxSize = Math.max(window.innerWidth, window.innerHeight) * 2;

            const expand = () => {
                size += 50;
                this.ring.style.width = size + 'px';
                this.ring.style.height = size + 'px';
                this.ring.style.opacity = 1 - (size / maxSize);

                if (size < maxSize) {
                    requestAnimationFrame(expand);
                }
            };

            requestAnimationFrame(expand);
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ TRAIL EFFECT ON MOUSE                                             ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class MouseTrail {
        constructor() {
            this.points = [];
            this.maxPoints = 20;

            document.addEventListener('mousemove', (e) => {
                this.addPoint(e.clientX, e.clientY);
            });

            this.animate();
        }

        addPoint(x, y) {
            this.points.push({ x, y, life: 1 });
            if (this.points.length > this.maxPoints) {
                this.points.shift();
            }
        }

        animate() {
            // Update existing trails
            document.querySelectorAll('.mouse-trail').forEach(el => el.remove());

            this.points.forEach((point, i) => {
                point.life -= 0.05;
                if (point.life > 0) {
                    const trail = document.createElement('div');
                    trail.className = 'mouse-trail';
                    const size = 10 * point.life;
                    trail.style.cssText = `
                        position: fixed;
                        left: ${point.x - size/2}px;
                        top: ${point.y - size/2}px;
                        width: ${size}px;
                        height: ${size}px;
                        background: radial-gradient(circle, #00FAFF ${point.life * 50}%, transparent);
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 9998;
                        opacity: ${point.life};
                    `;
                    document.body.appendChild(trail);
                }
            });

            this.points = this.points.filter(p => p.life > 0);
            requestAnimationFrame(() => this.animate());
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ TEXT SCRAMBLE EFFECT                                              ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class TextScramble {
        constructor() {
            this.chars = '!<>-_\\/[]{}—=+*^?#アイウエオ';
            this.initAll();
        }

        initAll() {
            document.querySelectorAll('h1, h2, .nav__logo, .hero__subtitle').forEach(el => {
                el.addEventListener('mouseenter', () => this.scramble(el));
            });
        }

        scramble(el) {
            const original = el.getAttribute('data-original') || el.textContent;
            el.setAttribute('data-original', original);

            let iteration = 0;
            const interval = setInterval(() => {
                el.textContent = original
                    .split('')
                    .map((char, i) => {
                        if (i < iteration) return original[i];
                        return this.chars[Math.floor(Math.random() * this.chars.length)];
                    })
                    .join('');

                iteration += 1/3;
                if (iteration >= original.length) {
                    clearInterval(interval);
                    el.textContent = original;
                }
            }, 30);
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ PERSPECTIVE TILT ON SCROLL                                        ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    class PerspectiveTilt {
        constructor() {
            window.addEventListener('scroll', () => {
                const scrollY = window.scrollY;
                const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                const progress = scrollY / maxScroll;

                document.querySelectorAll('.section').forEach((section, i) => {
                    const rect = section.getBoundingClientRect();
                    const centerY = rect.top + rect.height / 2;
                    const viewportCenter = window.innerHeight / 2;
                    const offset = (centerY - viewportCenter) / window.innerHeight;

                    section.style.transform = `perspective(1000px) rotateX(${offset * 2}deg)`;
                });
            }, { passive: true });
        }
    }

    // ╔═══════════════════════════════════════════════════════════════════╗
    // ║ INITIALIZE ALL CHAOS                                              ║
    // ╚═══════════════════════════════════════════════════════════════════╝
    document.addEventListener('DOMContentLoaded', () => {
        console.log(`
╔═══════════════════════════════════════════════════════════════════╗
║                                                                    ║
║   ██╗    ██╗██╗  ██╗██╗  ██╗██╗  ██╗ ██████╗                      ║
║   ██║    ██║╚██╗██╔╝╚██╗██╔╝╚██╗██╔╝██╔════╝                      ║
║   ██║ █╗ ██║ ╚███╔╝  ╚███╔╝  ╚███╔╝ ██║                           ║
║   ██║███╗██║ ██╔██╗  ██╔██╗  ██╔██╗ ██║                           ║
║   ╚███╔███╔╝██╔╝ ██╗██╔╝ ██╗██╔╝ ██╗╚██████╗                      ║
║    ╚══╝╚══╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝                      ║
║                                                                    ║
║   CHAOS ENGINE ACTIVATED                                           ║
║   NEON CYBER REBEL // THE VILLAIN EVERYONE WANTS                   ║
║                                                                    ║
╚═══════════════════════════════════════════════════════════════════╝
        `);

        // Initialize all chaos systems
        new PlasmaField();
        new CyberRain();
        new LightningStorm();
        new HolographicGlitch();
        new ClickExplosion();
        new CyberHUD();
        new BassDropEffect();
        new MouseTrail();
        new TextScramble();
        new PerspectiveTilt();

        // Add scanlines
        const scanlines = document.createElement('div');
        scanlines.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9996;
            background: repeating-linear-gradient(
                0deg,
                rgba(0, 0, 0, 0.1) 0px,
                rgba(0, 0, 0, 0.1) 1px,
                transparent 1px,
                transparent 2px
            );
            opacity: ${CONFIG.scanlineOpacity};
        `;
        document.body.appendChild(scanlines);

        // CRT curve effect
        document.body.style.borderRadius = '10px';
        document.body.style.boxShadow = 'inset 0 0 100px rgba(0,0,0,0.5)';

        console.log('⚡ CHAOS ENGINE: ALL SYSTEMS NOMINAL ⚡');
    });

    // Expose globally
    window.CHAOS = { CONFIG };

})();
