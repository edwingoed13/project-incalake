// Availability Calendar Component - FullCalendar Integration
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

// console.log('📅 Loading availability-calendar.js', new Date());

window.availabilityCalendarComponent = function() {
    return {
        calendar: null,

        // Availability data
        availabilityStart: '',
        availabilityEnd: '',
        activeDays: [0, 1, 2, 3, 4, 5, 6], // All days active by default
        specialDaysBlocked: [],

        init() {
            setTimeout(() => {
                this.initializeCalendar();
            }, 500);
        },

        initializeCalendar() {
            const calendarEl = document.getElementById('availability-calendar');
            if (!calendarEl) {
                // console.error('❌ Calendar element not found');
                return;
            }

            // console.log('📅 Initializing FullCalendar');

            this.calendar = new Calendar(calendarEl, {
                plugins: [dayGridPlugin, interactionPlugin],
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                selectable: true,
                selectMirror: true,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes'
                },

                // Event handlers
                dateClick: (info) => {
                    this.handleDateClick(info);
                },

                select: (info) => {
                    this.handleDateSelect(info);
                },

                // Day cell customization
                dayCellDidMount: (arg) => {
                    this.customizeDayCell(arg);
                },

                events: []
            });

            this.calendar.render();
            // console.log('✅ Calendar initialized');
        },

        handleDateClick(info) {
            // console.log('📅 Date clicked:', info.dateStr);
            // Handle individual date click
        },

        handleDateSelect(info) {
            // console.log('📅 Date range selected:', info.startStr, 'to', info.endStr);

            // Update availability range
            this.availabilityStart = info.startStr;
            this.availabilityEnd = info.endStr;

            // Update input fields
            document.getElementById('availability_start')?.setAttribute('value', info.startStr);
            document.getElementById('availability_end')?.setAttribute('value', info.endStr);

            this.updateCalendarDisplay();
        },

        customizeDayCell(arg) {
            const date = arg.date;
            const dayOfWeek = date.getDay();

            // Check if this day of week is active
            if (!this.activeDays.includes(dayOfWeek)) {
                arg.el.style.backgroundColor = '#fee2e2'; // red-100
                arg.el.style.opacity = '0.5';
                arg.el.title = 'Día no disponible';
            }

            // Check if within availability range
            if (this.availabilityStart && this.availabilityEnd) {
                const start = new Date(this.availabilityStart);
                const end = new Date(this.availabilityEnd);

                if (date >= start && date <= end) {
                    if (this.activeDays.includes(dayOfWeek)) {
                        arg.el.style.backgroundColor = '#dcfce7'; // green-100
                        arg.el.style.borderColor = '#22c55e'; // green-500
                    }
                }
            }

            // Check special days blocked (holidays)
            const dateStr = this.formatDateForSpecialDays(date);
            if (this.specialDaysBlocked.includes(dateStr)) {
                arg.el.style.backgroundColor = '#fef3c7'; // yellow-100
                arg.el.style.borderColor = '#f59e0b'; // yellow-500
                arg.el.title = 'Feriado bloqueado';
            }
        },

        formatDateForSpecialDays(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            return `${day}-${month}`;
        },

        updateCalendarDisplay() {
            if (this.calendar) {
                this.calendar.refetchEvents();
                this.calendar.render();
            }
        },

        toggleDay(dayValue) {
            const index = this.activeDays.indexOf(dayValue);
            if (index > -1) {
                this.activeDays.splice(index, 1);
            } else {
                this.activeDays.push(dayValue);
            }
            this.updateCalendarDisplay();
            // console.log('📅 Active days:', this.activeDays);
        },

        toggleSpecialDay(dayStr) {
            const index = this.specialDaysBlocked.indexOf(dayStr);
            if (index > -1) {
                this.specialDaysBlocked.splice(index, 1);
            } else {
                this.specialDaysBlocked.push(dayStr);
            }
            this.updateCalendarDisplay();
            // console.log('📅 Special days blocked:', this.specialDaysBlocked);
        },

        getAvailabilityData() {
            return {
                start: this.availabilityStart,
                end: this.availabilityEnd,
                active_days: this.activeDays,
                special_days_blocked: this.specialDaysBlocked
            };
        },

        loadAvailabilityData(data) {
            if (!data) return;

            this.availabilityStart = data.start || '';
            this.availabilityEnd = data.end || '';
            this.activeDays = data.active_days || [0, 1, 2, 3, 4, 5, 6];
            this.specialDaysBlocked = data.special_days_blocked || [];

            // Update input fields
            if (this.availabilityStart) {
                const startInput = document.getElementById('availability_start');
                if (startInput) startInput.value = this.availabilityStart;
            }
            if (this.availabilityEnd) {
                const endInput = document.getElementById('availability_end');
                if (endInput) endInput.value = this.availabilityEnd;
            }

            // Update checkboxes
            this.activeDays.forEach(day => {
                const checkbox = document.getElementById(`day_${day}`);
                if (checkbox) checkbox.checked = true;
            });

            this.specialDaysBlocked.forEach(day => {
                const checkbox = document.getElementById(`special_${day}`);
                if (checkbox) checkbox.checked = true;
            });

            this.updateCalendarDisplay();
            // console.log('✅ Availability data loaded');
        }
    };
};
