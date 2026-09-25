import { router, usePage } from '@inertiajs/react';
import { ActiveBadge, PageHead, Pager, SearchInput, useListFilters } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import { confirmDialog, toast } from '@/lib/feedback';
import { date, firstError, label, plural } from '@/lib/format';
import type { Paginated, User } from '@/types';

interface Props {
    users: Paginated<User>;
    filters: { search?: string; role?: string };
}

function updateUser(user: User, changes: { role?: string; is_active?: boolean }) {
    router.put(`/admin/users/${user.id}`, changes, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => toast(firstError(errors) ?? 'Could not update the account.', true),
    });
}

async function toggleActive(user: User) {
    if (
        user.is_active &&
        !(await confirmDialog({
            title: 'Deactivate account?',
            message: `${user.name} will be signed out everywhere and won't be able to log in.`,
            confirmText: 'Deactivate',
        }))
    ) {
        return;
    }

    updateUser(user, { is_active: !user.is_active });
}

export default function UsersIndex({ users, filters }: Props) {
    const me = usePage().props.auth.user;
    const update = useListFilters('/admin/users', filters);

    return (
        <AdminLayout title="Customers">
            <PageHead title="Customers" sub={plural(users.meta.total, 'account')} />

            <div className="toolbar">
                <SearchInput value={filters.search} placeholder="Search name or email…" onSearch={(search) => update({ search })} />
                <select className="inline" value={filters.role ?? ''} onChange={(e) => update({ role: e.target.value })}>
                    <option value="">All roles</option>
                    <option value="customer">Customers</option>
                    <option value="admin">Admins</option>
                </select>
            </div>

            <div className="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Joined</th>
                            <th className="num">Orders</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.length ? (
                            users.data.map((u) => {
                                const self = u.id === me?.id;

                                return (
                                    <tr key={u.id}>
                                        <td>
                                            <span className="cell-title">{u.name}</span>
                                            {self && <span className="muted"> (you)</span>}
                                            <div className="cell-sub">
                                                {u.email}
                                                {u.phone && ` · ${u.phone}`}
                                            </div>
                                        </td>
                                        <td>{date(u.created_at)}</td>
                                        <td className="num">{u.orders_count ?? 0}</td>
                                        <td>
                                            {self ? (
                                                label(u.role)
                                            ) : (
                                                <select className="compact" value={u.role ?? 'customer'} onChange={(e) => updateUser(u, { role: e.target.value })}>
                                                    <option value="customer">Customer</option>
                                                    <option value="admin">Admin</option>
                                                </select>
                                            )}
                                        </td>
                                        <td>
                                            <ActiveBadge on={u.is_active} yes="Active" no="Deactivated" />
                                        </td>
                                        <td className="actions">
                                            {!self && (
                                                <button type="button" className={`link-btn${u.is_active ? ' danger' : ''}`} onClick={() => toggleActive(u)}>
                                                    {u.is_active ? 'Deactivate' : 'Reactivate'}
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })
                        ) : (
                            <tr>
                                <td colSpan={6} className="empty">
                                    No accounts found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <Pager meta={users.meta} onPage={(page) => update({ page: String(page) })} />
        </AdminLayout>
    );
}
