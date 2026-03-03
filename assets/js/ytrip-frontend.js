/**
 * YTrip Frontend JavaScript
 * 
 * Pure Vanilla JavaScript for carousel, wishlist, and interactions.
 * No dependencies - optimized for performance.
 *
 * @package YTrip
 * @version 2.0.0
 */

(function() {
    'use strict';

    // ==========================================================================
    // Carousel
    // ==========================================================================

    class YTripCarousel {
        constructor(element) {
            this.element = element;
            this.track = element.querySelector('.ytrip-carousel__track');
            this.slides = [];
            
            // Options from data attributes
            this.options = {
                autoplay: element.dataset.autoplay === 'true',
                speed: parseInt(element.dataset.speed, 10) || 5000,
                loop: element.dataset.loop === 'true',
                slidesDesktop: parseInt(element.dataset.slidesDesktop, 10) || 3,
                slidesTablet: parseInt(element.dataset.slidesTablet, 10) || 2,
                slidesMobile: parseInt(element.dataset.slidesMobile, 10) || 1,
                gap: parseInt(element.dataset.gap, 10) || 20,
                pauseHover: element.dataset.pauseHover === 'true'
            };

            this.currentIndex = 0;
            this.slideWidth = 0;
            this.slidesPerView = this.options.slidesDesktop;
            this.autoplayInterval = null;
            this.isDragging = false;
            this.startX = 0;
            this.currentX = 0;

            this.init();
        }

        init() {
            this.slides = Array.from(this.track.children);
            if (this.slides.length === 0) return;

            this.updateSlidesPerView();
            this.setupNavigation();
            this.setupDots();
            this.setupEvents();

            // Defer layout reads/writes to next frame to avoid forced reflow during load (PageSpeed)
            requestAnimationFrame(() => {
                this.updateSlideWidth();
                this.equalizeSlideHeights();
                this.goTo(0, false);
                if (this.options.autoplay) {
                    this.startAutoplay();
                }
            });

            // When related section was below fold, re-run layout when it becomes visible so slides are not hidden
            const relatedSection = this.element.closest('.ytrip-related-tours');
            if (relatedSection && typeof IntersectionObserver !== 'undefined') {
                const observer = new IntersectionObserver((entries) => {
                    const ent = entries[0];
                    if (ent && ent.isIntersecting && this.element.offsetWidth > 0) {
                        this.updateSlideWidth();
                        this.equalizeSlideHeights();
                        this.goTo(this.currentIndex, false);
                        this.updateDots();
                    }
                }, { rootMargin: '50px', threshold: 0 });
                observer.observe(this.element);
            }
        }

        /**
         * Set track height to tallest slide so all slides (and cards) have same width and same height.
         * Skips when container has no width. If measurement is 0 (layout/images not ready), retries then uses fallback so content is never hidden.
         * Fallback used only when slides not yet laid out; otherwise height = tallest slide (natural card height).
         */
        equalizeSlideHeights() {
            if (this.element.offsetWidth === 0) return;

            const slides = this.slides;
            const track = this.track;
            const self = this;
            const fallback = 360;

            function measureAndSet() {
                if (self.element.offsetWidth === 0) return;
                let maxHeight = 0;
                for (let i = 0; i < slides.length; i++) {
                    const h = slides[i].offsetHeight;
                    if (h > maxHeight) maxHeight = h;
                }
                if (maxHeight <= 0) maxHeight = fallback;
                var isRelated = self.element.closest('.ytrip-related-tours');
                if (isRelated && maxHeight > 0 && maxHeight < 360) maxHeight = 360;
                slides.forEach(slide => {
                    slide.style.height = '100%';
                    slide.style.minHeight = maxHeight + 'px';
                });
                track.style.height = maxHeight + 'px';
                return true;
            }

            requestAnimationFrame(() => {
                if (measureAndSet()) return;
                requestAnimationFrame(() => {
                    if (measureAndSet()) return;
                    setTimeout(() => { measureAndSet(); }, 150);
                });
            });
        }

        updateSlidesPerView() {
            const width = window.innerWidth;
            if (width < 768) {
                this.slidesPerView = this.options.slidesMobile;
            } else if (width < 1024) {
                this.slidesPerView = this.options.slidesTablet;
            } else {
                this.slidesPerView = this.options.slidesDesktop;
            }
        }

        updateSlideWidth() {
            // Single layout read, then batch writes (avoids forced reflow)
            const containerWidth = this.element.offsetWidth;
            const totalGap = (this.slidesPerView - 1) * this.options.gap;
            const width = (containerWidth - totalGap) / this.slidesPerView;
            this.slideWidth = width;

            const gapPx = this.options.gap + 'px';
            const minW = width + 'px';
            const slides = this.slides;
            for (let i = 0; i < slides.length; i++) {
                const s = slides[i];
                s.style.minWidth = minW;
                s.style.marginRight = gapPx;
            }
        }

        setupNavigation() {
            const wrapper = this.element.closest('.ytrip-tours--carousel-wrapper');
            if (!wrapper) return;

            const prevBtn = wrapper.querySelector('.ytrip-carousel__arrow--prev');
            const nextBtn = wrapper.querySelector('.ytrip-carousel__arrow--next');

            if (prevBtn) {
                prevBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.prev();
                }.bind(this));
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.next();
                }.bind(this));
            }
        }

        setupDots() {
            const wrapper = this.element.closest('.ytrip-tours--carousel-wrapper');
            if (!wrapper) return;

            const dotsContainer = wrapper.querySelector('.ytrip-carousel__dots');
            if (!dotsContainer) return;

            const totalSlides = Math.ceil(this.slides.length / this.slidesPerView);
            
            dotsContainer.innerHTML = '';
            
            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'ytrip-carousel__dot';
                dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                if (i === 0) dot.classList.add('ytrip-carousel__dot--active');
                dot.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const targetIndex = i * this.slidesPerView;
                    this.goTo(targetIndex, true);
                });
                dotsContainer.appendChild(dot);
            }
        }

        setupEvents() {
            // Resize: debounce and run layout in rAF to avoid forced reflow (PageSpeed)
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    requestAnimationFrame(() => {
                        this.updateSlidesPerView();
                        this.updateSlideWidth();
                        this.equalizeSlideHeights();
                        this.setupDots();
                        this.goTo(this.currentIndex, false);
                    });
                }, 100);
            });

            // Touch/Mouse drag
            this.track.addEventListener('mousedown', this.handleDragStart.bind(this));
            this.track.addEventListener('mousemove', this.handleDragMove.bind(this));
            this.track.addEventListener('mouseup', this.handleDragEnd.bind(this));
            this.track.addEventListener('mouseleave', this.handleDragEnd.bind(this));
            this.track.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            this.track.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: true });
            this.track.addEventListener('touchend', this.handleTouchEnd.bind(this));

            // Pause on hover
            if (this.options.pauseHover) {
                this.element.addEventListener('mouseenter', () => this.stopAutoplay());
                this.element.addEventListener('mouseleave', () => {
                    if (this.options.autoplay) this.startAutoplay();
                });
            }

            // Visibility change
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAutoplay();
                } else if (this.options.autoplay) {
                    this.startAutoplay();
                }
            });
        }

        handleDragStart(e) {
            this.isDragging = true;
            this.startX = e.pageX;
            this.track.style.cursor = 'grabbing';
        }

        handleDragMove(e) {
            if (!this.isDragging) return;
            this.currentX = e.pageX - this.startX;
        }

        handleDragEnd() {
            if (!this.isDragging) return;
            this.isDragging = false;
            this.track.style.cursor = '';

            if (Math.abs(this.currentX) > 50) {
                if (this.currentX > 0) {
                    this.prev();
                } else {
                    this.next();
                }
            }
            this.currentX = 0;
        }

        handleTouchStart(e) {
            this.startX = e.touches[0].clientX;
        }

        handleTouchMove(e) {
            this.currentX = e.touches[0].clientX - this.startX;
        }

        handleTouchEnd() {
            if (Math.abs(this.currentX) > 50) {
                if (this.currentX > 0) {
                    this.prev();
                } else {
                    this.next();
                }
            }
            this.currentX = 0;
        }

        goTo(index, animate = true) {
            const maxIndex = this.slides.length - this.slidesPerView;
            
            if (this.options.loop && index > maxIndex) {
                index = 0;
            } else if (this.options.loop && index < 0) {
                index = maxIndex;
            } else {
                index = Math.max(0, Math.min(index, maxIndex));
            }

            this.currentIndex = index;
            
            const offset = index * (this.slideWidth + this.options.gap);
            this.track.style.transition = animate ? 'transform 0.4s ease' : 'none';
            this.track.style.transform = `translateX(-${offset}px)`;

            this.updateDots();
        }

        next() {
            this.goTo(this.currentIndex + this.slidesPerView);
        }

        prev() {
            this.goTo(this.currentIndex - this.slidesPerView);
        }

        updateDots() {
            const wrapper = this.element.closest('.ytrip-tours--carousel-wrapper');
            if (!wrapper) return;

            const dots = wrapper.querySelectorAll('.ytrip-carousel__dot');
            const activeIndex = Math.floor(this.currentIndex / this.slidesPerView);

            dots.forEach((dot, i) => {
                dot.classList.toggle('ytrip-carousel__dot--active', i === activeIndex);
            });
        }

        startAutoplay() {
            this.stopAutoplay();
            this.autoplayInterval = setInterval(() => {
                this.next();
            }, this.options.speed);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }
    }

    // ==========================================================================
    // Wishlist
    // ==========================================================================

    class YTripWishlist {
        constructor() {
            this.storageKey = 'ytrip_wishlist';
            this.items = this.load();
            this.hydrateFromServer();
            this.init();
        }

        init() {
            this.updateAllButtons();
            this.bindEvents();
        }

        load() {
            try {
                const saved = localStorage.getItem(this.storageKey);
                return saved ? JSON.parse(saved) : [];
            } catch (e) {
                return [];
            }
        }

        hydrateFromServer() {
            if (typeof ytripWishlist === 'undefined' || !ytripWishlist.wishlist || ytripWishlist.isGuest) {
                return;
            }
            this.items = ytripWishlist.wishlist.map(function (id) { return parseInt(id, 10); });
            this.save();
        }

        save() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.items));
            } catch (e) {
                // Storage full or unavailable
            }
        }

        add(tourId) {
            if (!this.has(tourId)) {
                this.items.push(parseInt(tourId, 10));
                this.save();
                return true;
            }
            return false;
        }

        remove(tourId) {
            const index = this.items.indexOf(parseInt(tourId, 10));
            if (index > -1) {
                this.items.splice(index, 1);
                this.save();
                return true;
            }
            return false;
        }

        has(tourId) {
            return this.items.indexOf(parseInt(tourId, 10)) > -1;
        }

        toggle(tourId) {
            if (this.has(tourId)) {
                this.remove(tourId);
                return false;
            } else {
                this.add(tourId);
                return true;
            }
        }

        updateAllButtons() {
            document.querySelectorAll('.ytrip-tour-card__wishlist').forEach(btn => {
                const tourId = btn.dataset.tourId;
                const isActive = this.has(tourId);
                btn.classList.toggle('ytrip-tour-card__wishlist--active', isActive);
                btn.setAttribute('aria-pressed', isActive);
            });
        }

        bindEvents() {
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.ytrip-tour-card__wishlist');
                if (!btn) return;

                e.preventDefault();
                const tourId = btn.dataset.tourId;
                const added = this.toggle(tourId);

                btn.classList.toggle('ytrip-tour-card__wishlist--active', added);
                btn.setAttribute('aria-pressed', added);

                // Animate
                btn.classList.add('ytrip-tour-card__wishlist--animate');
                setTimeout(() => btn.classList.remove('ytrip-tour-card__wishlist--animate'), 300);

                // Optional: Sync with server
                this.syncWithServer(tourId, added);
            });
        }

        async syncWithServer(tourId, added) {
            const ajaxurl = (typeof ytripWishlist !== 'undefined' && ytripWishlist.ajaxurl)
                ? ytripWishlist.ajaxurl
                : (ytripFrontend?.ajaxUrl || '');
            const wishlistNonce = (typeof ytripWishlist !== 'undefined' && ytripWishlist.nonce)
                ? ytripWishlist.nonce
                : (ytripFrontend?.wishlistNonce || ytripFrontend?.nonce || '');

            const formData = new FormData();
            formData.append('action', 'ytrip_toggle_wishlist');
            formData.append('tour_id', tourId);
            formData.append('added', added ? '1' : '0');
            formData.append('security', wishlistNonce);

            try {
                await fetch(ajaxurl, { method: 'POST', body: formData });
            } catch (e) {
                // Silent fail - local storage is authoritative
            }
        }
    }

    // ==========================================================================
    // Dark Mode Toggle
    // ==========================================================================

    class YTripDarkMode {
        constructor() {
            this.init();
        }

        init() {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            // Check stored preference
            const stored = localStorage.getItem('ytrip-theme');
            
            // Apply theme
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('ytrip-dark-mode');
                document.documentElement.setAttribute('data-theme', 'dark');
            }

            // Listen for toggle clicks
            document.addEventListener('click', (e) => {
                const toggle = e.target.closest('.ytrip-dark-mode-toggle');
                if (!toggle) return;

                e.preventDefault();
                this.toggle();
            });

            // Listen for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('ytrip-theme')) {
                    this.setTheme(e.matches ? 'dark' : 'light');
                }
            });
        }

        toggle() {
            const isDark = document.documentElement.classList.contains('ytrip-dark-mode');
            this.setTheme(isDark ? 'light' : 'dark');
        }

        setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('ytrip-dark-mode');
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.classList.remove('ytrip-dark-mode');
                document.documentElement.setAttribute('data-theme', 'light');
            }
            localStorage.setItem('ytrip-theme', theme);
        }
    }

    // ==========================================================================
    // Tabs
    // ==========================================================================

    class YTripTabs {
        constructor() {
            this.init();
        }

        init() {
            document.addEventListener('click', (e) => {
                const tabBtn = e.target.closest('.ytrip-tabs__btn');
                if (!tabBtn) return;

                const tabId = tabBtn.dataset.tab;
                const container = tabBtn.closest('.ytrip-tabs, .ytrip-tabs__content');
                if (!container) return;

                // Update buttons
                container.querySelectorAll('.ytrip-tabs__btn').forEach(btn => {
                    btn.classList.remove('ytrip-tabs__btn--active');
                    btn.setAttribute('aria-selected', 'false');
                });
                tabBtn.classList.add('ytrip-tabs__btn--active');
                tabBtn.setAttribute('aria-selected', 'true');

                // Update panels
                container.querySelectorAll('.ytrip-tab-panel').forEach(panel => {
                    panel.classList.remove('ytrip-tab-panel--active');
                    panel.setAttribute('aria-hidden', 'true');
                });

                const activePanel = container.querySelector(`[data-panel="${tabId}"]`);
                if (activePanel) {
                    activePanel.classList.add('ytrip-tab-panel--active');
                    activePanel.setAttribute('aria-hidden', 'false');
                }
            });
        }
    }

    // ==========================================================================
    // FAQ – Native <details>/<summary> only; no JS. Expand/collapse via HTML + CSS on all devices.
    // ==========================================================================
    class YTripFAQ {
        constructor() {}
    }

    // ==========================================================================
    // Load More / Infinite Scroll
    // ==========================================================================

    class YTripPagination {
        constructor() {
            this.init();
        }

        init() {
            // Load More Button
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.ytrip-loadmore-btn');
                if (!btn) return;

                e.preventDefault();
                this.loadMore(btn);
            });

            // Infinite Scroll
            this.setupInfiniteScroll();
        }

        async loadMore(btn) {
            const container = btn.closest('.ytrip-tours');
            if (!container) return;

            const page = parseInt(btn.dataset.page, 10) + 1;
            const max = parseInt(btn.dataset.max, 10);

            btn.disabled = true;
            btn.textContent = ytripFrontend?.strings?.loading || 'Loading...';

            try {
                const formData = new FormData();
                formData.append('action', 'ytrip_load_more_tours');
                formData.append('page', page.toString());
                formData.append('nonce', ytripFrontend?.nonce || '');

                const response = await fetch(ytripFrontend?.ajaxurl || '', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.data.html) {
                    container.insertAdjacentHTML('beforeend', result.data.html);
                    btn.dataset.page = page.toString();
                    initImageSkeletons();

                    if (page >= max) {
                        btn.remove();
                    } else {
                        btn.textContent = ytripFrontend?.strings?.loadMore || 'Load More';
                        btn.disabled = false;
                    }
                }
            } catch (e) {
                console.error('Load more failed:', e);
                btn.textContent = ytripFrontend?.strings?.error || 'Error';
                btn.disabled = false;
            }
        }

        setupInfiniteScroll() {
            const infiniteContainer = document.querySelector('.ytrip-pagination--infinite');
            if (!infiniteContainer) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.triggerInfiniteLoad(infiniteContainer);
                    }
                });
            }, { rootMargin: '100px' });

            observer.observe(infiniteContainer);
        }

        async triggerInfiniteLoad(container) {
            if (container.dataset.loading === 'true') return;

            const page = parseInt(container.dataset.page, 10) + 1;
            const max = parseInt(container.dataset.max, 10);

            if (page > max) {
                container.remove();
                return;
            }

            container.dataset.loading = 'true';

            try {
                const formData = new FormData();
                formData.append('action', 'ytrip_load_more_tours');
                formData.append('page', page.toString());
                formData.append('nonce', ytripFrontend?.nonce || '');

                const response = await fetch(ytripFrontend?.ajaxurl || '', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.data.html) {
                    const toursContainer = container.closest('.ytrip-wrapper')?.querySelector('.ytrip-tours');
                    if (toursContainer) {
                        toursContainer.insertAdjacentHTML('beforeend', result.data.html);
                        initImageSkeletons();
                    }
                    container.dataset.page = page.toString();
                }

                container.dataset.loading = 'false';

                if (page >= max) {
                    container.remove();
                }
            } catch (e) {
                container.dataset.loading = 'false';
            }
        }
    }

    // ==========================================================================
    // Image skeleton (LCP/CLS): hide skeleton when image has loaded
    // ==========================================================================

    function initImageSkeletons() {
        function markLoaded(container) {
            if (container && !container.classList.contains('ytrip-img-loaded')) {
                container.classList.add('ytrip-img-loaded');
            }
        }

        // Hero: .ytrip-img-wrap containing img
        document.querySelectorAll('.ytrip-img-wrap img').forEach(function (img) {
            if (img.complete) {
                markLoaded(img.closest('.ytrip-img-wrap'));
            } else {
                img.addEventListener('load', function () {
                    markLoaded(img.closest('.ytrip-img-wrap'));
                });
            }
        });

        // Tour cards: .ytrip-tour-card__image containing .ytrip-img-skeleton + img
        document.querySelectorAll('.ytrip-tour-card__image .ytrip-img-skeleton').forEach(function (skeleton) {
            const cardImage = skeleton.closest('.ytrip-tour-card__image');
            if (!cardImage) return;
            const img = cardImage.querySelector('img');
            if (!img) return;
            if (img.complete) {
                markLoaded(cardImage);
            } else {
                img.addEventListener('load', function () {
                    markLoaded(cardImage);
                });
            }
        });
    }

    // ==========================================================================
    // Initialize
    // ==========================================================================

    function init() {
        // Carousels
        document.querySelectorAll('.ytrip-carousel').forEach(el => {
            new YTripCarousel(el);
        });

        // Wishlist
        new YTripWishlist();

        // Dark Mode
        new YTripDarkMode();

        // Tabs
        new YTripTabs();

        // FAQ
        new YTripFAQ();

        // Pagination
        new YTripPagination();

        // Image skeletons (LCP/CLS)
        initImageSkeletons();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for external use
    window.YTripCarousel = YTripCarousel;
    window.YTripWishlist = YTripWishlist;
    window.YTripDarkMode = YTripDarkMode;

})();
