/**
 * YTrip Archive Filters – Vanilla JavaScript
 * Handles AJAX filtering, sorting, view switching, and pagination.
 * No jQuery dependency; encapsulated to avoid conflicts.
 *
 * @package YTrip
 * @since 1.0.0
 */

(function () {
    'use strict';

    const SCROLL_OFFSET_PX = 200;
    const SCROLL_TOP_OFFSET_PX = 100;
    const SKELETON_CARD_COUNT = 6;
    const FILTER_BAR_ANIMATION_MS = 200;
    const DEFAULT_ORDERBY = 'date';
    const DEFAULT_VIEW = 'grid';

    /**
     * DOM helpers (scoped to document).
     */
    function byId(id) {
        return document.getElementById(id);
    }

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsAll(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function isVisible(el) {
        if (!el) return false;
        const style = window.getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden' && el.offsetParent !== null;
    }

    /**
     * YTrip Archive Filters controller.
     */
    class YTripArchiveFilters {
        constructor() {
            this.container = byId('ytrip-tours-container');
            this.loading = byId('ytrip-loading');
            this.paginationWrapper = qs('.ytrip-pagination-wrapper');
            this.currentPage = 1;
            this.maxPages = 1;
            this.paginationStyle = 'numbered';
            this.isLoading = false;
            this._boundCheckInfinite = this.checkInfiniteScroll.bind(this);
        }

        /**
         * Resolve filter form(s). Returns array of form elements.
         */
        getFormElements() {
            const sidebar = byId('ytrip-filters-form');
            const topbar = byId('ytrip-filters-topbar');
            const forms = [];
            if (sidebar) forms.push(sidebar);
            if (topbar) forms.push(topbar);
            return forms;
        }

        /**
         * Get the form that is currently visible (for reading form data).
         */
        getActiveForm() {
            const sidebar = byId('ytrip-filters-form');
            const topbar = byId('ytrip-filters-topbar');
            if (sidebar && isVisible(sidebar)) return sidebar;
            if (topbar && isVisible(topbar)) return topbar;
            return sidebar || topbar || null;
        }

        init() {
            if (!this.container) return;

            if (this.paginationWrapper) {
                this.paginationStyle = this.paginationWrapper.dataset.style || 'numbered';
                this.maxPages = parseInt(this.paginationWrapper.dataset.maxPages, 10) || 1;
                this.currentPage = parseInt(this.paginationWrapper.dataset.currentPage, 10) || 1;
            }

            this.bindEvents();
            this.updateURLFromState();
        }

        bindEvents() {
            const self = this;

            qsAll('.ytrip-view-toggle__btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const view = this.dataset.view;
                    if (view) self.setView(view);
                });
            });

            qsAll('.ytrip-columns-selector__btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const cols = this.dataset.cols;
                    if (cols) self.setColumns(cols);
                });
            });

            const sortEl = byId('ytrip-sort');
            if (sortEl) {
                sortEl.addEventListener('change', function () {
                    self.currentPage = 1;
                    self.loadTours(false);
                });
            }

            this.getFormElements().forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    self.currentPage = 1;
                    self.loadTours(false);
                });
            });

            qsAll('.ytrip-clear-filters').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    self.clearFilters();
                });
            });

            this.getFormElements().forEach(function (form) {
                qsAll('select', form).forEach(function (sel) {
                    sel.addEventListener('change', function () {
                        self.currentPage = 1;
                        self.loadTours(false);
                    });
                });
            });

            if (this.paginationStyle === 'numbered') {
                document.addEventListener('click', function (e) {
                    const link = e.target.closest('#ytrip-pagination a.page-numbers');
                    if (!link) return;
                    e.preventDefault();
                    const page = self.extractPage(link.getAttribute('href'));
                    self.currentPage = page;
                    self.loadTours(false);
                    self.scrollToContainer();
                });
            }

            if (this.paginationStyle === 'loadmore') {
                const loadmoreBtn = byId('ytrip-loadmore-btn');
                if (loadmoreBtn) {
                    loadmoreBtn.addEventListener('click', function () {
                        if (self.currentPage < self.maxPages) {
                            self.currentPage += 1;
                            self.loadTours(true);
                        }
                    });
                }
            }

            if (this.paginationStyle === 'infinite') {
                window.addEventListener('scroll', self._boundCheckInfinite, { passive: true });
            }

            const filterToggle = qs('.ytrip-filter-toggle');
            if (filterToggle) {
                const filterBar = byId('ytrip-filter-bar');
                if (filterBar) {
                    filterToggle.addEventListener('click', function () {
                        self.toggleFilterBar(filterBar);
                    });
                }
            }

            qsAll('.ytrip-date-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const mode = this.dataset.mode;
                    qsAll('.ytrip-date-tab').forEach(function (t) { t.classList.remove('active'); });
                    this.classList.add('active');

                    const singleBlock = byId('ytrip-date-single');
                    const rangeBlock = byId('ytrip-date-range');
                    const dateFrom = byId('date_from');
                    const dateTo = byId('date_to');
                    const rangeDisplay = byId('ytrip-date-range-display');
                    const rangeCalendar = byId('ytrip-date-range-calendar');

                    if (mode === 'single') {
                        if (singleBlock) singleBlock.style.display = '';
                        if (rangeBlock) rangeBlock.style.display = 'none';
                        if (dateFrom) dateFrom.value = '';
                        if (dateTo) dateTo.value = '';
                        if (rangeDisplay) rangeDisplay.value = '';
                        if (rangeCalendar) rangeCalendar.classList.remove('active');
                    } else {
                        if (singleBlock) singleBlock.style.display = 'none';
                        if (rangeBlock) rangeBlock.style.display = '';
                        const tourDate = byId('tour_date');
                        if (tourDate) tourDate.value = '';
                        const singleDisplay = byId('ytrip-date-single-display');
                        const singleCalendar = byId('ytrip-date-single-calendar');
                        if (singleDisplay) singleDisplay.value = '';
                        if (singleCalendar) singleCalendar.classList.remove('active');
                    }
                });
            });

            this.getFormElements().forEach(function (form) {
                qsAll('.ytrip-date-input', form).forEach(function (input) {
                    input.addEventListener('change', function () {
                        if (this.id === 'date_from') {
                            const to = byId('date_to');
                            if (to) to.setAttribute('min', this.value);
                        }
                        self.currentPage = 1;
                        self.loadTours(false);
                    });
                });
            });

            qsAll('.ytrip-quick-date').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const days = parseInt(this.dataset.days, 10) || 0;
                    const today = new Date();
                    const endDate = new Date();
                    endDate.setDate(today.getDate() + days);
                    const formatDate = function (d) {
                        return d.toISOString().split('T')[0];
                    };

                    const rangeTab = qs('.ytrip-date-tab[data-mode="range"]');
                    if (rangeTab) rangeTab.click();

                    const dateFrom = byId('date_from');
                    const dateTo = byId('date_to');
                    if (dateFrom) dateFrom.value = formatDate(today);
                    if (dateTo) dateTo.value = formatDate(endDate);

                    self.updateDateRangeDisplay();
                    qsAll('.ytrip-quick-date').forEach(function (b) { b.classList.remove('active'); });
                    this.classList.add('active');
                    self.currentPage = 1;
                    self.loadTours(false);
                });
            });

            this.initDateFilterFromURL();

            const rangeTab = qs('.ytrip-date-tab[data-mode="range"]');
            const singleBlock = byId('ytrip-date-single');
            const rangeBlock = byId('ytrip-date-range');
            if (rangeTab && rangeTab.classList.contains('active')) {
                if (singleBlock) singleBlock.style.display = 'none';
                if (rangeBlock) rangeBlock.style.display = '';
            } else {
                if (rangeBlock) rangeBlock.style.display = 'none';
                if (singleBlock) singleBlock.style.display = '';
            }

            this.initRangeCalendar();
            this.initSingleDateCalendar();
        }

        toggleFilterBar(filterBar) {
            const isHidden = filterBar.style.display === 'none';
            filterBar.style.display = isHidden ? '' : 'none';
        }

        initDateFilterFromURL() {
            const url = new URL(window.location.href);
            const tourDate = url.searchParams.get('tour_date');
            const dateFrom = url.searchParams.get('date_from');
            const dateTo = url.searchParams.get('date_to');

            if (dateFrom && dateTo) {
                qsAll('.ytrip-date-tab').forEach(function (t) { t.classList.remove('active'); });
                const rangeTab = qs('.ytrip-date-tab[data-mode="range"]');
                if (rangeTab) rangeTab.classList.add('active');
                const singleBlock = byId('ytrip-date-single');
                const rangeBlock = byId('ytrip-date-range');
                if (singleBlock) singleBlock.style.display = 'none';
                if (rangeBlock) rangeBlock.style.display = '';
                const fromInput = byId('date_from');
                const toInput = byId('date_to');
                if (fromInput) fromInput.value = dateFrom;
                if (toInput) toInput.value = dateTo;
                this.updateDateRangeDisplay();
            } else if (tourDate) {
                qsAll('.ytrip-date-tab').forEach(function (t) { t.classList.remove('active'); });
                const singleTab = qs('.ytrip-date-tab[data-mode="single"]');
                if (singleTab) singleTab.classList.add('active');
                const rangeBlock = byId('ytrip-date-range');
                const singleBlock = byId('ytrip-date-single');
                if (rangeBlock) rangeBlock.style.display = 'none';
                if (singleBlock) singleBlock.style.display = '';
            }
        }

        updateDateRangeDisplay() {
            const display = byId('ytrip-date-range-display');
            if (!display) return;
            const fromInput = byId('date_from');
            const toInput = byId('date_to');
            const from = fromInput ? fromInput.value : '';
            const to = toInput ? toInput.value : '';
            if (!from && !to) {
                display.value = '';
                return;
            }
            // Compact format "24 Feb – 25 Feb 2026" to avoid truncation in narrow inputs
            const fmt = function (iso) {
                const d = new Date(iso + 'T00:00:00');
                const day = d.getDate();
                const mon = d.toLocaleDateString('en-US', { month: 'short' });
                const year = d.getFullYear();
                return day + ' ' + mon + ' ' + year;
            };
            if (from && to) {
                const d1 = new Date(from + 'T00:00:00');
                const d2 = new Date(to + 'T00:00:00');
                const sameYear = d1.getFullYear() === d2.getFullYear();
                const part1 = d1.getDate() + ' ' + d1.toLocaleDateString('en-US', { month: 'short' });
                const part2 = d2.getDate() + ' ' + d2.toLocaleDateString('en-US', { month: 'short' });
                display.value = sameYear ? (part1 + ' – ' + part2 + ' ' + d2.getFullYear()) : (fmt(from) + ' – ' + fmt(to));
            } else if (from) {
                display.value = fmt(from);
            }
        }

        initRangeCalendar() {
            const self = this;
            const display = byId('ytrip-date-range-display');
            const calendarEl = byId('ytrip-date-range-calendar');
            const dateFromInput = byId('date_from');
            const dateToInput = byId('date_to');
            if (!display || !calendarEl) return;

            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];

            let currentMonth = new Date().getMonth();
            let currentYear = new Date().getFullYear();
            let selectingFrom = true;

            function formatYmd(d) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + day;
            }

            function getFromTo() {
                const from = dateFromInput && dateFromInput.value ? new Date(dateFromInput.value + 'T00:00:00') : null;
                const to = dateToInput && dateToInput.value ? new Date(dateToInput.value + 'T00:00:00') : null;
                return { from: from, to: to };
            }

            function openCalendar() {
                calendarEl.classList.add('active');
                display.setAttribute('aria-expanded', 'true');
                renderCalendar(currentMonth, currentYear);
            }

            function closeCalendar() {
                calendarEl.classList.remove('active');
                display.setAttribute('aria-expanded', 'false');
            }

            display.addEventListener('click', function (e) {
                e.stopPropagation();
                if (calendarEl.classList.contains('active')) {
                    closeCalendar();
                } else {
                    openCalendar();
                }
            });

            display.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (calendarEl.classList.contains('active')) closeCalendar();
                    else openCalendar();
                }
                if (e.key === 'Escape') closeCalendar();
            });

            document.addEventListener('click', function (e) {
                if (!calendarEl.contains(e.target) && e.target !== display) {
                    closeCalendar();
                }
            });

            const selfRef = self;
            function renderCalendar(month, year) {
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const { from: selFrom, to: selTo } = getFromTo();

                const stepHint = selectingFrom ? '1. Select start date' : '2. Select end date';
                let html = '<div class="ytrip-calendar-header">' +
                    '<button type="button" class="ytrip-cal-prev" aria-label="Previous month">' +
                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></button>' +
                    '<span class="ytrip-cal-month" id="ytrip-range-cal-title">' + monthNames[month] + ' ' + year + '</span>' +
                    '<button type="button" class="ytrip-cal-next" aria-label="Next month">' +
                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></button>' +
                    '</div>' +
                    '<p class="ytrip-cal-range-step" aria-live="polite">' + stepHint + '</p>' +
                    '<div class="ytrip-calendar-overlay" id="ytrip-range-cal-overlay" style="display:none;"></div>' +
                    '<div class="ytrip-calendar-grid" id="ytrip-range-cal-grid">' +
                    '<div class="ytrip-cal-day-name">Su</div><div class="ytrip-cal-day-name">Mo</div><div class="ytrip-cal-day-name">Tu</div><div class="ytrip-cal-day-name">We</div>' +
                    '<div class="ytrip-cal-day-name">Th</div><div class="ytrip-cal-day-name">Fr</div><div class="ytrip-cal-day-name">Sa</div>';

                for (let i = 0; i < firstDay; i++) {
                    html += '<div class="ytrip-cal-empty"></div>';
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const isPast = date < today;
                    const ymd = formatYmd(date);
                    let isSelected = false;
                    let inRange = false;
                    if (selFrom && date.getTime() === selFrom.getTime()) isSelected = true;
                    if (selTo && date.getTime() === selTo.getTime()) isSelected = true;
                    if (selFrom && selTo) {
                        const t = date.getTime();
                        if (t > selFrom.getTime() && t < selTo.getTime()) inRange = true;
                    }
                    const classes = ['ytrip-cal-date'];
                    if (isPast) classes.push('disabled');
                    if (isSelected) classes.push('selected');
                    if (inRange) classes.push('in-range');
                    if (date.toDateString() === today.toDateString()) classes.push('today');
                    html += '<button type="button" class="' + classes.join(' ') + '" data-date="' + ymd + '">' + day + '</button>';
                }
                html += '</div>';
                calendarEl.innerHTML = html;

                const prevBtn = calendarEl.querySelector('.ytrip-cal-prev');
                const nextBtn = calendarEl.querySelector('.ytrip-cal-next');
                if (prevBtn) {
                    prevBtn.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        if (currentMonth === 0) { currentMonth = 11; currentYear -= 1; } else { currentMonth -= 1; }
                        renderCalendar(currentMonth, currentYear);
                    });
                }
                if (nextBtn) {
                    nextBtn.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        if (currentMonth === 11) { currentMonth = 0; currentYear += 1; } else { currentMonth += 1; }
                        renderCalendar(currentMonth, currentYear);
                    });
                }

                const titleEl = byId('ytrip-range-cal-title');
                const overlayEl = byId('ytrip-range-cal-overlay');
                if (titleEl && overlayEl) {
                    titleEl.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        if (overlayEl.style.display === 'none' || overlayEl.style.display === '') {
                            let oh = '';
                            const now = new Date();
                            const tM = now.getMonth();
                            const tY = now.getFullYear();
                            for (let i = 0; i < 12; i++) {
                                const m = (tM + i) % 12;
                                const y = tY + Math.floor((tM + i) / 12);
                                const isCur = (m === currentMonth && y === currentYear);
                                oh += '<div class="ytrip-cal-overlay-item' + (isCur ? ' active' : '') + '" data-m="' + m + '" data-y="' + y + '">' + monthNames[m] + (y !== tY ? ' ' + y : '') + '</div>';
                            }
                            overlayEl.innerHTML = oh;
                            overlayEl.style.display = 'grid';
                            overlayEl.querySelectorAll('.ytrip-cal-overlay-item').forEach(function (item) {
                                item.addEventListener('click', function (e) {
                                    e.stopPropagation();
                                    currentMonth = parseInt(item.getAttribute('data-m'), 10);
                                    currentYear = parseInt(item.getAttribute('data-y'), 10);
                                    renderCalendar(currentMonth, currentYear);
                                });
                            });
                        } else {
                            overlayEl.style.display = 'none';
                        }
                    });
                }

                calendarEl.querySelectorAll('.ytrip-cal-date:not(.disabled)').forEach(function (btn) {
                    btn.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        const dateStr = this.getAttribute('data-date');
                        if (!dateStr) return;

                        if (selectingFrom) {
                            if (dateFromInput) dateFromInput.value = dateStr;
                            if (dateToInput) dateToInput.value = '';
                            selectingFrom = false;
                            selfRef.updateDateRangeDisplay();
                            return;
                        }

                        const fromStr = dateFromInput ? dateFromInput.value : '';
                        if (!fromStr) {
                            if (dateFromInput) dateFromInput.value = dateStr;
                            if (dateToInput) dateToInput.value = dateStr;
                        } else {
                            const fromDate = new Date(fromStr + 'T00:00:00');
                            const toDate = new Date(dateStr + 'T00:00:00');
                            if (toDate.getTime() < fromDate.getTime()) {
                                if (dateFromInput) dateFromInput.value = dateStr;
                                if (dateToInput) dateToInput.value = fromStr;
                            } else {
                                if (dateToInput) dateToInput.value = dateStr;
                            }
                        }
                        selectingFrom = true;
                        selfRef.updateDateRangeDisplay();
                        closeCalendar();
                        selfRef.currentPage = 1;
                        selfRef.loadTours(false);
                    });
                });
            }

            const fromVal = dateFromInput && dateFromInput.value;
            const toVal = dateToInput && dateToInput.value;
            if (fromVal && toVal) selectingFrom = true;
            else if (fromVal) selectingFrom = false;
            else selectingFrom = true;
        }

        initSingleDateCalendar() {
            const self = this;
            const display = byId('ytrip-date-single-display');
            const calendarEl = byId('ytrip-date-single-calendar');
            const tourDateInput = byId('tour_date');
            if (!display || !calendarEl) return;

            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];

            let currentMonth = new Date().getMonth();
            let currentYear = new Date().getFullYear();

            function formatYmd(d) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + day;
            }

            function openCalendar() {
                calendarEl.classList.add('active');
                display.setAttribute('aria-expanded', 'true');
                renderCalendar(currentMonth, currentYear);
            }

            function closeCalendar() {
                calendarEl.classList.remove('active');
                display.setAttribute('aria-expanded', 'false');
            }

            display.addEventListener('click', function (e) {
                e.stopPropagation();
                if (calendarEl.classList.contains('active')) closeCalendar();
                else openCalendar();
            });

            display.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (calendarEl.classList.contains('active')) closeCalendar();
                    else openCalendar();
                }
                if (e.key === 'Escape') closeCalendar();
            });

            document.addEventListener('click', function (e) {
                if (!calendarEl.contains(e.target) && e.target !== display) closeCalendar();
            });

            function renderCalendar(month, year) {
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const selectedYmd = tourDateInput && tourDateInput.value ? tourDateInput.value : null;

                let html = '<div class="ytrip-calendar-header">' +
                    '<button type="button" class="ytrip-cal-prev" aria-label="Previous month">' +
                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></button>' +
                    '<span class="ytrip-cal-month" id="ytrip-single-cal-title">' + monthNames[month] + ' ' + year + '</span>' +
                    '<button type="button" class="ytrip-cal-next" aria-label="Next month">' +
                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></button>' +
                    '</div>' +
                    '<div class="ytrip-calendar-overlay" id="ytrip-single-cal-overlay" style="display:none;"></div>' +
                    '<div class="ytrip-calendar-grid" id="ytrip-single-cal-grid">' +
                    '<div class="ytrip-cal-day-name">Su</div><div class="ytrip-cal-day-name">Mo</div><div class="ytrip-cal-day-name">Tu</div><div class="ytrip-cal-day-name">We</div>' +
                    '<div class="ytrip-cal-day-name">Th</div><div class="ytrip-cal-day-name">Fr</div><div class="ytrip-cal-day-name">Sa</div>';

                for (let i = 0; i < firstDay; i++) {
                    html += '<div class="ytrip-cal-empty"></div>';
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const isPast = date < today;
                    const ymd = formatYmd(date);
                    const isSelected = selectedYmd && ymd === selectedYmd;
                    const classes = ['ytrip-cal-date'];
                    if (isPast) classes.push('disabled');
                    if (isSelected) classes.push('selected');
                    if (date.toDateString() === today.toDateString()) classes.push('today');
                    html += '<button type="button" class="' + classes.join(' ') + '" data-date="' + ymd + '">' + day + '</button>';
                }
                html += '</div>';
                calendarEl.innerHTML = html;

                const prevBtn = calendarEl.querySelector('.ytrip-cal-prev');
                const nextBtn = calendarEl.querySelector('.ytrip-cal-next');
                if (prevBtn) {
                    prevBtn.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        if (currentMonth === 0) { currentMonth = 11; currentYear -= 1; } else { currentMonth -= 1; }
                        renderCalendar(currentMonth, currentYear);
                    });
                }
                if (nextBtn) {
                    nextBtn.addEventListener('click', function (ev) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        if (currentMonth === 11) { currentMonth = 0; currentYear += 1; } else { currentMonth += 1; }
                        renderCalendar(currentMonth, currentYear);
                    });
                }

                const titleEl = byId('ytrip-single-cal-title');
                const overlayEl = byId('ytrip-single-cal-overlay');
                if (titleEl && overlayEl) {
                    titleEl.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        if (overlayEl.style.display === 'none' || overlayEl.style.display === '') {
                            let oh = '';
                            const now = new Date();
                            const tM = now.getMonth();
                            const tY = now.getFullYear();
                            for (let i = 0; i < 12; i++) {
                                const m = (tM + i) % 12;
                                const y = tY + Math.floor((tM + i) / 12);
                                const isCur = (m === currentMonth && y === currentYear);
                                oh += '<div class="ytrip-cal-overlay-item' + (isCur ? ' active' : '') + '" data-m="' + m + '" data-y="' + y + '">' + monthNames[m] + (y !== tY ? ' ' + y : '') + '</div>';
                            }
                            overlayEl.innerHTML = oh;
                            overlayEl.style.display = 'grid';
                            overlayEl.querySelectorAll('.ytrip-cal-overlay-item').forEach(function (item) {
                                item.addEventListener('click', function (e) {
                                    e.stopPropagation();
                                    currentMonth = parseInt(item.getAttribute('data-m'), 10);
                                    currentYear = parseInt(item.getAttribute('data-y'), 10);
                                    renderCalendar(currentMonth, currentYear);
                                });
                            });
                        } else {
                            overlayEl.style.display = 'none';
                        }
                    });
                }

                calendarEl.querySelectorAll('.ytrip-cal-date:not(.disabled)').forEach(function (btn) {
                    btn.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        const dateStr = this.getAttribute('data-date');
                        if (!dateStr) return;
                        if (tourDateInput) tourDateInput.value = dateStr;
                        const d = new Date(dateStr + 'T00:00:00');
                        display.value = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        closeCalendar();
                        self.currentPage = 1;
                        self.loadTours(false);
                    });
                });
            }

            const tourDateVal = tourDateInput && tourDateInput.value;
            if (tourDateVal) {
                const d = new Date(tourDateVal + 'T00:00:00');
                display.value = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            }
        }

        checkInfiniteScroll() {
            if (this.isLoading || this.currentPage >= this.maxPages) return;
            const trigger = byId('ytrip-infinite-trigger');
            if (!trigger) return;
            const rect = trigger.getBoundingClientRect();
            const triggerTop = rect.top + window.scrollY;
            const scrollBottom = window.scrollY + window.innerHeight;
            if (scrollBottom >= triggerTop - SCROLL_OFFSET_PX) {
                this.currentPage += 1;
                this.loadTours(true);
            }
        }

        scrollToContainer() {
            if (!this.container) return;
            const top = this.container.getBoundingClientRect().top + window.scrollY - SCROLL_TOP_OFFSET_PX;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        }

        setView(view) {
            qsAll('.ytrip-view-toggle__btn').forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            this.container.classList.remove('ytrip-view-grid', 'ytrip-view-list');
            this.container.classList.add('ytrip-view-' + view);
            this.updateURL('view', view);
            this.currentPage = 1;
            this.loadTours(false);
        }

        setColumns(cols) {
            qsAll('.ytrip-columns-selector__btn').forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.cols === cols);
            });
            this.container.classList.remove('ytrip-cols-2', 'ytrip-cols-3', 'ytrip-cols-4', 'ytrip-cols-5');
            this.container.classList.add('ytrip-cols-' + cols);
            this.updateURL('cols', cols);
        }

        getFormData() {
            const data = {};
            const activeForm = this.getActiveForm();
            if (!activeForm) return data;

            const rangeBlock = qs('#ytrip-date-range, .ytrip-date-filter__range', activeForm);
            const singleBlock = qs('#ytrip-date-single, .ytrip-date-filter__single', activeForm);
            const isRangeMode = rangeBlock && isVisible(rangeBlock) && (!singleBlock || !isVisible(singleBlock));

            qsAll('input, select', activeForm).forEach(function (el) {
                const name = el.getAttribute('name');
                if (!name) return;
                if (name === 'tour_date' && isRangeMode) return;
                if ((name === 'date_from' || name === 'date_to') && !isRangeMode) return;
                if (el.type === 'radio' || el.type === 'checkbox') {
                    if (!el.checked) return;
                }
                data[name] = el.value || '';
            });
            return data;
        }

        loadTours(append) {
            const self = this;
            const activeViewBtn = qs('.ytrip-view-toggle__btn.active');
            const view = (activeViewBtn && activeViewBtn.dataset.view) ? activeViewBtn.dataset.view : DEFAULT_VIEW;
            const sortEl = byId('ytrip-sort');
            const orderby = (sortEl && sortEl.value) ? sortEl.value : DEFAULT_ORDERBY;

            if (typeof ytrip_vars === 'undefined') {
                return;
            }

            this.isLoading = true;
            this.showLoading(append);

            const formData = this.getFormData();
            const payload = {
                action: 'ytrip_filter_tours',
                nonce: ytrip_vars.nonce,
                page: this.currentPage,
                view: view,
                orderby: orderby
            };
            Object.keys(formData).forEach(function (key) {
                payload[key] = formData[key];
            });

            const separator = ytrip_vars.ajax_url.indexOf('?') !== -1 ? '&' : '?';
            const ajaxUrl = ytrip_vars.ajax_url + separator +
                'action=ytrip_filter_tours&nonce=' + encodeURIComponent(ytrip_vars.nonce);

            const body = new URLSearchParams(payload).toString();

            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body
            })
                .then(function (res) { return res.json(); })
                .then(function (response) {
                    if (response.success) {
                        if (append) {
                            self.container.insertAdjacentHTML('beforeend', response.data.html);
                        } else {
                            self.container.innerHTML = response.data.html;
                        }
                        self.maxPages = response.data.max_pages;
                        self.updateResultsCount(response.data.found_posts);
                        const hasResults = (response.data.found_posts || 0) > 0;
                        const showPagination = hasResults && (response.data.max_pages || 1) > 1;
                        if (self.paginationWrapper) {
                            self.paginationWrapper.style.display = showPagination ? '' : 'none';
                        }
                        self.updatePaginationUI();
                        self.updateURLState();
                    } else {
                        if (response.data && response.data.message) {
                            alert(response.data.message);
                        }
                    }
                    self.isLoading = false;
                    self.hideLoading();
                })
                .catch(function (err) {
                    self.isLoading = false;
                    self.hideLoading();
                    if (err && err.message) {
                        alert(err.message);
                    }
                });
        }

        updatePaginationUI() {
            if (this.paginationStyle === 'loadmore') {
                const wrap = byId('ytrip-loadmore-wrap');
                if (wrap) {
                    wrap.style.display = this.currentPage >= this.maxPages ? 'none' : '';
                }
            }

            if (this.paginationStyle === 'infinite') {
                const loadingEl = qs('.ytrip-infinite-loading');
                if (loadingEl) loadingEl.style.display = 'none';
                const trigger = byId('ytrip-infinite-trigger');
                if (trigger && this.currentPage >= this.maxPages && !qs('.ytrip-all-loaded', trigger)) {
                    const p = document.createElement('p');
                    p.className = 'ytrip-all-loaded';
                    p.textContent = 'All tours loaded';
                    trigger.appendChild(p);
                }
            }

            if (this.paginationWrapper) {
                this.paginationWrapper.dataset.currentPage = String(this.currentPage);
                this.paginationWrapper.dataset.maxPages = String(this.maxPages);
            }
        }

        clearFilters() {
            if (typeof ytrip_vars !== 'undefined' && ytrip_vars.archive_url) {
                window.location.href = ytrip_vars.archive_url;
                return;
            }

            this.getFormElements().forEach(function (form) {
                qsAll('input[type="number"]', form).forEach(function (el) { el.value = ''; });
                qsAll('input[type="date"]', form).forEach(function (el) { el.value = ''; });
                qsAll('select', form).forEach(function (el) { el.value = ''; });
                qsAll('input[name="duration"]', form).forEach(function (el) { el.checked = false; });
                const durationAny = qs('input[name="duration"][value=""]', form);
                if (durationAny) durationAny.checked = true;
                const durationSelect = qs('select[name="duration"]', form);
                if (durationSelect) durationSelect.value = '';
                qsAll('input[name="rating"]', form).forEach(function (el) { el.checked = false; });
                const ratingAny = qs('input[name="rating"][value=""]', form);
                if (ratingAny) ratingAny.checked = true;
                const ratingSelect = qs('select[name="rating"]', form);
                if (ratingSelect) ratingSelect.value = '';
            });

            const sortEl = byId('ytrip-sort');
            if (sortEl) sortEl.value = DEFAULT_ORDERBY;

            qsAll('.ytrip-date-tab').forEach(function (t) { t.classList.remove('active'); });
            const singleTab = qs('.ytrip-date-tab[data-mode="single"]');
            if (singleTab) singleTab.classList.add('active');

            const rangeBlock = byId('ytrip-date-range');
            const singleBlock = byId('ytrip-date-single');
            if (rangeBlock) rangeBlock.style.display = 'none';
            if (singleBlock) singleBlock.style.display = '';

            const dateFrom = byId('date_from');
            const dateTo = byId('date_to');
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            const rangeDisplay = byId('ytrip-date-range-display');
            if (rangeDisplay) rangeDisplay.value = '';
            const rangeCalendar = byId('ytrip-date-range-calendar');
            if (rangeCalendar) rangeCalendar.classList.remove('active');
            const tourDate = byId('tour_date');
            if (tourDate) tourDate.value = '';
            const singleDisplay = byId('ytrip-date-single-display');
            if (singleDisplay) singleDisplay.value = '';
            const singleCalendar = byId('ytrip-date-single-calendar');
            if (singleCalendar) singleCalendar.classList.remove('active');
            qsAll('.ytrip-quick-date').forEach(function (b) { b.classList.remove('active'); });

            this.currentPage = 1;
            const url = new URL(window.location.href);
            url.search = '';
            window.history.replaceState({}, '', url);
            this.loadTours(false);
        }

        updateResultsCount(count) {
            const countEl = qs('.ytrip-archive-toolbar__count');
            if (!countEl) return;
            const label = count === 1 ? 'Tour' : 'Tours';
            const numEl = qs('.count-number', countEl);
            const labelEl = qs('.count-label', countEl);
            if (numEl && labelEl) {
                numEl.textContent = String(count);
                labelEl.textContent = label;
            } else {
                countEl.innerHTML = '<span class="count-number">' + count + '</span> <span class="count-label">' + label + '</span>';
            }
        }

        updateURL(key, value) {
            const url = new URL(window.location.href);
            if (value) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
            window.history.replaceState({}, '', url);
        }

        updateURLState() {
            const url = new URL(window.location.href);
            const formData = this.getFormData();

            Object.keys(formData).forEach(function (key) {
                if (formData[key]) {
                    url.searchParams.set(key, formData[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });

            const sortEl = byId('ytrip-sort');
            const orderby = (sortEl && sortEl.value) ? sortEl.value : DEFAULT_ORDERBY;
            if (orderby && orderby !== DEFAULT_ORDERBY) {
                url.searchParams.set('orderby', orderby);
            } else {
                url.searchParams.delete('orderby');
            }

            if (this.currentPage > 1) {
                url.searchParams.set('paged', String(this.currentPage));
            } else {
                url.searchParams.delete('paged');
            }

            window.history.pushState({}, '', url);
        }

        updateURLFromState() {
            const url = new URL(window.location.href);
            const forms = this.getFormElements();

            url.searchParams.forEach(function (value, key) {
                forms.forEach(function (form) {
                    const field = form.querySelector('[name="' + key + '"]');
                    if (field) {
                        if (field.type === 'radio' || field.type === 'checkbox') {
                            field.checked = (field.value === value);
                        } else {
                            field.value = value;
                        }
                    }
                });
            });

            const orderby = url.searchParams.get('orderby');
            const sortEl = byId('ytrip-sort');
            if (sortEl && orderby) sortEl.value = orderby;

            const view = url.searchParams.get('view');
            if (view) {
                qsAll('.ytrip-view-toggle__btn').forEach(function (btn) {
                    btn.classList.toggle('active', btn.dataset.view === view);
                });
                this.container.classList.remove('ytrip-view-grid', 'ytrip-view-list');
                this.container.classList.add('ytrip-view-' + view);
            }

            const cols = url.searchParams.get('cols');
            if (cols) {
                qsAll('.ytrip-columns-selector__btn').forEach(function (btn) {
                    btn.classList.toggle('active', btn.dataset.cols === cols);
                });
                this.container.classList.remove('ytrip-cols-2', 'ytrip-cols-3', 'ytrip-cols-4', 'ytrip-cols-5');
                this.container.classList.add('ytrip-cols-' + cols);
            }

            const paged = url.searchParams.get('paged');
            if (paged) {
                this.currentPage = parseInt(paged, 10) || 1;
            }
        }

        extractPage(url) {
            if (!url) return 1;
            const matchPage = String(url).match(/\/page\/(\d+)/);
            if (matchPage) return parseInt(matchPage[1], 10);
            try {
                const urlObj = new URL(url, window.location.origin);
                const paged = urlObj.searchParams.get('paged');
                return paged ? parseInt(paged, 10) : 1;
            } catch (e) {
                return 1;
            }
        }

        showLoading(append) {
            if (append) {
                if (this.paginationStyle === 'loadmore') {
                    const textEl = qs('#ytrip-loadmore-btn .ytrip-loadmore-text');
                    const spinnerEl = qs('#ytrip-loadmore-btn .ytrip-loadmore-spinner');
                    if (textEl) textEl.style.display = 'none';
                    if (spinnerEl) spinnerEl.style.display = '';
                } else if (this.paginationStyle === 'infinite') {
                    const loadingEl = qs('.ytrip-infinite-loading');
                    if (loadingEl) loadingEl.style.display = '';
                }
            } else {
                if (typeof ytrip_vars !== 'undefined' && ytrip_vars.enable_skeleton && ytrip_vars.skeleton_html) {
                    const skeletons = (ytrip_vars.skeleton_html).repeat(SKELETON_CARD_COUNT);
                    this.container.innerHTML = skeletons;
                } else {
                    this.container.style.opacity = '0.5';
                    if (this.loading) this.loading.style.display = '';
                }
            }
        }

        hideLoading() {
            this.container.style.opacity = '1';
            if (this.loading) this.loading.style.display = 'none';

            const loadmoreText = qs('#ytrip-loadmore-btn .ytrip-loadmore-text');
            const loadmoreSpinner = qs('#ytrip-loadmore-btn .ytrip-loadmore-spinner');
            if (loadmoreText) loadmoreText.style.display = '';
            if (loadmoreSpinner) loadmoreSpinner.style.display = 'none';

            const infiniteLoading = qs('.ytrip-infinite-loading');
            if (infiniteLoading) infiniteLoading.style.display = 'none';
        }
    }

    function run() {
        const app = new YTripArchiveFilters();
        app.init();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
