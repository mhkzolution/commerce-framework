import { apiPost, apiPatch, routeUrl } from './api.js';
import { getState, setState, showToast } from './state.js';
import {
    renderAll,
    renderReceipt,
    renderMixedPaymentEditor,
    collectMixedPaymentsFromDom,
    updatePaymentRemaining,
    getLastReceipt,
} from './render.js';
import { focusBarcodeInput } from './barcode.js';
import { parseBahtToMinor } from './money.js';

export function initCheckout() {
    document.addEventListener('click', async (event) => {
        const methodBtn = event.target.closest('[data-pos-payment-method]');
        if (methodBtn) {
            try {
                const data = await apiPatch(routeUrl('updatePaymentMethod'), { method: methodBtn.dataset.posPaymentMethod });
                setState(data);
                renderAll(data);
            } catch (error) {
                showToast(error.message);
            }
            return;
        }

        if (event.target.closest('[data-pos-action="checkout"]')) {
            openPaymentDialog();
            return;
        }

        if (event.target.closest('[data-pos-action="confirm-payment"]')) {
            event.preventDefault();
            await confirmPayment();
            return;
        }

        if (event.target.closest('[data-pos-action="add-payment-row"]')) {
            addPaymentRow();
            return;
        }

        if (event.target.closest('[data-pos-remove-payment-row]')) {
            event.target.closest('[data-payment-row]')?.remove();
            updatePaymentRemaining(getState());
            return;
        }

        if (event.target.closest('[data-pos-action="print-receipt"]')) {
            printReceipt();
            return;
        }

        const quick = event.target.closest('[data-pos-quick-amount]');
        if (quick) {
            handleQuickAmount(quick.dataset.posQuickAmount);
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.closest('[data-payment-amount-minor]') || event.target.closest('[data-payment-method-select]')) {
            updatePaymentRemaining(getState());
            setPaymentFeedback('');
        }
    });
}

export function openPaymentDialog() {
    const state = getState();
    if (!state?.cart?.lines?.length) {
        showToast('ยังไม่มีสินค้าในตะกร้า');
        return;
    }

    setPaymentFeedback('');
    renderMixedPaymentEditor(state);

    const dialog = document.getElementById('pos-payment-dialog');
    if (!dialog) {
        return;
    }

    dialog.hidden = false;
    document.getElementById('pos-payment-amount')?.focus();
}

function addPaymentRow() {
    const state = getState();
    const payments = collectMixedPaymentsFromDom();
    payments.push({ method: 'cash', amount_minor: 0 });
    state.payment.mixed_payments = payments;
    renderMixedPaymentEditor(state);
}

function setPaymentFeedback(message, type = 'error') {
    const feedback = document.getElementById('pos-payment-feedback');
    if (!feedback) {
        if (message) {
            showToast(message);
        }
        return;
    }

    if (!message) {
        feedback.hidden = true;
        feedback.textContent = '';
        feedback.className = 'pos-payment-feedback';
        return;
    }

    feedback.hidden = false;
    feedback.textContent = message;
    feedback.className = `pos-payment-feedback pos-payment-feedback--${type}`;
}

function setConfirmLoading(isLoading) {
    const button = document.getElementById('pos-confirm-payment-btn');
    if (!button) {
        return;
    }

    button.disabled = isLoading;
    button.textContent = isLoading ? 'กำลังดำเนินการ...' : 'ยืนยันชำระเงิน';
}

async function confirmPayment() {
    const state = getState();
    const payments = collectMixedPaymentsFromDom();
    const grand = state?.totals?.grand_total_minor || 0;
    const total = payments.reduce((sum, payment) => sum + payment.amount_minor, 0);

    if (!payments.length) {
        setPaymentFeedback('กรุณาระบุวิธีชำระเงิน');
        return;
    }

    if (total !== grand) {
        setPaymentFeedback('ยอดชำระต้องเท่ากับยอดรวม');
        showToast('ยอดชำระต้องเท่ากับยอดรวม');
        return;
    }

    const amountInput = document.getElementById('pos-payment-amount');
    let amountReceived = null;

    if (amountInput?.value) {
        amountReceived = parseBahtToMinor(amountInput.value);
        if (Number.isNaN(amountReceived)) {
            setPaymentFeedback('จำนวนเงินที่รับไม่ถูกต้อง');
            return;
        }
    }

    setConfirmLoading(true);
    setPaymentFeedback('');

    try {
        await apiPatch(routeUrl('updateMixedPayments'), { payments });

        const data = await apiPost(routeUrl('checkout'), {
            payments,
            payment_method: payments.length === 1 ? payments[0].method : 'mixed',
            amount_received: amountReceived,
        });

        setState(data);
        renderAll(data);

        document.getElementById('pos-payment-dialog').hidden = true;
        if (amountInput) {
            amountInput.value = '';
        }

        if (data.receipt) {
            renderReceipt(data.receipt);
            showToast('ชำระเงินสำเร็จ', false);
        } else {
            showToast('ชำระเงินสำเร็จ แต่ไม่พบข้อมูลใบเสร็จ', false);
        }

        focusBarcodeInput();
    } catch (error) {
        const message = error?.message || 'ไม่สามารถชำระเงินได้';
        setPaymentFeedback(message);
        showToast(message);
    } finally {
        setConfirmLoading(false);
    }
}

function handleQuickAmount(type) {
    const input = document.getElementById('pos-payment-amount');
    const state = getState();
    if (!input) {
        return;
    }

    if (type === 'clear') {
        input.value = '';
        return;
    }

    if (type === 'exact') {
        const grandMinor = state?.totals?.grand_total_minor || 0;
        input.value = (grandMinor / 100).toFixed(2);
        return;
    }

    const current = parseBahtToMinor(input.value) / 100;
    input.value = (current + parseInt(type, 10)).toFixed(2);
}

function printReceipt() {
    const receipt = getLastReceipt();
    const url = receipt?.print_url || document.getElementById('pos-print-receipt-btn')?.dataset.printUrl;
    if (!url) {
        showToast('ไม่พบใบเสร็จสำหรับพิมพ์');
        return;
    }
    window.open(url, '_blank', 'noopener,noreferrer,width=400,height=700');
}
