<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('case_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forensic_case_id')->constrained()->onDelete('cascade');
            $table->text('question');
            $table->integer('marks')->default(10);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('case_questions'); }
};
