/**
 * WXXXC TURBO - Performance & Advanced Effects
 * WebGL, Audio Reactive, Preloading, and Speed Optimizations
 */

(function() {
    'use strict';

    // ============================================
    // SERVICE WORKER REGISTRATION
    // ============================================
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('⚡ Service Worker registered'))
                .catch(err => console.log('SW registration failed:', err));
        });
    }

    // ============================================
    // PRELOAD CRITICAL RESOURCES
    // ============================================
    const preloadResources = () => {
        const pages = ['studios.html', 'portfolio.html', 'schedule.html', 'community.html', 'shop.html', 'contact.html', 'links.html'];

        // Preload on hover intent
        document.querySelectorAll('a[href]').forEach(link => {
            let preloaded = false;

            link.addEventListener('mouseenter', () => {
                if (preloaded) return;
                const href = link.getAttribute('href');
                if (href && href.endsWith('.html') && !href.startsWith('http')) {
                    const preload = document.createElement('link');
                    preload.rel = 'prefetch';
                    preload.href = href;
                    document.head.appendChild(preload);
                    preloaded = true;
                }
            }, { passive: true });
        });
    };

    // ============================================
    // WEBGL DISTORTION EFFECT
    // ============================================
    class WebGLDistortion {
        constructor(container) {
            this.container = container;
            this.canvas = document.createElement('canvas');
            this.canvas.className = 'webgl-canvas';
            this.canvas.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 0;
                pointer-events: none;
            `;

            this.gl = this.canvas.getContext('webgl') || this.canvas.getContext('experimental-webgl');
            if (!this.gl) return;

            container.style.position = 'relative';
            container.insertBefore(this.canvas, container.firstChild);

            this.mouse = { x: 0.5, y: 0.5 };
            this.time = 0;

            this.init();
            this.animate();
            this.addEventListeners();
        }

        init() {
            const gl = this.gl;

            // Vertex shader
            const vertexShader = gl.createShader(gl.VERTEX_SHADER);
            gl.shaderSource(vertexShader, `
                attribute vec2 position;
                varying vec2 vUv;
                void main() {
                    vUv = position * 0.5 + 0.5;
                    gl_Position = vec4(position, 0.0, 1.0);
                }
            `);
            gl.compileShader(vertexShader);

            // Fragment shader - Cyberpunk distortion
            const fragmentShader = gl.createShader(gl.FRAGMENT_SHADER);
            gl.shaderSource(fragmentShader, `
                precision mediump float;
                varying vec2 vUv;
                uniform float time;
                uniform vec2 mouse;
                uniform vec2 resolution;

                vec3 neonPurple = vec3(0.608, 0.114, 1.0);
                vec3 neonCyan = vec3(0.0, 0.98, 1.0);
                vec3 neonGreen = vec3(0.224, 1.0, 0.078);

                float noise(vec2 p) {
                    return fract(sin(dot(p, vec2(12.9898, 78.233))) * 43758.5453);
                }

                void main() {
                    vec2 uv = vUv;
                    vec2 center = mouse;

                    // Distance from mouse
                    float dist = distance(uv, center);

                    // Ripple effect
                    float ripple = sin(dist * 30.0 - time * 3.0) * 0.02;
                    ripple *= smoothstep(0.5, 0.0, dist);

                    // Distorted UV
                    vec2 distortedUv = uv + ripple;

                    // Animated gradient
                    float gradient = sin(distortedUv.x * 3.0 + time * 0.5) * 0.5 + 0.5;
                    gradient *= sin(distortedUv.y * 2.0 - time * 0.3) * 0.5 + 0.5;

                    // Color mixing
                    vec3 color = mix(neonPurple, neonCyan, gradient);
                    color = mix(color, neonGreen, sin(time * 0.2) * 0.3 + 0.2);

                    // Scanlines
                    float scanline = sin(uv.y * resolution.y * 0.5) * 0.04;
                    color -= scanline;

                    // Vignette
                    float vignette = 1.0 - smoothstep(0.3, 0.8, dist);
                    color *= vignette * 0.3;

                    // Noise grain
                    float grain = noise(uv * time) * 0.05;
                    color += grain;

                    gl_FragColor = vec4(color, 0.6);
                }
            `);
            gl.compileShader(fragmentShader);

            // Program
            this.program = gl.createProgram();
            gl.attachShader(this.program, vertexShader);
            gl.attachShader(this.program, fragmentShader);
            gl.linkProgram(this.program);
            gl.useProgram(this.program);

            // Geometry
            const vertices = new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]);
            const buffer = gl.createBuffer();
            gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
            gl.bufferData(gl.ARRAY_BUFFER, vertices, gl.STATIC_DRAW);

            const position = gl.getAttribLocation(this.program, 'position');
            gl.enableVertexAttribArray(position);
            gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);

            // Uniforms
            this.uniforms = {
                time: gl.getUniformLocation(this.program, 'time'),
                mouse: gl.getUniformLocation(this.program, 'mouse'),
                resolution: gl.getUniformLocation(this.program, 'resolution')
            };

            this.resize();
        }

        resize() {
            const rect = this.container.getBoundingClientRect();
            this.canvas.width = rect.width;
            this.canvas.height = rect.height;
            this.gl.viewport(0, 0, rect.width, rect.height);
            this.gl.uniform2f(this.uniforms.resolution, rect.width, rect.height);
        }

        animate() {
            if (!this.gl) return;

            this.time += 0.016;
            this.gl.uniform1f(this.uniforms.time, this.time);
            this.gl.uniform2f(this.uniforms.mouse, this.mouse.x, 1.0 - this.mouse.y);

            this.gl.drawArrays(this.gl.TRIANGLE_STRIP, 0, 4);

            requestAnimationFrame(() => this.animate());
        }

        addEventListeners() {
            this.container.addEventListener('mousemove', (e) => {
                const rect = this.container.getBoundingClientRect();
                this.mouse.x = (e.clientX - rect.left) / rect.width;
                this.mouse.y = (e.clientY - rect.top) / rect.height;
            }, { passive: true });

            window.addEventListener('resize', () => this.resize(), { passive: true });
        }
    }

    // ============================================
    // AUDIO REACTIVE VISUALIZER
    // ============================================
    class AudioVisualizer {
        constructor() {
            this.audioContext = null;
            this.analyser = null;
            this.dataArray = null;
            this.isActive = false;
            this.bars = [];

            this.createUI();
        }

        createUI() {
            // Create visualizer container
            this.container = document.createElement('div');
            this.container.className = 'audio-visualizer';
            this.container.style.cssText = `
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 60px;
                display: flex;
                align-items: flex-end;
                justify-content: center;
                gap: 2px;
                padding: 0 20px;
                pointer-events: none;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;

            // Create bars
            for (let i = 0; i < 64; i++) {
                const bar = document.createElement('div');
                bar.style.cssText = `
                    width: 4px;
                    height: 4px;
                    background: linear-gradient(to top, #9B1DFF, #00FAFF);
                    border-radius: 2px;
                    transition: height 0.05s ease;
                    box-shadow: 0 0 10px #9B1DFF;
                `;
                this.bars.push(bar);
                this.container.appendChild(bar);
            }

            document.body.appendChild(this.container);

            // Create toggle button
            this.toggleBtn = document.createElement('button');
            this.toggleBtn.innerHTML = '&#9835;';
            this.toggleBtn.className = 'audio-toggle';
            this.toggleBtn.style.cssText = `
                position: fixed;
                bottom: 80px;
                right: 20px;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: linear-gradient(135deg, #9B1DFF, #00FAFF);
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                z-index: 10000;
                box-shadow: 0 0 20px rgba(155, 29, 255, 0.5);
                transition: all 0.3s ease;
                display: none;
            `;
            this.toggleBtn.addEventListener('click', () => this.toggle());
            document.body.appendChild(this.toggleBtn);

            // Show button after user interaction
            document.addEventListener('click', () => {
                this.toggleBtn.style.display = 'flex';
                this.toggleBtn.style.alignItems = 'center';
                this.toggleBtn.style.justifyContent = 'center';
            }, { once: true });
        }

        async toggle() {
            if (this.isActive) {
                this.stop();
            } else {
                await this.start();
            }
        }

        async start() {
            try {
                // Get microphone access
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                this.analyser = this.audioContext.createAnalyser();
                this.analyser.fftSize = 128;

                const source = this.audioContext.createMediaStreamSource(stream);
                source.connect(this.analyser);

                this.dataArray = new Uint8Array(this.analyser.frequencyBinCount);
                this.isActive = true;

                this.container.style.opacity = '1';
                this.toggleBtn.style.background = 'linear-gradient(135deg, #39FF14, #00FAFF)';

                this.animate();
            } catch (err) {
                console.log('Audio access denied');
            }
        }

        stop() {
            this.isActive = false;
            this.container.style.opacity = '0';
            this.toggleBtn.style.background = 'linear-gradient(135deg, #9B1DFF, #00FAFF)';

            if (this.audioContext) {
                this.audioContext.close();
            }
        }

        animate() {
            if (!this.isActive) return;

            this.analyser.getByteFrequencyData(this.dataArray);

            this.bars.forEach((bar, i) => {
                const value = this.dataArray[i] || 0;
                const height = Math.max(4, (value / 255) * 60);
                bar.style.height = height + 'px';

                // Color based on intensity
                const hue = 280 - (value / 255) * 100;
                bar.style.background = `linear-gradient(to top, hsl(${hue}, 100%, 50%), #00FAFF)`;
            });

            requestAnimationFrame(() => this.animate());
        }
    }

    // ============================================
    // LOADING SCREEN
    // ============================================
    class LoadingScreen {
        constructor() {
            this.create();
        }

        create() {
            this.loader = document.createElement('div');
            this.loader.className = 'turbo-loader';
            this.loader.innerHTML = `
                <div class="loader-content">
                    <div class="loader-logo">WXXXC</div>
                    <div class="loader-bar">
                        <div class="loader-progress"></div>
                    </div>
                    <div class="loader-text">INITIALIZING</div>
                </div>
            `;
            this.loader.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: #05060A;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                transition: opacity 0.5s ease, visibility 0.5s ease;
            `;

            const style = document.createElement('style');
            style.textContent = `
                .loader-content {
                    text-align: center;
                }
                .loader-logo {
                    font-family: 'Orbitron', sans-serif;
                    font-size: 4rem;
                    font-weight: 900;
                    background: linear-gradient(135deg, #9B1DFF, #00FAFF);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    animation: loaderPulse 1s ease-in-out infinite;
                    margin-bottom: 2rem;
                }
                .loader-bar {
                    width: 200px;
                    height: 4px;
                    background: rgba(155, 29, 255, 0.2);
                    border-radius: 2px;
                    overflow: hidden;
                    margin: 0 auto 1rem;
                }
                .loader-progress {
                    width: 0%;
                    height: 100%;
                    background: linear-gradient(90deg, #9B1DFF, #00FAFF, #39FF14);
                    border-radius: 2px;
                    animation: loaderProgress 1.5s ease forwards;
                }
                .loader-text {
                    font-family: 'Orbitron', sans-serif;
                    font-size: 0.75rem;
                    letter-spacing: 0.3em;
                    color: #6B7280;
                    animation: loaderBlink 0.5s ease infinite;
                }
                @keyframes loaderPulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                }
                @keyframes loaderProgress {
                    0% { width: 0%; }
                    50% { width: 70%; }
                    100% { width: 100%; }
                }
                @keyframes loaderBlink {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.5; }
                }
            `;
            document.head.appendChild(style);

            document.body.insertBefore(this.loader, document.body.firstChild);

            // Hide after load
            window.addEventListener('load', () => {
                setTimeout(() => {
                    this.loader.style.opacity = '0';
                    this.loader.style.visibility = 'hidden';
                    setTimeout(() => this.loader.remove(), 500);
                }, 1500);
            });
        }
    }

    // ============================================
    // INTERSECTION OBSERVER FOR ANIMATIONS
    // ============================================
    const observeAnimations = () => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');

                    // Stagger children animations
                    const children = entry.target.querySelectorAll('.stagger-child');
                    children.forEach((child, i) => {
                        child.style.transitionDelay = `${i * 0.1}s`;
                        child.classList.add('revealed');
                    });
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('.section, .card, .stat, .product, .gallery__item').forEach(el => {
            el.classList.add('observe-reveal');
            observer.observe(el);
        });

        // Add CSS for observed elements
        const style = document.createElement('style');
        style.textContent = `
            .observe-reveal {
                opacity: 0;
                transform: translateY(30px);
                transition: opacity 0.6s ease, transform 0.6s ease;
            }
            .observe-reveal.revealed {
                opacity: 1;
                transform: translateY(0);
            }
            .stagger-child {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.4s ease, transform 0.4s ease;
            }
            .stagger-child.revealed {
                opacity: 1;
                transform: translateY(0);
            }
        `;
        document.head.appendChild(style);
    };

    // ============================================
    // SMOOTH PAGE TRANSITIONS
    // ============================================
    const initPageTransitions = () => {
        // Create transition overlay
        const overlay = document.createElement('div');
        overlay.className = 'page-transition';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #05060A;
            z-index: 99999;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        document.body.appendChild(overlay);

        // Intercept link clicks
        document.querySelectorAll('a[href$=".html"]').forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (href && !href.startsWith('http') && !href.startsWith('#')) {
                    e.preventDefault();
                    overlay.style.opacity = '1';
                    setTimeout(() => {
                        window.location.href = href;
                    }, 300);
                }
            });
        });

        // Fade in on page load
        window.addEventListener('pageshow', () => {
            overlay.style.opacity = '0';
        });
    };

    // ============================================
    // KONAMI CODE EASTER EGG
    // ============================================
    const initKonamiCode = () => {
        const konamiCode = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65];
        let konamiIndex = 0;

        document.addEventListener('keydown', (e) => {
            if (e.keyCode === konamiCode[konamiIndex]) {
                konamiIndex++;
                if (konamiIndex === konamiCode.length) {
                    activateUltraMode();
                    konamiIndex = 0;
                }
            } else {
                konamiIndex = 0;
            }
        });
    };

    const activateUltraMode = () => {
        document.body.style.animation = 'ultraRainbow 0.5s infinite';

        const style = document.createElement('style');
        style.textContent = `
            @keyframes ultraRainbow {
                0% { filter: hue-rotate(0deg); }
                100% { filter: hue-rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        // Create explosion of particles
        for (let i = 0; i < 100; i++) {
            const particle = document.createElement('div');
            particle.style.cssText = `
                position: fixed;
                width: 10px;
                height: 10px;
                background: ${['#9B1DFF', '#00FAFF', '#39FF14', '#FF1F3D'][Math.floor(Math.random() * 4)]};
                border-radius: 50%;
                left: 50%;
                top: 50%;
                pointer-events: none;
                z-index: 999999;
                animation: explode 1s ease forwards;
                --angle: ${Math.random() * 360}deg;
                --distance: ${Math.random() * 500 + 200}px;
            `;
            document.body.appendChild(particle);
            setTimeout(() => particle.remove(), 1000);
        }

        const explodeStyle = document.createElement('style');
        explodeStyle.textContent = `
            @keyframes explode {
                0% {
                    transform: translate(-50%, -50%) scale(0);
                    opacity: 1;
                }
                100% {
                    transform: translate(
                        calc(-50% + cos(var(--angle)) * var(--distance)),
                        calc(-50% + sin(var(--angle)) * var(--distance))
                    ) scale(1);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(explodeStyle);

        setTimeout(() => {
            document.body.style.animation = '';
        }, 5000);

        console.log('🎮 ULTRA MODE ACTIVATED! 🎮');
    };

    // ============================================
    // PERFORMANCE MONITORING
    // ============================================
    const initPerformanceMonitor = () => {
        if (window.location.hash === '#debug') {
            const monitor = document.createElement('div');
            monitor.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: rgba(5, 6, 10, 0.9);
                border: 1px solid #9B1DFF;
                padding: 10px;
                font-family: monospace;
                font-size: 12px;
                color: #00FAFF;
                z-index: 99999;
                border-radius: 4px;
            `;
            document.body.appendChild(monitor);

            let frames = 0;
            let lastTime = performance.now();

            const updateMonitor = () => {
                frames++;
                const now = performance.now();

                if (now - lastTime >= 1000) {
                    const fps = Math.round(frames * 1000 / (now - lastTime));
                    const memory = performance.memory ?
                        Math.round(performance.memory.usedJSHeapSize / 1048576) : 'N/A';

                    monitor.innerHTML = `
                        FPS: ${fps}<br>
                        Memory: ${memory}MB<br>
                        DOM: ${document.getElementsByTagName('*').length}
                    `;

                    frames = 0;
                    lastTime = now;
                }

                requestAnimationFrame(updateMonitor);
            };
            updateMonitor();
        }
    };

    // ============================================
    // INITIALIZE EVERYTHING
    // ============================================
    document.addEventListener('DOMContentLoaded', () => {
        // Loading screen (runs immediately)
        new LoadingScreen();

        // Initialize after a short delay to ensure DOM is ready
        setTimeout(() => {
            // WebGL on hero sections
            document.querySelectorAll('.hero').forEach(hero => {
                new WebGLDistortion(hero);
            });

            // Audio visualizer
            new AudioVisualizer();

            // Other initializations
            preloadResources();
            observeAnimations();
            initPageTransitions();
            initKonamiCode();
            initPerformanceMonitor();

            console.log('🚀 WXXXC TURBO initialized');
        }, 100);
    });

    // Expose for debugging
    window.WXXXC = {
        activateUltraMode,
        WebGLDistortion,
        AudioVisualizer
    };

})();
