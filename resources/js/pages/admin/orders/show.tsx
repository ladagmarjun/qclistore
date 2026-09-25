import { Link, router } from '@inertiajs/react';
import { PageHead, rowLink, StatusBadge, Thumb } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import { confirmDialog, toast } from '@/lib/feedback';
import { date, firstError, label, money } from '@/lib/format';
import type { Order, OrderStatus } from '@/types';

interface Props {
    order: Order;
    nextStatuses: OrderStatus[];
}

export default function OrderShow({ order: o, nextStatuses }: Props) {
    const changeStatus = async (status: OrderStatus) => {
        if (
            status === 'cancelled' &&
            !(await confirmDialog({
                title: 'Cancel this order?',
                message: 'The items will be returned to stock. This cannot be undone.',
                confirmText: 'Cancel order',
            }))
        ) {
            return;
        }

        router.put(
            `/admin/orders/${o.id}`,
            { status },
            {
                preserveScroll: true,
                onError: (errors) => toast(firstError(errors) ?? 'Could not update the order.', true),
            },
        );
    };

    return (
        <AdminLayout title={`Order #${o.id}`}>
            <PageHead
                title={`Order #${o.id}`}
                sub={
                    <>
                        Placed {date(o.created_at)} · <StatusBadge status={o.status} />
                    </>
                }
                crumb={
                    <>
                        <Link href="/admin/orders">Orders</Link> / Detail
                    </>
                }
                actions={nextStatuses.map((s) => (
                    <button key={s} type="button" className={`btn ${s === 'cancelled' ? 'danger' : 'leather'}`} onClick={() => changeStatus(s)}>
                        {s === 'cancelled' ? 'Cancel order' : `Mark as ${label(s)}`}
                    </button>
                ))}
            />

            <div className="form-layout">
                <div>
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Colour</th>
                                    <th className="num">Price</th>
                                    <th className="num">Qty</th>
                                    <th className="num">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(o.items ?? []).map((i) => (
                                    <tr key={i.id} {...(i.product ? rowLink(`/admin/products/${i.product.id}/edit`) : {})}>
                                        <td>
                                            {i.product ? (
                                                <div className="prod-cell">
                                                    <Thumb imageUrl={i.product.image_url} glyph={i.product.glyph} />
                                                    <div className="name">{i.product.name}</div>
                                                </div>
                                            ) : (
                                                `Product #${i.product_id}`
                                            )}
                                        </td>
                                        <td>{i.color || '—'}</td>
                                        <td className="num">{money(i.unit_price)}</td>
                                        <td className="num">{i.quantity}</td>
                                        <td className="num">{money(i.subtotal)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <table className="totals">
                            <tbody>
                                <tr className="grand">
                                    <td>Total</td>
                                    <td className="num">{money(o.total_amount)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {o.notes && (
                        <div className="panel" style={{ marginTop: 24 }}>
                            <h3>Customer notes</h3>
                            <p style={{ margin: 0, whiteSpace: 'pre-line' }}>{o.notes}</p>
                        </div>
                    )}
                </div>

                <aside>
                    <div className="panel">
                        <h3>Customer</h3>
                        <dl className="kv">
                            <dt>Name</dt>
                            <dd>{o.customer_name}</dd>
                            <dt>Email</dt>
                            <dd>
                                <a href={`mailto:${o.customer_email}`}>{o.customer_email}</a>
                            </dd>
                            <dt>Phone</dt>
                            <dd>{o.customer_phone || '—'}</dd>
                            <dt>Account</dt>
                            <dd>{o.user ? o.user.name : 'Guest checkout'}</dd>
                        </dl>
                    </div>
                    <div className="panel">
                        <h3>Shipping</h3>
                        <p style={{ margin: 0, color: 'var(--ink)', whiteSpace: 'pre-line' }}>
                            {o.shipping_address}
                            <br />
                            {[o.city, o.province, o.postal_code].filter(Boolean).join(', ')}
                        </p>
                    </div>
                    <div className="panel">
                        <h3>Payment</h3>
                        <dl className="kv">
                            <dt>Method</dt>
                            <dd>{label(o.payment_method)}</dd>
                            <dt>Updated</dt>
                            <dd>{date(o.updated_at)}</dd>
                        </dl>
                    </div>
                </aside>
            </div>
        </AdminLayout>
    );
}
