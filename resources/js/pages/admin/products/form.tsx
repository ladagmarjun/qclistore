import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Field, PageHead, UploadButton } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import { toast } from '@/lib/feedback';
import { date } from '@/lib/format';
import { deleteProduct } from '@/lib/products';
import type { Brand, Category, Product, ProductImage } from '@/types';

interface Props {
    product: Product | null;
    categories: Category[];
    brands: Brand[];
}

const MARKETPLACES = [
    { key: 'shopee', label: 'Shopee' },
    { key: 'lazada', label: 'Lazada' },
    { key: 'tiktok', label: 'TikTok Shop' },
] as const;

const strOrNull = (value: string) => (value.trim() === '' ? null : value.trim());
const numOrNull = (value: string) => (value.trim() === '' ? null : Number(value));

export default function ProductForm({ product, categories, brands }: Props) {
    const p = product;
    const form = useForm({
        name: p?.name ?? '',
        slug: p?.slug ?? '',
        description: p?.description ?? '',
        category_id: p?.category_id ? String(p.category_id) : '',
        brand: p?.brand ?? '',
        tag: p?.tag ?? '',
        price: p?.price ?? '',
        was_price: p?.was_price ?? '',
        stock: String(p?.stock ?? 0),
        leather_type: p?.leather_type ?? '',
        hardware: p?.hardware ?? '',
        dimensions: p?.dimensions ?? '',
        colors: p?.colors ?? [],
        color_stock: Object.fromEntries((p?.colors ?? []).map((c) => [c, String(p?.color_stock?.[c] ?? 0)])) as Record<string, string>,
        glyph: p?.glyph ?? '👜',
        image_url: p?.image_url ?? '',
        images: (p?.images ?? []) as ProductImage[],
        links: { shopee: p?.links.shopee ?? '', lazada: p?.links.lazada ?? '', tiktok: p?.links.tiktok ?? '' },
        rating: p?.rating ?? '5.0',
        review_count: String(p?.review_count ?? 0),
        is_active: p?.is_active ?? true,
    });
    const [newColor, setNewColor] = useState('');
    const { data, setData } = form;

    // Errors come back keyed by field path ("links.shopee", "images.0.url"); show each under its field.
    const errors = form.errors as Record<string, string | undefined>;
    const error = (key: string) => errors[key] ?? Object.entries(errors).find(([k]) => k.startsWith(`${key}.`))?.[1];

    const hasColors = data.colors.length > 0;
    const colorTotal = data.colors.reduce((sum, c) => sum + (Number(data.color_stock[c]) || 0), 0);

    const addColor = () => {
        const value = newColor.trim();
        if (value && !data.colors.some((c) => c.toLowerCase() === value.toLowerCase())) {
            // The first colour starts with the current total stock, so adding colours never silently zeroes it.
            const start = hasColors ? '0' : data.stock || '0';
            setData((d) => ({ ...d, colors: [...d.colors, value], color_stock: { ...d.color_stock, [value]: start } }));
        }
        setNewColor('');
    };

    const removeColor = (color: string) =>
        setData((d) => {
            const colors = d.colors.filter((c) => c !== color);
            const colorStock = Object.fromEntries(Object.entries(d.color_stock).filter(([c]) => c !== color));
            // Removing the last colour keeps its stock as the product's total.
            const stock = colors.length ? d.stock : (d.color_stock[color] ?? d.stock);
            return { ...d, colors, color_stock: colorStock, stock };
        });

    const setImage = (index: number, changes: Partial<ProductImage>) =>
        setData(
            'images',
            data.images.map((img, i) => (i === index ? { ...img, ...changes } : img)),
        );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        form.transform((d) => ({
            name: d.name.trim(),
            slug: strOrNull(d.slug),
            description: strOrNull(d.description),
            category_id: numOrNull(d.category_id),
            brand: strOrNull(d.brand),
            tag: strOrNull(d.tag),
            price: numOrNull(d.price),
            was_price: numOrNull(d.was_price),
            stock: d.colors.length ? d.colors.reduce((sum, c) => sum + (Number(d.color_stock[c]) || 0), 0) : numOrNull(d.stock),
            color_stock: d.colors.map((c) => ({ color: c, stock: Number(d.color_stock[c]) || 0 })),
            leather_type: strOrNull(d.leather_type),
            hardware: strOrNull(d.hardware),
            dimensions: strOrNull(d.dimensions),
            colors: d.colors,
            glyph: strOrNull(d.glyph),
            image_url: strOrNull(d.image_url),
            images: d.images.filter((img) => img.url.trim()).map((img) => ({ url: img.url.trim(), color: img.color || null })),
            links: { shopee: strOrNull(d.links.shopee), lazada: strOrNull(d.links.lazada), tiktok: strOrNull(d.links.tiktok) },
            rating: numOrNull(d.rating),
            review_count: numOrNull(d.review_count),
            is_active: d.is_active,
        }));

        const options = {
            preserveScroll: true,
            onError: () => toast('Please fix the highlighted fields.', true),
        };

        if (p) form.put(`/admin/products/${p.id}`, options);
        else form.post('/admin/products', options);
    };

    return (
        <AdminLayout title={p ? p.name : 'Add product'}>
            <PageHead
                title={p ? p.name : 'Add product'}
                sub={p ? `Last updated ${date(p.updated_at)}` : 'Fill in the details, then save to publish it to the shop.'}
                crumb={
                    <>
                        <Link href="/admin/products">Products</Link> / {p ? 'Edit' : 'New'}
                    </>
                }
            />

            <form noValidate onSubmit={submit}>
                <div className="form-layout">
                    <div>
                        <div className="panel">
                            <h3>Details</h3>
                            <Field label="Name *" htmlFor="f-name" error={error('name')}>
                                <input id="f-name" type="text" required value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            </Field>
                            <Field label="Description" htmlFor="f-desc" error={error('description')}>
                                <textarea id="f-desc" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                            </Field>
                            <div className="row">
                                <Field label="Category" htmlFor="f-cat" error={error('category_id')}>
                                    <select id="f-cat" value={data.category_id} onChange={(e) => setData('category_id', e.target.value)}>
                                        <option value="">No category</option>
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label="Brand" htmlFor="f-brand" error={error('brand')}>
                                    <input id="f-brand" type="text" list="brand-list" value={data.brand} onChange={(e) => setData('brand', e.target.value)} />
                                    <datalist id="brand-list">
                                        {brands.map((b) => (
                                            <option key={b.id} value={b.name} />
                                        ))}
                                    </datalist>
                                </Field>
                            </div>
                        </div>

                        <div className="panel">
                            <h3>Pricing &amp; stock</h3>
                            <div className="row">
                                <Field label="Price (₱) *" htmlFor="f-price" error={error('price')}>
                                    <input id="f-price" type="number" min="0" step="0.01" required value={data.price} onChange={(e) => setData('price', e.target.value)} />
                                </Field>
                                <Field label="Compare-at price (₱)" htmlFor="f-was" hint="Shown struck through when set." error={error('was_price')}>
                                    <input id="f-was" type="number" min="0" step="0.01" value={data.was_price} onChange={(e) => setData('was_price', e.target.value)} />
                                </Field>
                                <Field
                                    label={hasColors ? 'Total stock' : 'Stock *'}
                                    htmlFor="f-stock"
                                    hint={hasColors ? 'Sum of the colours below.' : undefined}
                                    error={error('stock')}
                                >
                                    {hasColors ? (
                                        <input id="f-stock" type="number" value={colorTotal} readOnly disabled />
                                    ) : (
                                        <input id="f-stock" type="number" min="0" step="1" required value={data.stock} onChange={(e) => setData('stock', e.target.value)} />
                                    )}
                                </Field>
                            </div>
                            <Field
                                label="Colours & stock"
                                htmlFor="f-color"
                                hint="Each colour has its own stock. A colour at 0 shows as sold out."
                                error={error('colors')}
                            >
                                {hasColors ? (
                                    <div className="stock-rows">
                                        {data.colors.map((c, i) => (
                                            <div className="stock-row" key={c}>
                                                <span className="name">
                                                    {c}
                                                    {(Number(data.color_stock[c]) || 0) === 0 && <span className="badge low">Sold out</span>}
                                                </span>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="1"
                                                    aria-label={`Stock for ${c}`}
                                                    value={data.color_stock[c] ?? '0'}
                                                    onChange={(e) => setData('color_stock', { ...data.color_stock, [c]: e.target.value })}
                                                />
                                                <button type="button" className="x" aria-label={`Remove ${c}`} onClick={() => removeColor(c)}>
                                                    ×
                                                </button>
                                                {error(`color_stock.${i}`) && <div className="error">{error(`color_stock.${i}`)}</div>}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="muted stock-empty">No colours yet, so stock is one number.</p>
                                )}
                                <div className="add-row">
                                    <input
                                        id="f-color"
                                        type="text"
                                        placeholder="Type a colour and press Enter"
                                        value={newColor}
                                        onChange={(e) => setNewColor(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                addColor();
                                            }
                                        }}
                                    />
                                    <button type="button" className="btn secondary small" onClick={addColor}>
                                        Add colour
                                    </button>
                                </div>
                            </Field>
                        </div>

                        <div className="panel">
                            <h3>Materials</h3>
                            <div className="row">
                                <Field label="Leather type" htmlFor="f-leather" error={error('leather_type')}>
                                    <input id="f-leather" type="text" placeholder="e.g. Full-grain" value={data.leather_type} onChange={(e) => setData('leather_type', e.target.value)} />
                                </Field>
                                <Field label="Hardware" htmlFor="f-hw" error={error('hardware')}>
                                    <input id="f-hw" type="text" placeholder="e.g. Gold-tone" value={data.hardware} onChange={(e) => setData('hardware', e.target.value)} />
                                </Field>
                                <Field label="Dimensions" htmlFor="f-dim" error={error('dimensions')}>
                                    <input id="f-dim" type="text" placeholder="30 x 25 x 13 cm" value={data.dimensions} onChange={(e) => setData('dimensions', e.target.value)} />
                                </Field>
                            </div>
                        </div>

                        <div className="panel">
                            <h3>Media</h3>
                            <Field label="Main image" htmlFor="f-img" hint="Upload a photo (JPG, PNG, WebP or GIF, up to 5 MB) or paste an image link." error={error('image_url')}>
                                <div className="add-row">
                                    <input id="f-img" type="url" placeholder="https://…" value={data.image_url} onChange={(e) => setData('image_url', e.target.value)} />
                                    <UploadButton onUploaded={([url]) => setData((d) => ({ ...d, image_url: url }))} />
                                </div>
                            </Field>
                            <Field label="Gallery (optionally one per colour)" error={error('images')}>
                                <div className="img-rows">
                                    {data.images.map((img, i) => (
                                        <div className="img-row" key={i}>
                                            {img.url ? <img className="thumb" src={img.url} alt="" /> : <span className="thumb" />}
                                            <input type="url" placeholder="https://…" value={img.url} onChange={(e) => setImage(i, { url: e.target.value })} />
                                            <select value={img.color ?? ''} onChange={(e) => setImage(i, { color: e.target.value || null })}>
                                                <option value="">Any colour</option>
                                                {[...new Set([...data.colors, img.color].filter((c): c is string => !!c))].map((c) => (
                                                    <option key={c}>{c}</option>
                                                ))}
                                            </select>
                                            <button
                                                type="button"
                                                className="x"
                                                aria-label="Remove image"
                                                onClick={() => setData('images', data.images.filter((_, j) => j !== i))}
                                            >
                                                ×
                                            </button>
                                        </div>
                                    ))}
                                </div>
                                <div className="add-row">
                                    <UploadButton
                                        label="Upload images"
                                        multiple
                                        onUploaded={(urls) => setData((d) => ({ ...d, images: [...d.images, ...urls.map((url) => ({ url, color: null }))] }))}
                                    />
                                    <button type="button" className="btn secondary small" onClick={() => setData('images', [...data.images, { url: '', color: null }])}>
                                        Add image link
                                    </button>
                                </div>
                            </Field>
                        </div>

                        <div className="panel">
                            <h3>Marketplace links</h3>
                            {MARKETPLACES.map(({ key, label }) => (
                                <Field key={key} label={label} htmlFor={`f-l-${key}`} error={error(`links.${key}`)}>
                                    <input
                                        id={`f-l-${key}`}
                                        type="url"
                                        placeholder="https://…"
                                        value={data.links[key]}
                                        onChange={(e) => setData('links', { ...data.links, [key]: e.target.value })}
                                    />
                                </Field>
                            ))}
                        </div>
                    </div>

                    <aside>
                        <div className="panel">
                            <h3>Visibility</h3>
                            <label className="check">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Show in shop
                            </label>
                        </div>
                        <div className="panel">
                            <h3>Preview</h3>
                            <div className="preview-img">{data.image_url.trim() ? <img src={data.image_url.trim()} alt="" /> : data.glyph || '👜'}</div>
                            <Field label="Tag" htmlFor="f-tag" error={error('tag')}>
                                <input id="f-tag" type="text" placeholder="e.g. New, Sale, Bestseller" value={data.tag} onChange={(e) => setData('tag', e.target.value)} />
                            </Field>
                            <Field label="Fallback icon" htmlFor="f-glyph" hint="Shown when there is no image." error={error('glyph')}>
                                <input id="f-glyph" type="text" maxLength={10} value={data.glyph} onChange={(e) => setData('glyph', e.target.value)} />
                            </Field>
                        </div>
                        <div className="panel">
                            <h3>SEO &amp; reviews</h3>
                            <Field label="URL slug" htmlFor="f-slug" error={error('slug')}>
                                <input id="f-slug" type="text" placeholder="Generated from name" value={data.slug} onChange={(e) => setData('slug', e.target.value)} />
                            </Field>
                            <div className="row">
                                <Field label="Rating" htmlFor="f-rating" error={error('rating')}>
                                    <input id="f-rating" type="number" min="0" max="5" step="0.1" value={data.rating} onChange={(e) => setData('rating', e.target.value)} />
                                </Field>
                                <Field label="Reviews" htmlFor="f-rc" error={error('review_count')}>
                                    <input id="f-rc" type="number" min="0" step="1" value={data.review_count} onChange={(e) => setData('review_count', e.target.value)} />
                                </Field>
                            </div>
                        </div>
                    </aside>
                </div>

                <div className="form-actions">
                    {p && (
                        <button type="button" className="btn danger push-left" onClick={() => deleteProduct(p)}>
                            Delete
                        </button>
                    )}
                    <Link className="btn secondary" href="/admin/products">
                        Cancel
                    </Link>
                    <button className="btn" type="submit" disabled={form.processing}>
                        {p ? 'Save changes' : 'Add product'}
                    </button>
                </div>
            </form>
        </AdminLayout>
    );
}
