<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the storefront's busiest product queries: new arrivals and the
     * default product listing (active, newest first) and bestsellers (active, by tag).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'created_at']);
            $table->index(['is_active', 'tag', 'review_count']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'created_at']);
            $table->dropIndex(['is_active', 'tag', 'review_count']);
        });
    }
};
