<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('case_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('case_enrollments')->onDelete('cascade')->unique();
            $table->string('student_name');
            $table->string('student_id_number');
            $table->string('program');
            $table->text('executive_summary');
            $table->text('findings');
            $table->text('timeline_reconstruction');
            $table->text('recommendations');
            $table->text('conclusion');
            $table->integer('marks')->nullable();
            $table->text('lecturer_feedback')->nullable();
            $table->text('ai_feedback')->nullable();
            $table->integer('ai_suggested_marks')->nullable();
            $table->string('answer_pdf_path')->nullable();
            $table->string('answer_pdf_name')->nullable();

            // Authorship telemetry — evidence for the lecturer, never an automatic verdict.
            $table->unsignedInteger('keystroke_count')->default(0);
            $table->unsignedInteger('paste_count')->default(0);
            $table->unsignedInteger('pasted_chars')->default(0);
            $table->unsignedInteger('compose_seconds')->default(0);
            $table->unsignedInteger('revision_count')->default(0);
            $table->json('paste_events')->nullable();
            $table->json('integrity_flags')->nullable();
            $table->unsignedTinyInteger('similarity_score')->nullable();
            $table->foreignId('similar_to_report_id')->nullable();

            $table->enum('status', ['draft', 'submitted', 'graded'])->default('draft');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('case_reports'); }
};
