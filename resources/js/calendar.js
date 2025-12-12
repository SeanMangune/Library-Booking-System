import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

// Expose plugins globally
window.FullCalendar = Calendar;
window.FullCalendarPlugins = {
    dayGrid: dayGridPlugin,
    timeGrid: timeGridPlugin,
    list: listPlugin,
    interaction: interactionPlugin
};

/**
 * Initialize the calendar
 * @param { Object } config - Configuration object with optional callbacks
 * @param { Function } config.onEventClick - Handler for event clicks
 * @param { Function } config.onDateClick - Handler for date clicks
 * @param { Function } config.onDatesSet - Handler for date range changes
 * @param { Function } config.onSelect - Handler for date range selections
 * @param { boolean } config.editable - Enable drag/drop and resizing (default: false)
 * @param { boolean } config.selectable - Enable date selection (default: true)
 * @returns { Object } Calendar instance
 */
export function initCalendar(config = {}) {
    const calendarEl= document.querySelector('#calendar');
    if (!calendarEl) {
        console.warn('No #calendar element found. Please add a div with id="calendar" to your page.');
        return null;
    }

    // Get initial events from window (injected by Blade template)
    const initialEvents = Array.isArray(window.initialEvents) ? window.initialEvents : [];

    const calendar = new Calendar(calendarEl, {
        plugins: [
            window.FullCalendarPlugins.dayGrid,
            window.FullCalendarPlugins.timeGrid,
            window.FullCalendarPlugins.list,
            window.FullCalendarPlugins.interaction
        ],

        // Initial view and display settings
        initialView: 'dayGridMonth',
        dayMaxEvents: true,
        nowIndicator: true,

        // Header toolbar configuration
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth, timeGridWeek, listMonth'
        },

        // Interaction settings
        editable: config.editable || false,
        selectable: config.selectable !== undefined ? config.selectable : true,
        selectMirror: true,

        // Event styling based on status
        eventClassName: function(arg) {
            const status = arg.event.extendedProps.status || 'pending';
            const classNames = [`booking-status-${ status }`];

            // Add additional classes for past events
            if(arg.isPast) {
                classNames.push('past-event');
            }

            return classNames;
        },

        // Event content customization
        eventContent: function(arg) {
            const event = arg.event;
            const timeText = arg.timeText;
            const title = event.title;
            const status = event.extendedProps.status;
            const roomName = event.extendedProps.roomName;

            // Create custom HTML for event display
            let html = '<div class="fc-event-main-frame">';
            if (timeText) {
                html += `<div class="fc-event-time">${ timeText }</div>`;
            }
            html += `<div class="fc-event-title-container">`;
            html += `<div class="fc-event-title">${ title }</div>`;
            if (roomName) {
                html += `<div class="fc-event-room">${ roomName }</div>`;
            }
            html += `<div></div>`;

            return { html: html };
        },

        // Event click handler
        eventClick: config.onEventClick || function(info) {
            console.log('Event clicked:', info.event);

            const bookingData = {
                id: info.event.id,
                title: info.event.title,
                start: info.event.start,
                end: info.event.end,
                status: info.event.extendedProps.status,
                userName: info.event.extendedProps.userName,
                userEmail: info.event.extendedProps.userEmail,
                roomName: info.event.extendedProps.roomName,
                description: info.event.extendedProps.description,
                allDay: info.event.allDay
            };

            // Dispatch custom event for modal/detail view
            window.dispatchEvent(new CustomEvent('calendar-event-clicked', { 
                detail: { booking:  bookingData } 
            }));
        },

        // Date range change handler
        datesSet: function(info) {
            console.log(`Calendar dates set: ${info.start. toISOString()} to ${info.end.toISOString()}`);
            refreshEvents(info.start, info.end);
            if (config.onDatesSet) config.onDatesSet(info);
        },

        // Date click handler
        dateClick:  config.onDateClick || function(info) {
            console.log('Date clicked:', info.dateStr);
            window.dispatchEvent(new CustomEvent('calendar-date-clicked', { 
                detail: { 
                    date: info.dateStr,
                    allDay: info. allDay,
                    jsEvent: info.jsEvent
                } 
            }));
        },

        // Date selection handler
        select: config.onSelect || function(info) {
            console.log('Date range selected:', info.startStr, 'to', info.endStr);
            window.dispatchEvent(new CustomEvent('calendar-date-range-selected', {
                detail: {
                    start:  info.startStr,
                    end: info.endStr,
                    allDay: info.allDay
                }
            }));
            
            // Clear selection after dispatching event
            calendar.unselect();
        },

        // Time format
        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
            meridiem: 'short'
        },

        // Slot time format for week/day views
        slotLabelFormat: {
            hour:  'numeric',
            minute:  '2-digit',
            meridiem: 'short'
        },

        // Business hours (adjust as needed for your library)
        businessHours: {
            daysOfWeek:  [1, 2, 3, 4, 5, 6], // Monday - Saturday
            startTime: '08:00',
            endTime: '20:00'
        },

        // Slot duration for week/day views
        slotDuration: '00:30:00',
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',

        // Height settings
        height: 'auto',
        contentHeight: 'auto',

        // Loading indicator
        loading: function(isLoading) {
            const loadingEl = document.querySelector('#calendar-loading');
            if (loadingEl) {
                loadingEl.style.display = isLoading ? 'block' : 'none';
            }
        },

        // Initial events
        events: initialEvents
    });

    calendar.render();

    /**
     * Refresh events from the server for a given date range
     */
    async function refreshEvents(startDate, endDate) {
        const start = startDate.toISOString().split('T')[0];
        const end = endDate.toISOString().split('T')[0];

        console.log('Refreshing events for range:', start, 'to', end);

        try {
            const url = new URL(window.location.origin + '/events');
            url.searchParams. set('start', start);
            url.searchParams.set('end', end);

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!response.ok) {
                throw new Error(`Failed to load events: ${response.statusText}`);
            }

            const events = await response.json();
            console.log('Events loaded:', events.length);

            // Remove all existing events
            calendar.removeAllEvents();

            // Add new events
            calendar.addEventSource(events);

            console.log('Calendar events refreshed successfully');
        } catch (error) {
            console.error('Error loading events:', error);
            showNotification('Failed to load calendar events.  Please refresh the page.', 'error');
        }
    }

    /**
     * Add a new event to the calendar
     */
    function addEvent(eventData) {
        try {
            calendar.addEvent(eventData);
            console.log('Event added:', eventData);
        } catch (error) {
            console.error('Error adding event:', error);
        }
    }

    /**
     * Remove an event from the calendar
     */
    function removeEvent(eventId) {
        const event = calendar.getEventById(eventId);
        if (event) {
            event.remove();
            console.log('Event removed:', eventId);
        } else {
            console.warn('Event not found:', eventId);
        }
    }

    /**
     * Update an existing event
     */
    function updateEvent(eventId, updates) {
        const event = calendar.getEventById(eventId);
        if (event) {
            if (updates.title) event.setProp('title', updates.title);
            if (updates.start) event.setStart(updates.start);
            if (updates.end) event.setEnd(updates. end);
            if (updates. allDay !== undefined) event.setAllDay(updates.allDay);
            if (updates.extendedProps) {
                for (const [key, value] of Object.entries(updates.extendedProps)) {
                    event.setExtendedProp(key, value);
                }
            }
            console.log('Event updated:', eventId);
        } else {
            console.warn('Event not found:', eventId);
        }
    }

    /**
     * Go to a specific date
     */
    function gotoDate(date) {
        calendar.gotoDate(date);
    }

    /**
     * Change calendar view
     */
    function changeView(viewName) {
        calendar. changeView(viewName);
    }

    /**
     * Get current view info
     */
    function getCurrentView() {
        return {
            type: calendar.view.type,
            title: calendar.view.title,
            start: calendar.view.currentStart,
            end: calendar. view.currentEnd
        };
    }

    /**
     * Navigate to today
     */
    function goToToday() {
        calendar. today();
    }

    /**
     * Navigate to next period
     */
    function goToNext() {
        calendar.next();
    }

    /**
     * Navigate to previous period
     */
    function goToPrev() {
        calendar.prev();
    }

    // Expose calendar instance and methods globally
    window.calendar = calendar;
    window.refreshEvents = refreshEvents;
    window.addCalendarEvent = addEvent;
    window.removeCalendarEvent = removeEvent;
    window.updateCalendarEvent = updateEvent;
    window.gotoCalendarDate = gotoDate;
    window.changeCalendarView = changeView;
    window.getCurrentCalendarView = getCurrentView;
    window.goToToday = goToToday;
    window. goToNext = goToNext;
    window.goToPrev = goToPrev;

    return calendar;
}

/**
 * Helper function to show notifications
 */
function showNotification(message, type = 'info') {
    console.log(`[${type.toUpperCase()}] ${message}`);
    
    // Dispatch event for custom notification handlers
    window. dispatchEvent(new CustomEvent('show-notification', {
        detail: { message, type }
    }));
}

/**
 * Utility function to format event data for FullCalendar
 */
export function formatEventData(booking) {
    return {
        id: booking.id,
        title: booking.title || `Booking #${booking.id}`,
        start: booking.start_time || booking.start,
        end: booking.end_time || booking. end,
        allDay: booking.all_day || false,
        backgroundColor: getStatusColor(booking.status),
        borderColor: getStatusColor(booking. status),
        extendedProps: {
            status: booking.status || 'pending',
            userName: booking.user_name || booking.userName,
            userEmail: booking. user_email || booking.userEmail,
            roomName: booking. room_name || booking.roomName,
            description: booking.description || ''
        }
    };
}

/**
 * Get color based on booking status
 */
function getStatusColor(status) {
    const colors = {
        'pending':  '#fbbf24',      // Yellow
        'confirmed': '#10b981',    // Green
        'cancelled': '#ef4444',    // Red
        'completed':  '#6b7280',    // Gray
        'rejected': '#dc2626'      // Dark Red
    };
    return colors[status] || colors['pending'];
}

// Export the initialization function as default
export default initCalendar;
