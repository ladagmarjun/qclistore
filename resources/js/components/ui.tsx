import { router } from '@inertiajs/react';
import { type ReactNode, useEffect, useRef, useState } from 'react';
import { toast } from '@/lib/feedback';
import { label } from '@/lib/format';
import { type UploadFolder, uploadImage } from '@/lib/uploads';
import type { OrderStatus, Paginated } from '@/types';

export function PageHead({ title, sub, actions, crumb }: { title: string; sub?: ReactNode; actions?: ReactNode; crumb?: ReactNode }) {
    return (
        <div className="page-head">
            <div>
                {crumb && <div className="crumb">{crumb}</div>}
                <h1>{title}</h1>
                {sub && <p>{sub}</p>}
            </div>
            {actions && <div className="head-actions">{actions}</div>}
        </div>
    );
}

export function StatusBadge({ status }: { status: OrderStatus }) {
    return <span className={`badge status-${status}`}>{label(status)}</span>;
}

export function ActiveBadge({ on, yes = 'Active', no = 'Hidden' }: { on: boolean; yes?: string; no?: string }) {
    return <span className={`badge ${on ? 'on' : 'off'}`}>{on ? yes : no}</span>;
}

export function Thumb({ imageUrl, glyph }: { imageUrl: string | null | undefined; glyph?: string | null }) {
    return imageUrl ? <img className="thumb" src={imageUrl} alt="" loading="lazy" /> : <span className="thumb">{glyph || '👜'}</span>;
}

export function Field({ label: text, error, hint, htmlFor, children }: { label?: ReactNode; error?: string; hint?: ReactNode; htmlFor?: string; children: ReactNode }) {
    return (
        <div className={`field${error ? ' has-error' : ''}`}>
            {text && <label htmlFor={htmlFor}>{text}</label>}
            {children}
            {hint && <span className="hint">{hint}</span>}
            {error && <div className="error">{error}</div>}
        </div>
    );
}

/** Picks image files from the computer, uploads them one by one and hands back the URLs that succeeded. */
export function UploadButton({
    label: text = 'Upload',
    folder = 'products',
    multiple = false,
    onUploaded,
}: {
    label?: string;
    folder?: UploadFolder;
    multiple?: boolean;
    onUploaded: (urls: string[]) => void;
}) {
    const input = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState(false);

    const upload = async (files: File[]) => {
        setBusy(true);
        const urls: string[] = [];
        for (const file of files) {
            try {
                urls.push(await uploadImage(file, folder));
            } catch (e) {
                toast(e instanceof Error ? e.message : `Could not upload ${file.name}.`, true);
            }
        }
        setBusy(false);
        if (urls.length) onUploaded(urls);
    };

    return (
        <>
            <input
                ref={input}
                type="file"
                accept="image/jpeg,image/png,image/webp,image/gif"
                multiple={multiple}
                hidden
                onChange={(e) => {
                    const files = Array.from(e.target.files ?? []);
                    e.target.value = '';
                    if (files.length) upload(files);
                }}
            />
            <button type="button" className="btn secondary small" disabled={busy} onClick={() => input.current?.click()}>
                {busy ? 'Uploading…' : text}
            </button>
        </>
    );
}

type Filters = Record<string, string | undefined>;

/**
 * Keeps list filters in the query string: update({ status: 'pending' }) reloads the page with the new filter.
 * Changing any filter other than the page goes back to page 1.
 */
export function useListFilters(url: string, filters: Filters) {
    return (changes: Filters) => {
        const next: Filters = { ...filters, ...('page' in changes ? {} : { page: undefined }), ...changes };
        const query = Object.fromEntries(Object.entries(next).filter(([, v]) => v !== undefined && v !== ''));
        router.get(url, query, { preserveState: true, preserveScroll: 'page' in changes ? false : true, replace: true });
    };
}

/** Searches as you type, once typing pauses; Enter searches straight away. */
export function SearchInput({ value, placeholder, onSearch }: { value?: string; placeholder: string; onSearch: (value: string) => void }) {
    const [text, setText] = useState(value ?? '');
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);

    useEffect(() => () => clearTimeout(timer.current), []);

    const search = (next: string) => {
        clearTimeout(timer.current);
        if (next.trim() !== (value ?? '')) onSearch(next.trim());
    };

    return (
        <input
            type="search"
            placeholder={placeholder}
            value={text}
            onChange={(e) => {
                const next = e.target.value;
                setText(next);
                clearTimeout(timer.current);
                timer.current = setTimeout(() => search(next), 350);
            }}
            onKeyDown={(e) => {
                if (e.key === 'Enter') search(text);
            }}
        />
    );
}

export function Pager({ meta, onPage }: { meta: Paginated<unknown>['meta']; onPage: (page: number) => void }) {
    if (meta.last_page <= 1) return null;

    return (
        <div className="pager">
            <button type="button" disabled={meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)}>
                ← Previous
            </button>
            <span>
                Page {meta.current_page} of {meta.last_page}
            </span>
            <button type="button" disabled={meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)}>
                Next →
            </button>
        </div>
    );
}

/** Navigate when a table row is clicked, ignoring clicks on buttons, links and inputs inside it. */
export function rowLink(href: string) {
    return {
        className: 'clickable',
        onClick: (e: React.MouseEvent) => {
            if ((e.target as HTMLElement).closest('button, a, input, select')) return;
            router.visit(href);
        },
    };
}
