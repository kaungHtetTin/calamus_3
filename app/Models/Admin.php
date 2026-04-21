<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'image_url',
        'access',
        'major_scope',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'access' => 'array',
        'major_scope' => 'array',
    ];

    /**
     * Check if the admin has a specific permission.
     * Root admins with ["*"] in both access and major_scope have all permissions.
     */
    public function hasPermission(string $sector, ?string $majorCode = null): bool
    {
        // 1. Check Sector Access
        $hasSectorAccess = false;
        if (!empty($this->access)) {
            if (in_array('*', $this->access) || in_array($sector, $this->access)) {
                $hasSectorAccess = true;
            }
        }

        if (!$hasSectorAccess) {
            return false;
        }

        // 2. Check Major Scope (if provided)
        if ($majorCode) {
            if (empty($this->major_scope)) {
                return false;
            }

            if (in_array('*', $this->major_scope) || in_array($majorCode, $this->major_scope)) {
                return true;
            }

            return false;
        }

        return true;
    }
}
