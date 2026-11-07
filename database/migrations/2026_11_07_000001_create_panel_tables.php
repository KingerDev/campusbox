<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->after('email');
        });

        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ico', 20);
            $table->string('dic', 20)->nullable();
            $table->json('types');
            $table->json('categories');
            $table->string('address');
            $table->text('pickup_instructions')->nullable();
            $table->string('description', 200)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('public_email')->nullable();
            $table->string('web')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('linkedin')->nullable();
            $table->json('opening_hours');
            $table->string('cover_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('delivery_enabled')->default(false);
            $table->decimal('delivery_fee', 6, 2)->default(0);
            $table->string('delivery_radius', 10)->default('2 km');
            $table->string('delivery_by', 40)->default('Kuriér CampusBox');
            $table->string('rescue_slot', 20)->default('19:30–20:00');
            $table->string('menu_slot', 20)->default('11:00–14:00');
            $table->string('iban', 40);
            $table->string('billing_email');
            $table->string('status', 20)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('issue_tried_at')->nullable();
            $table->timestamp('onboarding_dismissed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('business_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20);
            $table->string('invite_token', 64)->nullable()->unique();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'email']);
        });

        Schema::create('menu_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_section_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('category', 40);
            $table->string('description', 300)->nullable();
            $table->decimal('price', 8, 2);
            $table->json('allergens');
            $table->json('option_groups');
            $table->boolean('vegetarian')->default(false);
            $table->boolean('popular')->default(false);
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('offer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 20);
            $table->string('item_key', 40); // 'surprise' alebo 'dish:{id}'
            $table->foreignId('dish_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->decimal('regular_price', 8, 2);
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('quantity');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'date', 'type', 'item_key']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('code', 12);
            $table->date('pickup_date');
            $table->string('customer_name', 80);
            $table->string('fulfilment', 20)->default('pickup'); // pickup | delivery
            $table->string('delivery_address')->nullable();
            $table->string('status', 10)->default('nova');
            $table->string('eta', 5)->nullable();
            $table->string('reason')->nullable();
            $table->decimal('total', 8, 2);
            $table->decimal('commission', 8, 2)->default(0);
            $table->timestamp('ordered_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'pickup_date', 'code']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('name', 120);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->decimal('commission_rate', 5, 4);
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('offer_items');
        Schema::dropIfExists('dishes');
        Schema::dropIfExists('menu_sections');
        Schema::dropIfExists('business_members');
        Schema::dropIfExists('businesses');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('phone'));
    }
};
