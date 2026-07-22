<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scheduled_posts')) {
            Schema::table('scheduled_posts', function (Blueprint $table) {
                if (!Schema::hasColumn('scheduled_posts', 'media_kind')) {
                    $table->string('media_kind')->default('foto')->after('caption');
                }
            });

            // Migrar status antigo "publicado" → "concluido"
            DB::table('scheduled_posts')
                ->where('status', 'publicado')
                ->update(['status' => 'concluido']);
        }

        if (!Schema::hasTable('scheduled_post_destinations')) {
            Schema::create('scheduled_post_destinations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scheduled_post_id')->constrained('scheduled_posts')->cascadeOnDelete();
                $table->string('destination'); // feed | reels | stories
                $table->string('status')->default('pendente'); // pendente|publicando|publicado|erro
                $table->string('instagram_media_id')->nullable();
                $table->text('error_message')->nullable();
                $table->dateTime('published_at')->nullable();
                $table->timestamps();

                $table->unique(['scheduled_post_id', 'destination']);
                $table->index(['status']);
            });
        }

        // Posts antigos sem destinos: cria destino Feed e copia resultado
        if (Schema::hasTable('scheduled_posts') && Schema::hasTable('scheduled_post_destinations')) {
            $posts = DB::table('scheduled_posts')->get();
            foreach ($posts as $post) {
                $exists = DB::table('scheduled_post_destinations')
                    ->where('scheduled_post_id', $post->id)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $destStatus = 'pendente';
                if (($post->status ?? '') === 'concluido' || !empty($post->instagram_media_id)) {
                    $destStatus = 'publicado';
                } elseif (($post->status ?? '') === 'erro') {
                    $destStatus = 'erro';
                } elseif (($post->status ?? '') === 'publicando') {
                    $destStatus = 'publicando';
                }

                DB::table('scheduled_post_destinations')->insert([
                    'scheduled_post_id' => $post->id,
                    'destination' => 'feed',
                    'status' => $destStatus,
                    'instagram_media_id' => $post->instagram_media_id ?? null,
                    'error_message' => $post->error_message ?? null,
                    'published_at' => $post->published_at ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('scheduled_posts')) {
            Schema::table('scheduled_posts', function (Blueprint $table) {
                if (Schema::hasColumn('scheduled_posts', 'instagram_media_id')) {
                    $table->dropColumn('instagram_media_id');
                }
                if (Schema::hasColumn('scheduled_posts', 'error_message')) {
                    $table->dropColumn('error_message');
                }
                if (Schema::hasColumn('scheduled_posts', 'published_at')) {
                    $table->dropColumn('published_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scheduled_posts')) {
            Schema::table('scheduled_posts', function (Blueprint $table) {
                if (!Schema::hasColumn('scheduled_posts', 'instagram_media_id')) {
                    $table->string('instagram_media_id')->nullable();
                }
                if (!Schema::hasColumn('scheduled_posts', 'error_message')) {
                    $table->text('error_message')->nullable();
                }
                if (!Schema::hasColumn('scheduled_posts', 'published_at')) {
                    $table->dateTime('published_at')->nullable();
                }
            });
        }

        Schema::dropIfExists('scheduled_post_destinations');

        if (Schema::hasTable('scheduled_posts') && Schema::hasColumn('scheduled_posts', 'media_kind')) {
            Schema::table('scheduled_posts', function (Blueprint $table) {
                $table->dropColumn('media_kind');
            });
        }
    }
};
