/**
 * YTrip Admin Reports JavaScript
 *
 * @package YTrip
 * @since 1.2.0
 */

(function ($) {
    'use strict';

    var Charts = {};
    var Calendar = {
        currentDate: new Date()
    };

    /**
     * Initialize bookings chart.
     */
    function initBookingsChart() {
        var canvas = document.getElementById('ytrip-bookings-chart');
        if (!canvas || !window.Chart) {
            return;
        }

        var ctx = canvas.getContext('2d');
        var data = ytripReports.chartData || [];

        var labels = data.map(function (item) {
            var date = new Date(item.date);
            return date.toLocaleDateString('en', { month: 'short', day: 'numeric' });
        });

        var values = data.map(function (item) {
            return item.count;
        });

        Charts.bookings = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Bookings',
                    data: values,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#2271b1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1d2327',
                        titleFont: { weight: 'normal' },
                        bodyFont: { weight: '600' },
                        padding: 12,
                        cornerRadius: 6
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 10
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f1f1'
                        },
                        ticks: {
                            precision: 0
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    /**
     * Initialize revenue chart.
     */
    function initRevenueChart() {
        var canvas = document.getElementById('ytrip-revenue-chart');
        if (!canvas || !window.Chart) {
            return;
        }

        // Revenue chart would require separate data - placeholder for now
        var ctx = canvas.getContext('2d');

        Charts.revenue = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue',
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: '#2271b1',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f1f1'
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize calendar.
     */
    function initCalendar() {
        var $calendar = $('#ytrip-booking-calendar');
        if (!$calendar.length) {
            return;
        }

        renderCalendar();

        // Navigation
        $('#ytrip-calendar-prev').on('click', function () {
            Calendar.currentDate.setMonth(Calendar.currentDate.getMonth() - 1);
            renderCalendar();
        });

        $('#ytrip-calendar-next').on('click', function () {
            Calendar.currentDate.setMonth(Calendar.currentDate.getMonth() + 1);
            renderCalendar();
        });
    }

    /**
     * Render calendar.
     */
    function renderCalendar() {
        var $calendar = $('#ytrip-booking-calendar');
        var $title = $('#ytrip-calendar-title');

        var year = Calendar.currentDate.getFullYear();
        var month = Calendar.currentDate.getMonth();

        $title.text(Calendar.currentDate.toLocaleDateString('en', { month: 'long', year: 'numeric' }));

        var firstDay = new Date(year, month, 1);
        var lastDay = new Date(year, month + 1, 0);
        var startDay = firstDay.getDay();
        var daysInMonth = lastDay.getDate();

        var html = '<div class="ytrip-calendar-grid">';

        // Day headers
        var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        days.forEach(function (day) {
            html += '<div class="ytrip-calendar-day-header">' + day + '</div>';
        });

        // Previous month days
        var prevMonth = new Date(year, month, 0);
        var prevDays = prevMonth.getDate();

        for (var i = startDay - 1; i >= 0; i--) {
            html += '<div class="ytrip-calendar-day other-month">';
            html += '<span class="ytrip-calendar-date">' + (prevDays - i) + '</span>';
            html += '</div>';
        }

        // Current month days
        var today = new Date();
        for (var day = 1; day <= daysInMonth; day++) {
            var isToday = today.getFullYear() === year &&
                today.getMonth() === month &&
                today.getDate() === day;

            html += '<div class="ytrip-calendar-day' + (isToday ? ' today' : '') + '" data-date="' + year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0') + '">';
            html += '<span class="ytrip-calendar-date">' + day + '</span>';
            html += '<div class="ytrip-calendar-events"></div>';
            html += '</div>';
        }

        // Next month days
        var totalCells = startDay + daysInMonth;
        var remaining = 7 - (totalCells % 7);
        if (remaining < 7) {
            for (var i = 1; i <= remaining; i++) {
                html += '<div class="ytrip-calendar-day other-month">';
                html += '<span class="ytrip-calendar-date">' + i + '</span>';
                html += '</div>';
            }
        }

        html += '</div>';
        $calendar.html(html);

        // Load events
        loadCalendarEvents(year, month);
    }

    /**
     * Load calendar events.
     */
    function loadCalendarEvents(year, month) {
        var startDate = year + '-' + String(month + 1).padStart(2, '0') + '-01';
        var endDate = year + '-' + String(month + 1).padStart(2, '0') + '-31';

        $.ajax({
            url: ytripReports.ajaxurl,
            type: 'POST',
            data: {
                action: 'ytrip_get_calendar_events',
                security: ytripReports.nonce,
                start: startDate,
                end: endDate
            },
            success: function (response) {
                if (response.success && response.data) {
                    response.data.forEach(function (event) {
                        var $day = $('[data-date="' + event.date + '"] .ytrip-calendar-events');
                        if ($day.length) {
                            $day.append('<div class="ytrip-calendar-event" title="' + event.title + '">' + event.title + '</div>');
                        }
                    });
                }
            }
        });
    }

    /**
     * Initialize on document ready.
     */
    $(document).ready(function () {
        initBookingsChart();
        initRevenueChart();
        initCalendar();
    });

})(jQuery);
