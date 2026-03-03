/**
 * YTrip Shared Calendar Component
 * Single-date and date-range picker. Used on booking form and homepage search.
 * Edit this file to change calendar UI/UX everywhere.
 *
 * @package YTrip
 */
(function () {
    'use strict';

    var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    function byId(id) {
        return document.getElementById(id);
    }

    function initSingleCalendar(wrapper) {
        var displayId = wrapper.getAttribute('data-display-id');
        var hiddenId = wrapper.getAttribute('data-hidden-id');
        var containerId = wrapper.getAttribute('data-container-id');
        if (!displayId || !hiddenId || !containerId) return;

        var dateDisplay = byId(displayId);
        var dateInput = byId(hiddenId);
        var calendarContainer = byId(containerId);
        if (!dateDisplay || !dateInput || !calendarContainer) return;

        var currentDate = new Date();
        var currentMonth = currentDate.getMonth();
        var currentYear = currentDate.getFullYear();
        var selectedDate = null;

        dateDisplay.addEventListener('click', function (e) {
            e.stopPropagation();
            calendarContainer.classList.toggle('active');
            if (calendarContainer.classList.contains('active')) {
                renderCalendar(currentMonth, currentYear);
            }
        });

        document.addEventListener('click', function (e) {
            if (!calendarContainer.contains(e.target) && e.target !== dateDisplay) {
                calendarContainer.classList.remove('active');
            }
        });

        function renderCalendar(month, year) {
            var firstDay = new Date(year, month, 1).getDay();
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            var html = '<div class="ytrip-calendar-header">' +
                '<button type="button" class="ytrip-cal-prev" aria-label="Previous month">' +
                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></button>' +
                '<span class="ytrip-cal-month">' + monthNames[month] + ' ' + year + '</span>' +
                '<button type="button" class="ytrip-cal-next" aria-label="Next month">' +
                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></button>' +
                '</div>' +
                '<div class="ytrip-calendar-overlay" style="display:none;"></div>' +
                '<div class="ytrip-calendar-grid">' +
                '<div class="ytrip-cal-day-name">Su</div><div class="ytrip-cal-day-name">Mo</div><div class="ytrip-cal-day-name">Tu</div>' +
                '<div class="ytrip-cal-day-name">We</div><div class="ytrip-cal-day-name">Th</div><div class="ytrip-cal-day-name">Fr</div><div class="ytrip-cal-day-name">Sa</div>';

            for (var i = 0; i < firstDay; i++) {
                html += '<div class="ytrip-cal-empty"></div>';
            }
            for (var day = 1; day <= daysInMonth; day++) {
                var date = new Date(year, month, day);
                var isPast = date < today;
                var isSelected = selectedDate && date.getDate() === selectedDate.getDate() &&
                    date.getMonth() === selectedDate.getMonth() && date.getFullYear() === selectedDate.getFullYear();
                var ymd = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var classes = ['ytrip-cal-date'];
                if (isPast) classes.push('disabled');
                if (isSelected) classes.push('selected');
                if (date.toDateString() === today.toDateString()) classes.push('today');
                html += '<button type="button" class="' + classes.join(' ') + '" data-date="' + ymd + '">' + day + '</button>';
            }
            html += '</div>';
            calendarContainer.innerHTML = html;

            var prevBtn = calendarContainer.querySelector('.ytrip-cal-prev');
            var nextBtn = calendarContainer.querySelector('.ytrip-cal-next');
            if (prevBtn) {
                prevBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    currentMonth = currentMonth - 1;
                    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
                    renderCalendar(currentMonth, currentYear);
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    currentMonth = currentMonth + 1;
                    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
                    renderCalendar(currentMonth, currentYear);
                });
            }

            var titleEl = calendarContainer.querySelector('.ytrip-cal-month');
            var overlayEl = calendarContainer.querySelector('.ytrip-calendar-overlay');
            if (titleEl && overlayEl) {
                titleEl.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (overlayEl.style.display === 'none' || overlayEl.style.display === '') {
                        var oh = '';
                        var now = new Date();
                        var tM = now.getMonth();
                        var tY = now.getFullYear();
                        for (var i = 0; i < 12; i++) {
                            var m = (tM + i) % 12;
                            var y = tY + Math.floor((tM + i) / 12);
                            var isCur = (m === currentMonth && y === currentYear);
                            oh += '<div class="ytrip-cal-overlay-item' + (isCur ? ' active' : '') + '" data-m="' + m + '" data-y="' + y + '">' + monthNames[m] + (y !== tY ? ' ' + y : '') + '</div>';
                        }
                        overlayEl.innerHTML = oh;
                        overlayEl.style.display = 'grid';
                        overlayEl.querySelectorAll('.ytrip-cal-overlay-item').forEach(function (item) {
                            item.addEventListener('click', function (ev) {
                                ev.stopPropagation();
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

            calendarContainer.querySelectorAll('.ytrip-cal-date:not(.disabled)').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var dateStr = this.getAttribute('data-date');
                    selectedDate = new Date(dateStr + 'T00:00:00');
                    dateInput.value = dateStr;
                    dateDisplay.value = selectedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    calendarContainer.classList.remove('active');
                    dateInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }
    }

    function fmtDate(iso) {
        if (!iso) return '';
        var d = new Date(iso + 'T00:00:00');
        return d.getDate() + ' ' + d.toLocaleDateString('en-US', { month: 'short' }) + ' ' + d.getFullYear();
    }

    function updateRangeDisplay(displayEl, fromInput, toInput, toDisplayEl) {
        var from = fromInput && fromInput.value ? fromInput.value : '';
        var to = toInput && toInput.value ? toInput.value : '';
        if (toDisplayEl) {
            if (displayEl) displayEl.value = fmtDate(from);
            toDisplayEl.value = fmtDate(to);
            return;
        }
        if (!displayEl) return;
        if (!from && !to) {
            displayEl.value = '';
            return;
        }
        if (from && to) {
            var d1 = new Date(from + 'T00:00:00');
            var d2 = new Date(to + 'T00:00:00');
            var sameYear = d1.getFullYear() === d2.getFullYear();
            var part1 = d1.getDate() + ' ' + d1.toLocaleDateString('en-US', { month: 'short' });
            var part2 = d2.getDate() + ' ' + d2.toLocaleDateString('en-US', { month: 'short' });
            displayEl.value = sameYear ? (part1 + ' – ' + part2 + ' ' + d2.getFullYear()) : (fmtDate(from) + ' – ' + fmtDate(to));
        } else {
            displayEl.value = fmtDate(from);
        }
    }

    function initRangeCalendar(wrapper) {
        var displayId = wrapper.getAttribute('data-display-id');
        var fromDisplayId = wrapper.getAttribute('data-from-display-id');
        var toDisplayId = wrapper.getAttribute('data-to-display-id');
        var fromId = wrapper.getAttribute('data-from-id');
        var toId = wrapper.getAttribute('data-to-id');
        var containerId = wrapper.getAttribute('data-container-id');
        if (!fromId || !toId || !containerId) return;

        var dateFromInput = byId(fromId);
        var dateToInput = byId(toId);
        var calendarEl = byId(containerId);
        if (!calendarEl) return;

        var display = byId(displayId);
        var fromDisplay = fromDisplayId ? byId(fromDisplayId) : null;
        var toDisplay = toDisplayId ? byId(toDisplayId) : null;
        var primaryDisplay = fromDisplay || display;
        if (!primaryDisplay) return;

        var currentMonth = new Date().getMonth();
        var currentYear = new Date().getFullYear();
        var selectingFrom = true;

        function getFromTo() {
            var from = dateFromInput && dateFromInput.value ? new Date(dateFromInput.value + 'T00:00:00') : null;
            var to = dateToInput && dateToInput.value ? new Date(dateToInput.value + 'T00:00:00') : null;
            return { from: from, to: to };
        }

        function closeCalendar() {
            calendarEl.classList.remove('active');
            if (primaryDisplay) primaryDisplay.setAttribute('aria-expanded', 'false');
            if (toDisplay) toDisplay.setAttribute('aria-expanded', 'false');
        }

        var inSearchForm = wrapper.closest && wrapper.closest('.ytrip-search');
        function positionCalendarPanel() {
            if (!inSearchForm || !primaryDisplay) return;
            var rect = primaryDisplay.getBoundingClientRect();
            calendarEl.style.position = 'fixed';
            calendarEl.style.left = rect.left + 'px';
            calendarEl.style.top = (rect.bottom + 6) + 'px';
            calendarEl.style.minWidth = Math.max(rect.width, 280) + 'px';
        }

        function openCalendar() {
            if (calendarEl.classList.contains('active')) {
                closeCalendar();
            } else {
                if (inSearchForm) positionCalendarPanel();
                calendarEl.classList.add('active');
                if (primaryDisplay) primaryDisplay.setAttribute('aria-expanded', 'true');
                if (toDisplay) toDisplay.setAttribute('aria-expanded', 'true');
                renderCalendar(currentMonth, currentYear);
            }
        }

        function isDisplayOrIcon(target) {
            return target === primaryDisplay || target === toDisplay || (iconEl && iconEl.contains(target));
        }

        if (primaryDisplay) {
            primaryDisplay.addEventListener('click', function (e) {
                e.stopPropagation();
                openCalendar();
            });
        }
        if (toDisplay && toDisplay !== primaryDisplay) {
            toDisplay.addEventListener('click', function (e) {
                e.stopPropagation();
                openCalendar();
            });
        }

        var iconEl = wrapper.querySelector('.ytrip-date-range-calendar-icon');
        if (iconEl) {
            iconEl.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                openCalendar();
            });
        }

        document.addEventListener('click', function (e) {
            if (!calendarEl.contains(e.target) && !isDisplayOrIcon(e.target)) {
                closeCalendar();
            }
        });

        function renderCalendar(month, year) {
            var firstDay = new Date(year, month, 1).getDay();
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var sel = getFromTo();

            var stepHint = selectingFrom ? '1. Select start date' : '2. Select end date';
            var html = '<div class="ytrip-calendar-header">' +
                '<button type="button" class="ytrip-cal-prev" aria-label="Previous month">' +
                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></button>' +
                '<span class="ytrip-cal-month">' + monthNames[month] + ' ' + year + '</span>' +
                '<button type="button" class="ytrip-cal-next" aria-label="Next month">' +
                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></button>' +
                '</div>' +
                '<p class="ytrip-cal-range-step" aria-live="polite">' + stepHint + '</p>' +
                '<div class="ytrip-calendar-overlay" style="display:none;"></div>' +
                '<div class="ytrip-calendar-grid">' +
                '<div class="ytrip-cal-day-name">Su</div><div class="ytrip-cal-day-name">Mo</div><div class="ytrip-cal-day-name">Tu</div><div class="ytrip-cal-day-name">We</div>' +
                '<div class="ytrip-cal-day-name">Th</div><div class="ytrip-cal-day-name">Fr</div><div class="ytrip-cal-day-name">Sa</div>';

            for (var i = 0; i < firstDay; i++) {
                html += '<div class="ytrip-cal-empty"></div>';
            }
            for (var day = 1; day <= daysInMonth; day++) {
                var date = new Date(year, month, day);
                var isPast = date < today;
                var ymd = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                var isSelected = false;
                var inRange = false;
                if (sel.from && date.getTime() === sel.from.getTime()) isSelected = true;
                if (sel.to && date.getTime() === sel.to.getTime()) isSelected = true;
                if (sel.from && sel.to) {
                    var t = date.getTime();
                    if (t > sel.from.getTime() && t < sel.to.getTime()) inRange = true;
                }
                var classes = ['ytrip-cal-date'];
                if (isPast) classes.push('disabled');
                if (isSelected) classes.push('selected');
                if (inRange) classes.push('in-range');
                if (date.toDateString() === today.toDateString()) classes.push('today');
                html += '<button type="button" class="' + classes.join(' ') + '" data-date="' + ymd + '">' + day + '</button>';
            }
            html += '</div>';
            calendarEl.innerHTML = html;

            var prevBtn = calendarEl.querySelector('.ytrip-cal-prev');
            var nextBtn = calendarEl.querySelector('.ytrip-cal-next');
            if (prevBtn) {
                prevBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (currentMonth === 0) { currentMonth = 11; currentYear--; } else { currentMonth--; }
                    renderCalendar(currentMonth, currentYear);
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (currentMonth === 11) { currentMonth = 0; currentYear++; } else { currentMonth++; }
                    renderCalendar(currentMonth, currentYear);
                });
            }

            var titleEl = calendarEl.querySelector('.ytrip-cal-month');
            var overlayEl = calendarEl.querySelector('.ytrip-calendar-overlay');
            if (titleEl && overlayEl) {
                titleEl.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (overlayEl.style.display === 'none' || overlayEl.style.display === '') {
                        var oh = '';
                        var now = new Date();
                        var tM = now.getMonth();
                        var tY = now.getFullYear();
                        for (var i = 0; i < 12; i++) {
                            var m = (tM + i) % 12;
                            var y = tY + Math.floor((tM + i) / 12);
                            var isCur = (m === currentMonth && y === currentYear);
                            oh += '<div class="ytrip-cal-overlay-item' + (isCur ? ' active' : '') + '" data-m="' + m + '" data-y="' + y + '">' + monthNames[m] + (y !== tY ? ' ' + y : '') + '</div>';
                        }
                        overlayEl.innerHTML = oh;
                        overlayEl.style.display = 'grid';
                        overlayEl.querySelectorAll('.ytrip-cal-overlay-item').forEach(function (item) {
                            item.addEventListener('click', function (ev) {
                                ev.stopPropagation();
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
                btn.addEventListener('click', function () {
                    var dateStr = this.getAttribute('data-date');
                    if (!dateStr) return;

                    if (selectingFrom) {
                        if (dateFromInput) dateFromInput.value = dateStr;
                        if (dateToInput) dateToInput.value = '';
                        selectingFrom = false;
                        updateRangeDisplay(primaryDisplay, dateFromInput, dateToInput, toDisplay);
                        return;
                    }
                    var fromStr = dateFromInput ? dateFromInput.value : '';
                    if (!fromStr) {
                        if (dateFromInput) dateFromInput.value = dateStr;
                        if (dateToInput) dateToInput.value = dateStr;
                    } else {
                        var fromDate = new Date(fromStr + 'T00:00:00');
                        var toDate = new Date(dateStr + 'T00:00:00');
                        if (toDate.getTime() < fromDate.getTime()) {
                            if (dateFromInput) dateFromInput.value = dateStr;
                            if (dateToInput) dateToInput.value = fromStr;
                        } else {
                            if (dateToInput) dateToInput.value = dateStr;
                        }
                    }
                    selectingFrom = true;
                    updateRangeDisplay(primaryDisplay, dateFromInput, dateToInput, toDisplay);
                    closeCalendar();
                    if (dateFromInput) dateFromInput.dispatchEvent(new Event('change', { bubbles: true }));
                    if (dateToInput) dateToInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        var fromVal = dateFromInput && dateFromInput.value;
        var toVal = dateToInput && dateToInput.value;
        if (fromVal && toVal) selectingFrom = true;
        else if (fromVal) selectingFrom = false;
        else selectingFrom = true;
        updateRangeDisplay(primaryDisplay, dateFromInput, dateToInput, toDisplay);
    }

    function init() {
        document.querySelectorAll('[data-ytrip-calendar="single"]').forEach(initSingleCalendar);
        document.querySelectorAll('[data-ytrip-calendar="range"]').forEach(initRangeCalendar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
