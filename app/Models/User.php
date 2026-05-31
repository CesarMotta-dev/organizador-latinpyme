<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\CompanyEmail; // 🚀 Importación limpia del modelo relacionado

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    /**
     * 🚀 RELACIÓN CLAVE PARA EL ADMINISTRADOR CENTRALIZADO
     * Un usuario administrador tiene y gestiona múltiples correos de la empresa.
     */
    public function companyEmails()
    {
        return $this->hasMany(CompanyEmail::class);
    }

    /**
     * Correos asignados a este usuario (si es trabajador)
     */
    public function assignedCompanyEmails()
    {
        return $this->hasMany(CompanyEmail::class, 'worker_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isWorker(): bool
    {
        return $this->role === 'worker';
    }
}
