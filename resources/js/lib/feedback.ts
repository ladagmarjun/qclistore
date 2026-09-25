// Tiny global stores for the toast and the confirm dialog, so any page can call
// toast('Saved.') or `await confirmDialog({...})` without threading props around.

type Listener<T> = (value: T) => void;

function createStore<T>(initial: T) {
    let value = initial;
    const listeners = new Set<Listener<T>>();

    return {
        get: () => value,
        set(next: T) {
            value = next;
            listeners.forEach((listener) => listener(value));
        },
        subscribe(listener: Listener<T>) {
            listeners.add(listener);
            return () => {
                listeners.delete(listener);
            };
        },
    };
}

export interface ToastState {
    message: string;
    isError: boolean;
    id: number;
}

export const toastStore = createStore<ToastState | null>(null);

let toastId = 0;

export function toast(message: string, isError = false): void {
    toastStore.set({ message, isError, id: ++toastId });
}

export interface ConfirmRequest {
    title: string;
    message: string;
    confirmText?: string;
    resolve: (confirmed: boolean) => void;
}

export const confirmStore = createStore<ConfirmRequest | null>(null);

export function confirmDialog(options: Omit<ConfirmRequest, 'resolve'>): Promise<boolean> {
    return new Promise((resolve) => {
        confirmStore.set({
            ...options,
            resolve: (confirmed) => {
                confirmStore.set(null);
                resolve(confirmed);
            },
        });
    });
}
