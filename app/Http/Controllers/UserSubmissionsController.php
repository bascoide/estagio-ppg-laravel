<?php

namespace App\Http\Controllers;

use App\Models\FinalDocument;
use Illuminate\Http\Request;

class UserSubmissionsController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) session('user_id');
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('status', '');

        $query = FinalDocument::with(['document', 'plan'])
            ->where('user_id', $userId)
            ->whereHas('document', function ($documentQuery) use ($type) {
                if (in_array($type, ['Plano', 'Protocolo'], true)) {
                    $documentQuery->where('type', $type);
                }
            });

        if ($status !== '') {
            $query->where('status', $status);
        }

        $submissions = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $plansCount = FinalDocument::where('user_id', $userId)
            ->whereHas('document', fn ($documentQuery) => $documentQuery->where('type', 'Plano'))
            ->count();

        $protocolsCount = FinalDocument::where('user_id', $userId)
            ->whereHas('document', fn ($documentQuery) => $documentQuery->where('type', 'Protocolo'))
            ->count();

        $activeCount = FinalDocument::where('user_id', $userId)
            ->whereNotIn('status', ['Validado', 'Inativo', 'Cancelado'])
            ->count();

        $statuses = ['Pendente', 'Aceite', 'Recusado', 'Por validar', 'Validado', 'Invalidado', 'Inativo', 'Cancelado'];

        return view('userSubmissions', compact(
            'submissions',
            'plansCount',
            'protocolsCount',
            'activeCount',
            'statuses',
            'type',
            'status'
        ));
    }
}
