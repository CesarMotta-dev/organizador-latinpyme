<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnclassifiedEmail extends Model
{
    use HasFactory;

    protected $table = 'unclassified_emails';

    protected $fillable = [
        'company_email_id',
        'correo_remitente',
        'asunto',
        'id_mensaje',
    ];

    // Opcional: Relación con la cuenta corporativa
    public function companyEmail()
    {
        return $this->belongsTo(CompanyEmail::class, 'company_email_id');
    }
}
