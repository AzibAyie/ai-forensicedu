<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('forensic_cases', function (Blueprint $table) {
            $table->string('evidence_hash', 64)->nullable()->after('simulated_evidence');
        });

        // Backfill a baseline hash for cases that already existed before this
        // column did — the model's saving() hook only computes it going
        // forward, so without this, every pre-existing case would show no
        // integrity hash at all.
        DB::table('forensic_cases')->select('id', 'simulated_evidence')->orderBy('id')
            ->each(function ($case) {
                $evidence = json_decode($case->simulated_evidence ?? '[]', true) ?? [];
                $hash = hash('sha256', json_encode($evidence, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                DB::table('forensic_cases')->where('id', $case->id)->update(['evidence_hash' => $hash]);
            });
    }
    public function down(): void {
        Schema::table('forensic_cases', function (Blueprint $table) {
            $table->dropColumn('evidence_hash');
        });
    }
};
