<?php

namespace App\Models;

use Database\Factories\LaptopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laptop extends Model
{
    /** @use HasFactory<LaptopFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function activeBorrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class)->whereIn('status', ['aktif', 'menunggu']);
    }

    public function activeBorrowing(): ?Borrowing
    {
        return $this->borrowings()
            ->whereIn('status', ['aktif', 'menunggu'])
            ->latest('borrowed_at')
            ->first();
    }

    public function isAvailable(): bool
    {
        return $this->activeBorrowing() === null;
    }

    public function qrImagePath(): string
    {
        return asset("storage/qrcodes/{$this->qr_token}.png");
    }
}
