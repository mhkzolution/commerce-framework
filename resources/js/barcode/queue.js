/**
 * Barcode Center — in-memory print queue (source-agnostic).
 */

import {
    queueItemFromPayload,
    queueItemKey,
    queueItemToExpandedLabel,
    queueItemToPayload,
} from './queue-item.js';

export class BarcodeQueue {
    /** @type {Array<import('./queue-item.js').QueueItem>} */
    #lines = [];
    #nextId = 1;
    #listeners = new Set();

    subscribe(listener) {
        this.#listeners.add(listener);
        return () => this.#listeners.delete(listener);
    }

    #notify() {
        this.#listeners.forEach((fn) => fn(this.snapshot()));
    }

    snapshot() {
        const totalLabels = this.#lines.reduce((sum, line) => sum + line.quantity, 0);

        return {
            lines: this.#lines.map((line, index) => ({ ...line, position: index })),
            totalLabels,
            totalProducts: this.#lines.length,
        };
    }

    /**
     * @param {Omit<import('./queue-item.js').QueueItem, 'id'>} item
     */
    addItem(item) {
        const key = queueItemKey(/** @type {import('./queue-item.js').QueueItem} */ (item));
        const existing = this.#lines.find((line) => queueItemKey(line) === key);

        if (existing) {
            existing.quantity += Math.max(1, item.quantity);
        } else {
            this.#lines.push({
                ...item,
                id: String(this.#nextId++),
                quantity: Math.max(1, item.quantity),
            });
        }

        this.#notify();
    }

    remove(lineId) {
        this.#lines = this.#lines.filter((line) => line.id !== lineId);
        this.#notify();
    }

    setQuantity(lineId, quantity) {
        const line = this.#lines.find((l) => l.id === lineId);
        if (!line) return;
        line.quantity = Math.max(1, quantity);
        this.#notify();
    }

    duplicate(lineId) {
        const index = this.#lines.findIndex((l) => l.id === lineId);
        if (index === -1) return;

        const source = this.#lines[index];
        const clone = {
            ...source,
            id: String(this.#nextId++),
            quantity: 1,
        };
        this.#lines.splice(index + 1, 0, clone);
        this.#notify();
    }

    moveUp(lineId) {
        const index = this.#lines.findIndex((l) => l.id === lineId);
        if (index <= 0) return;
        [this.#lines[index - 1], this.#lines[index]] = [this.#lines[index], this.#lines[index - 1]];
        this.#notify();
    }

    moveDown(lineId) {
        const index = this.#lines.findIndex((l) => l.id === lineId);
        if (index === -1 || index >= this.#lines.length - 1) return;
        [this.#lines[index], this.#lines[index + 1]] = [this.#lines[index + 1], this.#lines[index]];
        this.#notify();
    }

    clear() {
        this.#lines = [];
        this.#notify();
    }

    /**
     * @param {Array<Record<string, unknown>>} lines
     */
    loadItems(lines) {
        this.#lines = lines.map((line) => ({
            ...queueItemFromPayload(line),
            id: String(this.#nextId++),
        }));
        this.#notify();
    }

    /**
     * @returns {Array<import('./queue-item.js').ExpandedLabel>}
     */
    expandedLabels() {
        const labels = [];

        for (const line of this.#lines) {
            const expanded = queueItemToExpandedLabel(line);
            for (let i = 0; i < line.quantity; i++) {
                labels.push(expanded);
            }
        }

        return labels;
    }

    /**
     * @returns {Array<Record<string, unknown>>}
     */
    toPayloadLines() {
        return this.#lines.map((line) => queueItemToPayload(line));
    }
}
