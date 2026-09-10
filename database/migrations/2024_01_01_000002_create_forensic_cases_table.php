<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('forensic_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->enum('incident_type', ['unauthorized_modification', 'brute_force', 'mass_deletion']);
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->text('description');
            $table->text('scenario');
            $table->text('learning_objectives');
            $table->text('investigation_instructions');
            $table->json('simulated_evidence')->nullable();
            $table->json('timeline_events')->nullable();
            $table->string('question_pdf_path')->nullable();
            $table->string('question_pdf_name')->nullable();
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_published')->default(false);
            $table->integer('expected_duration')->default(60);
            $table->integer('total_marks')->default(100);
            $table->boolean('ai_generated')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('forensic_cases'); }
};
