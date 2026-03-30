import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

const FULLCALENDAR_PLUGINS = [
    dayGridPlugin,
    timeGridPlugin,
    listPlugin,
    interactionPlugin,
];

window.FullCalendar = window.FullCalendar || {};
window.FullCalendar.Calendar = Calendar;
window.FullCalendar.dayGridPlugin = dayGridPlugin;
window.FullCalendar.timeGridPlugin = timeGridPlugin;
window.FullCalendar.listPlugin = listPlugin;
window.FullCalendar.interactionPlugin = interactionPlugin;

window.FullCalendarPlugins = {
    dayGrid: dayGridPlugin,
    timeGrid: timeGridPlugin,
    list: listPlugin,
    interaction: interactionPlugin,
};

function todayDateString() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function buildUrl(base, params) {
    const url = new URL(base, window.location.origin);
    Object.keys(params).forEach(key => {
        if (params[key] !== undefined && params[key] !== null) {
            url.searchParams.append(key, params[key]);
        }
    });
    return url.toString();
}

async function performQcIdVerification(file, userName, verifyUrl) {
    const reader = new FileReader();
    const base64Promise = new Promise((resolve) => {
        reader.onload = () => resolve(reader.result);
    });
    reader.readAsDataURL(file);
    const base64Image = await base64Promise;

    const response = await fetch(verifyUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({
            image: base64Image,
            user_name: userName,
        }),
    });

    const payload = await response.json();
    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'ID verification failed.');
    }
    return payload;
}



function formatClockValue(value) {
    if (!value) {
        return '';
    }

    const parts = String(value).split(':');
    if (parts.length < 2) {
        return String(value);
    }

    const hour = parseInt(parts[0], 10);
    const minute = parseInt(parts[1], 10);
    if (Number.isNaN(hour) || Number.isNaN(minute)) {
        return String(value);
    }

    const date = new Date();
    date.setHours(hour, minute, 0, 0);

    return date.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function formatRange(startValue, endValue) {
    const start = formatClockValue(startValue);
    const end = formatClockValue(endValue);

    if (start && end) {
        return `${start} - ${end}`;
    }

    return start || end || '';
}

const BOOKING_OPEN_HOUR = 8;
const BOOKING_CLOSE_HOUR = 17;
const BOOKING_DATE_RANGE_DAYS = 90;

function hourToTimeValue(hour) {
    return `${String(hour).padStart(2, '0')}:00`;
}

function buildBookingTimeSlots(startHour = BOOKING_OPEN_HOUR, endHour = BOOKING_CLOSE_HOUR) {
    const slots = [];

    for (let hour = startHour; hour < endHour; hour += 1) {
        const start = hourToTimeValue(hour);
        const end = hourToTimeValue(hour + 1);

        slots.push({
            value: `${start}-${end}`,
            start_time: start,
            end_time: end,
            label: formatRange(start, end),
        });
    }

    return slots;
}

const BOOKING_TIME_SLOTS = buildBookingTimeSlots();

function filterPastTimeSlots(slots, selectedDate) {
    const todayStr = todayDateString();
    if (selectedDate !== todayStr) {
        return slots;
    }

    const now = new Date();
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();

    return slots.filter((slot) => {
        const startMinutes = parseTimeToMinutes(slot.start_time);
        // Allow slot if it starts at least 15 minutes from now
        return startMinutes !== null && startMinutes > ((currentHour * 60) + currentMinute + 15);
    });
}

function resolveBookingTimeSlot(slotValue) {
    if (slotValue) {
        const match = BOOKING_TIME_SLOTS.find((slot) => slot.value === slotValue);
        if (match) {
            return match;
        }
    }

    return BOOKING_TIME_SLOTS[0];
}

function applyBookingTimeSlot(bookingForm, slotValue) {
    const slot = resolveBookingTimeSlot(slotValue);

    if (bookingForm.time_slot !== slot.value) {
        bookingForm.time_slot = slot.value;
    }

    if (bookingForm.start_time !== slot.start_time) {
        bookingForm.start_time = slot.start_time;
    }

    if (bookingForm.end_time !== slot.end_time) {
        bookingForm.end_time = slot.end_time;
    }
}

function formatBookingDateLabel(dateValue) {
    const date = new Date(`${dateValue}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return dateValue;
    }

    return date.toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function buildBookingDateOptions(daysAhead = BOOKING_DATE_RANGE_DAYS) {
    const options = [];
    // Always get the local date at the time of function call
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate()); // local midnight

    for (let dayOffset = 0; dayOffset <= daysAhead; dayOffset += 1) {
        const date = new Date(today);
        date.setDate(today.getDate() + dayOffset);

        // Skip Sunday - Removed to allow same day booking if it's Sunday
        // if (date.getDay() === 0) continue;

        const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        options.push({
            value,
            label: formatBookingDateLabel(value),
        });
    }

    // Remove any past dates (shouldn't be present, but extra safety)
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    return options.filter(opt => opt.value >= todayStr);
}

function ensureBookingDateOption(options, dateValue) {
    if (!dateValue || !/^\d{4}-\d{2}-\d{2}$/.test(String(dateValue))) {
        return;
    }

    if (options.some((option) => option.value === dateValue)) {
        return;
    }

    options.push({
        value: dateValue,
        label: formatBookingDateLabel(dateValue),
    });

    options.sort((first, second) => first.value.localeCompare(second.value));
}

function parseTimeToMinutes(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const normalized = String(value).trim();
    if (!normalized) {
        return null;
    }

    const timePart = normalized.includes('T')
        ? normalized.split('T')[1]
        : normalized;
    const match = timePart.match(/(\d{1,2}):(\d{2})/);

    if (!match) {
        return null;
    }

    return (Number(match[1]) * 60) + Number(match[2]);
}

function toBookingRange(startValue, endValue) {
    const start = parseTimeToMinutes(startValue);

    if (start === null) {
        return null;
    }

    const endMinutes = parseTimeToMinutes(endValue);

    return {
        start,
        end: endMinutes !== null && endMinutes > start ? endMinutes : start + 60,
    };
}

function hasInclusiveTimeConflict(existingRange, candidateRange) {
    return (
        (existingRange.start >= candidateRange.start && existingRange.start <= candidateRange.end)
        || (existingRange.end >= candidateRange.start && existingRange.end <= candidateRange.end)
        || (existingRange.start <= candidateRange.start && existingRange.end >= candidateRange.end)
    );
}

function normalizeEventRanges(events) {
    if (!Array.isArray(events)) {
        return [];
    }

    return events
        .map((event) => toBookingRange(
            event?.start || event?.start_time || event?.extendedProps?.start_time,
            event?.end || event?.end_time || event?.extendedProps?.end_time,
        ))
        .filter(Boolean);
}

function slotConflictsWithRanges(slotValue, ranges) {
    const slot = resolveBookingTimeSlot(slotValue);
    const slotRange = toBookingRange(slot?.start_time, slot?.end_time);

    if (!slotRange) {
        return false;
    }

    return ranges.some((range) => hasInclusiveTimeConflict(range, slotRange));
}

function buildNearbyAvailableTimeSuggestions(slotValue, events, limit = 4) {
    const selectedSlot = resolveBookingTimeSlot(slotValue);
    const selectedRange = toBookingRange(selectedSlot?.start_time, selectedSlot?.end_time);

    if (!selectedSlot || !selectedRange) {
        return [];
    }

    const occupiedRanges = normalizeEventRanges(events);

    return BOOKING_TIME_SLOTS
        .filter((slot) => slot.value !== selectedSlot.value)
        .filter((slot) => !slotConflictsWithRanges(slot.value, occupiedRanges))
        .map((slot) => {
            const slotRange = toBookingRange(slot.start_time, slot.end_time);

            return {
                ...slot,
                distance: Math.abs((slotRange?.start ?? 0) - selectedRange.start),
            };
        })
        .sort((first, second) => first.distance - second.distance || first.start_time.localeCompare(second.start_time))
        .slice(0, limit)
        .map(({ distance, ...slot }) => slot);
}

function hasConflictError(response, payload) {
    const message = String(payload?.message || '').toLowerCase();
    const errorsText = payload?.errors && typeof payload.errors === 'object'
        ? Object.values(payload.errors).flat().join(' ').toLowerCase()
        : '';

    return (response?.status === 422 || response?.status === 409)
        && (message.includes('conflict') || errorsText.includes('conflict'));
}

function mapEventFromCalendarInfo(context, info) {
    const props = info.event.extendedProps || {};
    const derivedDate = props.formatted_date || props.date || context.formatDate(info.event.start);

    let derivedTime = props.formatted_time;
    if (!derivedTime && info.event.start && info.event.end) {
        const start = `${String(info.event.start.getHours()).padStart(2, '0')}:${String(info.event.start.getMinutes()).padStart(2, '0')}`;
        const end = `${String(info.event.end.getHours()).padStart(2, '0')}:${String(info.event.end.getMinutes()).padStart(2, '0')}`;
        derivedTime = context.formatTimeRange(start, end);
    }

    return {
        id: info.event.id,
        title: info.event.title,
        purpose: props.purpose || info.event.title,
        room_name: props.room_name || props.room || props.roomName,
        date: derivedDate,
        formatted_date: props.formatted_date || derivedDate,
        formatted_time: derivedTime || '',
        user_name: props.user_name || props.userName,
        attendees: props.attendees,
        status: props.status,
        description: props.description,
    };
}

/**
 * Generic FullCalendar initializer used by lightweight pages.
 */
export function initCalendar(config = {}) {
    const calendarEl = document.querySelector(config.selector || '#calendar');
    if (!calendarEl) {
        console.warn('No calendar element found.');
        return null;
    }

    const initialEvents = Array.isArray(config.initialEvents)
        ? config.initialEvents
        : (Array.isArray(window.initialEvents) ? window.initialEvents : []);

    const calendar = new Calendar(calendarEl, {
        plugins: FULLCALENDAR_PLUGINS,
        initialView: config.initialView || 'dayGridMonth',
        dayMaxEvents: true,
        nowIndicator: true,
        headerToolbar: config.headerToolbar || {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek',
        },
        editable: config.editable || false,
        selectable: config.selectable !== undefined ? config.selectable : true,
        selectMirror: true,
        events: initialEvents,
        eventClick: config.onEventClick,
        dateClick: config.onDateClick,
        datesSet: config.onDatesSet,
        select: config.onSelect,
    });

    calendar.render();
    return calendar;
}

function showNotification(message, type = 'info') {
    console.log(`[${type.toUpperCase()}] ${message}`);

    window.dispatchEvent(new CustomEvent('show-notification', {
        detail: { message, type },
    }));
}

export function formatEventData(booking) {
    return {
        id: booking.id,
        title: booking.title || `Booking #${booking.id}`,
        start: booking.start_time || booking.start,
        end: booking.end_time || booking.end,
        allDay: booking.all_day || false,
        backgroundColor: getStatusColor(booking.status),
        borderColor: getStatusColor(booking.status),
        extendedProps: {
            status: booking.status || 'pending',
            userName: booking.user_name || booking.userName,
            userEmail: booking.user_email || booking.userEmail,
            roomName: booking.room_name || booking.roomName,
            description: booking.description || '',
        },
    };
}

function getStatusColor(status) {
    const colors = {
        pending: '#fbbf24',
        confirmed: '#10b981',
        cancelled: '#ef4444',
        completed: '#6b7280',
        rejected: '#dc2626',
    };

    return colors[status] || colors.pending;
}

function createRoomBookingForm(config, dateOverride = null) {
    const defaultSlot = resolveBookingTimeSlot(config.defaultTimeSlot || BOOKING_TIME_SLOTS[0]?.value);

    return {
        purpose: '',
        room_id: config.defaultRoomId ? String(config.defaultRoomId) : '',
        date: dateOverride || config.defaultDate || todayDateString(),
        time_slot: defaultSlot.value,
        start_time: defaultSlot.start_time,
        end_time: defaultSlot.end_time,
        attendees: 1,
        user_name: config.userName || '',
        user_email: '',
        description: '',
        qc_id_ocr_text: '',
        qc_id_cardholder_name: config.verifiedRegistrationName || '',
    };
}

export function createRoomCalendarApp(config = {}) {
    const eventsUrl = config.eventsUrl || '/rooms/calendar/events';
    const storeBookingUrl = config.storeBookingUrl || '/rooms/room-reservations';
    const verifyQcIdUrl = config.verifyQcIdUrl || '/rooms/qc-id/verify';

    return {
        calendar: null,
        calendarTitle: '',
        currentView: 'dayGridMonth',
        selectedRoom: config.selectedRoom || null,
        roomSearch: '',
        showBookingModal: false,
        showEventModal: false,
        showSuccessModal: false,
        successMessage: '',
        successBooking: null,
        selectedEvent: null,
        isSubmitting: false,
        hasVerifiedRegistration: Boolean(config.hasVerifiedRegistration),
        verifiedRegistrationName: config.verifiedRegistrationName || '',
        verifiedRegistrationQcidNumber: config.verifiedRegistrationQcidNumber || '',
        isStaffUser: Boolean(config.isStaffUser),
        rooms: Array.isArray(config.rooms) ? config.rooms : [],
        get bookingTimeSlots() {
            return filterPastTimeSlots(BOOKING_TIME_SLOTS, this.bookingForm.date);
        },
        bookingDateOptions: buildBookingDateOptions(config.bookingDateRangeDays),
        qcIdFile: null,
        qcIdPreviewUrl: '',
        qcIdIsProcessing: false,
        qcIdProgress: 0,
        qcIdStatusMessage: '',
        qcIdError: '',
        qcIdVerification: null,
        timeConflictSuggestions: [],
        timeConflictMessage: '',
        isLoadingTimeConflictSuggestions: false,

        bookingForm: createRoomBookingForm(config),

        init() {
            // Autofill user_name and QC ID if available
            if (this.hasVerifiedRegistration) {
                if (config.userName) {
                    this.bookingForm.user_name = config.userName;
                }
                if (config.verifiedRegistrationName) {
                    this.bookingForm.qc_id_cardholder_name = config.verifiedRegistrationName;
                }
                // Mark QC ID as verified in the form state
                this.qcIdVerification = {
                    is_valid: true,
                    cardholder_name: config.verifiedRegistrationName || config.userName || '',
                    confidence_score: 100,
                    source: 'registration',
                };
                this.qcIdError = '';
            } else {
                if (this.bookingForm && config.userName) {
                    this.bookingForm.user_name = config.userName;
                }
            }
            this.$nextTick(() => {
                this.initCalendar();
            });

            this.$watch('roomSearch', (value) => {
                const query = String(value || '').toLowerCase();
                document.querySelectorAll('.room-item').forEach((item) => {
                    const name = item.dataset.name;
                    item.style.display = name.includes(query) ? '' : 'none';
                });
            });

            this.$watch('bookingForm.user_name', (value) => {
                if (this.hasVerifiedRegistration) {
                    return;
                }

                if (!this.qcIdVerification?.cardholder_name) {
                    return;
                }

                if (!this.namesMatch(value, this.qcIdVerification.cardholder_name)) {
                    this.qcIdVerification = null;
                    this.bookingForm.qc_id_cardholder_name = '';
                    this.bookingForm.qc_id_ocr_text = '';
                    this.qcIdError = 'The booking name changed after verification. Please upload the QC ID again.';
                }
            });

            this.$watch('bookingForm.room_id', () => {
                const max = this.attendeeInputMax;
                if (max && Number(this.bookingForm.attendees) > Number(max)) {
                    this.bookingForm.attendees = max;
                }

                this.clearTimeConflictSuggestions();
            });

            this.$watch('bookingForm.date', (value) => {
                this.ensureBookingDateOption(value);
                this.clearTimeConflictSuggestions();
            });

            this.$watch('bookingForm.time_slot', (value) => {
                applyBookingTimeSlot(this.bookingForm, value);
                this.clearTimeConflictSuggestions();
            });

            this.ensureBookingDateOption(this.bookingForm.date);
            applyBookingTimeSlot(this.bookingForm, this.bookingForm.time_slot);

            if (this.hasVerifiedRegistration) {
                this.qcIdVerification = {
                    is_valid: true,
                    cardholder_name: this.verifiedRegistrationName || '',
                    confidence_score: 100,
                    source: 'registration',
                };
            }
        },

        get selectedRoomMeta() {
            return this.rooms.find((room) => String(room.id) === String(this.bookingForm.room_id)) || null;
        },

        get attendeeInputMax() {
            const room = this.selectedRoomMeta;

            if (!room) {
                return null;
            }

            return this.isStaffUser ? room.capacity : room.student_limit;
        },

        get attendeeGuidance() {
            const room = this.selectedRoomMeta;

            if (!room) {
                return '';
            }

            if (!room.is_collaborative) {
                return `Room capacity: ${room.capacity} attendees.`;
            }

            if (this.isStaffUser) {
                return `Collaborative room capacity: ${room.capacity} attendees.`;
            }

            if (room.student_limit > room.standard_limit) {
                return `Collaborative rooms allow up to ${room.standard_limit} attendees by default. Requests up to ${room.student_limit} attendees need librarian approval.`;
            }

            return `This collaborative room allows up to ${room.standard_limit} attendees.`;
        },

        ensureBookingDateOption(dateValue) {
            ensureBookingDateOption(this.bookingDateOptions, dateValue);
        },

        clearTimeConflictSuggestions() {
            this.timeConflictSuggestions = [];
            this.timeConflictMessage = '';
        },

        async loadNearbyTimeSuggestions() {
            this.timeConflictSuggestions = [];

            if (!this.bookingForm.date || !this.bookingForm.room_id || !this.bookingForm.time_slot) {
                return [];
            }

            this.isLoadingTimeConflictSuggestions = true;

            try {
                const response = await fetch(buildUrl(eventsUrl, {
                    start: this.bookingForm.date,
                    end: this.bookingForm.date,
                    room_id: this.bookingForm.room_id,
                }));

                if (!response.ok) {
                    return [];
                }

                const events = await response.json();
                const suggestions = buildNearbyAvailableTimeSuggestions(
                    this.bookingForm.time_slot,
                    Array.isArray(events) ? events : [],
                );

                this.timeConflictSuggestions = suggestions;
                return suggestions;
            } catch (error) {
                console.error('Failed to load nearby booking slots:', error);
                return [];
            } finally {
                this.isLoadingTimeConflictSuggestions = false;
            }
        },

        applySuggestedTimeSlot(slotValue) {
            applyBookingTimeSlot(this.bookingForm, slotValue);
            this.clearTimeConflictSuggestions();
            this.qcIdError = '';
        },

        async handleBookingConflict(response, payload) {
            const message = payload?.message || 'This time slot conflicts with an existing booking.';
            this.qcIdError = message;

            if (!hasConflictError(response, payload)) {
                this.clearTimeConflictSuggestions();
                return false;
            }

            this.timeConflictMessage = 'This time slot is unavailable. Try one of these nearby options:';
            await this.loadNearbyTimeSuggestions();

            if (!this.timeConflictSuggestions.length) {
                this.timeConflictMessage = 'No nearby open slots were found for this date.';
            }

            return true;
        },

        normalizeName(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[^A-Z\s]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },

        normalizeOcrText(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/\r/g, '')
                .replace(/[^A-Z0-9,./\-\+\n\s]/g, ' ')
                .replace(/[ \t]+/g, ' ')
                .replace(/\n{2,}/g, '\n')
                .trim();
        },

        async runQcIdVerification(file) {
            this.qcIdIsProcessing = true;
            this.qcIdStatusMessage = 'Scanning ID...';
            this.qcIdProgress = 20;

            try {
                this.qcIdProgress = 50;
                this.qcIdStatusMessage = 'Verifying with server...';

                const payload = await performQcIdVerification(file, this.bookingForm.user_name, verifyQcIdUrl);

                // Use the returned payload to update state
                const verification = payload.verification || null;
                this.qcIdVerification = verification;
                
                if (verification?.is_valid && verification?.cardholder_name) {
                    this.bookingForm.qc_id_cardholder_name = verification.cardholder_name;
                    this.bookingForm.user_name = verification.cardholder_name;
                }

                if (!payload.success || !verification?.is_valid) {
                    this.qcIdError = payload.message || 'The uploaded image is not recognized as a QC ID.';
                    return;
                }

                this.qcIdError = '';
                this.qcIdProgress = 100;
                this.qcIdVerification = payload.verification;
                
                if (payload.verification?.cardholder_name) {
                    this.bookingForm.user_name = payload.verification.cardholder_name;
                    this.bookingForm.qc_id_cardholder_name = payload.verification.cardholder_name;
                }
                
                this.bookingForm.qc_id_ocr_text = payload.verification.normalized_text || 'VERIFIED';

                this.qcIdStatusMessage = 'ID Verified successfully';
                this.qcIdError = '';
            } catch (error) {
                console.error('OCR Error:', error);
                this.qcIdError = error.message;
                this.qcIdStatusMessage = 'Verification failed';
            } finally {
                this.qcIdIsProcessing = false;
            }
        },

        namesMatch(a, b) {
            const n1 = String(a || '').toUpperCase().replace(/[^A-Z]/g, '');
            const n2 = String(b || '').toUpperCase().replace(/[^A-Z]/g, '');
            return n1 === n2 || n1.includes(n2) || n2.includes(n1);
        },

        initCalendar() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) {
                return;
            }

            const self = this;
            this.calendar = new Calendar(calendarEl, {
                plugins: FULLCALENDAR_PLUGINS,
                initialView: 'dayGridMonth',
                headerToolbar: false,
                height: 'auto',
                allDaySlot: false,
                views: {
                    timeGridWeek: {
                        buttonText: 'week',
                    },
                    listWeek: {
                        buttonText: 'list',
                    },
                },
                events: this.fetchEvents.bind(this),
                eventClick(info) {
                    self.selectedEvent = mapEventFromCalendarInfo(self, info);
                    self.showEventModal = true;
                },
                dateClick(info) {
                    self.openBookingModal(info.dateStr);
                },
                datesSet(info) {
                    self.currentView = info.view.type;
                    self.calendarTitle = info.view.title;
                },
                eventDidMount(info) {
                    const props = info.event.extendedProps || {};
                    const selfRef = self;

                    info.el.removeAttribute('title');

                    const onEnter = () => selfRef.showEventTooltip(info, props);
                    const onLeave = () => selfRef.hideEventTooltip();

                    info.el.addEventListener('mouseenter', onEnter);
                    info.el.addEventListener('mouseleave', onLeave);
                    info.el.addEventListener('focusin', onEnter);
                    info.el.addEventListener('focusout', onLeave);

                    info.el.__tooltipHandlers = { onEnter, onLeave };
                },
                eventWillUnmount(info) {
                    const handlers = info.el.__tooltipHandlers;
                    if (handlers) {
                        info.el.removeEventListener('mouseenter', handlers.onEnter);
                        info.el.removeEventListener('mouseleave', handlers.onLeave);
                        info.el.removeEventListener('focusin', handlers.onEnter);
                        info.el.removeEventListener('focusout', handlers.onLeave);
                    }

                    if (self.tooltipAnchorEl === info.el) {
                        self.hideEventTooltip();
                    }
                },
            });

            this.calendar.render();
            this.currentView = this.calendar.view.type;
            this.calendarTitle = this.calendar.view.title;
        },

        async fetchEvents(info, successCallback, failureCallback) {
            try {
                const params = {
                    start: info.startStr,
                    end: info.endStr,
                };

                if (this.selectedRoom) {
                    params.room_id = this.selectedRoom.id;
                }

                const response = await fetch(buildUrl(eventsUrl, params));
                const events = await response.json();
                successCallback(events);
            } catch (error) {
                console.error('Failed to fetch events:', error);
                failureCallback(error);
            }
        },

        changeView(view) {
            this.calendar?.changeView(view);
            this.currentView = this.calendar?.view?.type || view;
        },

        selectRoom(room) {
            this.selectedRoom = room;
            this.bookingForm.room_id = room.id;
            this.calendar?.refetchEvents();

            const url = new URL(window.location.href);
            url.searchParams.set('room', room.id);
            window.history.pushState({}, '', url);
        },

        openBookingModal(date = null) {
            this.clearTimeConflictSuggestions();

            // Always rebuild bookingDateOptions from today for every modal open
            this.bookingDateOptions = buildBookingDateOptions();

            // Prevent selecting a past date, even if pre-filled
            const todayStr = new Date().toISOString().split('T')[0];
            let formDate = date || this.bookingForm.date;
            if (formDate && formDate < todayStr) {
                this.bookingForm.date = todayStr;
            } else if (formDate && this.bookingDateOptions.some(opt => opt.value === formDate)) {
                this.bookingForm.date = formDate;
            } else {
                this.bookingForm.date = todayStr;
            }

            applyBookingTimeSlot(this.bookingForm, this.bookingForm.time_slot);

            if (this.selectedRoom) {
                this.bookingForm.room_id = this.selectedRoom.id;
            }

            this.qcIdError = '';

            if (this.hasVerifiedRegistration) {
                this.qcIdVerification = {
                    is_valid: true,
                    cardholder_name: this.verifiedRegistrationName || '',
                    confidence_score: 100,
                    source: 'registration',
                };
            }

            this.showBookingModal = true;
        },

        closeBookingModal() {
            this.qcIdError = '';
            this.clearTimeConflictSuggestions();
            this.showBookingModal = false;
        },

        closeEventModal() {
            this.showEventModal = false;
            this.selectedEvent = null;
        },

        closeSuccessModal() {
            this.showSuccessModal = false;
            this.successMessage = '';
            this.successBooking = null;
            window.location.reload();
        },

        formatDate(value) {
            if (!value) {
                return '';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return String(value);
            }

            return date.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        },

        formatTime(value) {
            return formatClockValue(value);
        },

        formatTimeRange(startValue, endValue) {
            return formatRange(startValue, endValue);
        },

        tooltipEl: null,
        tooltipAnchorEl: null,
        tooltipCleanup: null,

        showEventTooltip(info, props) {
            this.hideEventTooltip();

            const title = info?.event?.title || '';
            const purpose = props.purpose || title;
            const roomName = props.room_name || props.room || '';
            const time = props.formatted_time || '';
            const userName = props.user_name || props.userName || '';
            const attendees = props.attendees != null ? String(props.attendees) : '';

            const tooltip = document.createElement('div');
            tooltip.className = 'fixed z-50 w-72 bg-gray-900 text-white text-xs rounded-lg shadow-xl p-3';
            tooltip.style.pointerEvents = 'none';

            tooltip.innerHTML = `
                <div class="font-semibold text-sm mb-2">${this.escapeHtml(purpose || roomName)}</div>
                <div class="space-y-1.5 text-gray-300">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-building"></i>
                        <span>${this.escapeHtml(roomName)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock"></i>
                        <span>${this.escapeHtml(time)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-user"></i>
                        <span>${this.escapeHtml(userName)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-users"></i>
                        <span>${this.escapeHtml(attendees ? attendees + ' attendees' : '')}</span>
                    </div>
                </div>
                <div data-arrow class="absolute left-6 top-full w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-900"></div>
            `;

            document.body.appendChild(tooltip);

            const anchor = info?.el;
            if (!anchor) {
                tooltip.remove();
                return;
            }

            this.tooltipEl = tooltip;
            this.tooltipAnchorEl = anchor;

            const position = () => {
                if (!this.tooltipEl || !this.tooltipAnchorEl) {
                    return;
                }

                const rect = this.tooltipAnchorEl.getBoundingClientRect();
                const tipRect = this.tooltipEl.getBoundingClientRect();

                const viewportW = window.innerWidth;
                const viewportH = window.innerHeight;
                const padding = 8;
                const gap = 10;

                let left = rect.left + (rect.width / 2);
                const half = tipRect.width / 2;
                left = Math.max(padding + half, Math.min(viewportW - padding - half, left));

                let top = rect.top - tipRect.height - gap;
                let placeBelow = false;

                if (top < padding) {
                    top = rect.bottom + gap;
                    placeBelow = true;
                }

                if (top + tipRect.height > viewportH - padding) {
                    top = Math.max(padding, viewportH - padding - tipRect.height);
                }

                this.tooltipEl.style.left = `${left}px`;
                this.tooltipEl.style.top = `${top}px`;
                this.tooltipEl.style.transform = 'translateX(-50%)';

                const arrow = this.tooltipEl.querySelector('[data-arrow]');
                if (arrow) {
                    if (placeBelow) {
                        arrow.className = 'absolute left-6 bottom-full w-0 h-0 border-l-8 border-r-8 border-b-8 border-transparent border-b-gray-900';
                    } else {
                        arrow.className = 'absolute left-6 top-full w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-900';
                    }
                }
            };

            position();

            const onScrollOrResize = () => position();
            window.addEventListener('scroll', onScrollOrResize, true);
            window.addEventListener('resize', onScrollOrResize);
            this.tooltipCleanup = () => {
                window.removeEventListener('scroll', onScrollOrResize, true);
                window.removeEventListener('resize', onScrollOrResize);
            };
        },

        hideEventTooltip() {
            if (this.tooltipCleanup) {
                this.tooltipCleanup();
            }
            this.tooltipCleanup = null;
            this.tooltipAnchorEl = null;

            if (this.tooltipEl) {
                this.tooltipEl.remove();
            }
            this.tooltipEl = null;
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        },

        async submitBooking() {
            if (!this.hasVerifiedRegistration && (!this.qcIdVerification?.is_valid || !this.bookingForm.qc_id_ocr_text)) {
                this.qcIdError = 'Upload and verify a valid QC ID before creating the booking.';
                return;
            }

            applyBookingTimeSlot(this.bookingForm, this.bookingForm.time_slot);

            const selectedRoom = this.selectedRoomMeta;
            const requestedAttendees = Number(this.bookingForm.attendees || 0);
            const requiresLibrarianApproval = Boolean(
                selectedRoom?.is_collaborative
                && !this.isStaffUser
                && requestedAttendees > Number(selectedRoom.standard_limit || 10),
            );

            if (requiresLibrarianApproval) {
                showNotification(
                    'This collaborative-room booking exceeds the 10-attendee limit and will be submitted for librarian approval.',
                    'warning',
                );
            }

            this.isSubmitting = true;

            try {
                const response = await fetch(storeBookingUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(this.bookingForm),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.clearTimeConflictSuggestions();
                    this.successMessage = data.message || 'Booking created successfully.';
                    this.successBooking = data.booking || null;
                    this.closeBookingModal();
                    this.showSuccessModal = true;
                } else {
                    const isConflict = await this.handleBookingConflict(response, data);

                    if (isConflict) {
                        showNotification(
                            data.message || 'This time slot is unavailable. Choose from the suggested nearby times.',
                            'warning',
                        );
                    } else {
                        showNotification(data.message || 'Failed to create booking', 'error');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while creating the booking', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },
    };
}

function createDashboardBookingForm(config, dateOverride = null) {
    const defaultSlot = resolveBookingTimeSlot(config.defaultTimeSlot || BOOKING_TIME_SLOTS[0]?.value);

    return {
        purpose: '',
        room_id: '',
        date: dateOverride || config.defaultDate || todayDateString(),
        time_slot: defaultSlot.value,
        start_time: defaultSlot.start_time,
        end_time: defaultSlot.end_time,
        attendees: 1,
        user_name: config.userName || '',
        user_email: config.userEmail || '',
        description: '',
        qc_id_ocr_text: '',
        qc_id_cardholder_name: config.verifiedRegistrationName || '',
    };
}

export function createDashboardApp(config = {}) {
    const eventsUrl = config.eventsUrl || '/rooms/calendar/events';
    const storeBookingUrl = config.storeBookingUrl || '/rooms/room-reservations';
    const verifyQcIdUrl = config.verifyQcIdUrl || '/rooms/qc-id/verify';
    const monthDataUrl = config.monthDataUrl || '/rooms/calendar/month';
    const initialCalendarData = config.initialCalendarData && typeof config.initialCalendarData === 'object'
        ? config.initialCalendarData
        : {};
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const bookingsPanelPreferenceKey = 'smartspace-dashboard-bookings-panel-open';

    return {
        dashboardCalendar: null,
        showBookingModal: false,
        showViewModal: false,
        showDayEventsModal: false,
        showStatsModal: false,
        statsModalTitle: '',
        statsModalList: [],
        openStatsModal(title, list) {
            this.statsModalTitle = title;
            this.statsModalList = list || [];
            this.showStatsModal = true;
        },
        selectedBooking: null,
        selectedDay: null,
        isSubmitting: false,
        calendarView: 'dayGridMonth',
        calendarTitle: '',
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        calendarData: initialCalendarData,
        monthNames,
        bookingsPanelOpen: true,
        hasVerifiedRegistration: Boolean(config.hasVerifiedRegistration),
        verifiedRegistrationName: config.verifiedRegistrationName || '',
        isStaffUser: Boolean(config.isStaffUser),
        rooms: Array.isArray(config.rooms) ? config.rooms : [],
        get bookingTimeSlots() {
            return filterPastTimeSlots(BOOKING_TIME_SLOTS, this.bookingForm.date);
        },
        bookingDateOptions: buildBookingDateOptions(config.bookingDateRangeDays),
        qcIdFile: null,
        qcIdPreviewUrl: '',
        qcIdIsProcessing: false,
        qcIdProgress: 0,
        qcIdStatusMessage: '',
        qcIdError: '',
        qcIdVerification: null,
        timeConflictSuggestions: [],
        timeConflictMessage: '',
        isLoadingTimeConflictSuggestions: false,
        bookingForm: createDashboardBookingForm(config),

        viewEvent: null,

        showRoomModal: false,
        selectedRoom: null,
        selectedRoomCount: 0,
        
        openRoomModal(room, count) {
            this.selectedRoom = room;
            this.selectedRoomCount = count || 0;
            this.showRoomModal = true;
        },

        // Flat cell array for user dashboard calendar grid (used by dashboard-user.blade.php)
        get calCells() {
            const cells = [];
            const year = parseInt(this.currentYear, 10) || new Date().getFullYear();
            const month = parseInt(this.currentMonth, 10) || new Date().getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startPadding = firstDay.getDay();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Previous month padding
            const prevMonth = new Date(year, month, 0);
            for (let i = startPadding - 1; i >= 0; i--) {
                cells.push({
                    day: prevMonth.getDate() - i,
                    date: null,
                    isCurrentMonth: false,
                    isToday: false,
                    events: [],
                });
            }

            // Current month days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                const dateStr = this.formatDateKey(date);
                cells.push({
                    day: i,
                    date: dateStr,
                    isCurrentMonth: true,
                    isToday: date.getTime() === today.getTime(),
                    isPast: date < today,
                    events: this.calendarData[dateStr] || [],
                });
            }

            // Next month padding to fill to 42 cells (6 rows)
            let nextDay = 1;
            while (cells.length < 42) {
                cells.push({
                    day: nextDay++,
                    date: null,
                    isCurrentMonth: false,
                    isToday: false,
                    events: [],
                });
            }

            return cells;
        },

        // Flat list of upcoming events for the list view (used by dashboard-user.blade.php)
        get listEvents() {
            const events = [];
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const twoWeeksLater = new Date(today);
            twoWeeksLater.setDate(twoWeeksLater.getDate() + 14);

            if (!this.calendarData || typeof this.calendarData !== 'object') {
                return events;
            }

            Object.keys(this.calendarData).sort().forEach((dateStr) => {
                const dateObj = new Date(dateStr + 'T00:00:00');
                if (dateObj >= today && dateObj <= twoWeeksLater) {
                    const dayEvents = this.calendarData[dateStr] || [];
                    dayEvents.forEach((evt) => {
                        events.push({ ...evt, date: dateStr });
                    });
                }
            });

            return events;
        },

        selectCalendarDate(dateStr) {
            if (!dateStr) return;
            this.openBookingModal(dateStr);
        },

        get calendarWeeks() {
            const weeks = [];
            const year = parseInt(this.currentYear, 10) || new Date().getFullYear();
            const month = parseInt(this.currentMonth, 10) || new Date().getMonth();
            
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startPadding = firstDay.getDay();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            let currentWeek = [];

            const prevMonth = new Date(year, month, 0);
            for (let i = startPadding - 1; i >= 0; i -= 1) {
                currentWeek.push({
                    day: prevMonth.getDate() - i,
                    isCurrentMonth: false,
                    isToday: false,
                    events: [],
                });
            }

            for (let i = 1; i <= lastDay.getDate(); i += 1) {
                const date = new Date(year, month, i);
                const dateStr = this.formatDateKey(date);
                const isToday = date.getTime() === today.getTime();
                const isPast = date < today;

                currentWeek.push({
                    day: i,
                    date: dateStr,
                    isCurrentMonth: true,
                    isToday,
                    isPast,
                    events: this.calendarData[dateStr] || [],
                });

                if (currentWeek.length === 7) {
                    weeks.push(currentWeek);
                    currentWeek = [];
                }
            }

            let nextMonthDay = 1;
            while (currentWeek.length < 7 && currentWeek.length > 0) {
                currentWeek.push({
                    day: nextMonthDay,
                    isCurrentMonth: false,
                    isToday: false,
                    events: [],
                });
                nextMonthDay += 1;
            }

            if (currentWeek.length > 0) {
                weeks.push(currentWeek);
            }

            while (weeks.length < 6) {
                const week = [];
                for (let i = 0; i < 7; i += 1) {
                    week.push({
                        day: nextMonthDay,
                        isCurrentMonth: false,
                        isToday: false,
                        events: [],
                    });
                    nextMonthDay += 1;
                }
                weeks.push(week);
            }

            return weeks;
        },

        init() {
            // Ensure title and data are loaded
            this.setMonthTitle();
            this.fetchCalendarData();

            try {
                const storedPanelState = window.localStorage.getItem(bookingsPanelPreferenceKey);
                if (storedPanelState !== null) {
                    this.bookingsPanelOpen = storedPanelState === '1';
                }
            } catch (error) {
                this.bookingsPanelOpen = true;
            }

            window.addEventListener('layout:sidebar-toggled', () => {
                this.resizeDashboardCalendar();
            });

            // Restore calendar view immediately on reload
            this.$nextTick(() => {
                this.initDashboardCalendar(this.calendarView || 'dayGridMonth');
            });

            window.addEventListener('resize', () => {
                this.resizeDashboardCalendar();
            });

            this.$watch('bookingForm.user_name', (value) => {
                if (this.hasVerifiedRegistration) {
                    return;
                }

                if (!this.qcIdVerification?.cardholder_name) {
                    return;
                }

                if (!this.namesMatch(value, this.qcIdVerification.cardholder_name)) {
                    this.qcIdVerification = null;
                    this.bookingForm.qc_id_cardholder_name = '';
                    this.bookingForm.qc_id_ocr_text = '';
                    this.qcIdError = 'The booking name changed after verification. Please upload the QC ID again.';
                }
            });

            this.$watch('bookingForm.room_id', () => {
                const max = this.attendeeInputMax;
                if (max && Number(this.bookingForm.attendees) > Number(max)) {
                    this.bookingForm.attendees = max;
                }

                this.clearTimeConflictSuggestions();
            });

            this.$watch('bookingForm.attendees', (value) => {
                const max = this.attendeeInputMax;
                if (max && Number(value) > Number(max)) {
                    this.bookingForm.attendees = Number(max);
                }
                if (Number(value) < 1 && value !== '' && value !== null) {
                    this.bookingForm.attendees = 1;
                }
            });

            this.$watch('bookingForm.date', (value) => {
                this.ensureBookingDateOption(value);
                this.clearTimeConflictSuggestions();
            });

            this.$watch('bookingForm.time_slot', (value) => {
                applyBookingTimeSlot(this.bookingForm, value);
                this.clearTimeConflictSuggestions();
            });

            this.ensureBookingDateOption(this.bookingForm.date);
            applyBookingTimeSlot(this.bookingForm, this.bookingForm.time_slot);

            if (this.hasVerifiedRegistration) {
                this.qcIdVerification = {
                    is_valid: true,
                    cardholder_name: this.verifiedRegistrationName || '',
                    id_number: config.verifiedQcIdNumber || '',
                    confidence_score: 100,
                    source: 'registration',
                };
            }

            this.syncMonthFromDate(new Date());
            this.setMonthTitle();
            this.fetchCalendarData();
        },

        resizeDashboardCalendar() {
            this.$nextTick(() => {
                this.dashboardCalendar?.updateSize();
            });
        },

        toggleBookingsPanel() {
            this.bookingsPanelOpen = !this.bookingsPanelOpen;

            try {
                window.localStorage.setItem(bookingsPanelPreferenceKey, this.bookingsPanelOpen ? '1' : '0');
            } catch (error) {
                // Ignore storage restrictions in private browsing contexts.
            }

            this.resizeDashboardCalendar();
        },

        get selectedRoomMeta() {
            return this.rooms.find((room) => String(room.id) === String(this.bookingForm.room_id)) || null;
        },

        get attendeeInputMax() {
            const room = this.selectedRoomMeta;

            if (!room) {
                return null;
            }

            return this.isStaffUser ? room.capacity : room.student_limit;
        },

        get attendeeGuidance() {
            const room = this.selectedRoomMeta;

            if (!room) {
                return '';
            }

            if (!room.is_collaborative) {
                return `Room capacity: ${room.capacity} attendees.`;
            }

            if (this.isStaffUser) {
                return `Collaborative room capacity: ${room.capacity} attendees.`;
            }

            if (room.student_limit > room.standard_limit) {
                return `Collaborative rooms allow up to ${room.standard_limit} attendees by default. Requests up to ${room.student_limit} attendees need librarian approval.`;
            }

            return `This collaborative room allows up to ${room.standard_limit} attendees.`;
        },

        ensureBookingDateOption(dateValue) {
            ensureBookingDateOption(this.bookingDateOptions, dateValue);
        },

        clearTimeConflictSuggestions() {
            this.timeConflictSuggestions = [];
            this.timeConflictMessage = '';
        },

        async loadNearbyTimeSuggestions() {
            this.timeConflictSuggestions = [];

            if (!this.bookingForm.date || !this.bookingForm.room_id || !this.bookingForm.time_slot) {
                return [];
            }

            this.isLoadingTimeConflictSuggestions = true;

            try {
                const response = await fetch(buildUrl(eventsUrl, {
                    start: this.bookingForm.date,
                    end: this.bookingForm.date,
                    room_id: this.bookingForm.room_id,
                }));

                if (!response.ok) {
                    return [];
                }

                const events = await response.json();
                const suggestions = buildNearbyAvailableTimeSuggestions(
                    this.bookingForm.time_slot,
                    Array.isArray(events) ? events : [],
                );

                this.timeConflictSuggestions = suggestions;
                return suggestions;
            } catch (error) {
                console.error('Failed to load nearby booking slots:', error);
                return [];
            } finally {
                this.isLoadingTimeConflictSuggestions = false;
            }
        },

        async runQcIdVerification(file) {
            this.qcIdIsProcessing = true;
            this.qcIdStatusMessage = 'Scanning ID...';
            this.qcIdProgress = 20;

            try {
                this.qcIdProgress = 50;
                this.qcIdStatusMessage = 'Verifying with server...';

                const payload = await performQcIdVerification(file, this.bookingForm.user_name, verifyQcIdUrl);

                this.qcIdProgress = 100;
                this.qcIdVerification = payload.verification;
                
                if (payload.verification?.cardholder_name) {
                    this.bookingForm.user_name = payload.verification.cardholder_name;
                    this.bookingForm.qc_id_cardholder_name = payload.verification.cardholder_name;
                }
                
                this.bookingForm.qc_id_ocr_text = payload.verification.normalized_text || 'VERIFIED';

                this.qcIdStatusMessage = 'ID Verified successfully';
                this.qcIdError = '';
            } catch (error) {
                console.error('OCR Error:', error);
                this.qcIdError = error.message;
                this.qcIdStatusMessage = 'Verification failed';
            } finally {
                this.qcIdIsProcessing = false;
            }
        },

        namesMatch(a, b) {
            const n1 = String(a || '').toUpperCase().replace(/[^A-Z]/g, '');
            const n2 = String(b || '').toUpperCase().replace(/[^A-Z]/g, '');
            return n1 === n2 || n1.includes(n2) || n2.includes(n1);
        },
        async handleBookingConflict(response, payload) {
            const message = payload?.message || 'This time slot conflicts with an existing booking.';
            this.qcIdError = message;

            if (!hasConflictError(response, payload)) {
                this.clearTimeConflictSuggestions();
                return false;
            }

            this.timeConflictMessage = 'This time slot is unavailable. Try one of these nearby options:';
            await this.loadNearbyTimeSuggestions();

            if (!this.timeConflictSuggestions.length) {
                this.timeConflictMessage = 'No nearby open slots were found for this date.';
            }

            return true;
        },

        normalizeName(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[^A-Z\s]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },

        normalizeOcrText(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/\r/g, '')
                .replace(/[^A-Z0-9,./\-\+\n\s]/g, ' ')
                .replace(/[ \t]+/g, ' ')
                .replace(/\n{2,}/g, '\n')
                .trim();
        },

        async buildQcCanvas(file) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const scale = Math.max(1, 2800 / Math.max(img.width, img.height));
                    canvas.width = Math.round(img.width * scale);
                    canvas.height = Math.round(img.height * scale);
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const data = imageData.data;
                    for (let i = 0; i < data.length; i += 4) {
                        const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                        const contrast = Math.min(255, Math.max(0, ((gray - 128) * 1.7) + 128));
                        data[i] = contrast;
                        data[i + 1] = contrast;
                        data[i + 2] = contrast;
                    }
                    ctx.putImageData(imageData, 0, 0);

                    resolve(canvas);
                };
                img.onerror = () => resolve(null);
                img.src = URL.createObjectURL(file);
            });
        },

        createQcCropCanvas(sourceCanvas, rect, threshold = false) {
            const crop = document.createElement('canvas');
            const sx = Math.max(0, Math.round(sourceCanvas.width * rect.x));
            const sy = Math.max(0, Math.round(sourceCanvas.height * rect.y));
            const sw = Math.max(1, Math.round(sourceCanvas.width * rect.w));
            const sh = Math.max(1, Math.round(sourceCanvas.height * rect.h));

            crop.width = sw;
            crop.height = sh;

            const ctx = crop.getContext('2d');
            ctx.drawImage(sourceCanvas, sx, sy, sw, sh, 0, 0, sw, sh);

            if (threshold) {
                const imageData = ctx.getImageData(0, 0, sw, sh);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const value = data[i] > 145 ? 255 : 0;
                    data[i] = value;
                    data[i + 1] = value;
                    data[i + 2] = value;
                }
                ctx.putImageData(imageData, 0, 0);
            }

            return crop;
        },

        async recognizeQcCanvas(canvas, ocrConfig = {}, withProgress = false) {
            const options = {
                preserve_interword_spaces: '1',
                ...ocrConfig,
            };

            if (withProgress) {
                options.logger = (message) => {
                    if (message.status) {
                        this.qcIdStatusMessage = message.status;
                    }

                    if (typeof message.progress === 'number') {
                        this.qcIdProgress = message.progress * 100;
                    }
                };
            }

            const result = await window.Tesseract.recognize(canvas, 'eng', options);
            return this.normalizeOcrText(result?.data?.text || '');
        },

        async collectQcOcrText(file) {
            const enhancedCanvas = await this.buildQcCanvas(file);
            if (!enhancedCanvas) {
                throw new Error('Unable to prepare the QC ID image for OCR.');
            }

            const fullText = await this.recognizeQcCanvas(enhancedCanvas, {
                tessedit_pageseg_mode: 6,
            }, true);

            const sparseText = await this.recognizeQcCanvas(enhancedCanvas, {
                tessedit_pageseg_mode: 11,
            });

            const nameStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.23, y: 0.24, w: 0.45, h: 0.13 }, false);
            const demographicStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.22, y: 0.33, w: 0.48, h: 0.16 }, true);
            const issuedStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.34, y: 0.43, w: 0.19, h: 0.09 }, true);
            const validStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.52, y: 0.43, w: 0.19, h: 0.09 }, true);
            const addressStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.19, y: 0.54, w: 0.46, h: 0.19 }, true);
            const idStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.60, y: 0.74, w: 0.36, h: 0.18 }, true);
            const bottomStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.62, y: 0.76, w: 0.34, h: 0.14 }, true);
            const dateStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.25, y: 0.39, w: 0.48, h: 0.15 }, true);

            const nameText = await this.recognizeQcCanvas(nameStrip, {
                tessedit_pageseg_mode: 7,
                tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ,.- ',
            });

            const demographicsText = await this.recognizeQcCanvas(demographicStrip, {
                tessedit_pageseg_mode: 6,
                tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/ -',
            });

            const issuedText = await this.recognizeQcCanvas(issuedStrip, {
                tessedit_pageseg_mode: 7,
                tessedit_char_whitelist: '0123456789/ -',
            });

            const validText = await this.recognizeQcCanvas(validStrip, {
                tessedit_pageseg_mode: 7,
                tessedit_char_whitelist: '0123456789/ -',
            });

            const addressText = await this.recognizeQcCanvas(addressStrip, {
                tessedit_pageseg_mode: 6,
                tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789,.- ',
            });

            const idText = await this.recognizeQcCanvas(idStrip, {
                tessedit_pageseg_mode: 6,
                tessedit_char_whitelist: '0123456789 ',
            });

            const bottomText = await this.recognizeQcCanvas(bottomStrip, {
                tessedit_pageseg_mode: 7,
                tessedit_char_whitelist: '0123456789 ',
            });

            const dateText = await this.recognizeQcCanvas(dateStrip, {
                tessedit_pageseg_mode: 7,
                tessedit_char_whitelist: '0123456789/ -',
            });

            const structuredLines = [fullText, sparseText];

            if (nameText) {
                structuredLines.push('LAST NAME, FIRST NAME, MIDDLE NAME');
                structuredLines.push(nameText);
            }

            if (demographicsText) {
                structuredLines.push(demographicsText);
                structuredLines.push('SEX DATE OF BIRTH CIVIL STATUS');
            }

            if (issuedText) {
                structuredLines.push(`DATE ISSUED ${issuedText}`);
            }

            if (validText) {
                structuredLines.push(`VALID UNTIL ${validText}`);
            }

            if (dateText) {
                structuredLines.push(`DATE ISSUED VALID UNTIL ${dateText}`);
            }

            if (addressText) {
                structuredLines.push(`ADDRESS ${addressText}`);
            }

            if (idText) {
                structuredLines.push(idText);
            }

            if (bottomText && bottomText !== idText) {
                structuredLines.push(bottomText);
            }

            return this.normalizeOcrText(structuredLines.filter(Boolean).join('\n'));
        },

        namesMatch(first, second) {
            const firstTokens = this.normalizeName(first).split(' ').filter((token) => token.length >= 2);
            const secondTokens = this.normalizeName(second).split(' ').filter((token) => token.length >= 2);

            if (!firstTokens.length || !secondTokens.length) {
                return false;
            }

            const overlap = firstTokens.filter((token) => secondTokens.includes(token));
            const threshold = Math.min(firstTokens.length, secondTokens.length);

            return threshold <= 2 ? overlap.length === threshold : overlap.length >= 2;
        },

        resetQcIdState({ keepPreview = true } = {}) {
            this.qcIdIsProcessing = false;
            this.qcIdProgress = 0;
            this.qcIdStatusMessage = '';
            this.qcIdError = '';
            this.qcIdVerification = null;
            this.bookingForm.qc_id_ocr_text = '';
            this.bookingForm.qc_id_cardholder_name = '';

            if (!keepPreview) {
                if (this.qcIdPreviewUrl) {
                    URL.revokeObjectURL(this.qcIdPreviewUrl);
                }

                this.qcIdPreviewUrl = '';
                this.qcIdFile = null;
            }
        },

        async handleQcIdUpload(event) {
            const file = event.target?.files?.[0];
            this.resetQcIdState({ keepPreview: false });

            if (!file) {
                return;
            }

            if (!file.type.startsWith('image/')) {
                this.qcIdError = 'Please upload an image file for the QC ID.';
                return;
            }

            this.qcIdFile = file;
            this.qcIdPreviewUrl = URL.createObjectURL(file);

            await this.runQcIdVerification(file);
        },

        async reprocessQcId() {
            if (!this.qcIdFile) {
                this.qcIdError = 'Upload a QC ID image first.';
                return;
            }

            this.resetQcIdState();
            await this.runQcIdVerification(this.qcIdFile);
        },

        async runQcIdVerification(file) {
            if (!window.Tesseract) {
                this.qcIdError = 'OCR is not available right now. Please refresh the page and try again.';
                return;
            }

            this.qcIdIsProcessing = true;
            this.qcIdStatusMessage = 'Reading QC ID image...';
            this.qcIdProgress = 0;

            try {
                this.qcIdStatusMessage = 'Enhancing image for OCR...';
                const extractedText = await this.collectQcOcrText(file);
                if (!extractedText) {
                    throw new Error('No readable text was found in the uploaded QC ID image.');
                }

                this.bookingForm.qc_id_ocr_text = extractedText;
                this.qcIdStatusMessage = 'Validating QC ID format...';

                const response = await fetch(verifyQcIdUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        ocr_text: extractedText,
                        user_name: this.bookingForm.user_name,
                    }),
                });

                const payload = await response.json();
                const verification = payload.verification || null;

                this.qcIdVerification = verification;
                if (verification?.cardholder_name) {
                    this.bookingForm.qc_id_cardholder_name = verification.cardholder_name;
                    this.bookingForm.user_name = verification.cardholder_name;
                }

                if (!payload.success) {
                    this.qcIdError = payload.message || 'The uploaded image is not recognized as a QC ID.';
                    return;
                }

                this.qcIdError = '';
                this.qcIdProgress = 100;
                this.qcIdStatusMessage = 'QC ID verified.';
            } catch (error) {
                console.error('QC ID verification failed:', error);
                this.qcIdError = error?.message || 'Unable to read the QC ID image. Please upload a clearer photo.';
                this.qcIdVerification = null;
                this.bookingForm.qc_id_cardholder_name = '';
                this.bookingForm.qc_id_ocr_text = '';
            } finally {
                this.qcIdIsProcessing = false;
            }
        },

        syncMonthFromDate(value) {
            const date = value instanceof Date ? value : new Date(value);
            if (Number.isNaN(date.getTime())) {
                return;
            }

            this.currentMonth = date.getMonth();
            this.currentYear = date.getFullYear();
        },

        setMonthTitle() {
            this.calendarTitle = `${this.monthNames[this.currentMonth]} ${this.currentYear}`;
        },

        formatDateKey(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        },

        async fetchCalendarData() {
            try {
                const response = await fetch(buildUrl(monthDataUrl, {
                    month: this.currentMonth + 1,
                    year: this.currentYear,
                }));
                this.calendarData = await response.json();
            } catch (error) {
                console.error('Failed to fetch calendar data:', error);
            }
        },

        initDashboardCalendar(initialView = 'dayGridMonth') {
            const calendarEl = document.getElementById('dashboard-calendar');
            if (!calendarEl) {
                return;
            }

            const self = this;
            this.dashboardCalendar = new Calendar(calendarEl, {
                plugins: FULLCALENDAR_PLUGINS,
                initialView,
                initialDate: new Date(this.currentYear, this.currentMonth, 1),
                headerToolbar: false,
                height: 'auto',
                dayMaxEvents: true,
                nowIndicator: true,
                allDaySlot: false,
                views: {
                    timeGridWeek: {
                        buttonText: 'week',
                    },
                    listWeek: {
                        buttonText: 'list',
                    },
                },
                events: this.fetchDashboardEvents.bind(this),
                eventClick(info) {
                    self.openViewBookingModal(self.mapDashboardEvent(info));
                },
                dateClick(info) {
                    self.openBookingModal(info.dateStr);
                },
                datesSet(info) {
                    if (info.view.type === 'dayGridMonth') {
                        self.calendarView = 'dayGridMonth';
                        self.syncMonthFromDate(info.view.currentStart);
                        self.setMonthTitle();
                        self.fetchCalendarData();
                        return;
                    }

                    self.calendarView = info.view.type;
                    self.calendarTitle = info.view.title;
                },
            });

            this.dashboardCalendar.render();

            if (initialView === 'dayGridMonth') {
                this.calendarView = 'dayGridMonth';
                this.setMonthTitle();
                return;
            }

            this.calendarView = this.dashboardCalendar.view.type;
            this.calendarTitle = this.dashboardCalendar.view.title;
        },

        async fetchDashboardEvents(info, successCallback, failureCallback) {
            try {
                const response = await fetch(buildUrl(eventsUrl, {
                    start: info.startStr,
                    end: info.endStr,
                }));
                const events = await response.json();
                successCallback(events);
            } catch (error) {
                console.error('Failed to fetch dashboard events:', error);
                failureCallback(error);
            }
        },

        mapDashboardEvent(info) {
            const booking = mapEventFromCalendarInfo(this, info);

            return {
                ...booking,
                room_name: booking.room_name || 'Room',
                user_name: booking.user_name || 'Unknown',
                status: booking.status || 'pending',
                attendees: booking.attendees || 0,
            };
        },

        prevMonth() {
            if (this.calendarView === 'dayGridMonth') {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear -= 1;
                } else {
                    this.currentMonth -= 1;
                }

                this.setMonthTitle();
                this.fetchCalendarData();
                return;
            }

            this.dashboardCalendar?.prev();
            this.calendarView = this.dashboardCalendar?.view?.type || this.calendarView;
            this.calendarTitle = this.dashboardCalendar?.view?.title || this.calendarTitle;
            this.$nextTick(() => {
                this.dashboardCalendar?.updateSize();
            });
        },

        nextMonth() {
            if (this.calendarView === 'dayGridMonth') {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear += 1;
                } else {
                    this.currentMonth += 1;
                }

                this.setMonthTitle();
                this.fetchCalendarData();
                return;
            }

            this.dashboardCalendar?.next();
            this.calendarView = this.dashboardCalendar?.view?.type || this.calendarView;
            this.calendarTitle = this.dashboardCalendar?.view?.title || this.calendarTitle;
            this.$nextTick(() => {
                this.dashboardCalendar?.updateSize();
            });
        },

        goToToday() {
            if (this.calendarView === 'dayGridMonth') {
                this.syncMonthFromDate(new Date());
                this.setMonthTitle();
                this.fetchCalendarData();
                return;
            }

            this.dashboardCalendar?.today();
            this.calendarView = this.dashboardCalendar?.view?.type || this.calendarView;
            this.calendarTitle = this.dashboardCalendar?.view?.title || this.calendarTitle;
            this.$nextTick(() => {
                this.dashboardCalendar?.updateSize();
            });
        },

        changeDashboardView(view) {
            if (view === 'dayGridMonth') {
                const focusDate = this.dashboardCalendar?.getDate() || new Date(this.currentYear, this.currentMonth, 1);
                this.syncMonthFromDate(focusDate);
                this.calendarView = 'dayGridMonth';
                this.setMonthTitle();
                this.fetchCalendarData();
                return;
            }

            if (!this.dashboardCalendar) {
                this.calendarView = view;
                this.$nextTick(() => {
                    this.initDashboardCalendar(view);
                    this.dashboardCalendar?.updateSize();
                });
                return;
            }

            if (this.calendarView === 'dayGridMonth') {
                this.dashboardCalendar.gotoDate(new Date(this.currentYear, this.currentMonth, 1));
            }

            this.calendarView = view;
            this.$nextTick(() => {
                this.dashboardCalendar?.changeView(view);
                this.dashboardCalendar?.updateSize();
                this.calendarView = this.dashboardCalendar?.view?.type || view;
                this.calendarTitle = this.dashboardCalendar?.view?.title || this.calendarTitle;
            });
        },

        openBookingModal(date = null) {
            this.clearTimeConflictSuggestions();
            // Always rebuild bookingDateOptions from today for every modal open
            this.bookingDateOptions = buildBookingDateOptions();

            this.bookingForm = createDashboardBookingForm(config, date);

            // Prevent selecting a past date, even if pre-filled
            const todayStr = new Date().toISOString().split('T')[0];
            let formDate = date || this.bookingForm.date;
            if (formDate && formDate < todayStr) {
                this.bookingForm.date = todayStr;
            } else if (formDate && this.bookingDateOptions.some(opt => opt.value === formDate)) {
                this.bookingForm.date = formDate;
            } else {
                this.bookingForm.date = todayStr;
            }

            applyBookingTimeSlot(this.bookingForm, this.bookingForm.time_slot);

            this.qcIdError = '';

            if (this.hasVerifiedRegistration) {
                this.qcIdVerification = {
                    is_valid: true,
                    cardholder_name: this.verifiedRegistrationName || '',
                    id_number: config.verifiedQcIdNumber || '',
                    confidence_score: 100,
                    source: 'registration',
                };
                this.bookingForm.user_name = config.userName || this.verifiedRegistrationName || '';
                this.bookingForm.qc_id_cardholder_name = this.verifiedRegistrationName || '';
            } else {
                if (config.userName) {
                    this.bookingForm.user_name = config.userName;
                }
                this.resetQcIdState({ keepPreview: false });
            }

            this.showBookingModal = true;
        },

        openBookingModalForDay(day) {
            if (!day?.isCurrentMonth || !day?.date) {
                return;
            }

            this.openBookingModal(day.date);
        },

        closeBookingModal() {
            this.qcIdError = '';
            this.clearTimeConflictSuggestions();
            this.showBookingModal = false;
        },

        openViewBookingModal(booking) {
            this.selectedBooking = booking;
            this.viewEvent = booking;
            this.showViewModal = true;
        },

        openDayEventsModal(day) {
            this.selectedDay = day;
            this.showDayEventsModal = true;
        },

        viewBooking(booking) {
            // Enhanced mapping: ensure relationship data is present for the modal
            const mapped = {
                ...booking,
                room_name: booking.room_name || booking.room?.name || 'Room',
                user_name: booking.user_name || booking.user?.name || 'Unknown',
                formatted_time: booking.formatted_time || this.formatTimeRange(booking.start_time, booking.end_time),
                status: booking.status || 'pending',
                attendees: booking.attendees || 0,
            };
            this.openViewBookingModal(mapped);
        },

        formatDate(value) {
            if (!value) {
                return '';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return String(value);
            }

            return date.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
        },

        formatTime(value) {
            return formatClockValue(value);
        },

        formatTimeRange(startValue, endValue) {
            return formatRange(startValue, endValue);
        },

        async submitBooking() {
            if (!this.hasVerifiedRegistration && (!this.qcIdVerification?.is_valid || !this.bookingForm.qc_id_ocr_text)) {
                this.qcIdError = 'Upload and verify a valid QC ID before creating the booking.';
                return;
            }

            applyBookingTimeSlot(this.bookingForm, this.bookingForm.time_slot);

            const selectedRoom = this.selectedRoomMeta;
            const requestedAttendees = Number(this.bookingForm.attendees || 0);
            const requiresLibrarianApproval = Boolean(
                selectedRoom?.is_collaborative
                && !this.isStaffUser
                && requestedAttendees > Number(selectedRoom.standard_limit || 10),
            );

            if (requiresLibrarianApproval) {
                showNotification(
                    'This collaborative-room booking exceeds the 10-attendee limit and will be submitted for librarian approval.',
                    'warning',
                );
            }

            this.isSubmitting = true;

            try {
                const response = await fetch(storeBookingUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(this.bookingForm),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.clearTimeConflictSuggestions();
                    showNotification(data.message || 'Booking created successfully.', 'success');
                    this.closeBookingModal();
                    window.setTimeout(() => {
                        window.location.reload();
                    }, 850);
                } else {
                    const isConflict = await this.handleBookingConflict(response, data);

                    if (isConflict) {
                        showNotification(
                            data.message || 'This time slot is unavailable. Choose from the suggested nearby times.',
                            'warning',
                        );
                    } else {
                        showNotification(data.message || 'Failed to create booking', 'error');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while creating the booking', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },
    };
}

window.calendarApp = function calendarApp() {
    return createRoomCalendarApp(window.roomCalendarConfig || {});
};

window.dashboardApp = function dashboardApp() {
    return createDashboardApp(window.dashboardCalendarConfig || {});
};

export default initCalendar;
