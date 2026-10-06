// Shapes of the props the admin controllers send. They mirror the JSON resources in app/Http/Resources.

export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export interface Category {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    image_url: string | null;
    sort_order: number;
    products_count?: number;
    children_count?: number;
}

export interface Brand {
    id: number;
    name: string;
    slug: string;
    sort_order: number;
    is_active: boolean;
}

export interface Store {
    id: number;
    name: string;
    address: string;
    hours: string;
    map_url: string | null;
    sort_order: number;
    is_active: boolean;
}

export interface Banner {
    id: number;
    image_url: string;
    headline: string | null;
    subtext: string | null;
    link_url: string | null;
    sort_order: number;
    is_active: boolean;
}

export interface ProductImage {
    url: string;
    color: string | null;
}

export type MarketplaceLinks = Partial<Record<'shopee' | 'lazada' | 'tiktok', string>>;

export interface Product {
    id: number;
    category_id: number | null;
    category?: Category;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    was_price: string | null;
    tag: string | null;
    brand: string | null;
    leather_type: string | null;
    hardware: string | null;
    dimensions: string | null;
    colors: string[] | null;
    /** Units per colour, e.g. { Black: 4 }; empty for products without colours. */
    color_stock: Record<string, number>;
    glyph: string;
    image_url: string | null;
    images: ProductImage[] | null;
    links: MarketplaceLinks;
    rating: string;
    review_count: number;
    stock: number;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export type OrderStatus = 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';

export interface OrderItem {
    id: number;
    product_id: number;
    product?: Product;
    quantity: number;
    unit_price: string;
    subtotal: string;
    color: string | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: 'customer' | 'admin' | null;
    is_active: boolean;
    email_verified_at: string | null;
    orders_count?: number;
    created_at: string | null;
}

export interface Order {
    id: number;
    user_id: number | null;
    user?: User;
    customer_name: string;
    customer_email: string;
    customer_phone: string | null;
    shipping_address: string;
    city: string | null;
    province: string | null;
    postal_code: string | null;
    total_amount: string;
    status: OrderStatus;
    payment_method: string;
    notes: string | null;
    items?: OrderItem[];
    items_count?: number;
    created_at: string | null;
    updated_at: string | null;
}

/** A Laravel paginated resource collection. */
export interface Paginated<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: { user: AuthUser | null };
        };
        flashDataType: {
            success?: string;
            error?: string;
        };
    }
}
