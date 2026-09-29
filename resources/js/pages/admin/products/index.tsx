import { Link } from '@inertiajs/react';
import { ActiveBadge, PageHead, Pager, rowLink, SearchInput, Thumb, useListFilters } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import { categoryOption, money, plural } from '@/lib/format';
import { deleteProduct } from '@/lib/products';
import type { Category, Paginated, Product } from '@/types';

const SORTS = {
    newest: 'Newest',
    price_asc: 'Price: low to high',
    price_desc: 'Price: high to low',
    rating: 'Top rated',
    popular: 'Most reviewed',
};

interface Props {
    products: Paginated<Product>;
    categories: Category[];
    filters: { search?: string; category?: string; sort?: string };
}

export default function ProductsIndex({ products, categories, filters }: Props) {
    const update = useListFilters('/admin/products', filters);

    return (
        <AdminLayout title="Products">
            <PageHead
                title="Products"
                sub={`${plural(products.meta.total, 'product')} in the catalog`}
                actions={
                    <Link className="btn" href="/admin/products/create">
                        Add product
                    </Link>
                }
            />

            <div className="toolbar">
                <SearchInput value={filters.search} placeholder="Search name, brand, description…" onSearch={(search) => update({ search })} />
                <select className="inline" value={filters.category ?? ''} onChange={(e) => update({ category: e.target.value })}>
                    <option value="">All categories</option>
                    {categories.map((c) => (
                        <option key={c.id} value={c.slug}>
                            {categoryOption(c)}
                        </option>
                    ))}
                </select>
                <select className="inline" value={filters.sort ?? 'newest'} onChange={(e) => update({ sort: e.target.value })}>
                    {Object.entries(SORTS).map(([value, text]) => (
                        <option key={value} value={value}>
                            {text}
                        </option>
                    ))}
                </select>
            </div>

            <div className="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th className="num">Price</th>
                            <th className="num">Stock</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {products.data.length ? (
                            products.data.map((p) => (
                                <tr key={p.id} {...rowLink(`/admin/products/${p.id}/edit`)}>
                                    <td>
                                        <div className="prod-cell">
                                            <Thumb imageUrl={p.image_url} glyph={p.glyph} />
                                            <div>
                                                <div className="name">
                                                    {p.name} {p.tag && <span className="badge sale">{p.tag}</span>}
                                                </div>
                                                <div className="sub">{p.brand || '—'}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{p.category?.name ?? '—'}</td>
                                    <td className="num">
                                        {money(p.price)}
                                        {p.was_price && <span className="price-was">{money(p.was_price)}</span>}
                                    </td>
                                    <td className="num">{p.stock < 5 ? <span className="badge low">{p.stock}</span> : p.stock}</td>
                                    <td>
                                        <ActiveBadge on={p.is_active} />
                                    </td>
                                    <td className="actions">
                                        <Link href={`/admin/products/${p.id}/edit`}>Edit</Link>
                                        <button type="button" className="link-btn danger" onClick={() => deleteProduct(p)}>
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={6} className="empty">
                                    No products match these filters.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <Pager meta={products.meta} onPage={(page) => update({ page: String(page) })} />
        </AdminLayout>
    );
}
