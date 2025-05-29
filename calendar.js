class BookingCalendar {
    constructor() {
        this.currentDate = new Date();
        this.selectedDate = null;
        this.availableDates = new Set();
        this.userCredits = null;
        this.dayBookingStatus = new Map(); // Store booking status for each day
        this.monthNames = [
            'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
        ];
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadUserCredits();
        this.loadAvailableDates();
        this.renderCalendar();
    }

    bindEvents() {
        document.getElementById('prevMonth').addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendar();
            this.loadAvailableDates();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.renderCalendar();
            this.loadAvailableDates();
        });
    }

    async loadUserCredits() {
        try {
            const response = await fetch('api.php?action=getUserCredits');
            const data = await response.json();
            
            if (data.error) {
                console.error('Errore nel caricamento crediti:', data.error);
                return;
            }
            
            this.userCredits = data;
            this.updateCreditsDisplay();
        } catch (error) {
            console.error('Errore nel caricamento dei crediti:', error);
        }
    }

    updateCreditsDisplay() {
        if (!this.userCredits) return;
        
        // Update the existing credits display in the left sidebar
        const creditsElement = document.querySelector('.crediti span');
        if (creditsElement) {
            creditsElement.textContent = this.userCredits.available_credits;
        }
        
        // Remove any existing credits display we might have created
        const existingDisplay = document.getElementById('creditsDisplay');
        if (existingDisplay) {
            existingDisplay.remove();
        }
    }

    async loadAvailableDates() {
        try {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth() + 1;
            
            const response = await fetch(`api.php?action=getAvailableDates&year=${year}&month=${month}`);
            const data = await response.json();
            
            this.availableDates = new Set(data.dates);
            
            // Load booking status for each date
            await this.loadMonthBookingStatus(year, month);
            
            this.updateCalendarDays();
        } catch (error) {
            console.error('Errore nel caricamento delle date disponibili:', error);
        }
    }

    async loadMonthBookingStatus(year, month) {
        try {
            const response = await fetch(`api.php?action=getMonthBookingStatus&year=${year}&month=${month}`);
            const data = await response.json();
            
            this.dayBookingStatus.clear();
            if (data.bookingStatus) {
                Object.entries(data.bookingStatus).forEach(([date, status]) => {
                    this.dayBookingStatus.set(date, status);
                });
            }
        } catch (error) {
            console.error('Errore nel caricamento dello stato prenotazioni:', error);
        }
    }

    renderCalendar() {
        const monthYear = document.getElementById('monthYear');
        monthYear.textContent = `${this.monthNames[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;

        this.renderCalendarGrid();
    }

    renderCalendarGrid() {
        const grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';

        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        // Primo giorno del mese e ultimo giorno del mese
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        // Calcola il primo lunedì da mostrare
        const startDate = new Date(firstDay);
        const dayOfWeek = (firstDay.getDay() + 6) % 7; // Converte domenica=0 in domenica=6
        startDate.setDate(startDate.getDate() - dayOfWeek);

        // Genera 42 giorni (6 settimane)
        for (let i = 0; i < 42; i++) {
            const date = new Date(startDate);
            date.setDate(startDate.getDate() + i);
            
            const dayElement = this.createDayElement(date, month);
            grid.appendChild(dayElement);
        }
    }

    createDayElement(date, currentMonth) {
        const dayElement = document.createElement('div');
        dayElement.className = 'day';
        dayElement.textContent = date.getDate();
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        date.setHours(0, 0, 0, 0);
        
        const dateString = this.formatDate(date);
        const isCurrentMonth = date.getMonth() === currentMonth;
        const isToday = date.getTime() === today.getTime();
        const isPast = date < today;
        const hasSlots = this.availableDates.has(dateString);
        const hasCredits = this.userCredits && this.userCredits.available_credits > 0;

        if (!isCurrentMonth) {
            dayElement.classList.add('other-month');
        } else if (isPast || !hasSlots) {
            dayElement.classList.add('disabled');
        } else if (!hasCredits) {
            dayElement.classList.add('disabled'); // Disable if no credits
        } else {
            dayElement.addEventListener('click', () => this.selectDate(date, dateString));
        }

        if (isToday && isCurrentMonth) {
            dayElement.classList.add('today');
        }

        if (this.selectedDate && dateString === this.selectedDate) {
            dayElement.classList.add('selected');
        }

        // Add status indicator dot
        if (hasSlots && isCurrentMonth && !isPast) {
            const bookingStatus = this.dayBookingStatus.get(dateString);
            if (bookingStatus) {
                if (bookingStatus.hasMyBooking) {
                    dayElement.classList.add('has-my-booking');
                } else if (bookingStatus.hasAvailableSlots) {
                    dayElement.classList.add('has-slots');
                } else {
                    dayElement.classList.add('all-occupied');
                }
            } else {
                dayElement.classList.add('has-slots'); // Default to available
            }
        }

        return dayElement;
    }

    updateCalendarDays() {
        this.renderCalendarGrid();
    }

    async selectDate(date, dateString) {
        // Rimuovi selezione precedente
        document.querySelectorAll('.day.selected').forEach(el => {
            el.classList.remove('selected');
        });

        // Aggiungi nuova selezione
        event.target.classList.add('selected');
        this.selectedDate = dateString;

        // Mostra la data selezionata
        document.getElementById('selectedDate').textContent = 
            `Turni disponibili per ${date.toLocaleDateString('it-IT', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            })}`;

        // Carica i turni per la data selezionata
        await this.loadTimeSlots(dateString);
        
        // Mostra la sezione turni
        document.getElementById('timeSlotsContainer').style.display = 'block';
    }

    async loadTimeSlots(date) {
        const slotsContainer = document.getElementById('timeSlots');
        slotsContainer.innerHTML = '<div class="loading">Caricamento turni...</div>';

        try {
            const response = await fetch(`api.php?action=getTimeSlots&date=${date}`);
            const data = await response.json();
            
            if (data.error) {
                slotsContainer.innerHTML = `<div class="loading">Errore: ${data.error}</div>`;
                return;
            }
            
            slotsContainer.innerHTML = '';
            
            if (data.slots && data.slots.length > 0) {
                data.slots.forEach(slot => {
                    const slotElement = this.createTimeSlotElement(slot, date);
                    slotsContainer.appendChild(slotElement);
                });
            } else {
                slotsContainer.innerHTML = '<div class="loading">Nessun turno disponibile per questa data.</div>';
            }
        } catch (error) {
            console.error('Errore nel caricamento dei turni:', error);
            slotsContainer.innerHTML = '<div class="loading">Errore nel caricamento dei turni.</div>';
        }
    }

    createTimeSlotElement(slot, date) {
        const slotElement = document.createElement('div');
        slotElement.className = 'time-slot';
        slotElement.textContent = slot.name;
        
        const hasCredits = this.userCredits && this.userCredits.available_credits > 0;
        
        if (slot.status === 'available' && hasCredits) {
            slotElement.classList.add('available');
            slotElement.addEventListener('click', () => this.confirmBookSlot(date, slot.column, slot.name));
        } else if (slot.status === 'available' && !hasCredits) {
            slotElement.classList.add('occupied'); // Show as occupied if no credits
        } else if (slot.status === 'booked_by_me') {
            slotElement.classList.add('booked-by-me');
            slotElement.addEventListener('click', () => this.confirmCancelBooking(date, slot.column, slot.name));
        } else {
            slotElement.classList.add('occupied');
        }

        return slotElement;
    }

    confirmBookSlot(date, column, slotName) {
        if (!this.userCredits || this.userCredits.available_credits <= 0) {
            alert('Non hai crediti sufficienti per prenotare questa guida.');
            return;
        }
        
        const message = `Sei sicuro di voler prenotare il turno "${slotName}" per il ${new Date(date).toLocaleDateString('it-IT')}?`;
        if (confirm(message)) {
            this.bookSlot(date, column);
        }
    }

    confirmCancelBooking(date, column, slotName) {
        const message = `Sei sicuro di voler cancellare la prenotazione per il turno "${slotName}" del ${new Date(date).toLocaleDateString('it-IT')}?`;
        if (confirm(message)) {
            this.cancelBooking(date, column);
        }
    }

    async bookSlot(date, column) {
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'bookSlot',
                    date: date,
                    column: column
                })
            });

            const result = await response.json();
            
            if (result.success) {
                alert('Prenotazione effettuata con successo!');
                await this.loadUserCredits(); // Ricarica i crediti
                await this.loadAvailableDates(); // Ricarica le date per aggiornare i pallini
                await this.loadTimeSlots(date);
            } else {
                alert('Errore nella prenotazione: ' + (result.message || 'Errore sconosciuto'));
            }
        } catch (error) {
            console.error('Errore nella prenotazione:', error);
            alert('Errore nella prenotazione: ' + error.message);
        }
    }

    async cancelBooking(date, column) {
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'cancelBooking',
                    date: date,
                    column: column
                })
            });

            const result = await response.json();
            
            if (result.success) {
                alert('Prenotazione cancellata con successo!');
                await this.loadUserCredits(); // Ricarica i crediti
                await this.loadAvailableDates(); // Ricarica le date per aggiornare i pallini
                await this.loadTimeSlots(date);
            } else {
                alert('Errore nella cancellazione: ' + (result.message || 'Errore sconosciuto'));
            }
        } catch (error) {
            console.error('Errore nella cancellazione:', error);
            alert('Errore nella cancellazione: ' + error.message);
        }
    }

    formatDate(date) {
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0');
    }
}

// Inizializza il calendario quando la pagina è caricata
document.addEventListener('DOMContentLoaded', () => {
    new BookingCalendar();
});
