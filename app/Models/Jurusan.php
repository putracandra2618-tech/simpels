<?php

namespace App\Models;

use Database\Factories\JurusanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurusan extends Model
{
    /** @use HasFactory<JurusanFactory> */
    use HasFactory;

    protected $table = 'jurusan';

    protected $fillable = ['kode', 'nama'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'jurusan_id');
    }
}
