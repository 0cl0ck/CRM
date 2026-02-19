<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            // Business data (from Pappers PDF)
            $table->string('siren', 9)->nullable()->after('website');
            $table->string('siret', 14)->nullable()->after('siren');
            $table->string('naf_code', 10)->nullable()->after('siret');
            $table->string('naf_label')->nullable()->after('naf_code');
            $table->string('legal_form')->nullable()->after('naf_label');
            $table->unsignedBigInteger('capital')->nullable()->after('legal_form');
            $table->unsignedBigInteger('revenue')->nullable()->after('capital'); // CA in €
            $table->unsignedInteger('employees')->nullable()->after('revenue');
            $table->date('creation_date')->nullable()->after('employees');
            $table->string('city')->nullable()->after('creation_date');
            $table->string('director_name')->nullable()->after('city');

            // Lighthouse scores
            $table->unsignedTinyInteger('lh_performance')->nullable()->after('notes');
            $table->unsignedTinyInteger('lh_accessibility')->nullable()->after('lh_performance');
            $table->unsignedTinyInteger('lh_best_practices')->nullable()->after('lh_accessibility');
            $table->unsignedTinyInteger('lh_seo')->nullable()->after('lh_best_practices');
            $table->float('lh_fcp')->nullable()->after('lh_seo');
            $table->float('lh_lcp')->nullable()->after('lh_fcp');
            $table->float('lh_tbt')->nullable()->after('lh_lcp');
            $table->float('lh_cls')->nullable()->after('lh_tbt');
            $table->date('lh_report_date')->nullable()->after('lh_cls');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn([
                'siren',
                'siret',
                'naf_code',
                'naf_label',
                'legal_form',
                'capital',
                'revenue',
                'employees',
                'creation_date',
                'city',
                'director_name',
                'lh_performance',
                'lh_accessibility',
                'lh_best_practices',
                'lh_seo',
                'lh_fcp',
                'lh_lcp',
                'lh_tbt',
                'lh_cls',
                'lh_report_date',
            ]);
        });
    }
};
