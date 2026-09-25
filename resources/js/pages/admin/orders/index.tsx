import { PageHead, Pager, rowLink, SearchInput, StatusBadge, useListFilters } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import { date, label, money, plural } from '@/lib/format';
import type { Order, OrderStatus, Paginated } from '@/types';

interface Props {
    orders: Paginated<Order>;
    filters: { status?: string; search?: string };
    statuses: OrderStatus[];
}

export default function OrdersIndex({ orders, filters, statuses }: Props) {
    const update = useListFilters('/admin/orders', filters);

    return (
        <AdminLayout title="Orders">
            <PageHead title="Orders" sub={plural(orders.meta.total, 'order')} />

            <div className="toolbar">
                <SearchInput value={filters.search} placeholder="Search name, email or #order…" onSearch={(search) => update({ search })} />
                <select className="inline" value={filters.status ?? ''} onChange={(e) => update({ status: e.target.value })}>
                    <option value="">All statuses</option>
                    {statuses.map((s) => (
                        <option key={s} value={s}>
                            {label(s)}
                        </option>
                    ))}
                </select>
            </div>

            <div className="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th className="num">Items</th>
                            <th className="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {orders.data.length ? (
                            orders.data.map((o) => (
                                <tr key={o.id} {...rowLink(`/admin/orders/${o.id}`)}>
                                    <td className="cell-title">#{o.id}</td>
                                    <td>{date(o.created_at)}</td>
                                    <td>
                                        {o.customer_name}
                                        <div className="cell-sub">{o.customer_email}</div>
                                    </td>
                                    <td>{label(o.payment_method)}</td>
                                    <td>
                                        <StatusBadge status={o.status} />
                                    </td>
                                    <td className="num">{o.items_count ?? '—'}</td>
                                    <td className="num">{money(o.total_amount)}</td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={7} className="empty">
                                    No orders found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <Pager meta={orders.meta} onPage={(page) => update({ page: String(page) })} />
        </AdminLayout>
    );
}
