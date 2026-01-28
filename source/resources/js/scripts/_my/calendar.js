var Calendar = (function (Calendar) {
    var tabHolder = '#student-history',
        calendarEl = document.getElementById('calendar'),
        eventToUpdate,
        sidebar = $('.event-sidebar'),
        calendarsColor = {
            AUTH: 'primary',
            LMS: 'success',
            OTHER: 'info',
        },
        eventForm = $('.event-form'),
        addEventBtn = $('.add-event-btn'),
        cancelBtn = $('.btn-cancel'),
        updateEventBtn = $('.update-event-btn'),
        toggleSidebarBtn = $('.btn-toggle-sidebar'),
        eventTitle = $('#title'),
        eventLabel = $('#select-label'),
        startDate = $('#start-date'),
        endDate = $('#end-date'),
        eventUrl = $('#event-url'),
        eventGuests = $('#event-guests'),
        eventLocation = $('#event-location'),
        allDaySwitch = $('.allDay-switch'),
        selectAll = $('.select-all'),
        calEventFilter = $('.calendar-events-filter'),
        filterInput = $('.input-filter'),
        btnDeleteEvent = $('.btn-delete-event'),
        calendarEditor = $('#event-description-editor'),
        student_id = 0;
    Calendar.init = (tab, student) => {
        tabHolder = tab;
        student_id = student;
        // console.log('init calendar', tabHolder);
        // document.addEventListener('DOMContentLoaded', function() {
        // console.log('Calendar rendering');

        const _timezone =
            '"' + $('meta[name="_timezone"]').attr('content') + '"';
        // console.log(_timezone);
        let calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'listWeek', //'dayGridMonth',
            timeZone: _timezone,
            events: Calendar.fetchEvents,
            editable: true,
            dragScroll: true,
            dayMaxEvents: 2,
            // moreLinkClick:'day',
            eventResizableFromStart: true,
            headerToolbar: {
                start: 'sidebarToggle, prev,next, title',
                end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
            },
            initialDate: new Date(),
            navLinks: true, // can click day/week names to navigate views
            eventClassNames: function ({ event: calendarEvent }) {
                const colorName =
                    calendarsColor[calendarEvent._def.extendedProps.calendar];

                return [
                    // Background Color
                    'bg-light-' + colorName,
                ];
            },
            // dateClick: function(info) {
            //     Calendar.dateClick(info);
            // },
            // eventClick: function(info) {
            //     Calendar.eventClick(info);
            // },
            eventDidMount: function (info) {
                var showPopover = new bootstrap.Popover(info.el, {
                    title: info.event._def.title,
                    content: info.event._def.extendedProps.description
                        ? info.event._def.extendedProps.description.toString()
                        : '',
                    trigger: 'click',
                    placement: 'top',
                    container: 'body',
                    html: true,
                });
            },
        });
        calendar.render();
        // console.log('Calendar rendered');
        // });
    };

    Calendar.fetchEvents = (info, successCallback) => {
        // console.log('info', info);
        // console.log('successCallback', successCallback);
        if (student_id === 0) {
            // console.log('student id not set', student_id);
            return false;
        }
        axios
            .post('/api/v1/student/history/' + student_id, {
                start: info.start.toISOString(),
                end: info.end.toISOString(),
            })
            .then(response => {
                const res = response.data;
                const historyData = res.data;
                // console.log(res, historyData);
                $(tabHolder + ' > .spinner-border').hide();

                successCallback(historyData);
            })
            .catch(error => {
                $(tabHolder + ' > .spinner-border').hide();
                // console.log(error);
            });
    };
    Calendar.dateClick = info => {
        // console.log(info);
    };
    Calendar.eventClick = info => {
        // console.log(info);
        let details = info.event._def.extendedProps.details;
        // console.log(details);
        if (details) {
            // console.log('alert');
            // sweetAlert(details);
            var showPopover = new bootstrap.Popover(info.el, {
                title: info.event._def.title,
                content: details.toString(),
                trigger: 'click',
                placement: 'top',
                container: 'body',
                html: true,
            });
        }
    };
    return Calendar;
})(Calendar || {});

(function (window, undefined) {
    'use strict';
})(window);
