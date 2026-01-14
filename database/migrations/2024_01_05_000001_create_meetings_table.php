<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pgi_id')->constrained('pgis')->onDelete('cascade');
            $table->date('meeting_date');
            $table->string('subject')->nullable();
            $table->decimal('total_value', 10, 2)->default(0.00);
            $table->integer('participants_count')->default(0);
            $table->integer('visitors_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('pgi_id');
            $table->index('meeting_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};


