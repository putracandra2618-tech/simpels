<?php

namespace App\Models;

use Database\Factories\BorrowingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrowing extends Model
{
    /** @use HasFactory<BorrowingFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function laptop(): BelongsTo
    {
        return $this->belongsTo(Laptop::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'borrowing_students')
            ->withTimestamps()
            ->orderByRaw('borrowing_students.user_id = (select created_by from borrowings where borrowings.id = borrowing_students.borrowing_id) desc');
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function latestReturnRequest(): ?ReturnRequest
    {
        if ($this->relationLoaded('returnRequests')) {
            return $this->returnRequests->sortByDesc('requested_at')->first();
        }

        return $this->returnRequests()->latest('requested_at')->first();
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['aktif', 'menunggu'], true);
    }
}
