<?php

namespace App\Models;

use App\Enums\Budget;
use App\Enums\ProspectSource;
use App\Enums\ProspectStatus;
use App\Enums\ProspectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'website',
        'type',
        'source',
        'status',
        'budget',
        'urgency',
        'main_problem',
        'notes',
        'last_action_at',
        'next_action_at',
        // Business data (Pappers)
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
        // Lighthouse scores
        'lh_performance',
        'lh_accessibility',
        'lh_best_practices',
        'lh_seo',
        'lh_fcp',
        'lh_lcp',
        'lh_tbt',
        'lh_cls',
        'lh_report_date',
    ];

    protected $casts = [
        'type' => ProspectType::class,
        'source' => ProspectSource::class,
        'status' => ProspectStatus::class,
        'budget' => Budget::class,
        'urgency' => 'integer',
        'last_action_at' => 'datetime',
        'next_action_at' => 'datetime',
        'capital' => 'integer',
        'revenue' => 'integer',
        'employees' => 'integer',
        'creation_date' => 'date',
        'lh_performance' => 'integer',
        'lh_accessibility' => 'integer',
        'lh_best_practices' => 'integer',
        'lh_seo' => 'integer',
        'lh_fcp' => 'float',
        'lh_lcp' => 'float',
        'lh_tbt' => 'float',
        'lh_cls' => 'float',
        'lh_report_date' => 'date',
    ];

    /**
     * Scope for prospects requiring action today or overdue
     */
    public function scopeNeedsAction($query)
    {
        return $query->whereNotNull('next_action_at')
            ->where('next_action_at', '<=', now())
            ->whereNotIn('status', [ProspectStatus::WON, ProspectStatus::LOST]);
    }

    /**
     * Scope for active prospects (not won or lost)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [ProspectStatus::WON, ProspectStatus::LOST]);
    }

    /**
     * Check if prospect is overdue for follow-up
     */
    public function isOverdue(): bool
    {
        if (!$this->next_action_at) {
            return false;
        }

        return $this->next_action_at->isPast()
            && !in_array($this->status, [ProspectStatus::WON, ProspectStatus::LOST]);
    }
}
