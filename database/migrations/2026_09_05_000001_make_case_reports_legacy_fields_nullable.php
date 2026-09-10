<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('case_reports', function (Blueprint $table) {
            $table->text('executive_summary')->nullable()->change();
            $table->text('timeline_reconstruction')->nullable()->change();
            $table->text('recommendations')->nullable()->change();
            $table->text('conclusion')->nullable()->change();
        });
    }
    public function down(): void {
        Schema::table('case_reports', function (Blueprint $table) {
            $table->text('executive_summary')->nullable(false)->change();
            $table->text('timeline_reconstruction')->nullable(false)->change();
            $table->text('recommendations')->nullable(false)->change();
            $table->text('conclusion')->nullable(false)->change();
        });
    }
};
