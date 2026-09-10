<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['lecturer', 'student'])->default('student');
            $table->string('student_id')->nullable()->unique();
            $table->string('staff_id')->nullable()->unique();
            $table->string('faculty')->nullable();
            $table->string('program')->nullable();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('lecturer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
