<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GmuNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'related_id', 'related_type', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    public function related() { return $this->morphTo(); }

    /** The page where the person acts on this, the same one the email's button opens. */
    public function actionUrl(): ?string
    {
        $related = $this->related_type ? $this->related : null;

        return match (true) {
            // An agreement waiting for the PI opens the agreement itself (the owner's "Staff Pages" design).
            $related instanceof \App\Models\Seed\SeedApplication && $this->type === 'seed_agreement'
                && $this->user_id === $related->pi_id && $related->status === \App\Support\SeedGrant::AGREEMENT_ISSUED
                => route('seed.applications.agreement', $related),
            $related instanceof \App\Models\Seed\SeedApplication && $this->user !== null
                => app(\App\Services\SeedGrant\Workflow::class)->linkFor($this->user, $related),
            $related instanceof \App\Models\Seed\SeedCall => route('seed.calls.show', $related),
            default => null,
        };
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
