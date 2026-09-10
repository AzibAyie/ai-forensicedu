<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('case_answers', function (Blueprint $table) {
            // Set once, client-side, the moment a rich/formatted paste (the
            // signal for "copied from outside the app") is detected while
            // writing this specific answer. Never unset, even if the student
            // edits the text afterwards — see IntegrityService.
            $table->boolean('flagged_external_paste')->default(false)->after('answer');
        });
    }
    public function down(): void {
        Schema::table('case_answers', function (Blueprint $table) {
            $table->dropColumn('flagged_external_paste');
        });
    }
};
