import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
