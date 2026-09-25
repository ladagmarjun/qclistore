import { useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { confirmStore, toastStore } from '@/lib/feedback';

export function Toaster() {
    const toast = useSyncExternalStore(toastStore.subscribe, toastStore.get);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (!toast) return;
        setVisible(true);
        const timer = setTimeout(() => setVisible(false), 3200);
        return () => clearTimeout(timer);
    }, [toast]);

    return (
        <div className={`toast${visible ? ' show' : ''}${toast?.isError ? ' error' : ''}`} role="status" aria-live="polite">
            {toast?.message}
        </div>
    );
}

export function ConfirmHost() {
    const request = useSyncExternalStore(confirmStore.subscribe, confirmStore.get);
    const confirmButton = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!request) return;
        confirmButton.current?.focus();
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') request.resolve(false);
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [request]);

    if (!request) return null;

    return (
        <div className="modal-backdrop" onClick={(e) => e.target === e.currentTarget && request.resolve(false)}>
            <div className="modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
                <h2 id="confirm-title">{request.title}</h2>
                <p>{request.message}</p>
                <div className="actions">
                    <button type="button" className="btn secondary" onClick={() => request.resolve(false)}>
                        Cancel
                    </button>
                    <button type="button" className="btn danger" ref={confirmButton} onClick={() => request.resolve(true)}>
                        {request.confirmText ?? 'Delete'}
                    </button>
                </div>
            </div>
        </div>
    );
}
