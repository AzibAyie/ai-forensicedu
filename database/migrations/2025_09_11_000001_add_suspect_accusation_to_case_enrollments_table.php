<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('case_enrollments', function (Blueprint $table) {
            $table->string('accused_suspect')->nullable()->after('progress_percent');
            $table->boolean('accusation_correct')->nullable()->after('accused_suspect');
        });
    }
    public function down(): void {
        Schema::table('case_enrollments', function (Blueprint $table) {
            $table->dropColumn(['accused_suspect', 'accusation_correct']);
        });
    }
};
