<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', Rule::in(Order::PAYMENT_METHODS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.color' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * The validated customer and shipping details for OrderService::place().
     *
     * @return array{customer_name: string, customer_email: string, customer_phone: string|null, shipping_address: string, city: string|null, province: string|null, postal_code: string|null, payment_method: string, notes: string|null}
     */
    public function customerDetails(): array
    {
        return [
            'customer_name' => $this->string('customer_name')->toString(),
            'customer_email' => $this->string('customer_email')->toString(),
            'customer_phone' => $this->optionalString('customer_phone'),
            'shipping_address' => $this->string('shipping_address')->toString(),
            'city' => $this->optionalString('city'),
            'province' => $this->optionalString('province'),
            'postal_code' => $this->optionalString('postal_code'),
            'payment_method' => $this->string('payment_method')->toString(),
            'notes' => $this->optionalString('notes'),
        ];
    }

    /**
     * The validated line items for OrderService::place().
     *
     * @return list<array{product_id: int, quantity: int, color: string|null}>
     */
    public function orderItems(): array
    {
        /** @var list<array{product_id: int|string, quantity: int|string, color?: string|null}> $items */
        $items = $this->validated('items');

        return array_map(fn (array $item) => [
            'product_id' => (int) $item['product_id'],
            'quantity' => (int) $item['quantity'],
            'color' => $item['color'] ?? null,
        ], $items);
    }

    private function optionalString(string $key): ?string
    {
        return $this->filled($key) ? $this->string($key)->toString() : null;
    }
}
