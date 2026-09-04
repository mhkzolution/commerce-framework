import { initConfig, getInitialState } from './api.js';
import { setState } from './state.js';
import { renderAll } from './render.js';
import { bindCartEvents, clearCart } from './cart.js';
import { initSearch } from './search.js';
import { initBarcode, focusBarcodeInput } from './barcode.js';
import { initCustomer, saveNotes } from './customer.js';
import { initCheckout } from './checkout.js';
import { initHold } from './hold.js';
import { initKeyboard } from './keyboard.js';
import { initDialogs } from './dialogs.js';
import { initOffline } from './offline.js';
import { initDiscount } from './discount.js';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('pos-app');
    if (!app) return;

    initConfig(app);
    setState(getInitialState());
    renderAll(getInitialState());

    initDialogs();
    initOffline();
    initDiscount();
    initSearch();
    initBarcode();
    initCustomer();
    initCheckout();
    initHold();
    bindCartEvents(app);
    initKeyboard({ clearCart });

    focusBarcodeInput();
    initClock();
    initNotesAutosave();
});

function initClock() {
    const clock = document.getElementById('pos-clock');
    if (!clock) return;

    const tick = () => {
        const now = new Date();
        clock.textContent = now.toLocaleString('th-TH', {
            day: 'numeric', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: false,
        }).replace(',', ' ·');
    };

    tick();
    setInterval(tick, 30000);
}

function initNotesAutosave() {
    document.addEventListener('input', (event) => {
        if (event.target.id !== 'pos-notes-input') return;
        clearTimeout(initNotesAutosave._timer);
        initNotesAutosave._timer = setTimeout(() => saveNotes(event.target.value), 500);
    });
}
