<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'kelas', 'jurusan'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function borrowings(): BelongsToMany
    {
        return $this->belongsToMany(Borrowing::class, 'borrowing_students')
            ->withTimestamps();
    }

    public function borrowingSessions(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isSiswa(): bool
    {
        return $this->role === 'siswa';
    }

    public function hasActiveBorrowingFor(Laptop $laptop): bool
    {
        return $this->borrowings()
            ->where('laptop_id', $laptop->id)
            ->whereIn('status', ['aktif', 'menunggu'])
            ->exists();
    }
}
