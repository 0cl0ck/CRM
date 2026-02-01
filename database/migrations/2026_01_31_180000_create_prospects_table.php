<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Classification
            $table->string('type')->default('other'); // ProspectType enum
            $table->string('source')->default('other'); // ProspectSource enum
            $table->string('status')->default('identified'); // ProspectStatus enum
            $table->string('budget')->default('unknown'); // Budget enum
            $table->unsignedTinyInteger('urgency')->default(3); // 1-5 scale

            // Context
            $table->text('main_problem')->nullable();
            $table->text('notes')->nullable();

            // Tracking
            $table->timestamp('last_action_at')->nullable();
            $table->timestamp('next_action_at')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index('status');
            $table->index('next_action_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
