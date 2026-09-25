<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $customer_name
 * @property string $customer_email
 * @property string|null $customer_phone
 * @property string $shipping_address
 * @property string|null $city
 * @property string|null $province
 * @property string|null $postal_code
 * @property string $total_amount
 * @property string $status
 * @property string $payment_method
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'customer_name', 'customer_email', 'customer_phone', 'shipping_address',
    'city', 'province', 'postal_code', 'total_amount', 'status', 'payment_method', 'notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_METHODS = ['cod', 'gcash', 'bank_transfer', 'credit_card'];

    /**
     * Filter by status, and search by customer name, email or order number.
     *
     * @param  Builder<Order>  $query
     * @param  array{status?: string|null, search?: string|null}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;
        $orderId = ltrim((string) $search, '#');

        $query
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search, fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->when(ctype_digit($orderId), fn (Builder $query) => $query->orWhere('id', (int) $orderId))
            ));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }
}
