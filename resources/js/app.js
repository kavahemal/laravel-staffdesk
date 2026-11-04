import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import Swal from 'sweetalert2';

window.Alpine = Alpine;

// SweetAlert2 styled with the theme's button classes instead of its own.
const button = 'rounded-lg px-4 py-2.5 text-sm font-medium shadow-theme-xs';
const confirmStyles = {
    danger: { icon: 'warning', confirmButton: `${button} bg-error-500 text-white hover:bg-error-600` },
    primary: { icon: 'question', confirmButton: `${button} bg-brand-500 text-white hover:bg-brand-600` },
};

const dialogClasses = {
    popup: 'rounded-2xl! font-sans',
    title: 'text-lg! font-semibold! text-gray-800!',
    htmlContainer: 'text-sm! text-gray-500!',
    actions: 'gap-3',
    cancelButton: `${button} border border-gray-300 bg-white text-gray-700 hover:bg-gray-50`,
};

const dialog = Swal.mixin({ buttonsStyling: false, reverseButtons: true, showCancelButton: true });

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    showCloseButton: true,
    timerProgressBar: true,
    customClass: { popup: 'rounded-xl! font-sans text-sm!' },
});

window.toast = (icon, title) => toast.fire({ icon, title, timer: icon === 'error' ? 6000 : 3000 });

// <form data-confirm="Delete department?"> asks before submitting. Optional:
// data-confirm-text="…" (message), data-confirm-button="Delete" (button label),
// data-confirm-style="danger|primary" (red for destructive actions – the default – or blue).
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.dataset.confirm) return;

    event.preventDefault();
    const { confirm, confirmText, confirmButton, confirmStyle } = form.dataset;
    const style = confirmStyles[confirmStyle] ?? confirmStyles.danger;

    const { isConfirmed } = await dialog.fire({
        icon: style.icon,
        title: confirm,
        text: confirmText ?? 'This action cannot be undone.',
        confirmButtonText: confirmButton ?? 'Delete',
        customClass: { ...dialogClasses, confirmButton: style.confirmButton },
    });
    if (isConfirmed) form.submit();
});

// Date pickers: <input data-datepicker> (use <x-date-input>). The form still sends Y-m-d;
// the user sees the same format as config('hrms.date_format'). Options via data attributes:
// data-datepicker="range", data-min-date="today", data-max-date="2026-12-31", data-disable-sundays.
window.initDatepickers = (root = document) => {
    root.querySelectorAll('input[data-datepicker]').forEach((input) => {
        if (input._flatpickr) return;

        const { datepicker, displayFormat, minDate, maxDate, disableSundays } = input.dataset;
        flatpickr(input, {
            mode: datepicker === 'range' ? 'range' : 'single',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: displayFormat || 'd M Y',
            minDate: minDate || null,
            maxDate: maxDate || null,
            disable: disableSundays !== undefined ? [(date) => date.getDay() === 0] : [],
            locale: { firstDayOfWeek: 1, rangeSeparator: ' – ' },
        });
    });
};

// Flash messages from the server (`->with('success' | 'error', …)`) are passed on <body data-flash-*>.
document.addEventListener('DOMContentLoaded', () => {
    window.initDatepickers();

    const { flashSuccess, flashError } = document.body.dataset;
    if (flashError) window.toast('error', flashError);
    else if (flashSuccess) window.toast('success', flashSuccess);
});

// Sidebar state (TailAdmin): expanded / collapsed on desktop, slide-in on mobile.
// Breakpoint 1280px = Tailwind `xl`, where the sidebar stops being an overlay.
const DESKTOP = 1280;

Alpine.store('sidebar', {
    isExpanded: false,
    isMobileOpen: false,
    isHovered: false,

    init() {
        this.isExpanded = window.innerWidth >= DESKTOP && localStorage.getItem('sidebarExpanded') !== 'false';
        window.addEventListener('resize', () => this.handleResize());
    },

    handleResize() {
        this.isMobileOpen = false;
        if (window.innerWidth >= DESKTOP) {
            this.isExpanded = localStorage.getItem('sidebarExpanded') !== 'false';
        }
    },

    toggleExpanded() {
        this.isExpanded = !this.isExpanded;
        this.isMobileOpen = false;
        if (window.innerWidth >= DESKTOP) {
            localStorage.setItem('sidebarExpanded', this.isExpanded);
        }
    },

    toggleMobileOpen() {
        this.isMobileOpen = !this.isMobileOpen;
    },

    setMobileOpen(value) {
        this.isMobileOpen = value;
    },

    setHovered(value) {
        if (window.innerWidth >= DESKTOP && !this.isExpanded) {
            this.isHovered = value;
        }
    },
});

// Register stores / plugins above this line – Alpine must start only once.
Alpine.start();
