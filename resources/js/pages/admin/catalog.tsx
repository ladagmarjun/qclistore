import { router, useForm } from '@inertiajs/react';
import { type ReactNode, useRef, useState } from 'react';
import { ActiveBadge, Field, PageHead, Thumb, UploadButton } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import { confirmDialog } from '@/lib/feedback';
import type { Banner, Brand, Category, Store } from '@/types';

// The four small catalog screens share this page; each one is described by a config entry.

type Row = Category | Brand | Store | Banner;
type ResourceKey = 'categories' | 'brands' | 'stores' | 'banners';

interface FieldConfig {
    name: string;
    label: string;
    type?: 'text' | 'url' | 'number' | 'textarea' | 'checkbox' | 'select';
    required?: boolean;
    /** Choices for a select field, built from the listed rows and the row being edited. */
    options?: (rows: any[], row: any | null) => Array<{ value: string; label: string }>;
    /** Label of a select field's blank choice, which submits null. */
    empty?: string;
    hint?: string;
    placeholder?: string;
    /** Initial value for a new row: checked state for a checkbox, chosen value for a select. */
    default?: boolean | string;
    /** Adds an Upload button that fills the field with the uploaded image's URL, stored under this folder. */
    upload?: 'banners' | 'categories';
}

interface ResourceConfig {
    title: string;
    singular: string;
    sub: string;
    columns: Array<{ label: string; num?: boolean; render: (row: any) => ReactNode }>;
    fields: FieldConfig[];
}

const BANNER_PLACEMENTS = [
    { value: 'hero', label: 'Hero (top)' },
    { value: 'middle', label: 'Middle' },
];

const PH_REGIONS = [
    'Metro Manila (NCR)',
    'Cordillera (CAR)',
    'Ilocos Region (Region I)',
    'Cagayan Valley (Region II)',
    'Central Luzon (Region III)',
    'CALABARZON (Region IV-A)',
    'MIMAROPA (Region IV-B)',
    'Bicol Region (Region V)',
    'Western Visayas (Region VI)',
    'Central Visayas (Region VII)',
    'Eastern Visayas (Region VIII)',
    'Zamboanga Peninsula (Region IX)',
    'Northern Mindanao (Region X)',
    'Davao Region (Region XI)',
    'SOCCSKSARGEN (Region XII)',
    'Caraga (Region XIII)',
    'BARMM',
];

const NameCell =({ title, sub }: { title: string; sub: string }) => (
    <>
        <span className="cell-title">{title}</span>
        <div className="cell-sub">{sub}</div>
    </>
);

const RESOURCES: Record<ResourceKey, ResourceConfig> = {
    categories: {
        title: 'Categories',
        singular: 'category',
        sub: 'Group products so shoppers can browse by type.',
        columns: [
            {
                label: 'Name',
                render: (r: Category) => (
                    <div className="prod-cell" style={r.parent_id ? { paddingLeft: 20 } : undefined}>
                        <Thumb imageUrl={r.image_url} glyph="🏷️" />
                        <div>
                            <NameCell title={r.parent_id ? `↳ ${r.name}` : r.name} sub={`/${r.slug}`} />
                        </div>
                    </div>
                ),
            },
            { label: 'Subcategories', num: true, render: (r: Category) => (r.parent_id ? '—' : (r.children_count ?? 0)) },
            { label: 'Products', num: true, render: (r: Category) => r.products_count ?? '—' },
            { label: 'Order', num: true, render: (r: Category) => r.sort_order },
        ],
        fields: [
            { name: 'name', label: 'Name', required: true },
            {
                name: 'parent_id',
                label: 'Parent category',
                type: 'select',
                empty: 'None (top-level)',
                hint: 'Subcategories go one level deep, so only top-level categories are listed.',
                options: (rows: Category[], row: Category | null) =>
                    rows.filter((c) => c.parent_id === null && c.id !== row?.id).map((c) => ({ value: String(c.id), label: c.name })),
            },
            { name: 'slug', label: 'Slug', hint: 'Leave blank to generate from the name.' },
            { name: 'image_url', label: 'Image', type: 'url', upload: 'categories', hint: 'Optional. Upload a photo (JPG, PNG, WebP or GIF, up to 5 MB) or paste an image link.' },
            { name: 'sort_order', label: 'Sort order', type: 'number' },
        ],
    },
    brands: {
        title: 'Brands',
        singular: 'brand',
        sub: 'Brands appear as suggestions when adding products.',
        columns: [
            { label: 'Name', render: (r: Brand) => <NameCell title={r.name} sub={`/${r.slug}`} /> },
            { label: 'Order', num: true, render: (r: Brand) => r.sort_order },
            { label: 'Status', render: (r: Brand) => <ActiveBadge on={r.is_active} /> },
        ],
        fields: [
            { name: 'name', label: 'Name', required: true },
            { name: 'slug', label: 'Slug', hint: 'Leave blank to generate from the name.' },
            { name: 'sort_order', label: 'Sort order', type: 'number' },
            { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
        ],
    },
    stores: {
        title: 'Store Locations',
        singular: 'store',
        sub: 'Physical branches listed on the storefront.',
        columns: [
            { label: 'Store', render: (r: Store) => <NameCell title={r.name} sub={[r.address, r.barangay && `Brgy. ${r.barangay}`, r.city, r.region].filter(Boolean).join(', ')} /> },
            { label: 'Hours', render: (r: Store) => r.hours },
            { label: 'Status', render: (r: Store) => <ActiveBadge on={r.is_active} /> },
        ],
        fields: [
            { name: 'name', label: 'Name', required: true },
            { name: 'address', label: 'Street address', required: true, placeholder: 'Unit / building / street' },
            { name: 'barangay', label: 'Barangay', required: true },
            { name: 'city', label: 'City / Municipality', required: true },
            { name: 'region', label: 'Region', type: 'select', required: true, empty: 'Select region', options: () => PH_REGIONS.map((r) => ({ value: r, label: r })) },
            { name: 'hours', label: 'Opening hours', placeholder: 'Mon–Sun 10:00 AM – 9:00 PM' },
            { name: 'map_url', label: 'Map link', type: 'url' },
            { name: 'sort_order', label: 'Sort order', type: 'number' },
            { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
        ],
    },
    banners: {
        title: 'Banners',
        singular: 'banner',
        sub: 'Slides for the home page slideshows: hero (top) and middle.',
        columns: [
            {
                label: 'Banner',
                render: (r: Banner) => (
                    <div className="prod-cell">
                        <img className="banner-thumb" src={r.image_url} alt="" loading="lazy" />
                        <div>
                            <div className="name">{r.headline || 'Untitled'}</div>
                            <div className="sub">{r.subtext}</div>
                        </div>
                    </div>
                ),
            },
            { label: 'Placement', render: (r: Banner) => BANNER_PLACEMENTS.find((p) => p.value === r.placement)?.label ?? r.placement },
            { label: 'Order', num: true, render: (r: Banner) => r.sort_order },
            { label: 'Status', render: (r: Banner) => <ActiveBadge on={r.is_active} /> },
        ],
        fields: [
            { name: 'placement', label: 'Placement', type: 'select', required: true, default: 'hero', options: () => BANNER_PLACEMENTS },
            { name: 'image_url', label: 'Image', type: 'url', required: true, upload: 'banners', hint: 'Upload a wide photo (JPG, PNG, WebP or GIF, up to 5 MB) or paste an image link.' },
            { name: 'headline', label: 'Headline' },
            { name: 'subtext', label: 'Subtext' },
            { name: 'link_url', label: 'Button link', placeholder: '/collections/womens' },
            { name: 'sort_order', label: 'Sort order', type: 'number' },
            { name: 'is_active', label: 'Active', type: 'checkbox', default: true },
        ],
    },
};

type FormValues = Record<string, string | boolean>;

function initialValues(fields: FieldConfig[], row: Row | null): FormValues {
    const record = row as unknown as Record<string, unknown> | null;

    return Object.fromEntries(
        fields.map((f) => {
            const value = record ? record[f.name] : undefined;
            if (f.type === 'checkbox') return [f.name, record ? Boolean(value) : Boolean(f.default)];
            if (!record && typeof f.default === 'string') return [f.name, f.default];
            if (f.type === 'number') return [f.name, String(value ?? 0)];
            return [f.name, value == null ? '' : String(value)];
        }),
    );
}

export default function Catalog({ resource, rows }: { resource: ResourceKey; rows: Row[] }) {
    const cfg = RESOURCES[resource];
    const [editing, setEditing] = useState<Row | null>(null);
    const panel = useRef<HTMLDivElement>(null);

    const edit = (row: Row | null) => {
        setEditing(row);
        requestAnimationFrame(() => {
            if (window.innerWidth <= 1000) panel.current?.scrollIntoView({ behavior: 'smooth' });
            panel.current?.querySelector<HTMLInputElement>('input, textarea')?.focus();
        });
    };

    const remove = async (row: Row) => {
        const name = 'name' in row ? row.name : row.headline || `this ${cfg.singular}`;
        const confirmed = await confirmDialog({
            title: `Delete ${cfg.singular}?`,
            message: `"${name}" will be permanently removed.`,
        });

        if (confirmed) {
            router.delete(`/admin/${resource}/${row.id}`, {
                preserveScroll: true,
                onSuccess: () => editing?.id === row.id && setEditing(null),
            });
        }
    };

    return (
        <AdminLayout title={cfg.title}>
            <PageHead
                title={cfg.title}
                sub={cfg.sub}
                actions={
                    <button type="button" className="btn" onClick={() => edit(null)}>
                        Add {cfg.singular}
                    </button>
                }
            />

            <div className="split">
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                {cfg.columns.map((c) => (
                                    <th key={c.label} className={c.num ? 'num' : undefined}>
                                        {c.label}
                                    </th>
                                ))}
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length ? (
                                rows.map((row) => (
                                    <tr key={row.id} className="clickable" onClick={(e) => !(e.target as HTMLElement).closest('button') && edit(row)}>
                                        {cfg.columns.map((c) => (
                                            <td key={c.label} className={c.num ? 'num' : undefined}>
                                                {c.render(row)}
                                            </td>
                                        ))}
                                        <td className="actions">
                                            <button type="button" className="link-btn" onClick={() => edit(row)}>
                                                Edit
                                            </button>
                                            <button type="button" className="link-btn danger" onClick={() => remove(row)}>
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={cfg.columns.length + 1} className="empty">
                                        No {cfg.title.toLowerCase()} yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="panel" ref={panel}>
                    {/* Keyed so switching rows starts a fresh form. */}
                    <ResourceForm key={`${resource}-${editing?.id ?? 'new'}`} resource={resource} cfg={cfg} rows={rows} row={editing} onDone={() => setEditing(null)} />
                </div>
            </div>
        </AdminLayout>
    );
}

function ResourceForm({ resource, cfg, rows, row, onDone }: { resource: ResourceKey; cfg: ResourceConfig; rows: Row[]; row: Row | null; onDone: () => void }) {
    const form = useForm<FormValues>(initialValues(cfg.fields, row));
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        form.transform((data) => {
            const payload: Record<string, unknown> = {};
            for (const f of cfg.fields) {
                const value = data[f.name];
                if (f.type === 'checkbox') payload[f.name] = Boolean(value);
                else if (f.type === 'number') payload[f.name] = String(value).trim() === '' ? 0 : Number(value);
                else if (f.name === 'slug' && String(value).trim() === '') continue; // let the server generate it
                else payload[f.name] = String(value).trim() === '' ? null : String(value).trim();
            }
            return payload;
        });

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onDone();
            },
        };

        if (row) form.put(`/admin/${resource}/${row.id}`, options);
        else form.post(`/admin/${resource}`, options);
    };

    return (
        <>
            <h3>{row ? `Edit ${cfg.singular}` : `New ${cfg.singular}`}</h3>
            <form noValidate onSubmit={submit}>
                {cfg.fields.map((f) => {
                    const id = `r-${f.name}`;
                    const value = form.data[f.name];

                    if (f.type === 'checkbox') {
                        return (
                            <Field key={f.name} error={errors[f.name]}>
                                <label className="check">
                                    <input type="checkbox" checked={Boolean(value)} onChange={(e) => form.setData(f.name, e.target.checked)} /> {f.label}
                                </label>
                            </Field>
                        );
                    }

                    if (f.type === 'select') {
                        return (
                            <Field key={f.name} label={`${f.label}${f.required ? ' *' : ''}`} htmlFor={id} hint={f.hint} error={errors[f.name]}>
                                <select id={id} value={String(value)} onChange={(e) => form.setData(f.name, e.target.value)}>
                                    {!f.required && <option value="">{f.empty ?? '—'}</option>}
                                    {f.options?.(rows, row).map((o) => (
                                        <option key={o.value} value={o.value}>
                                            {o.label}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                        );
                    }

                    if (f.upload) {
                        const url = String(value).trim();
                        return (
                            <Field key={f.name} label={`${f.label}${f.required ? ' *' : ''}`} htmlFor={id} hint={f.hint} error={errors[f.name]}>
                                {url && <img className={`${f.upload === 'banners' ? 'banner' : 'square'}-preview`} src={url} alt="" />}
                                <div className="add-row">
                                    <input id={id} type="url" placeholder="https://…" value={String(value)} onChange={(e) => form.setData(f.name, e.target.value)} />
                                    <UploadButton folder={f.upload} onUploaded={([uploaded]) => form.setData((d) => ({ ...d, [f.name]: uploaded }))} />
                                </div>
                            </Field>
                        );
                    }

                    return (
                        <Field key={f.name} label={`${f.label}${f.required ? ' *' : ''}`} htmlFor={id} hint={f.hint} error={errors[f.name]}>
                            {f.type === 'textarea' ? (
                                <textarea id={id} style={{ minHeight: 90 }} value={String(value)} onChange={(e) => form.setData(f.name, e.target.value)} />
                            ) : (
                                <input
                                    id={id}
                                    type={f.type ?? 'text'}
                                    min={f.type === 'number' ? 0 : undefined}
                                    step={f.type === 'number' ? 1 : undefined}
                                    placeholder={f.placeholder}
                                    value={String(value)}
                                    onChange={(e) => form.setData(f.name, e.target.value)}
                                />
                            )}
                        </Field>
                    );
                })}
                <div className="button-row">
                    <button className="btn" type="submit" disabled={form.processing}>
                        {row ? 'Save' : `Add ${cfg.singular}`}
                    </button>
                    {row && (
                        <button className="btn secondary" type="button" onClick={onDone}>
                            Cancel
                        </button>
                    )}
                </div>
            </form>
        </>
    );
}
