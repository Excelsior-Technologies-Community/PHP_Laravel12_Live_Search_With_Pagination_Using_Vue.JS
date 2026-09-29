<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
            $table->unsignedInteger('stock')->default(0)->after('price');
            $table->string('status')->default('active')->after('stock');
            $table->boolean('is_featured')->default(false)->after('status');

            $table->index('category');
            $table->index('status');
            $table->index('stock');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['status']);
            $table->dropIndex(['stock']);
            $table->dropIndex(['is_featured']);

            $table->dropColumn([
                'category',
                'stock',
                'status',
                'is_featured',
            ]);
        });
    }
};