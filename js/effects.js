/**
 * WYATT XXX COLE - COUNTRY BOY EFFECTS
 * Subtle particles, warm effects, and visual enhancements
 */

document.addEventListener('DOMContentLoaded', function() {
    initParticles();
    initScanlines();
    initCursor();
    initGlitchText();
    initNeonFlicker();
    initParallax();
    initTypewriter();
    initMatrixRain();
});

/**
 * Floating Particle System
 */
function initParticles() {
    const canvas = document.createElement('canvas');
    canvas.id = 'particles';
    canvas.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        opacity: 0.6;
    `;
    document.body.insertBefore(canvas, document.body.firstChild);

    const ctx = canvas.getContext('2d');
    let particles = [];
    const particleCount = 80;

    // Colors from country brand palette - warm and earthy
    const colors = ['#C68E3F', '#A44A2A', '#DAA54D', '#5C4033'];

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    class Particle {
        constructor() {
            this.reset();
        }

        reset() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 2 + 0.5;
            this.speedX = (Math.random() - 0.5) * 0.5;
            this.speedY = (Math.random() - 0.5) * 0.5;
            this.color = colors[Math.floor(Math.random() * colors.length)];
            this.alpha = Math.random() * 0.5 + 0.2;
            this.pulse = Math.random() * Math.PI * 2;
            this.pulseSpeed = Math.random() * 0.02 + 0.01;
        }

        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            this.pulse += this.pulseSpeed;
            this.alpha = 0.3 + Math.sin(this.pulse) * 0.2;

            if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
            if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
        }

        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.globalAlpha = this.alpha;
            ctx.fill();

            // Glow effect
            ctx.shadowBlur = 15;
            ctx.shadowColor = this.color;
            ctx.fill();
            ctx.shadowBlur = 0;
            ctx.globalAlpha = 1;
        }
    }

    // Connect nearby particles with lines
    function connectParticles() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 150) {
                    ctx.beginPath();
                    ctx.strokeStyle = particles[i].color;
                    ctx.globalAlpha = (150 - distance) / 150 * 0.15;
                    ctx.lineWidth = 0.5;
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                    ctx.globalAlpha = 1;
                }
            }
        }
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        particles.forEach(p => {
            p.update();
            p.draw();
        });

        connectParticles();
        requestAnimationFrame(animate);
    }

    // Initialize
    resize();
    for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
    }
    animate();

    window.addEventListener('resize', resize);
}

/**
 * CRT Scanlines Effect
 */
function initScanlines() {
    const scanlines = document.createElement('div');
    scanlines.className = 'scanlines';
    scanlines.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 9998;
        background: repeating-linear-gradient(
            0deg,
            rgba(0, 0, 0, 0.1) 0px,
            rgba(0, 0, 0, 0.1) 1px,
            transparent 1px,
            transparent 2px
        );
        opacity: 0.3;
    `;
    document.body.appendChild(scanlines);
}

/**
 * Custom Neon Cursor
 */
function initCursor() {
    const cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    cursor.innerHTML = `
        <div class="cursor-dot"></div>
        <div class="cursor-ring"></div>
    `;

    const style = document.createElement('style');
    style.textContent = `
        .custom-cursor {
            position: fixed;
            pointer-events: none;
            z-index: 99999;
            mix-blend-mode: difference;
        }
        .cursor-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            background: #A44A2A;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: transform 0.1s ease;
        }
        .cursor-ring {
            position: absolute;
            width: 40px;
            height: 40px;
            border: 2px solid #C68E3F;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.15s ease;
            opacity: 0.5;
        }
        .cursor-hover .cursor-dot {
            transform: translate(-50%, -50%) scale(2);
            background: #C68E3F;
        }
        .cursor-hover .cursor-ring {
            transform: translate(-50%, -50%) scale(1.5);
            border-color: #A44A2A;
            opacity: 1;
        }
        @media (max-width: 768px) {
            .custom-cursor { display: none; }
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(cursor);

    let mouseX = 0, mouseY = 0;
    let cursorX = 0, cursorY = 0;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    // Hover effect on interactive elements
    document.querySelectorAll('a, button, .btn, .card, .gallery__item, .product').forEach(el => {
        el.addEventListener('mouseenter', () => cursor.classList.add('cursor-hover'));
        el.addEventListener('mouseleave', () => cursor.classList.remove('cursor-hover'));
    });

    function updateCursor() {
        cursorX += (mouseX - cursorX) * 0.15;
        cursorY += (mouseY - cursorY) * 0.15;
        cursor.style.left = cursorX + 'px';
        cursor.style.top = cursorY + 'px';
        requestAnimationFrame(updateCursor);
    }
    updateCursor();
}

/**
 * Glitch Text Effect
 */
function initGlitchText() {
    const glitchElements = document.querySelectorAll('.hero__title, .gradient-text');

    glitchElements.forEach(el => {
        el.setAttribute('data-text', el.textContent);
        el.classList.add('glitch-text');
    });

    const style = document.createElement('style');
    style.textContent = `
        .glitch-text {
            position: relative;
        }
        .glitch-text::before,
        .glitch-text::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.8;
        }
        .glitch-text::before {
            color: #A44A2A;
            animation: glitch-1 2s infinite linear alternate-reverse;
            clip-path: polygon(0 0, 100% 0, 100% 35%, 0 35%);
        }
        .glitch-text::after {
            color: #C68E3F;
            animation: glitch-2 3s infinite linear alternate-reverse;
            clip-path: polygon(0 65%, 100% 65%, 100% 100%, 0 100%);
        }
        @keyframes glitch-1 {
            0%, 90%, 100% { transform: translate(0); }
            92% { transform: translate(-3px, 1px); }
            94% { transform: translate(3px, -1px); }
            96% { transform: translate(-2px, 2px); }
            98% { transform: translate(2px, -2px); }
        }
        @keyframes glitch-2 {
            0%, 90%, 100% { transform: translate(0); }
            91% { transform: translate(2px, -1px); }
            93% { transform: translate(-2px, 1px); }
            95% { transform: translate(1px, -2px); }
            97% { transform: translate(-1px, 2px); }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Neon Flicker Effect
 */
function initNeonFlicker() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes warm-glow {
            0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% {
                text-shadow:
                    0 0 4px #fff,
                    0 0 11px #fff,
                    0 0 19px #fff,
                    0 0 40px var(--whiskey),
                    0 0 80px var(--whiskey),
                    0 0 90px var(--whiskey),
                    0 0 100px var(--whiskey),
                    0 0 150px var(--whiskey);
            }
            20%, 24%, 55% {
                text-shadow: none;
            }
        }
        .warm-glow {
            animation: warm-glow 1.5s infinite alternate;
        }
        .nav__logo {
            animation: warm-glow 3s infinite alternate;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Parallax Scroll Effect
 */
function initParallax() {
    const parallaxElements = document.querySelectorAll('.hero__background');

    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;

        parallaxElements.forEach(el => {
            const speed = 0.5;
            el.style.transform = `translateY(${scrolled * speed}px)`;
        });
    });
}

/**
 * Typewriter Effect for Taglines
 */
function initTypewriter() {
    const taglines = document.querySelectorAll('.hero__tagline');

    taglines.forEach(el => {
        const text = el.textContent;
        el.textContent = '';
        el.style.borderRight = '2px solid var(--rust)';

        let i = 0;
        function type() {
            if (i < text.length) {
                el.textContent += text.charAt(i);
                i++;
                setTimeout(type, 50);
            } else {
                el.style.borderRight = 'none';
            }
        }

        // Start typewriter when element is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(type, 500);
                    observer.unobserve(el);
                }
            });
        });
        observer.observe(el);
    });
}

/**
 * Matrix Rain Background (for hero)
 */
function initMatrixRain() {
    const hero = document.querySelector('.hero');
    if (!hero) return;

    const canvas = document.createElement('canvas');
    canvas.className = 'matrix-rain';
    canvas.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        opacity: 0.1;
    `;
    hero.appendChild(canvas);

    const ctx = canvas.getContext('2d');

    function resize() {
        canvas.width = hero.offsetWidth;
        canvas.height = hero.offsetHeight;
    }
    resize();

    const chars = 'WXXXC01アイウエオカキクケコサシスセソタチツテトナニヌネノ';
    const fontSize = 14;
    const columns = canvas.width / fontSize;
    const drops = Array(Math.floor(columns)).fill(1);

    function draw() {
        ctx.fillStyle = 'rgba(5, 6, 10, 0.05)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.fillStyle = '#C68E3F';
        ctx.font = fontSize + 'px monospace';

        for (let i = 0; i < drops.length; i++) {
            const text = chars[Math.floor(Math.random() * chars.length)];
            ctx.fillText(text, i * fontSize, drops[i] * fontSize);

            if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                drops[i] = 0;
            }
            drops[i]++;
        }
    }

    setInterval(draw, 50);
    window.addEventListener('resize', resize);
}

/**
 * Magnetic Button Effect
 */
document.querySelectorAll('.btn--primary').forEach(btn => {
    btn.addEventListener('mousemove', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;

        this.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
    });

    btn.addEventListener('mouseleave', function() {
        this.style.transform = 'translate(0, 0)';
    });
});

/**
 * Card Tilt Effect
 */
document.querySelectorAll('.card, .product').forEach(card => {
    card.addEventListener('mousemove', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = (y - centerY) / 20;
        const rotateY = (centerX - x) / 20;

        this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
    });

    card.addEventListener('mouseleave', function() {
        this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
    });
});

/**
 * Sound Effects (optional - user must interact first)
 */
let audioContext;
function initAudio() {
    if (audioContext) return;
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
}

function playHoverSound() {
    if (!audioContext) return;

    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(1200, audioContext.currentTime + 0.05);

    gainNode.gain.setValueAtTime(0.05, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.05);

    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.05);
}

// Enable audio on first click
document.addEventListener('click', initAudio, { once: true });

/**
 * Scroll Progress Bar
 */
const progressBar = document.createElement('div');
progressBar.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, #C68E3F, #A44A2A, #DAA54D);
    z-index: 10001;
    transition: width 0.1s ease;
    box-shadow: 0 0 10px #C68E3F, 0 0 20px #A44A2A;
`;
document.body.appendChild(progressBar);

window.addEventListener('scroll', () => {
    const scrollTop = window.pageYOffset;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const progress = (scrollTop / docHeight) * 100;
    progressBar.style.width = progress + '%';
});

console.log('🤠 WXXXC Effects Loaded | Bad Boy of the South');
