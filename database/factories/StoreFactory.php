<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * City, region and sample barangays.
     *
     * @var list<array{string, string, list<string>}>
     */
    private const LOCATIONS = [
        ['Quezon City', 'Metro Manila (NCR)', ['Batasan Hills', 'Commonwealth', 'Project 4']],
        ['Makati City', 'Metro Manila (NCR)', ['Poblacion', 'Bel-Air', 'San Lorenzo']],
        ['Cebu City', 'Central Visayas (Region VII)', ['Lahug', 'Guadalupe', 'Mabolo']],
        ['Davao City', 'Davao Region (Region XI)', ['Poblacion District', 'Matina', 'Buhangin']],
        ['Angeles City', 'Central Luzon (Region III)', ['Balibago', 'Pulung Maragul', 'Malabanias']],
        ['Iloilo City', 'Western Visayas (Region VI)', ['Jaro', 'Mandurriao', 'La Paz']],
    ];

    private const STREETS = ['Rizal Street', 'Mabini Avenue', 'Bonifacio Street', 'Aguinaldo Highway', 'Luna Street'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$city, $region, $barangays] = fake()->randomElement(self::LOCATIONS);
        $barangay = fake()->randomElement($barangays);

        return [
            'name' => $city.' Branch',
            'address' => fake()->buildingNumber().' '.fake()->randomElement(self::STREETS),
            'barangay' => $barangay,
            'city' => $city,
            'region' => $region,
            'hours' => 'Mon–Sun 10:00 AM – 9:00 PM',
            'map_url' => 'https://maps.google.com/?q='.urlencode($city),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the store is hidden from the storefront.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
