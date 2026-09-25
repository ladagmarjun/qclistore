import { router } from '@inertiajs/react';
import { PageHead } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';

interface Props {
    settings: { cart_enabled: boolean };
}

export default function Settings({ settings }: Props) {
    return (
        <AdminLayout title="Settings">
            <PageHead title="Settings" sub="Store-wide switches." />
            <div className="panel" style={{ maxWidth: 640 }}>
                <h3>Online ordering</h3>
                <p>When off, shoppers can still browse, but checkout is disabled and marketplace links are the only way to buy.</p>
                <label className="check">
                    <input
                        type="checkbox"
                        checked={settings.cart_enabled}
                        onChange={(e) => router.put('/admin/settings', { cart_enabled: e.target.checked }, { preserveScroll: true })}
                    />{' '}
                    Allow customers to place orders
                </label>
            </div>
        </AdminLayout>
    );
}
