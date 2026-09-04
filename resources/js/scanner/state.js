let state = {
    mode: 'stock-check',
    product: null,
    sku: '',
    quantity: 1,
    step: 'idle',
    history: [],
    pickLines: [],
    packLines: [],
    packedCount: 0,
};

const listeners = new Set();

export function getState() {
    return state;
}

export function setState(patch) {
    state = { ...state, ...patch };
    listeners.forEach((listener) => listener(state));
}

export function subscribe(listener) {
    listeners.add(listener);
    return () => listeners.delete(listener);
}

export function resetProduct() {
    setState({
        product: null,
        sku: '',
        quantity: 1,
        step: 'idle',
    });
}

export function setQuantity(value) {
    const qty = Math.max(1, parseInt(String(value), 10) || 1);
    setState({ quantity: qty });
}
