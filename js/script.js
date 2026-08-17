/* ==========================================================================
   HELA WEDA GEDARA - ADVANCED FOREST & AYURVEDA ANIMATIONS
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sticky Header Effect
    const header = document.querySelector('.site-header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // 2. Mobile Nav Toggle
    const mobileToggler = document.querySelector('.mobile-toggler');
    const mobileNavPanel = document.querySelector('.mobile-nav-panel');
    const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');

    if (mobileToggler && mobileNavPanel && mobileNavOverlay) {
        const toggleMenu = () => {
            mobileNavPanel.classList.toggle('open');
            mobileNavOverlay.classList.toggle('show');
            
            const spans = mobileToggler.querySelectorAll('span');
            if (mobileNavPanel.classList.contains('open')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(6deg, -6deg)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        };

        mobileToggler.addEventListener('click', toggleMenu);
        mobileNavOverlay.addEventListener('click', toggleMenu);
    }

    // 3. Hero Banner Slider / Carousel
    const slides = document.querySelectorAll('.hero-slider .slide');
    const prevBtn = document.querySelector('.slider-prev');
    const nextBtn = document.querySelector('.slider-next');
    
    if (slides.length > 0) {
        let currentSlide = 0;
        let slideInterval;

        const showSlide = (n) => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (n + slides.length) % slides.length;
            slides[currentSlide].classList.add('active');
        };

        const nextSlide = () => showSlide(currentSlide + 1);
        const prevSlide = () => showSlide(currentSlide - 1);

        const startSlider = () => {
            slideInterval = setInterval(nextSlide, 7000);
        };

        const resetInterval = () => {
            clearInterval(slideInterval);
            startSlider();
        };

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                prevSlide();
                resetInterval();
            });
            nextBtn.addEventListener('click', () => {
                nextSlide();
                resetInterval();
            });
        }

        startSlider();
    }

    // 4. Advanced Canvas: Immersive Sri Lankan Coconut Palms & Subtle Leaf Particles
    let canvasContainer = document.getElementById('bg-canvas-container');
    if (!canvasContainer) {
        canvasContainer = document.createElement('div');
        canvasContainer.id = 'bg-canvas-container';
        canvasContainer.style.position = 'fixed';
        canvasContainer.style.top = '0';
        canvasContainer.style.left = '0';
        canvasContainer.style.width = '100%';
        canvasContainer.style.height = '100%';
        canvasContainer.style.zIndex = '-1';
        canvasContainer.style.pointerEvents = 'none';
        document.body.appendChild(canvasContainer);
    }
    
    if (canvasContainer) {
        const canvas = document.createElement('canvas');
        canvasContainer.appendChild(canvas);
        const ctx = canvas.getContext('2d');
        
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        let mouse = { x: -1000, y: -1000, radius: 120 };

        window.addEventListener('mousemove', (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });

        window.addEventListener('mouseleave', () => {
            mouse.x = -1000;
            mouse.y = -1000;
        });

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });

        // Subtly glowing Prana life particles (extremely low opacity)
        class PranaParticle {
            constructor() {
                this.reset();
            }

            reset() {
                this.x = Math.random() * width;
                this.y = height + 20 + Math.random() * 100;
                this.radius = 1 + Math.random() * 2;
                this.speedY = 0.2 + Math.random() * 0.5;
                this.speedX = -0.2 + Math.random() * 0.4;
                this.opacity = 0.05 + Math.random() * 0.15;
                this.pulseSpeed = 0.005 + Math.random() * 0.015;
                this.pulseAngle = Math.random() * Math.PI;
            }

            update() {
                this.y -= this.speedY;
                this.x += this.speedX + Math.sin(this.y / 60) * 0.15;
                this.pulseAngle += this.pulseSpeed;
                this.currentOpacity = this.opacity + Math.sin(this.pulseAngle) * 0.05;

                const dx = this.x - mouse.x;
                const dy = this.y - mouse.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < mouse.radius) {
                    const force = (mouse.radius - dist) / mouse.radius;
                    this.x += (dx / dist) * force * 2;
                    this.y += (dy / dist) * force * 2;
                }

                if (this.y < -20) {
                    this.reset();
                }
            }

            draw() {
                ctx.save();
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(46, 125, 50, ${Math.max(0.01, this.currentOpacity)})`;
                ctx.fill();
                ctx.restore();
            }
        }

        // Falling Organic leaves
        class HerbalLeaf {
            constructor() {
                this.reset();
            }

            reset() {
                this.x = Math.random() * width;
                this.y = -20 - Math.random() * 100;
                this.size = 6 + Math.random() * 8;
                this.speedY = 0.4 + Math.random() * 0.8;
                this.speedX = -0.3 + Math.random() * 0.6;
                this.rotation = Math.random() * Math.PI * 2;
                this.rotSpeed = -0.01 + Math.random() * 0.02;
                this.opacity = 0.08 + Math.random() * 0.15;
                this.hue = 120 + Math.random() * 30;
            }

            update() {
                this.y += this.speedY;
                this.x += this.speedX + Math.sin(this.y / 50) * 0.3;
                this.rotation += this.rotSpeed;

                const dx = this.x - mouse.x;
                const dy = this.y - mouse.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < mouse.radius) {
                    const force = (mouse.radius - dist) / mouse.radius;
                    this.x += (dx / dist) * force * 3;
                    this.y += (dy / dist) * force * 3;
                }

                if (this.y > height + 20 || this.x < -20 || this.x > width + 20) {
                    this.reset();
                }
            }

            draw() {
                ctx.save();
                ctx.translate(this.x, this.y);
                ctx.rotate(this.rotation);
                ctx.beginPath();
                ctx.moveTo(0, -this.size);
                ctx.quadraticCurveTo(this.size * 0.6, -this.size * 0.2, 0, this.size);
                ctx.quadraticCurveTo(-this.size * 0.6, -this.size * 0.2, 0, -this.size);
                ctx.fillStyle = `hsla(${this.hue}, 50%, 30%, ${this.opacity})`;
                ctx.fill();
                ctx.restore();
            }
        }

        // Swaying Sri Lankan Coconut Palm Tree
        class SriLankanPalm {
            constructor(x, y, scale, alignRight = false) {
                this.x = x;
                this.y = y;
                this.scale = scale;
                this.alignRight = alignRight;
                this.swayAngle = 0;
                this.swaySpeed = 0.005 + Math.random() * 0.005;
                this.swayRange = 0.02 + Math.random() * 0.03;
            }

            drawFrond(tx, ty, angle, length, sway) {
                const finalAngle = angle + sway;
                ctx.beginPath();
                ctx.moveTo(tx, ty);
                
                const cx1 = tx + Math.cos(finalAngle) * length * 0.5;
                const cy1 = ty + Math.sin(finalAngle) * length * 0.5 + 20 * this.scale;
                const ex = tx + Math.cos(finalAngle) * length;
                const ey = ty + Math.sin(finalAngle) * length + 50 * this.scale;
                
                ctx.quadraticCurveTo(cx1, cy1, ex, ey);
                ctx.strokeStyle = 'rgba(15, 60, 20, 0.07)';
                ctx.lineWidth = 3 * this.scale;
                ctx.stroke();

                const leafSegments = 16;
                for (let i = 2; i < leafSegments; i++) {
                    const t = i / leafSegments;
                    
                    const mt = 1 - t;
                    const px = mt * mt * tx + 2 * mt * t * cx1 + t * t * ex;
                    const py = mt * mt * ty + 2 * mt * t * cy1 + t * t * ey;
                    
                    const leafAngle = finalAngle + Math.PI / 2 + 0.2 * t;
                    const leafLen = (25 * (1 - t) + 5) * this.scale;
                    
                    ctx.beginPath();
                    ctx.moveTo(px, py);
                    ctx.lineTo(px + Math.cos(leafAngle) * leafLen, py + Math.sin(leafAngle) * leafLen);
                    ctx.strokeStyle = 'rgba(15, 60, 20, 0.06)';
                    ctx.lineWidth = 1.5 * this.scale;
                    ctx.stroke();
                }
            }

            updateAndDraw() {
                this.swayAngle += this.swaySpeed;
                const sway = Math.sin(this.swayAngle) * this.swayRange;
                
                const trunkHeight = 350 * this.scale;
                const tx = this.alignRight ? this.x - 40 * this.scale : this.x + 40 * this.scale;
                const ty = this.y - trunkHeight;

                ctx.beginPath();
                ctx.moveTo(this.x, this.y);
                
                const controlX = this.alignRight ? this.x - 80 * this.scale : this.x + 80 * this.scale;
                const controlY = this.y - trunkHeight * 0.5;
                ctx.quadraticCurveTo(controlX, controlY, tx, ty);
                ctx.strokeStyle = 'rgba(60, 50, 30, 0.05)';
                ctx.lineWidth = 14 * this.scale;
                ctx.stroke();

                for (let i = 1; i < 10; i++) {
                    const t = i / 10;
                    const mt = 1 - t;
                    const rx = mt * mt * this.x + 2 * mt * t * controlX + t * t * tx;
                    const ry = mt * mt * this.y + 2 * mt * t * controlY + t * t * ty;
                    
                    ctx.beginPath();
                    ctx.arc(rx, ry, (7 - t * 3) * this.scale, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(15, 48, 20, 0.03)';
                    ctx.fill();
                }

                const frondCount = 6;
                const startAngle = this.alignRight ? Math.PI * 0.8 : -Math.PI * 0.1;
                const frondLength = 160 * this.scale;
                
                for (let i = 0; i < frondCount; i++) {
                    const angle = startAngle + (i * Math.PI / 4);
                    this.drawFrond(tx, ty, angle, frondLength, sway * (1.2 + i * 0.1));
                }
            }
        }

        const pranaCount = 20;
        const leafCount = 12;
        
        const pranas = Array.from({ length: pranaCount }, () => new PranaParticle());
        const leaves = Array.from({ length: leafCount }, () => new HerbalLeaf());
        
        const leftPalm = new SriLankanPalm(-20, height + 50, 1.1, false);
        const rightPalm = new SriLankanPalm(width + 20, height + 50, 1.1, true);

        window.addEventListener('resize', () => {
            leftPalm.x = -20;
            leftPalm.y = height + 50;
            rightPalm.x = width + 20;
            rightPalm.y = height + 50;
        });

        const animate = () => {
            ctx.clearRect(0, 0, width, height);

            leftPalm.updateAndDraw();
            rightPalm.updateAndDraw();

            pranas.forEach(p => {
                p.update();
                p.draw();
            });

            leaves.forEach(l => {
                l.update();
                l.draw();
            });

            requestAnimationFrame(animate);
        };

        animate();
    }

    // 5. Scroll Reveal Animation using Intersection Observer
    const animatedElements = document.querySelectorAll('.reveal-on-scroll');
    if (animatedElements.length > 0) {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            observer.observe(el);
        });

        const styleSheet = document.createElement("style");
        styleSheet.innerText = `.reveal-on-scroll.visible { opacity: 1 !important; transform: translateY(0) !important; }`;
        document.head.appendChild(styleSheet);
    }

    // 6. Academy Course selection tabs
    const courseSelectors = document.querySelectorAll('.selector-card-box');
    const coursePanes = document.querySelectorAll('.course-detail-pane');

    if (courseSelectors.length > 0 && coursePanes.length > 0) {
        courseSelectors.forEach(selector => {
            selector.addEventListener('click', () => {
                const target = selector.getAttribute('data-target');
                
                courseSelectors.forEach(c => c.classList.remove('active'));
                selector.classList.add('active');
                
                coursePanes.forEach(pane => {
                    if (pane.id === target) {
                        pane.classList.add('active');
                        pane.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else {
                        pane.classList.remove('active');
                    }
                });
            });
        });
    }

    // 7. Local Course Bilingual Switcher (English / Sinhala)
    const langBtns = document.querySelectorAll('.language-btn');
    const langContents = document.querySelectorAll('.language-content');

    if (langBtns.length > 0 && langContents.length > 0) {
        langBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const lang = btn.getAttribute('data-lang');
                
                langBtns.forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-outline');
                });
                btn.classList.add('btn-primary', 'active');
                btn.classList.remove('btn-outline');

                langContents.forEach(content => {
                    if (content.id === `${lang}-content`) {
                        content.style.display = 'block';
                    } else {
                        content.style.display = 'none';
                    }
                });
            });
        });
    }

    // 8. Academy Outline Accordion
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    if (accordionHeaders.length > 0) {
        accordionHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const parent = header.parentElement;
                parent.classList.toggle('open');
            });
        });
    }

    // 9. Generic Booking / Contact Form handler with modern feedback notifications
    const forms = document.querySelectorAll('form');
    if (forms.length > 0) {
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                // Allow standard PHP submit if action is set
                if (form.getAttribute('action') && form.getAttribute('action') !== '#') {
                    return;
                }
                e.preventDefault();
                const formData = new FormData(form);
                let isValid = true;
                
                form.querySelectorAll('[required]').forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.style.borderColor = 'red';
                    } else {
                        input.style.borderColor = '';
                    }
                });

                if (isValid) {
                    const toast = document.createElement('div');
                    toast.style.position = 'fixed';
                    toast.style.bottom = '30px';
                    toast.style.right = '30px';
                    toast.style.backgroundColor = 'var(--primary)';
                    toast.style.color = '#fff';
                    toast.style.padding = '16px 28px';
                    toast.style.borderRadius = 'var(--radius-sm)';
                    toast.style.boxShadow = 'var(--shadow-lg)';
                    toast.style.zIndex = '2000';
                    toast.style.fontWeight = '600';
                    toast.style.borderLeft = '6px solid var(--accent)';
                    
                    const name = formData.get('first_name') || formData.get('name') || 'Guest';
                    toast.innerText = `Thank you, ${name}! Your request has been sent successfully. We will contact you soon.`;
                    
                    document.body.appendChild(toast);
                    form.reset();
                    
                    setTimeout(() => {
                        toast.style.opacity = '0';
                        toast.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => toast.remove(), 500);
                    }, 4000);
                }
            });
        });
    }

    // 10. CMS dynamic text contents loader from admin/content.json
    const cmsPath = window.location.pathname.includes('/pages/') ? '../../admin/content.json' : 'admin/content.json';
    fetch(cmsPath)
        .then(res => {
            if (res.ok) return res.json();
            throw new Error('Not found');
        })
        .then(data => {
            Object.keys(data).forEach(key => {
                const el = document.getElementById(key);
                if (el) {
                    el.innerText = data[key];
                }
            });
        })
        .catch(err => console.log('CMS values skipped or local fallback active:', err));

    // Track unique page visits
    (function() {
        let path = 'admin/submit_form.php';
        if (window.location.pathname.includes('/pages/')) {
            path = '../../admin/submit_form.php';
        }
        fetch(path + '?track_visit=1').catch(err => console.log('Tracking error:', err));
    })();

});
