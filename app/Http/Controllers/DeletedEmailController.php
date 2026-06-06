<?php

namespace App\Http\Controllers;

use App\Models\DeletedEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeletedEmailController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $query = DeletedEmail::query();

        if ($user->isAdmin()) {
            // Admin can see deleted emails belonging to their company emails
            $companyEmailIds = $user->companyEmails()->pluck('id');
            $query->where(function ($q) use ($companyEmailIds, $user) {
                $q->whereIn('company_email_id', $companyEmailIds)
                  ->orWhere('user_id', $user->id);
            });
        } else {
            // Worker sees deleted emails for their assigned company emails
            $companyEmailIds = $user->assignedCompanyEmails()->pluck('id');
            $query->whereIn('company_email_id', $companyEmailIds);
        }

        $deletedEmails = $query->latest('fecha_eliminacion')->paginate(20);

        return Inertia::render('DeletedEmails/Index', [
            'deletedEmails' => $deletedEmails
        ]);
    }
}
