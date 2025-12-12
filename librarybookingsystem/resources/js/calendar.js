import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

// Expose plugins (optional)
window.FullCalendar = Calendar;
window.FullCalendarPlugins = {
    dayGrid: dayGridPlugin,
    timeGrid: timeGridPlugin,
    list: listPlugin,
    interaction: interactionPlugin
};

/**
 * Initialize the calendar.
 * - config: optional callbacks ( onEventClick, onDateClick, onDatesSet)
 * 
 * This function sets window.calendar and window.refreshEvents so external code can call them
 */
export function initCalendar(config = {}) {
    const calendarEl = document.querySelector('#calendar');
    if (!calendarEl) {
        console.warn('No #calendar element found');
        return null;
    }

    // initialEvents should be injected by Blade into window.initialEvents
    const initialEvents = Array.isArray(window.initialEvents) ? window.initialEvents : [];

    const calendar = new Calendar(calendarEl, {
        plugins: [
            window.FullCalendarPlugins.dayGrid,
            window.FullCalendarPlugins.timeGrid,
            window.FullCalendarPlugins.list,
            window.FullCalendarPlugins.interaction
        ],
        initialView: 'dayGridMonth',
        dayMaxEvents: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth, timeGridMonth, listMonth'
        },
        editable: false,

        eventClick: config.onEventClick || ((info) => {
            console.log('Event clicked:', info.event.id);
            // default behavior: dispatch a custom event to let other UI handle it
            window.dispatchEvent(new CustomEvent('calendar-event-clicked', { detail: { bookingId: info.event.id } }));
        }),

        dateSet: (info) => {
            console.log(`Dates set: ${info.start} to ${info.end}`);
            // refresh events for this range
            refreshEvents(info.start, info.end);
            if (config.onDatesSet) config.onDatesSet(info);
        },

        dateClick: config.onDateClick || ((info) => {
            console.log('Date clicked:', info.dateStr);
            window.dispatchEvent(new CustomEvent('calendar-date-clicked', { detail: { date: info.dateStr } }));
        }),

        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
            meridiem: 'short'
        },

        events: initialEvents
    });

    calendar.render();

    async function refreshEvents(startDate, endDate) {
        const start = startDate.toISOString().split('T')[0];
        const end = endDate.toISOString().split('T')[0];

        console.log('Refreshing events for range:', start, 'to', end);

        try {
            const url = new URL(window.location.origin + '/events');
            url.searchParams.set('start', start);
            url.searchParams.set('end', end);

            const res = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                console.error('Failed to load events', res.statusText);
                return;
            }

            const items = await res.json();
            console.log('Items loaded:', items.length, items);

            calendar.removeAllEvents();

            // Add the returned events
            calendar.addEventSource(items);

            console.log('Calendar events refreshed for range:', start, 'to', end);
        } catch (err) {
            console.error('Error loading events:', err);
        }
    }

    // Return the calendar instance if caller wants it
    return calendar;
}

// Optionally auto-init if you want:
// document.addEventListener('DOMContentLoaded', () => initCalendar());