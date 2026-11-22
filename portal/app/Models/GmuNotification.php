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

        return $related instanceof \App\Models\Seed\SeedApplication && $this->user
            ? app(\App\Services\SeedGrant\Workflow::class)->linkFor($this->user, $related)
            : null;
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
