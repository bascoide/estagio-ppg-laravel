<?php

namespace App\Http\Controllers;

use App\Models\Logs as LogsModel;
use Illuminate\Http\Request;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $currentPage  = max(1, (int) $request->query('page', 1));
        $itemsPerPage = 10;
        $offset       = ($currentPage - 1) * $itemsPerPage;

        $actionType = $request->query('action_type');
        $loggedName = $request->query('logged_name');
        $date       = $request->query('date');

        $query = LogsModel::join('user', 'logs.user_id', '=', 'user.id')
            ->select('logs.*', 'user.email');

        if ($loggedName) $query->where('logs.name', $loggedName);
        if ($actionType) $query->where('logs.action', $actionType);
        if ($date)       $query->whereDate('logs.created_at', $date);

        $totalRecords = $query->count();
        $totalPages   = max(1, (int) ceil($totalRecords / $itemsPerPage));

        if ($currentPage > $totalPages && $totalPages > 0) {
            return redirect()->route('admin.logs', ['page' => $totalPages]);
        }

        $logs         = $query->orderByDesc('logs.created_at')->offset($offset)->limit($itemsPerPage)->get()->toArray();
        $loggedNames  = LogsModel::distinct()->pluck('name')->toArray();
        $startRecord  = $offset + 1;
        $endRecord    = min($offset + $itemsPerPage, $totalRecords);

        return view('adminDashboard.logs', compact(
            'logs', 'loggedNames', 'totalRecords', 'totalPages',
            'currentPage', 'startRecord', 'endRecord', 'actionType', 'loggedName', 'date'
        ));
    }

    public function logAction(string $action, ?int $finalDocumentId = null): void
    {
        $allowedActions = [
            'create-account', 'accept-document', 'reject-document',
            'invalidate-document', 'validate-document', 'edit-document',
            'annul-document', 'addition-document', 'upload-document',
            'deactivation-document', 'restore-document', 'create-course',
            'delete-course', 'edit-course', 'deactivation-course',
        ];

        if (!in_array($action, $allowedActions)) return;

        $userId    = (int) session('user_id');
        $adminName = session('admin_name', 'Unknown Admin');

        LogsModel::create([
            'user_id'           => $userId,
            'action'            => $action,
            'name'              => $adminName,
            'final_document_id' => $finalDocumentId,
        ]);
    }
}
