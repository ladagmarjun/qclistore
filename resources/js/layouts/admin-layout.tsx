import { Head, Link, usePage } from '@inertiajs/react';
import { type ReactNode, useEffect, useState } from 'react';
import { ConfirmHost, Toaster } from '@/components/feedback';

const NAV: Array<{ group: string; links: Array<{ href: string; label: string }> }> = [
    { group: 'Overview', links: [{ href: '/admin', label: 'Dashboard' }] },
    {
        group: 'Catalog',
        links: [
            { href: '/admin/products', label: 'Products' },
            { href: '/admin/categories', label: 'Categories' },
            { href: '/admin/brands', label: 'Brands' },
        ],
    },
    {
        group: 'Sales',
        links: [
            { href: '/admin/orders', label: 'Orders' },
            { href: '/admin/users', label: 'Customers' },
        ],
    },
    {
        group: 'Storefront',
        links: [
            { href: '/admin/banners', label: 'Banners' },
            { href: '/admin/stores', label: 'Store Locations' },
            { href: '/admin/settings', label: 'Settings' },
        ],
    },
];

function isActive(url: string, href: string): boolean {
    const path = url.split('?')[0];
    return href === '/admin' ? path === '/admin' : path === href || path.startsWith(`${href}/`);
}

export function TopBar({ children, onMenu }: { children?: ReactNode; onMenu?: () => void }) {
    return (
        <>
            <div className="announce">Store Admin · Manage your catalog, orders and storefront</div>
            <header className="topbar">
                <div className="topbar-left">
                    {onMenu && (
                        <button
                            type="button"
                            className="menu-toggle"
                            aria-label="Menu"
                            onClick={(e) => {
                                e.stopPropagation();
                                onMenu();
                            }}
                        >
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                                <path d="M3 6h18M3 12h18M3 18h18" />
                            </svg>
                        </button>
                    )}
                    <Link className="brandmark" href="/admin">
                        <img src="/images/peacock-logo.jpg" alt="Peacock Genuine Leather" />
                        <em>ADMIN</em>
                    </Link>
                </div>
                <div className="topbar-right">{children}</div>
            </header>
        </>
    );
}

export default function AdminLayout({ title, children }: { title: string; children: ReactNode }) {
    const { url, props } = usePage();
    const [navOpen, setNavOpen] = useState(false);

    // Close the mobile menu after navigating.
    useEffect(() => setNavOpen(false), [url]);

    return (
        <>
            <Head title={title} />
            <TopBar onMenu={() => setNavOpen((open) => !open)}>
                <span className="who">{props.auth.user?.name}</span>
                <Link href="/admin/logout" method="post" as="button" className="link-btn">
                    Log out
                </Link>
            </TopBar>
            <div className={`shell${navOpen ? ' nav-open' : ''}`} onClick={() => navOpen && setNavOpen(false)}>
                <nav className="sidebar" onClick={(e) => e.stopPropagation()}>
                    {NAV.map((section) => (
                        <div key={section.group}>
                            <div className="group-label">{section.group}</div>
                            {section.links.map((link) => (
                                <Link key={link.href} href={link.href} className={isActive(url, link.href) ? 'active' : undefined}>
                                    {link.label}
                                </Link>
                            ))}
                        </div>
                    ))}
                </nav>
                <main className="main">{children}</main>
            </div>
            <Toaster />
            <ConfirmHost />
        </>
    );
}
