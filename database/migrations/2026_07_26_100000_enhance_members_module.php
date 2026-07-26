<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (!Schema::hasColumn('members', 'marriage_date')) {
                    $table->date('marriage_date')->nullable()->after('marital_status');
                }
                if (!Schema::hasColumn('members', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('zip_code');
                }
                if (!Schema::hasColumn('members', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
                if (!Schema::hasColumn('members', 'geocoded_at')) {
                    $table->timestamp('geocoded_at')->nullable()->after('longitude');
                }
            });

            // Ampliar enum de status com "pendente" (MySQL)
            try {
                DB::statement("ALTER TABLE members MODIFY COLUMN status ENUM('ativo','inativo','visitante','membro_transferido','pendente') NOT NULL DEFAULT 'visitante'");
            } catch (\Throwable) {
                // SQLite / outros: ignora
            }
        }

        if (!Schema::hasTable('member_custom_fields')) {
            Schema::create('member_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type')->default('text'); // text|number|date|select
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_custom_field_values')) {
            Schema::create('member_custom_field_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->foreignId('member_custom_field_id')->constrained('member_custom_fields')->cascadeOnDelete();
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unique(['member_id', 'member_custom_field_id'], 'mcfv_member_field_unique');
            });
        }

        if (!Schema::hasTable('member_settings')) {
            Schema::create('member_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });

            DB::table('member_settings')->insert([
                [
                    'key' => 'public_registration_enabled',
                    'value' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'public_registration_token',
                    'value' => Str::random(48),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_custom_field_values');
        Schema::dropIfExists('member_custom_fields');
        Schema::dropIfExists('member_settings');

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                foreach (['marriage_date', 'latitude', 'longitude', 'geocoded_at'] as $col) {
                    if (Schema::hasColumn('members', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
