<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('case_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('case_enrollments')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('case_questions')->onDelete('cascade');
            $table->text('answer')->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'question_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('case_answers'); }
};
