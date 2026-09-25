import { Link, usePage } from '@inertiajs/react';
import { PageHead, rowLink, StatusBadge, Thumb } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import { money } from '@/lib/format';
import type { Order, Product } from '@/types';

interface Props {
    stats: {
        revenue: string;
        pending_orders: number;
        active_products: number;
        low_stock: number;
        customers: number;
    };
    recent_orders: Order[];
    low_stock_products: Product[];
}

export default function Dashboard({ stats, recent_orders, low_stock_products }: Props) {
    const firstName = usePage().props.auth.user?.name.split(' ')[0] ?? '';

    return (
        <AdminLayout title="Dashboard">
            <PageHead
                title={`Welcome back, ${firstName}`}
                sub="Here's what is happening in the store."
                actions={
                    <Link className="btn" href="/admin/products/create">
                        Add product
                    </Link>
                }
            />

            <div className="stats">
                <Stat label="Revenue" value={money(stats.revenue)} />
                <Stat label="Pending orders" value={stats.pending_orders} />
                <Stat label="Active products" value={stats.active_products} />
                <Stat label="Low stock" value={stats.low_stock} accent={stats.low_stock > 0} />
                <Stat label="Customers" value={stats.customers} />
            </div>

            <div className="grid-2">
                <section>
                    <div className="page-head compact">
                        <h2>Recent orders</h2>
                        <Link href="/admin/orders">View all</Link>
                    </div>
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th className="num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recent_orders.length ? (
                                    recent_orders.map((o) => (
                                        <tr key={o.id} {...rowLink(`/admin/orders/${o.id}`)}>
                                            <td>#{o.id}</td>
                                            <td>{o.customer_name}</td>
                                            <td>
                                                <StatusBadge status={o.status} />
                                            </td>
                                            <td className="num">{money(o.total_amount)}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={4} className="empty">
                                            No orders yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section>
                    <div className="page-head compact">
                        <h2>Running low</h2>
                        <Link href="/admin/products">All products</Link>
                    </div>
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th className="num">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                {low_stock_products.length ? (
                                    low_stock_products.map((p) => (
                                        <tr key={p.id} {...rowLink(`/admin/products/${p.id}/edit`)}>
                                            <td>
                                                <div className="prod-cell">
                                                    <Thumb imageUrl={p.image_url} glyph={p.glyph} />
                                                    <div>
                                                        <div className="name">{p.name}</div>
                                                        <div className="sub">{p.brand}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="num">
                                                <span className="badge low">{p.stock} left</span>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={2} className="empty">
                                            Everything is well stocked.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}

function Stat({ label, value, accent = false }: { label: string; value: string | number; accent?: boolean }) {
    return (
        <div className={`stat${accent ? ' accent' : ''}`}>
            <div className="label">{label}</div>
            <div className="value">{value}</div>
        </div>
    );
}
