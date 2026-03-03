/**
 * YTrip Single Tour - Interactive JavaScript
 * 
 * @package YTrip
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // ============================================================
        // Tabs Navigation
        // ============================================================
        const tabButtons = document.querySelectorAll('.ytrip-tabs__btn');
        const tabPanels = document.querySelectorAll('.ytrip-tab-panel');

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const targetTab = this.getAttribute('data-tab');

                // Update active button
                tabButtons.forEach(function (btn) {
                    btn.classList.remove('ytrip-tabs__btn--active');
                });
                this.classList.add('ytrip-tabs__btn--active');

                // Update active panel
                tabPanels.forEach(function (panel) {
                    panel.classList.remove('ytrip-tab-panel--active');
                    if (panel.getAttribute('data-panel') === targetTab) {
                        panel.classList.add('ytrip-tab-panel--active');
                    }
                });

                // Keep active tab visible when tab bar is scrollable
                this.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            });
        });

        // ============================================================
        // Tour gallery Swiper (when hero is single image, section .ytrip-tour-gallery-section)
        // ============================================================
        (function initTourGallerySwiper() {
            var galleryEl = document.querySelector('.ytrip-tour-gallery-swiper');
            if (!galleryEl || typeof Swiper === 'undefined') return;
            if (galleryEl.classList.contains('ytrip-tour-gallery-initialized')) return;
            var slideCount = galleryEl.querySelectorAll('.swiper-slide').length;
            new Swiper(galleryEl, {
                slidesPerView: 1,
                spaceBetween: 0,
                loop: slideCount > 1,
                autoplay: slideCount > 1 ? { delay: 5000, disableOnInteraction: false } : false,
                pagination: { el: galleryEl.querySelector('.swiper-pagination'), clickable: true },
                navigation: {
                    nextEl: galleryEl.querySelector('.swiper-button-next'),
                    prevEl: galleryEl.querySelector('.swiper-button-prev')
                },
                on: { init: function () { galleryEl.classList.add('ytrip-tour-gallery-initialized'); } }
            });
        })();

        // ============================================================
        // FAQ Accordion
        // ============================================================
        const faqItems = document.querySelectorAll('.ytrip-faq__item');

        faqItems.forEach(function (item) {
            const question = item.querySelector('.ytrip-faq__question');
            if (!question) return;

            question.addEventListener('click', function () {
                const isOpen = item.classList.contains('ytrip-faq__item--open');

                // Close all items
                faqItems.forEach(function (faq) {
                    faq.classList.remove('ytrip-faq__item--open');
                });

                // Toggle current item
                if (!isOpen) {
                    item.classList.add('ytrip-faq__item--open');
                }
            });
        });

        // ============================================================
        // Quantity Selector
        // ============================================================
        // ============================================================
        // Guest Selector (Stepper)
        // ============================================================
        const guestDisplay = document.getElementById('ytrip-guests-display');
        const guestContainer = document.getElementById('ytrip-guest-container');
        const hiddenAdults = document.getElementById('ytrip-field-adults');
        const hiddenChildren = document.getElementById('ytrip-field-children');
        const qtyButtons = document.querySelectorAll('.ytrip-qty-btn');

        if (guestDisplay && guestContainer) {
            // Toggle Dropdown
            guestDisplay.addEventListener('click', function (e) {
                e.stopPropagation();
                guestContainer.classList.toggle('active');
                // Close calendar if open
                const calendar = document.getElementById('ytrip-calendar-container');
                if (calendar) calendar.classList.remove('active');
            });

            // Close on click outside
            document.addEventListener('click', function (e) {
                if (!guestContainer.contains(e.target) && e.target !== guestDisplay) {
                    guestContainer.classList.remove('active');
                }
            });

            // Update Display Text
            function updateGuestDisplay() {
                const adults = parseInt(hiddenAdults.value) || 1;
                const children = parseInt(hiddenChildren.value) || 0;

                let text = adults + (adults === 1 ? ' Adult' : ' Adults');
                if (children > 0) {
                    text += ', ' + children + (children === 1 ? ' Child' : ' Children');
                }
                guestDisplay.value = text;
            }

            // Stepper Logic
            qtyButtons.forEach(function (button) {
                button.addEventListener('click', function (e) {
                    e.stopPropagation(); // Prevent closing dropdown
                    const action = this.getAttribute('data-action');
                    const target = this.getAttribute('data-target');
                    const hiddenInput = document.getElementById('ytrip-field-' + target);
                    const valSpan = document.getElementById('val-' + target);

                    if (!hiddenInput || !valSpan) return;

                    let currentValue = parseInt(hiddenInput.value);
                    const min = target === 'adults' ? 1 : 0;
                    const max = 20; // Reasonable limit

                    if (action === 'plus' && currentValue < max) {
                        currentValue++;
                    } else if (action === 'minus' && currentValue > min) {
                        currentValue--;
                    }

                    // Update State
                    hiddenInput.value = currentValue;
                    valSpan.textContent = currentValue;

                    // Update Button State
                    const minusBtn = this.parentElement.querySelector('[data-action="minus"]');
                    if (minusBtn) {
                        minusBtn.disabled = (currentValue <= min);
                    }

                    updateGuestDisplay();
                });
            });

            // Initial Update
            updateGuestDisplay();
        }

        // ============================================================
        // Gallery Lightbox (Simple)
        // ============================================================
        const galleryThumbs = document.querySelectorAll('.ytrip-hero-gallery__thumb');
        const mainImage = document.querySelector('.ytrip-hero-gallery__image');

        galleryThumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                const img = this.querySelector('img');
                if (img && mainImage) {
                    // Get full size image from srcset or use current src
                    const fullSrc = img.getAttribute('data-full') || img.src;
                    mainImage.src = fullSrc;
                    mainImage.classList.add('ytrip-hero-gallery__image--loading');
                    mainImage.onload = function () {
                        mainImage.classList.remove('ytrip-hero-gallery__image--loading');
                    };
                }
            });
        });

        // ============================================================
        // Video Modal
        // ============================================================
        const playButton = document.querySelector('.ytrip-hero-gallery__play');

        if (playButton) {
            playButton.addEventListener('click', function () {
                const videoUrl = this.getAttribute('data-video');
                if (videoUrl) {
                    // Create modal
                    const modal = document.createElement('div');
                    modal.className = 'ytrip-video-modal';
                    modal.innerHTML = `
                        <div class="ytrip-video-modal__overlay"></div>
                        <div class="ytrip-video-modal__content">
                            <button class="ytrip-video-modal__close">&times;</button>
                            <div class="ytrip-video-modal__wrapper">
                                <iframe src="${getEmbedUrl(videoUrl)}" frameborder="0" allowfullscreen></iframe>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    document.body.style.overflow = 'hidden';

                    // Close handlers
                    modal.querySelector('.ytrip-video-modal__close').addEventListener('click', closeVideoModal);
                    modal.querySelector('.ytrip-video-modal__overlay').addEventListener('click', closeVideoModal);

                    function closeVideoModal() {
                        modal.remove();
                        document.body.style.overflow = '';
                    }
                }
            });
        }

        function getEmbedUrl(url) {
            // YouTube
            const youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/);
            if (youtubeMatch) {
                return 'https://www.youtube.com/embed/' + youtubeMatch[1] + '?autoplay=1';
            }
            // Vimeo
            const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
            if (vimeoMatch) {
                return 'https://player.vimeo.com/video/' + vimeoMatch[1] + '?autoplay=1';
            }
            return url;
        }

        // ============================================================
        // Sticky Sidebar (desktop only)
        // ============================================================
        const sidebar = document.querySelector('.ytrip-sidebar');

        if (sidebar && window.innerWidth > 1024) {
            const headerHeight = 100;
            const sidebarTop = sidebar.offsetTop - headerHeight;

            window.addEventListener('scroll', function () {
                if (window.pageYOffset >= sidebarTop) {
                    sidebar.classList.add('ytrip-sidebar--sticky');
                } else {
                    sidebar.classList.remove('ytrip-sidebar--sticky');
                }
            });
        }

        // ============================================================
        // Calendar: see ytrip-calendar.js (shared component)
        // ============================================================
        const dateDisplay = document.getElementById('ytrip-date-display');
        const dateInput = document.getElementById('ytrip-tour-date');
        const calendarContainer = document.getElementById('ytrip-calendar-container');

        if (false) { // Calendar handled by ytrip-calendar.js
            let currentDate = new Date();
            let currentMonth = currentDate.getMonth();
            let currentYear = currentDate.getFullYear();
            let selectedDate = null;

            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];

            // Open/Close Calendar
            dateDisplay.addEventListener('click', function (e) {
                e.stopPropagation();
                calendarContainer.classList.toggle('active');
                if (calendarContainer.classList.contains('active')) {
                    renderCalendar(currentMonth, currentYear);
                }
            });

            // Close on click outside
            document.addEventListener('click', function (e) {
                if (!calendarContainer.contains(e.target) && e.target !== dateDisplay) {
                    calendarContainer.classList.remove('active');
                }
            });

            // Render Calendar
            function renderCalendar(month, year) {
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                // Header with Month Selector
                let html = `
                    <div class="ytrip-calendar-header">
                        <button type="button" class="ytrip-cal-prev">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <span class="ytrip-cal-month" id="ytrip-cal-title">${monthNames[month]} ${year}</span>
                        <button type="button" class="ytrip-cal-next">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                    <div class="ytrip-calendar-overlay" id="ytrip-cal-overlay" style="display: none;"></div>
                    <div class="ytrip-calendar-grid" id="ytrip-cal-grid">
                        <div class="ytrip-cal-day-name">Su</div>
                        <div class="ytrip-cal-day-name">Mo</div>
                        <div class="ytrip-cal-day-name">Tu</div>
                        <div class="ytrip-cal-day-name">We</div>
                        <div class="ytrip-cal-day-name">Th</div>
                        <div class="ytrip-cal-day-name">Fr</div>
                        <div class="ytrip-cal-day-name">Sa</div>
                `;

                // Empty cells
                for (let i = 0; i < firstDay; i++) {
                    html += `<div class="ytrip-cal-empty"></div>`;
                }

                // Days
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const isPast = date < today;
                    const isSelected = selectedDate &&
                        date.getDate() === selectedDate.getDate() &&
                        date.getMonth() === selectedDate.getMonth() &&
                        date.getFullYear() === selectedDate.getFullYear();

                    const classes = ['ytrip-cal-date'];
                    if (isPast) classes.push('disabled');
                    if (isSelected) classes.push('selected');
                    if (date.toDateString() === today.toDateString()) classes.push('today');

                    html += `<button type="button" class="${classes.join(' ')}" data-date="${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}">${day}</button>`;
                }

                html += `</div>`;
                calendarContainer.innerHTML = html;

                // Bind Events
                bindCalendarEvents(month, year);
            }

            function bindCalendarEvents(month, year) {
                // Update state when navigating
                currentMonth = month;
                currentYear = year;

                // Prev/Next
                const prevBtn = calendarContainer.querySelector('.ytrip-cal-prev');
                const nextBtn = calendarContainer.querySelector('.ytrip-cal-next');

                if (prevBtn) {
                    prevBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        let newMonth = currentMonth - 1;
                        let newYear = currentYear;
                        if (newMonth < 0) {
                            newMonth = 11;
                            newYear--;
                        }
                        currentMonth = newMonth;
                        currentYear = newYear;
                        renderCalendar(currentMonth, currentYear);
                    });
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        let newMonth = currentMonth + 1;
                        let newYear = currentYear;
                        if (newMonth > 11) {
                            newMonth = 0;
                            newYear++;
                        }
                        currentMonth = newMonth;
                        currentYear = newYear;
                        renderCalendar(currentMonth, currentYear);
                    });
                }

                // Month Selector Click
                const title = document.getElementById('ytrip-cal-title');
                const overlay = document.getElementById('ytrip-cal-overlay');

                if (title) {
                    title.addEventListener('click', function (e) {
                        e.stopPropagation();
                        if (overlay.style.display === 'none' || overlay.style.display === '') {
                            showMonthSelection(month, year);
                        } else {
                            overlay.style.display = 'none';
                        }
                    });
                }

                // Date Clicks
                calendarContainer.querySelectorAll('.ytrip-cal-date:not(.disabled)').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const dateStr = this.getAttribute('data-date');
                        selectedDate = new Date(dateStr + 'T00:00:00');
                        dateInput.value = dateStr;
                        const options = { year: 'numeric', month: 'short', day: 'numeric' };
                        dateDisplay.value = selectedDate.toLocaleDateString('en-US', options);
                        calendarContainer.classList.remove('active');
                        dateInput.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
            }

            function showMonthSelection(currentM, currentY) {
                const overlay = document.getElementById('ytrip-cal-overlay');
                if (!overlay) return;

                let html = '';
                const now = new Date();
                const todayMonth = now.getMonth();
                const todayYear = now.getFullYear();

                // Show upcoming 12 months from today
                for (let i = 0; i < 12; i++) {
                    const m = (todayMonth + i) % 12;
                    const y = todayYear + Math.floor((todayMonth + i) / 12);
                    const isSelected = (m === currentM && y === currentY);

                    html += `<div class="ytrip-cal-overlay-item ${isSelected ? 'active' : ''}" data-m="${m}" data-y="${y}">${monthNames[m]} ${y !== todayYear ? y : ''}</div>`;
                }

                overlay.innerHTML = html;
                overlay.style.display = 'grid';

                // Bind selection
                overlay.querySelectorAll('.ytrip-cal-overlay-item').forEach(item => {
                    item.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const m = parseInt(this.getAttribute('data-m'));
                        const y = parseInt(this.getAttribute('data-y'));
                        currentMonth = m;
                        currentYear = y;
                        renderCalendar(m, y);
                    });
                });
            }
        }

        // ============================================================
        // Inquiry Form AJAX
        // ============================================================
        const inquiryForm = document.getElementById('ytrip-inquiry-form');

        if (inquiryForm) {
            inquiryForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Sending...';
                submitBtn.disabled = true;

                const formData = new FormData(this);

                fetch(ytripAjax.ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data.success) {
                            inquiryForm.innerHTML = '<div class="ytrip-form-success">' +
                                '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
                                '<h4>Thank You!</h4>' +
                                '<p>Your inquiry has been sent. We\'ll get back to you soon.</p></div>';
                        } else {
                            alert(data.data || 'Error sending inquiry. Please try again.');
                            submitBtn.textContent = originalText;
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(function () {
                        alert('Error sending inquiry. Please try again.');
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    });
            });
        }

        // ============================================================
        // Sticky CTA bar (tablet/mobile): show when booking widget is below the fold
        // ============================================================
        const bookingWidget = document.getElementById('ytrip-booking-widget');
        const ctaBar = document.getElementById('ytrip-booking-cta-bar');
        const ctaBarAmount = document.getElementById('ytrip-cta-bar-amount');
        const ctaBarGuests = document.getElementById('ytrip-cta-bar-guests');
        const ctaBarBtn = document.getElementById('ytrip-cta-bar-btn');
        const widgetPriceEl = document.querySelector('.ytrip-booking-widget__amount');
        const widgetGuestsEl = document.getElementById('ytrip-guests-display');

        if (bookingWidget && ctaBar && ctaBarBtn) {
            var CTA_BAR_BREAKPOINT = 768;

            function syncCtaBarPrice() {
                if (ctaBarAmount && widgetPriceEl && widgetPriceEl.innerHTML.trim() !== '') {
                    ctaBarAmount.innerHTML = widgetPriceEl.innerHTML;
                }
                if (ctaBarGuests && widgetGuestsEl && widgetGuestsEl.value) {
                    ctaBarGuests.textContent = widgetGuestsEl.value;
                }
            }

            function isSmallViewport() {
                return window.innerWidth <= CTA_BAR_BREAKPOINT;
            }

            function updateCtaBarVisibility(entry) {
                if (!isSmallViewport()) {
                    ctaBar.classList.remove('is-visible');
                    ctaBar.setAttribute('aria-hidden', 'true');
                    return;
                }
                var isBelowFold = !entry.isIntersecting;
                if (isBelowFold) {
                    syncCtaBarPrice();
                    ctaBar.classList.add('is-visible');
                    ctaBar.setAttribute('aria-hidden', 'false');
                } else {
                    ctaBar.classList.remove('is-visible');
                    ctaBar.setAttribute('aria-hidden', 'true');
                }
            }

            syncCtaBarPrice();

            var observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        updateCtaBarVisibility(entry);
                    });
                },
                { root: null, rootMargin: '0px', threshold: 0.1 }
            );
            observer.observe(bookingWidget);

            ctaBarBtn.addEventListener('click', function () {
                bookingWidget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            window.addEventListener('resize', function () {
                if (!isSmallViewport()) {
                    ctaBar.classList.remove('is-visible');
                    ctaBar.setAttribute('aria-hidden', 'true');
                } else {
                    var rect = bookingWidget.getBoundingClientRect();
                    var inView = rect.top < window.innerHeight && rect.bottom > 0;
                    if (!inView) {
                        syncCtaBarPrice();
                        ctaBar.classList.add('is-visible');
                        ctaBar.setAttribute('aria-hidden', 'false');
                    } else {
                        ctaBar.classList.remove('is-visible');
                        ctaBar.setAttribute('aria-hidden', 'true');
                    }
                }
            });
        }

        // ============================================================
        // Gallery lightbox (same-page, no external resources)
        // ============================================================
        var galleryLinks = document.querySelectorAll('.ytrip-gallery-link');
        if (galleryLinks.length) {
            var galleryUrls = Array.prototype.map.call(galleryLinks, function (a) { return a.getAttribute('href'); });
            var currentIndex = 0;
            var lightbox = null;
            var lightboxImg = null;
            var lightboxPrev = null;
            var lightboxNext = null;

            function openLightbox(index) {
                currentIndex = index;
                if (!lightbox) {
                    lightbox = document.createElement('div');
                    lightbox.className = 'ytrip-lightbox';
                    lightbox.setAttribute('role', 'dialog');
                    lightbox.setAttribute('aria-modal', 'true');
                    lightbox.setAttribute('aria-label', 'Gallery');
                    lightbox.innerHTML =
                        '<div class="ytrip-lightbox__backdrop" aria-hidden="true"></div>' +
                        '<div class="ytrip-lightbox__wrap">' +
                        '<button type="button" class="ytrip-lightbox__close" aria-label="Close"></button>' +
                        '<button type="button" class="ytrip-lightbox__prev" aria-label="Previous"></button>' +
                        '<button type="button" class="ytrip-lightbox__next" aria-label="Next"></button>' +
                        '<img class="ytrip-lightbox__img" src="" alt="">' +
                        '</div>';
                    document.body.appendChild(lightbox);
                    lightboxImg = lightbox.querySelector('.ytrip-lightbox__img');
                    lightboxPrev = lightbox.querySelector('.ytrip-lightbox__prev');
                    lightboxNext = lightbox.querySelector('.ytrip-lightbox__next');

                    lightbox.querySelector('.ytrip-lightbox__backdrop').addEventListener('click', closeLightbox);
                    lightbox.querySelector('.ytrip-lightbox__close').addEventListener('click', closeLightbox);
                    lightboxPrev.addEventListener('click', function (e) { e.stopPropagation(); prevImage(); });
                    lightboxNext.addEventListener('click', function (e) { e.stopPropagation(); nextImage(); });
                    lightbox.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') closeLightbox();
                        if (e.key === 'ArrowLeft') prevImage();
                        if (e.key === 'ArrowRight') nextImage();
                    });
                }
                updateLightboxImage();
                lightbox.classList.add('ytrip-lightbox--open');
                document.body.style.overflow = 'hidden';
                lightboxPrev.focus();
            }

            function closeLightbox() {
                if (lightbox) {
                    lightbox.classList.remove('ytrip-lightbox--open');
                    document.body.style.overflow = '';
                }
            }

            function updateLightboxImage() {
                if (lightboxImg && galleryUrls[currentIndex]) {
                    lightboxImg.src = galleryUrls[currentIndex];
                    lightboxImg.alt = 'Gallery image ' + (currentIndex + 1);
                }
                if (lightboxPrev) lightboxPrev.style.visibility = currentIndex > 0 ? 'visible' : 'hidden';
                if (lightboxNext) lightboxNext.style.visibility = currentIndex < galleryUrls.length - 1 ? 'visible' : 'hidden';
            }

            function prevImage() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateLightboxImage();
                }
            }

            function nextImage() {
                if (currentIndex < galleryUrls.length - 1) {
                    currentIndex++;
                    updateLightboxImage();
                }
            }

            galleryLinks.forEach(function (link, index) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    openLightbox(index);
                });
            });
        }

    });

})();
