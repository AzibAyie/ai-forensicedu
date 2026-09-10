<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('case_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forensic_case_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['unlocked', 'in_progress', 'submitted', 'graded'])->default('unlocked');
            $table->integer('progress_percent')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unique(['forensic_case_id', 'student_id']);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('case_enrollments'); }
};
