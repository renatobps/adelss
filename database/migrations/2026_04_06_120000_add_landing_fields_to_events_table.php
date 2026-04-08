<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('public_slug')->nullable()->unique()->after('title');
            $table->string('subtitle')->nullable()->after('public_slug');
            $table->string('banner_image')->nullable()->after('subtitle');
            $table->longText('about_html')->nullable()->after('description');
            $table->boolean('is_paid')->default(false)->after('about_html');
            $table->decimal('price', 10, 2)->nullable()->after('is_paid');
            $table->unsignedInteger('max_spots')->nullable()->after('price');
            $table->boolean('phone_required')->default(false)->after('max_spots');
            $table->boolean('address_required')->default(false)->after('phone_required');
            $table->boolean('email_required')->default(true)->after('address_required');
            $table->boolean('hide_phone')->default(false)->after('email_required');
            $table->boolean('hide_address')->default(false)->after('hide_phone');
            $table->text('notify_emails')->nullable()->after('hide_address');
            $table->boolean('registration_enabled')->default(true)->after('notify_emails');
            $table->json('location_photos')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'public_slug',
                'subtitle',
                'banner_image',
                'about_html',
                'is_paid',
                'price',
                'max_spots',
                'phone_required',
                'address_required',
                'email_required',
                'hide_phone',
                'hide_address',
                'notify_emails',
                'registration_enabled',
                'location_photos',
            ]);
        });
    }
};
