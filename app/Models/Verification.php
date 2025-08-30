<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'code', 'verified', 'expires_at'];

    protected $dates = ['expires_at'];

    // Check if code is expired
    public function isExpired()
    {
        return $this->expires_at && Carbon::now()->gt($this->expires_at);
    }
}
