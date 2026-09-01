import { formatMoney, parseBahtToMinor } from './money.js';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function imageTag(url, className) {
    if (url) {
        return `<img src="${escapeHtml(url)}" alt="" class="${className}" loading="lazy">`;
    }
    return `<div class="${className} pos-product-result__image--placeholder" aria-hidden="true">📦</div>`;
}

function stockClass(warning) {
    if (warning === 'out') return 'pos-product-result__stock--out';
    if (warning === 'low') return 'pos-product-result__stock--low';
    return '';
}

function stockLabel(warning, stock) {
    if (warning === 'out') return 'หมด';
    if (warning === 'low') return `เหลือ ${stock}`;
    if (stock !== null && stock !== undefined) return `${stock} ชิ้น`;
    return null;
}

const PAYMENT_METHODS = [
    ['cash', 'เงินสด'],
    ['qr', 'QR'],
    ['transfer', 'โอน'],
    ['card', 'บัตร'],
    ['gift', 'ของขวัญ'],
    ['credit', 'เครดิต'],
];

let lastReceipt = null;

export function renderProductResults(products) {
    const container = document.getElementById('pos-product-results');
    if (!container) return;

    if (!products || products.length === 0) {
        container.innerHTML = `
            <div class="pos-cart__empty">
                <div class="pos-cart__empty-icon" aria-hidden="true">📦</div>
                <p class="pos-cart__empty-text">ไม่พบสินค้า</p>
                <p class="pos-cart__empty-hint">ลองค้นหาด้วยคำอื่นหรือสแกนบาร์โค้ด</p>
            </div>`;
        return;
    }

    container.innerHTML = `<div class="pos-product-grid__list" role="listbox">${products.map((product) => {
        const label = stockLabel(product.stock_warning, product.stock);
        return `
        <div class="pos-product-result" data-pos-product-result data-product-uuid="${escapeHtml(product.uuid)}" role="option" tabindex="-1">
            ${imageTag(product.image_url, 'pos-product-result__image')}
            <div class="pos-product-result__body">
                <p class="pos-product-result__name">${escapeHtml(product.name)}</p>
                ${product.sku ? `<p class="pos-product-result__meta">${escapeHtml(product.sku)}</p>` : ''}
                ${product.attributes?.length ? `<p class="pos-product-result__meta">${escapeHtml(product.attributes.join(' · '))}</p>` : ''}
            </div>
            <div class="pos-product-result__footer">
                <p class="pos-product-result__price">${escapeHtml(product.price)}</p>
                ${label ? `<span class="pos-product-result__stock ${stockClass(product.stock_warning)}">${escapeHtml(label)}</span>` : ''}
            </div>
        </div>`;
    }).join('')}</div>`;
}

export function renderCart(state) {
    const root = document.getElementById('pos-cart-root');
    if (!root || !state?.cart) return;

    const { cart } = state;

    if (!cart.lines?.length) {
        root.innerHTML = `
            <section class="pos-cart" aria-label="ตะกร้าสินค้า">
                <div class="pos-cart__header"><span>ตะกร้า</span></div>
                <div class="pos-cart__empty">
                    <div class="pos-cart__empty-icon" aria-hidden="true">🛒</div>
                    <p class="pos-cart__empty-text">ยังไม่มีสินค้าในตะกร้า</p>
                    <p class="pos-cart__empty-hint">สแกนบาร์โค้ดหรือค้นหาเพื่อเพิ่มสินค้า</p>
                </div>
            </section>`;
        return;
    }

    root.innerHTML = `
        <section class="pos-cart" aria-label="ตะกร้าสินค้า">
            <div class="pos-cart__header">
                <span>ตะกร้า</span>
                <span class="pos-cart__badge">${cart.item_count}</span>
            </div>
            <ul class="pos-cart__lines" role="list">
                ${cart.lines.map((line) => `
                    <li class="pos-cart-line ${line.price_overridden ? 'is-price-overridden' : ''}" data-pos-cart-line data-line-id="${escapeHtml(line.purchasable_uuid)}">
                        ${imageTag(line.image_url, 'pos-cart-line__image')}
                        <div class="pos-cart-line__body">
                            <p class="pos-cart-line__name">${escapeHtml(line.name)}${line.price_overridden ? ' <span class="pos-override-badge">ปรับราคา</span>' : ''}</p>
                            ${line.variant ? `<p class="pos-cart-line__variant">${escapeHtml(line.variant)}</p>` : ''}
                            <div class="pos-cart-line__controls">
                                <button type="button" class="pos-cart-line__qty-btn" data-pos-qty-decrease aria-label="ลดจำนวน">−</button>
                                <input type="number" class="pos-cart-line__qty-input" value="${line.quantity}" min="1" data-pos-qty-input aria-label="จำนวน">
                                <button type="button" class="pos-cart-line__qty-btn" data-pos-qty-increase aria-label="เพิ่มจำนวน">+</button>
                                <button type="button" class="pos-btn pos-btn--secondary pos-btn--icon" data-pos-action="override-price" title="ปรับราคา">฿</button>
                                <button type="button" class="pos-btn pos-btn--danger pos-btn--icon" data-pos-remove-line aria-label="ลบรายการ" title="ลบ">×</button>
                            </div>
                            ${line.stock_warning ? `<p class="pos-cart-line__warning">${escapeHtml(line.stock_warning)}</p>` : ''}
                        </div>
                        <div class="pos-cart-line__pricing">
                            <p class="text-xs text-muted">${escapeHtml(line.unit_price)}</p>
                            <p class="pos-cart-line__subtotal">${escapeHtml(line.subtotal)}</p>
                        </div>
                    </li>
                `).join('')}
            </ul>
        </section>`;
}

export function renderDiscount(state) {
    const root = document.getElementById('pos-discount-root');
    if (!root || !state?.cart) return;

    const coupon = state.cart.coupon_code;
    const promo = state.cart.promotion_name;

    root.innerHTML = `
        <section class="pos-right-section" aria-label="ส่วนลดและหมายเหตุ">
            <p class="pos-right-section__title">ส่วนลด & หมายเหตุ</p>
            <div class="mb-3">
                <label for="pos-discount-input" class="mb-1 block text-xs font-semibold text-muted">คูปอง / โค้ดส่วนลด</label>
                <div class="flex gap-2">
                    <input type="text" id="pos-discount-input" class="pos-input text-base" placeholder="กรอกรหัสคูปอง..." value="${escapeHtml(coupon || '')}">
                    <button type="button" class="pos-btn pos-btn--secondary" data-pos-action="apply-coupon"><kbd>F3</kbd></button>
                    ${coupon ? '<button type="button" class="pos-btn pos-btn--danger pos-btn--icon" data-pos-action="remove-coupon" title="ลบคูปอง">×</button>' : ''}
                </div>
                ${promo ? `<p class="mt-1 text-xs font-semibold text-primary">${escapeHtml(promo)}</p>` : ''}
            </div>
            <div>
                <label for="pos-notes-input" class="mb-1 block text-xs font-semibold text-muted">หมายเหตุการขาย</label>
                <textarea id="pos-notes-input" class="pos-input min-h-[3rem] resize-none text-sm" placeholder="บันทึกภายในสำหรับการขายครั้งนี้..." rows="2">${escapeHtml(state.notes || '')}</textarea>
            </div>
        </section>`;
}

export function renderCustomer(state) {
    const root = document.getElementById('pos-customer-root');
    if (!root || !state?.customer) return;

    const { customer } = state;
    const info = customer.customer;

    root.innerHTML = `
        <section class="pos-right-section" aria-label="ลูกค้า">
            <p class="pos-right-section__title">ลูกค้า</p>
            <div class="pos-customer">
                <div class="pos-customer__info">
                    ${customer.is_guest ? `
                        <p class="pos-customer__name">ลูกค้าทั่วไป</p>
                        <p class="pos-customer__meta">ยังไม่ได้เลือกลูกค้า</p>
                    ` : `
                        <p class="pos-customer__name">${escapeHtml(info?.name)}</p>
                        <p class="pos-customer__meta">
                            ${escapeHtml(info?.phone || info?.email || '')}
                            ${customer.tier ? `· <span class="pos-customer__badge">${escapeHtml(customer.tier)}</span>` : ''}
                        </p>
                        ${customer.reward_points !== null ? `<p class="pos-customer__meta">แต้มสะสม: ${Number(customer.reward_points).toLocaleString('th-TH')} แต้ม</p>` : ''}
                        ${customer.has_special_pricing ? '<p class="pos-customer__meta text-primary font-semibold">ใช้ราคาพิเศษ</p>' : ''}
                    `}
                </div>
                <div class="flex gap-2">
                    <button type="button" class="pos-btn pos-btn--secondary" data-pos-dialog-open="pos-customer-dialog"><kbd>F2</kbd> เลือก</button>
                    ${!customer.is_guest ? '<button type="button" class="pos-btn pos-btn--secondary" data-pos-action="detach-customer" aria-label="ยกเลิกลูกค้า">×</button>' : ''}
                </div>
            </div>
        </section>`;
}

export function renderSummary(state) {
    const root = document.getElementById('pos-summary-root');
    if (!root || !state?.totals) return;

    const t = state.totals;
    const payments = state.payment?.mixed_payments || [];

    root.innerHTML = `
        <section class="pos-right-section pos-right-section--sticky" aria-label="สรุปยอดชำระ">
            <div class="pos-summary">
                <div class="pos-summary__row"><span class="text-muted">ยอดรวม</span><span>${escapeHtml(t.subtotal)}</span></div>
                <div class="pos-summary__row"><span class="text-muted">ส่วนลด</span><span class="text-danger">−${escapeHtml(t.discount)}</span></div>
                <div class="pos-summary__row"><span class="text-muted">ภาษี</span><span>${escapeHtml(t.tax)}</span></div>
                <div class="pos-summary__row"><span class="text-muted">ค่าจัดส่ง</span><span>${escapeHtml(t.shipping)}</span></div>
                <div class="pos-summary__row pos-summary__row--total">
                    <span class="pos-summary__total-label">ยอดชำระ</span>
                    <span class="pos-summary__total-value" id="pos-grand-total">${escapeHtml(t.grand_total)}</span>
                </div>
                ${payments.length > 1 ? payments.map((p) => `
                    <div class="pos-summary__row text-xs"><span class="uppercase">${escapeHtml(p.method)}</span><span>${formatMoney(p.amount_minor, { minor: true, currency: t.currency })}</span></div>
                `).join('') : ''}
            </div>
        </section>`;
}

export function renderPayment(state) {
    const root = document.getElementById('pos-payment-root');
    if (!root || !state?.payment) return;

    const method = state.payment.method || 'cash';
    const statusLabel = { paid: 'ชำระแล้ว', unpaid: 'ยังไม่ชำระ', idle: 'ว่าง' }[state.payment.status] || state.payment.status;

    root.innerHTML = `
        <section class="pos-right-section" aria-label="การชำระเงิน">
            <p class="pos-right-section__title">วิธีชำระเงิน</p>
            <div class="pos-payment-methods" role="radiogroup">
                ${PAYMENT_METHODS.map(([key, label]) => `
                    <button type="button" class="pos-payment-method ${method === key ? 'is-selected' : ''}" data-pos-payment-method="${key}" role="radio">${label}</button>
                `).join('')}
            </div>
            <div class="mt-3 flex items-center justify-between gap-2">
                <button type="button" class="pos-btn pos-btn--secondary flex-1" data-pos-dialog-open="pos-payment-dialog"><kbd>F4</kbd> แบ่งชำระ</button>
                <span class="text-xs font-semibold uppercase text-muted">${escapeHtml(statusLabel)}</span>
            </div>
        </section>`;
}

export function renderMixedPaymentEditor(state) {
    const root = document.getElementById('pos-mixed-payments-root');
    if (!root) return;

    const grand = state?.totals?.grand_total_minor || 0;
    const method = state?.payment?.method || 'cash';
    let payments = state?.payment?.mixed_payments?.length
        ? state.payment.mixed_payments.map((payment) => ({ ...payment }))
        : [{ method, amount_minor: grand }];

    if (payments.length === 1) {
        payments[0] = {
            method: payments[0].method || method,
            amount_minor: grand,
        };
    }

    const methods = ['cash', 'qr', 'transfer', 'card', 'gift', 'credit'];

    root.innerHTML = payments.map((payment, index) => `
        <div class="pos-mixed-payment-row" data-payment-row="${index}">
            <select class="pos-input" data-payment-method-select>
                ${methods.map((m) => `<option value="${m}" ${payment.method === m ? 'selected' : ''}>${PAYMENT_METHODS.find(([k]) => k === m)?.[1] ?? m.toUpperCase()}</option>`).join('')}
            </select>
            <input type="number" class="pos-input" data-payment-amount-minor value="${(payment.amount_minor / 100).toFixed(2)}" step="0.01" min="0" inputmode="decimal">
            <button type="button" class="pos-btn pos-btn--danger pos-btn--icon" data-pos-remove-payment-row ${payments.length <= 1 ? 'hidden' : ''}>×</button>
        </div>
    `).join('');

    updatePaymentRemaining(state);
}

function updatePaymentRemaining(state) {
    const remaining = document.getElementById('pos-payment-remaining');
    if (!remaining) return;

    const grand = state?.totals?.grand_total_minor || 0;
    const rows = [...document.querySelectorAll('[data-payment-row]')];
    const allocated = rows.reduce((sum, row) => {
        const val = row.querySelector('[data-payment-amount-minor]')?.value || '0';
        return sum + parseBahtToMinor(val);
    }, 0);

    const diff = grand - allocated;
    remaining.textContent = diff === 0
        ? 'ยอดชำระครบแล้ว'
        : `คงเหลือ: ${formatMoney(diff, { minor: true, currency: state?.totals?.currency ?? 'THB' })}`;
    remaining.classList.toggle('text-danger', diff !== 0);
}

export function collectMixedPaymentsFromDom() {
    return [...document.querySelectorAll('[data-payment-row]')].map((row) => ({
        method: row.querySelector('[data-payment-method-select]')?.value || 'cash',
        amount_minor: parseBahtToMinor(row.querySelector('[data-payment-amount-minor]')?.value || '0'),
    }));
}

export function renderHolds(holds) {
    const list = document.getElementById('pos-hold-list');
    if (!list) return;

    if (!holds?.length) {
        list.innerHTML = '<p class="text-sm text-muted">ไม่มีบิลที่พักไว้</p>';
        return;
    }

    list.innerHTML = holds.map((hold) => `
        <button type="button" class="pos-btn pos-btn--secondary w-full justify-between" data-pos-resume-hold="${escapeHtml(hold.id)}">
            <span>${escapeHtml(hold.label)}</span>
            <span class="text-muted">${hold.item_count} รายการ</span>
        </button>
    `).join('');
}

export function renderReceipt(receipt) {
    lastReceipt = receipt;
    const preview = document.getElementById('pos-receipt-preview');
    const printBtn = document.getElementById('pos-print-receipt-btn');
    if (!preview || !receipt) return;

    preview.innerHTML = `
        <p class="text-lg font-bold">ขายสำเร็จ</p>
        <p class="mt-2 text-2xl font-extrabold text-primary">${escapeHtml(receipt.grand_total)}</p>
        <p class="mt-2 text-sm text-muted">ออเดอร์ #${escapeHtml(receipt.order_number)}</p>
        ${receipt.change_amount ? `<p class="mt-4 text-lg font-bold">เงินทอน: ${escapeHtml(receipt.change_amount)}</p>` : ''}
        ${(receipt.payments || []).length > 1 ? `
            <div class="mt-4 text-left text-sm">
                ${receipt.payments.map((p) => `<div>${escapeHtml(p.method)}: ${escapeHtml(p.amount)}</div>`).join('')}
            </div>
        ` : ''}
    `;

    if (printBtn) {
        printBtn.disabled = false;
        printBtn.dataset.printUrl = receipt.print_url || '';
    }

    const dialog = document.getElementById('pos-receipt-dialog');
    if (dialog) dialog.hidden = false;
}

export function getLastReceipt() {
    return lastReceipt;
}

export function renderAll(state) {
    renderCart(state);
    renderDiscount(state);
    renderCustomer(state);
    renderSummary(state);
    renderPayment(state);
    renderHolds(state.holds);
    renderMixedPaymentEditor(state);
}

export { updatePaymentRemaining };
