import { router } from '@inertiajs/react';
import { confirmDialog } from '@/lib/feedback';
import type { Product } from '@/types';

/** Ask, then delete a product. The server hides it instead if it appears on past orders. */
export async function deleteProduct(product: Pick<Product, 'id' | 'name'>): Promise<void> {
    const confirmed = await confirmDialog({
        title: 'Delete product?',
        message: `"${product.name}" will be removed from the catalog. Products that appear on past orders are hidden instead.`,
    });

    if (confirmed) router.delete(`/admin/products/${product.id}`);
}
