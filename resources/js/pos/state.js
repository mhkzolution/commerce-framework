let currentState = null;
const listeners = new Set();

export function getState() {
    return currentState;
}

export function setState(next) {
    currentState = next;
    listeners.forEach((listener) => listener(currentState));
}

export function subscribe(listener) {
    listeners.add(listener);
    return () => listeners.delete(listener);
}

export function showToast(message, isError = true) {
    const toast = document.getElementById('pos-toast');
    if (!toast) return;

    if (!message) {
        toast.classList.add('hidden');
        return;
    }

    toast.textContent = message;
    toast.classList.remove('hidden');
    toast.style.borderColor = isError ? '' : 'color-mix(in srgb, var(--color-success) 35%, var(--color-border))';
    toast.style.background = isError ? '' : 'color-mix(in srgb, var(--color-success) 10%, var(--color-surface))';
    toast.style.color = isError ? '' : 'var(--color-success)';

    clearTimeout(showToast._timer);
    showToast._timer = setTimeout(() => showToast('', false), 4000);
}
