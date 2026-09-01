/**
 * Barcode Center — layout calculation and preview rendering.
 */

import JsBarcode from 'jsbarcode';
import { previewLabelStyleAttribute } from './label-style.js';

const PAPER_MM = {
    a4: { width: 210, height: 297 },
    a5: { width: 148, height: 210 },
    thermal: { width: 50, height: 30 },
    custom: { width: 210, height: 297 },
};

const PREVIEW_SCALE = 2.5; // px per mm

/**
 * @param {Record<string, unknown>} settings
 */
export function getPaperDimensions(settings) {
    const size = settings.paper_size || 'a4';
    if (size === 'custom') {
        return {
            width: Number(settings.custom_width) || 210,
            height: Number(settings.custom_height) || 297,
        };
    }
    return PAPER_MM[size] || PAPER_MM.a4;
}

/**
 * @param {Record<string, unknown>} settings
 */
export function computeLayout(settings) {
    const paper = getPaperDimensions(settings);
    const rows = Math.max(1, Number(settings.rows) || 1);
    const columns = Math.max(1, Number(settings.columns) || 1);
    const marginTop = Number(settings.margin_top) || 0;
    const marginRight = Number(settings.margin_right) || 0;
    const marginBottom = Number(settings.margin_bottom) || 0;
    const marginLeft = Number(settings.margin_left) || 0;
    const spacingH = Number(settings.spacing_horizontal) || 0;
    const spacingV = Number(settings.spacing_vertical) || 0;
    const labelWidth = Number(settings.label_width) || 48.5;
    const labelHeight = Number(settings.label_height) || 25.4;

    const labelsPerPage = rows * columns;

    const cells = [];
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < columns; col++) {
            cells.push({
                left: marginLeft + col * (labelWidth + spacingH),
                top: marginTop + row * (labelHeight + spacingV),
                width: labelWidth,
                height: labelHeight,
            });
        }
    }

    return {
        paper,
        rows,
        columns,
        labelsPerPage,
        cells,
        pageWidthPx: paper.width * PREVIEW_SCALE,
        pageHeightPx: paper.height * PREVIEW_SCALE,
        scale: PREVIEW_SCALE,
    };
}

/**
 * @param {HTMLElement} pageEl
 * @param {Array<{owner_name: string, barcode: string, display_text: string}>} labels
 * @param {Record<string, unknown>} settings
 * @param {number} pageIndex
 * @param {boolean} showGuides
 */
export function renderPreviewPage(pageEl, labels, settings, pageIndex, showGuides) {
    const layout = computeLayout(settings);
    const start = pageIndex * layout.labelsPerPage;
    const pageLabels = labels.slice(start, start + layout.labelsPerPage);
    const totalPages = Math.max(1, Math.ceil(labels.length / layout.labelsPerPage) || 1);

    pageEl.style.width = `${layout.pageWidthPx}px`;
    pageEl.style.height = `${layout.pageHeightPx}px`;
    pageEl.innerHTML = '';

    layout.cells.forEach((cell, index) => {
        const label = pageLabels[index];
        const cellEl = document.createElement('div');
        cellEl.className = 'bc-preview__cell';
        cellEl.style.left = `${cell.left * layout.scale}px`;
        cellEl.style.top = `${cell.top * layout.scale}px`;
        cellEl.style.width = `${cell.width * layout.scale}px`;
        cellEl.style.height = `${cell.height * layout.scale}px`;

        if (label) {
            cellEl.innerHTML = buildLabelHtml(
                label.owner_name,
                label.barcode,
                settings,
                label.display_text,
            );
        }

        pageEl.appendChild(cellEl);
    });

    requestAnimationFrame(() => renderBarcodes(pageEl));

    return { layout, totalPages, currentPage: pageIndex + 1 };
}

function buildLabelHtml(ownerName, barcode, settings = {}, displayText = barcode) {
    const orientation = settings.label_orientation || 'vertical';
    const orientationClass = orientation === 'horizontal' ? 'bc-label--horizontal' : 'bc-label--vertical';
    const styleAttr = previewLabelStyleAttribute(settings);

    return `
        <div class="bc-label ${orientationClass}" data-bc-label style="${styleAttr}">
            <p class="bc-label__owner">${escapeHtml(ownerName)}</p>
            <div class="bc-label__barcode" aria-hidden="true">
                <svg data-bc-barcode-svg data-barcode="${escapeHtml(barcode)}"></svg>
            </div>
            <p class="bc-label__sku">${escapeHtml(displayText)}</p>
        </div>
    `;
}

function renderBarcodes(root) {
    root.querySelectorAll('[data-bc-barcode-svg]').forEach((svg) => {
        const barcode = svg.dataset.barcode || svg.dataset.sku;
        if (!barcode || !(svg instanceof SVGSVGElement)) {
            return;
        }

        const label = svg.closest('[data-bc-label]');
        const barcodeWrap = svg.closest('.bc-label__barcode');
        const availableHeight = barcodeWrap instanceof HTMLElement
            ? barcodeWrap.clientHeight
            : (label instanceof HTMLElement ? Math.max(24, label.clientHeight * 0.45) : 36);

        try {
            JsBarcode(svg, barcode, {
                format: 'CODE128',
                displayValue: false,
                margin: 0,
                height: Math.max(20, Math.min(48, availableHeight - 2)),
                width: 1.2,
            });
        } catch {
            // Invalid SKU for barcode rendering — keep label shell.
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * @param {HTMLElement} canvasEl
 * @param {number} zoomPercent
 */
export function applyZoom(canvasEl, zoomPercent) {
    canvasEl.style.transform = `scale(${zoomPercent / 100})`;
}
