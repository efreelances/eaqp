document.addEventListener('DOMContentLoaded', function() {
    console.log("✅ script.js cargado correctamente");

    // ============================================
    // 1. SLIDER CON TIEMPO PERSONALIZADO + COLORES GLOBALES
    // ============================================
    const slides = document.querySelectorAll('.slide');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const dotsContainer = document.querySelector('.slider-dots');

    console.log(`🖼️ Sliders encontrados: ${slides.length}`);

    if (slides.length > 0 && dotsContainer) {
        let currentSlide = 0;
        const totalSlides = slides.length;
        let autoSlideTimeout = null;

        // Crear dots de navegación
        slides.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.classList.add('dot');
            if (index === 0) dot.classList.add('active');
            dot.addEventListener('click', () => {
                goToSlide(index);
                resetAutoSlide();
            });
            dotsContainer.appendChild(dot);
        });

        const dots = document.querySelectorAll('.dot');

        // Función para actualizar la vista
        function updateSlides() {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');

            // 🎨 LLAMAR A TU FUNCIÓN DE COLORES
            applySlideTheme();
        }

        // Función para ir a un slide específico
        function goToSlide(index) {
            currentSlide = index;
            if (currentSlide >= totalSlides) currentSlide = 0;
            if (currentSlide < 0) currentSlide = totalSlides - 1;
            updateSlides();
        }

        // ⏱️ Leer duración personalizada desde el HTML
        function getSlideDuration() {
            const duration = parseInt(slides[currentSlide].getAttribute('data-duration')) || 5;
            return duration * 1000; // Convertir a milisegundos
        }

        // Motor del auto-slide
        function startAutoSlide() {
            clearTimeout(autoSlideTimeout);
            if (totalSlides > 1) {
                autoSlideTimeout = setTimeout(() => {
                    goToSlide(currentSlide + 1);
                    startAutoSlide();
                }, getSlideDuration());
            }
        }

        function resetAutoSlide() {
            clearTimeout(autoSlideTimeout);
            startAutoSlide();
        }

        // 🎨 TU FUNCIÓN: Aplicar colores del tema del slide activo a TODA la página
        function applySlideTheme() {
            const activeSlide = slides[currentSlide];
            const themeId = activeSlide.getAttribute('data-theme-id');
            
            if (!themeId) return;
            
            // Obtener colores desde las variables CSS del slide
            const slideStyle = getComputedStyle(activeSlide);
            const goldColor = slideStyle.getPropertyValue('--slide-gold').trim() || '#d4af37';
            const goldHover = slideStyle.getPropertyValue('--slide-gold-hover').trim() || '#b5952f';
            const textColor = slideStyle.getPropertyValue('--slide-white').trim() || '#ffffff';
            const bgColor = slideStyle.getPropertyValue('--slide-black').trim() || '#0a0a0a';
            const lightGray = slideStyle.getPropertyValue('--slide-light-gray').trim() || '#cccccc';
            
            // 🌍 ACTUALIZAR VARIABLES GLOBALES DE TODA LA PÁGINA
            const root = document.documentElement;
            root.style.setProperty('--color-gold', goldColor);
            root.style.setProperty('--color-gold-hover', goldHover);
            root.style.setProperty('--color-white', textColor);
            root.style.setProperty('--color-black', bgColor);
            root.style.setProperty('--color-light-gray', lightGray);
            
            // Aplicar a elementos del slider
            const h2 = activeSlide.querySelector('h2');
            const p = activeSlide.querySelector('p');
            const btn = activeSlide.querySelector('.btn-gold');
            
            if (h2) {
                h2.style.color = goldColor;
                h2.style.textShadow = `2px 2px 8px ${bgColor}`;
            }
            if (p) p.style.color = textColor;
            if (btn) {
                btn.style.backgroundColor = goldColor;
                btn.style.borderColor = goldColor;
                btn.style.color = '#000';
            }
            
            // Aplicar a botones de navegación
            [prevBtn, nextBtn].forEach(navBtn => {
                if (navBtn) {
                    navBtn.style.borderColor = goldColor;
                    navBtn.style.color = goldColor;
                    navBtn.style.background = `${goldColor}33`;
                }
            });
            
            // Aplicar a dots
            dots.forEach(dot => {
                dot.style.borderColor = goldColor;
                dot.style.backgroundColor = dot.classList.contains('active') ? goldColor : 'transparent';
            });
            
            console.log(`🎨 Slide ${currentSlide + 1} - Tema global actualizado a: ${goldColor}`);
        }

        // Event listeners para botones de navegación
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                goToSlide(currentSlide + 1);
                resetAutoSlide();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                goToSlide(currentSlide - 1);
                resetAutoSlide();
            });
        }

        // Pausar al hacer hover sobre el slider
        const sliderSection = document.querySelector('.slider-section');
        if (sliderSection) {
            sliderSection.addEventListener('mouseenter', () => clearTimeout(autoSlideTimeout));
            sliderSection.addEventListener('mouseleave', () => startAutoSlide());
        }

        // 🚀 INICIALIZAR EL SLIDER
        updateSlides();
        startAutoSlide();
        console.log(`✅ Slider inicializado con ${totalSlides} slides`);
    } else {
        console.warn("⚠️ No se encontraron slides o el contenedor .slider-dots");
    }

    // ============================================
    // 2. MENÚ MÓVIL
    // ============================================
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', () => {
            mainNav.classList.toggle('active');
        });
    }

    // ============================================
    // 3. SELECTOR DE IDIOMA (DROPDOWN)
    // ============================================
    const langToggle = document.getElementById('langToggle');
    const langDropdown = document.getElementById('langDropdown');
    
    if (langToggle && langDropdown) {
        langToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            langDropdown.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!langDropdown.contains(e.target) && !langToggle.contains(e.target)) {
                langDropdown.classList.remove('active');
            }
        });
    }

    // ============================================
    // 4. BOTÓN SCROLL TO TOP (Si lo tienes en el footer)
    // ============================================
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    if (scrollTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.style.display = 'flex';
            } else {
                scrollTopBtn.style.display = 'none';
            }
        });
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});