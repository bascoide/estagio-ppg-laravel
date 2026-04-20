<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Document;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FinalDocument;
use App\Models\Professor;
use App\Models\PresidentEmail;
use App\Models\User;
use App\Services\EmailService;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPanelController extends Controller
{
    public function createAdmin(Request $request)
    {
        if ($request->isMethod('POST')) {
            $email    = $request->input('email', '');
            $password = $request->input('password', '');

            if (empty($email) || empty($password)) {
                return back()->with('error', 'Email e senha são obrigatórios!');
            }

            try {
                if (User::where('email', $email)->exists()) {
                    return back()->with('error', 'Email já está registado!');
                }

                User::create([
                    'name'     => 'Admin',
                    'email'    => $email,
                    'password' => bcrypt($password),
                    'admin'    => true,
                    'verified' => true,
                ]);

                (new LogsController())->logAction('create-account');
                return back()->with('message', 'Utilizador criado com sucesso!');
            } catch (Exception $e) {
                return back()->with('error', 'Erro ao criar utilizador: ' . $e->getMessage());
            }
        }

        return view('adminDashboard.createAdmin');
    }

    public function showUsers(Request $request)
    {
        $search = $request->query('search', '');
        $query  = User::query();
        if ($search !== '') {
            $query->where('name', 'like', "%$search%");
        }
        $users = $query->orderBy('name')->get()->toArray();
        return view('adminDashboard.showUsers', compact('users', 'search'));
    }

    public function showDocuments(Request $request)
    {
        $search    = $request->query('search', '');
        $query     = Document::query();
        if ($search !== '') {
            $query->where('name', 'like', "%$search%");
        }
        $documents = $query->get()->toArray();
        return view('adminDashboard.showDocuments', compact('documents', 'search'));
    }

    public function viewFinalDocument(Request $request)
    {
        $finalDocumentId = (int) $request->query('final_document_id', 0);
        $documentId      = (int) $request->query('document_id', 0);

        if (!$finalDocumentId || !$documentId) {
            return redirect('/show-documents')->with('error', "ID's inválidos");
        }

        $finalDocument = FinalDocument::find($finalDocumentId);
        $status        = $finalDocument->status;
        $userId        = $finalDocument->user_id;
        $planId        = $finalDocument->plan_id;
        
        $userEmail = User::find($userId)->email ?? '';

        $teachers    = Professor::all()->toArray();
        $fieldNames  = Field::where('document_id', $documentId)->get()->toArray();
        $fieldValues = FieldValue::where('final_document_id', $finalDocumentId)->get()->toArray();

        session(['previous_page' => $request->fullUrl()]);

        return view('adminDashboard.viewUserDocument', compact(
            'finalDocumentId', 'documentId', 'status', 'userId', 'planId',
            'teachers', 'fieldNames', 'fieldValues', 'userEmail'
        ));
    }

    public function viewUserDocuments(Request $request)
    {
        $userId = (int) $request->query('user_id', 0);

        if (!$userId) {
            return redirect('/show-users')->with('error', 'ID do utilizador inválido');
        }

        $search      = $request->query('search', '');
        $dateFilter  = $request->query('date_filter', '');
        $orderBy     = $request->query('order_by', '');
        $status      = $request->query('status', '');

        $userName = User::find($userId)->name ?? '';
        $userEmail = User::find($userId)->email ?? '';

        $query = FinalDocument::join('document', 'final_document.document_id', '=', 'document.id')
            ->where('final_document.user_id', $userId)
            ->select('document.id as document_id', 'document.name as document_name', 'document.type as document_type',
                'final_document.id as final_document_id', 'final_document.created_at',
                'final_document.pdf_path', 'final_document.status');

        if ($status !== '') $query->where('final_document.status', $status);
        if ($search !== '') $query->where('document.name', 'like', "%$search%");
        if ($dateFilter !== '') $query->whereDate('final_document.created_at', $dateFilter);

        if ($orderBy === 'date_newest') $query->orderByDesc('final_document.created_at');
        elseif ($orderBy === 'date_oldest') $query->orderBy('final_document.created_at');

        $documents = $query->get()->toArray();
        $presidencialEmails = PresidentEmail::all()->toArray();

        return view('adminDashboard.viewDocumentsFromUser', compact(
            'documents', 'userName', 'userId', 'userEmail', 'search', 'dateFilter', 'orderBy', 'status', 'presidencialEmails'
        ));
    }

    public function viewPendingDocuments(Request $request)
    {
        $currentPage  = max(1, (int) $request->query('page', 1));
        $itemsPerPage = 10;
        $offset       = ($currentPage - 1) * $itemsPerPage;

        $documents = $this->getDocumentsWithStatus('Pendente', $offset, $itemsPerPage);
        $totalRecords = FinalDocument::join('document', 'final_document.document_id', '=', 'document.id')
            ->where('final_document.status', 'Pendente')
            ->where('document.type', '!=', 'Plano')
            ->count();
        $totalPages   = max(1, (int) ceil($totalRecords / $itemsPerPage));

        if ($currentPage > $totalPages && $totalPages > 0) {
            return redirect()->withQueryString()->with('page', $totalPages);
        }

        $startRecord = $offset + 1;
        $endRecord   = min($offset + $itemsPerPage, $totalRecords);

        return view('adminDashboard.viewPendingDocuments', compact(
            'documents', 'totalRecords', 'totalPages', 'currentPage', 'startRecord', 'endRecord'
        ));
    }

    public function viewNeedValidationDocuments(Request $request)
    {
        $currentPage  = max(1, (int) $request->query('page', 1));
        $itemsPerPage = 10;
        $offset       = ($currentPage - 1) * $itemsPerPage;

        $documents          = $this->getDocumentsWithStatus('Por validar', $offset, $itemsPerPage);
        $presidencialEmails = PresidentEmail::all()->toArray();
        $totalRecords       = FinalDocument::where('status', 'Por validar')->count();
        $totalPages         = max(1, (int) ceil($totalRecords / $itemsPerPage));

        if ($currentPage > $totalPages && $totalPages > 0) {
            return redirect()->withQueryString()->with('page', $totalPages);
        }

        $startRecord = $offset + 1;
        $endRecord   = min($offset + $itemsPerPage, $totalRecords);

        return view('adminDashboard.viewNeedValidationDocuments', compact(
            'documents', 'presidencialEmails', 'totalRecords', 'totalPages',
            'currentPage', 'startRecord', 'endRecord'
        ));
    }

    public function viewValidationDocuments(Request $request)
    {
        $currentPage  = max(1, (int) $request->query('page', 1));
        $itemsPerPage = 10;
        $offset       = ($currentPage - 1) * $itemsPerPage;

        $protocolCount = null;

        if ($request->query('school_year')) {
            $yearParts = explode('/', $request->query('school_year'));
            if (count($yearParts) === 2 && is_numeric($yearParts[0]) && is_numeric($yearParts[1])) {
                $protocolCount = FinalDocument::join('user', 'final_document.user_id', '=', 'user.id')
                    ->where('user.course_id', (int) $request->query('course_id'))
                    ->where('final_document.status', 'Validado')
                    ->whereBetween('final_document.created_at', [$yearParts[0] . '-09-01', $yearParts[1] . '-08-31'])
                    ->count();
            } else {
                return redirect('/view-validation-documents')->with('error', 'Ano escolar inválido');
            }
        } elseif ($request->query('civil_year')) {
            $year = $request->query('civil_year');
            $protocolCount = FinalDocument::join('user', 'final_document.user_id', '=', 'user.id')
                ->where('user.course_id', (int) $request->query('course_id'))
                ->where('final_document.status', 'Validado')
                ->whereBetween('final_document.created_at', [$year . '-01-01', $year . '-12-31'])
                ->count();
        }

        if ($request->query('select_school_year')) {
            $parts = explode('/', $request->query('select_school_year'));
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $startDate = $parts[0] . '-09-01';
                $endDate   = $parts[1] . '-08-31';
            } else {
                return redirect('/view-validation-documents')->with('error', 'Ano escolar inválido');
            }
        } else {
            $currentYear = (int) date('Y');
            if ((int) date('n') >= 8) {
                $startDate = $currentYear . '-09-01';
                $endDate   = ($currentYear + 1) . '-08-31';
            } else {
                $startDate = ($currentYear - 1) . '-09-01';
                $endDate   = $currentYear . '-08-31';
            }
        }

        $courseId = $request->query('course_id', null);
        $documents = $this->getDocumentsWithStatus('Validado', $offset, $itemsPerPage, $startDate, $endDate, $courseId);

        $dates       = FinalDocument::where('status', 'Validado')->selectRaw('DISTINCT DATE(created_at) as d')->orderByDesc('d')->pluck('d')->toArray();
        $schoolYears = [];
        $civilYears  = [];

        foreach ($dates as $date) {
            $dt = new DateTime($date);
            $civilYears[] = $dt->format('Y');
            $m = (int) $dt->format('m');
            $y = (int) $dt->format('Y');
            if ($m >= 8) {
                $schoolYears[] = "$y/" . ($y + 1);
            } else {
                $schoolYears[] = ($y - 1) . "/$y";
            }
        }

        $civilYears  = array_unique($civilYears);
        $schoolYears = array_unique($schoolYears);

        // Fallback para quando ainda não existem protocolos validados.
        if (empty($civilYears) || empty($schoolYears)) {
            $currentYear = (int) date('Y');
            if ((int) date('n') >= 8) {
                $schoolYearStart = $currentYear;
                $schoolYearEnd   = $currentYear + 1;
            } else {
                $schoolYearStart = $currentYear - 1;
                $schoolYearEnd   = $currentYear;
            }

            if (empty($civilYears)) {
                $civilYears = [(string) $currentYear];
            }

            if (empty($schoolYears)) {
                $schoolYears = ["$schoolYearStart/$schoolYearEnd"];
            }
        }

        $courses      = Course::with('typeCourse')->get()->toArray();
        $totalQuery = FinalDocument::join('user', 'final_document.user_id', '=', 'user.id')
            ->where('final_document.status', 'Validado')
            ->whereBetween('final_document.created_at', [$startDate, $endDate]);
        
        if ($courseId !== null) {
            $totalQuery->where('user.course_id', $courseId);
        }
        
        $totalRecords = $totalQuery->count();
        $totalPages   = max(1, (int) ceil($totalRecords / $itemsPerPage));

        if ($currentPage > $totalPages && $totalPages > 0) {
            return redirect()->withQueryString()->with('page', $totalPages);
        }

        $startRecord = $offset + 1;
        $endRecord   = min($offset + $itemsPerPage, $totalRecords);

        return view('adminDashboard.viewValidationDocuments', compact(
            'documents', 'courses', 'schoolYears', 'civilYears', 'protocolCount',
            'totalRecords', 'totalPages', 'currentPage', 'startRecord', 'endRecord'
        ));
    }

    public function viewDocumentation()
    {
        return view('adminDashboard.documentation');
    }

    public function viewAdditionDocuments(Request $request)
    {
        if ($request->hasFile('documentFile')) {
            try {
                $file = $request->file('documentFile');
                if ($file->getMimeType() !== 'application/pdf') {
                    throw new Exception('Apenas PDFs são permitidos');
                }

                $uploadDir = public_path('uploads/submittedAdditions/');
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $filename = uniqid('addition_') . '.pdf';
                $file->move($uploadDir, $filename);

                $finalDocumentId = (int) $request->input('final_document_id');
                $additionName    = $request->input('addition_name', 'Adicionamento');

                \App\Models\Addition::create([
                    'final_document_id' => $finalDocumentId,
                    'name'              => $additionName,
                    'path'              => '/uploads/submittedAdditions/' . $filename,
                ]);

                (new LogsController())->logAction('addition-document', $finalDocumentId);
                return back()->with('message', 'Adicionamento submetido com sucesso!');
            } catch (Exception $e) {
                return back()->with('error', 'Erro ao submeter adicionamento: ' . $e->getMessage());
            }
        }

        $finalDocumentId = (int) $request->query('final_document_id', 0);
        $additions = [];
        
        if ($finalDocumentId) {
            $additions = \App\Models\Addition::where('final_document_id', $finalDocumentId)
                ->select('id', 'name', 'path as addition_path', 'final_document_id', 'created_at')
                ->get()
                ->toArray();
        }

        return view('adminDashboard.additionDocument', compact('additions', 'finalDocumentId'));
    }

    public function viewFinalDocumentAdmin(Request $request)
    {
        $finalDocumentId = (int) $request->query('final_document_id', 0);
        if (!$finalDocumentId) {
            return redirect('/show-documents')->with('error', 'ID inválido');
        }

        $finalDocument = FinalDocument::with(['document', 'plan'])->find($finalDocumentId);
        return view('adminDashboard.viewFinalDocument', compact('finalDocument'));
    }

    public function editFinalDocument(Request $request)
    {
        try {
            $finalDocumentId = (int) $request->input('final_document_id');
            $fields          = $request->input('fields', []);
            $fieldNames      = $request->input('field_names', []);
            $status          = $request->input('status');
            $userEmail       = $request->input('email');
            

            $finalDocument   = FinalDocument::find($finalDocumentId);
            if (!$finalDocument) throw new Exception('Documento não encontrado');

            $hasChanges = false;
            foreach ($fields as $fieldId => $value) {
                $fv = FieldValue::where('final_document_id', $finalDocumentId)
                    ->where('field_id', $fieldId)->first();
                if ($fv && $fv->value !== $value) {
                    $hasChanges = true;
                    break;
                }
            }

            if (!$hasChanges) {
                // Apenas atualiza o status
                if ($status) {
                    $finalDocument->update(['status' => $status]);

                    if (in_array($status, ['Aceite', 'Recusado'])) {
                        $rejectionReason = $request->input('rejection_reason') ?? '';
                        $this->sendStatusEmail($userEmail, $finalDocumentId,
                            $status === 'Recusado' ? 'rejected' : 'accepted',
                            $rejectionReason
                        );
                    }
                }

                // Associar professor se necessário
                if ($request->has('professor_name')) {
                    $professorName = $request->input('professor_name');
                    $courseId      = $finalDocument->user->course_id;

                    if (!$professorName) throw new Exception('Nome do orientador não encontrado');

                    $professor = Professor::where('name', $professorName)->first();
                    if (!$professor) {
                        $professor = Professor::create(['name' => $professorName]);
                    }

                    DB::table('professor_course')->insert([
                        'professor_id' => $professor->id,
                        'course_id'    => $courseId,
                        'intern_id'    => $finalDocument->user_id,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }

                (new LogsController())->logAction('edit-document', $finalDocumentId);
                return back()->with('message', 'Status atualizado com sucesso!');
            }

            // Criar nova versão com campos alterados
            foreach ($fields as $fieldId => $value) {
                FieldValue::where('final_document_id', $finalDocumentId)
                    ->where('field_id', $fieldId)
                    ->update(['value' => $value]);
            }

            if ($status) {
                $finalDocument->update(['status' => $status]);

                if (in_array($status, ['Aceite', 'Recusado'])) {
                    $rejectionReason  = $request->input('rejection_reason', '');
                    $rejectedFields   = $request->input('rejected_fields', []);
                    $this->sendStatusEmail($userEmail, $finalDocumentId,
                        $status === 'Recusado' ? 'rejected' : 'accepted',
                        $rejectionReason, $rejectedFields
                    );
                }
            }

            (new LogsController())->logAction('edit-document', $finalDocumentId);
            return back()->with('message', 'Documento atualizado com sucesso!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelFinalDocument(Request $request)
    {
        try {
            $finalDocumentId = (int) $request->input('final_document_id');
            $userEmail       = $request->input('email');

            FinalDocument::where('id', $finalDocumentId)->update(['status' => 'Cancelado']);
            (new EmailService())->sendCancelledEmail($userEmail, $finalDocumentId);
            (new LogsController())->logAction('annul-document', $finalDocumentId);

            $previousPage = session('previous_page', '/show-documents');
            return redirect($previousPage)->with('message', 'Documento cancelado com sucesso!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function sendStatusEmail(string $userEmail, int $documentId, string $type, string $rejectionReason = '', array $rejectedFields = []): void
    {
        $emailService = new EmailService();
        switch ($type) {
            case 'rejected':
                $emailService->sendRejectedEmail($userEmail, $documentId, $rejectionReason, $rejectedFields);
                break;
            case 'accepted':
                $emailService->sendAcceptedEmail($userEmail, $documentId);
                break;
            case 'cancelled':
                $emailService->sendCancelledEmail($userEmail, $documentId);
                break;
        }
    }

    private function getDocumentsWithStatus(string $status, int $offset, int $limit, ?string $startDate = null, ?string $endDate = null, ?int $courseId = null): array
    {
        $query = DB::table('final_document')
            ->leftJoin('submitted_plans', 'submitted_plans.id', '=', 'final_document.plan_id')
            ->join('user', 'final_document.user_id', '=', 'user.id')
            ->join('document', 'final_document.document_id', '=', 'document.id')
            ->where('final_document.status', $status)
            ->where('document.type', '!=', 'Plano')
            ->select(
                'user.name', 'user.email',
                'document.id as document_id', 'document.name as document_name', 'document.type as document_type',
                'final_document.id as final_document_id', 'final_document.created_at as final_document_created_at',
                'submitted_plans.id as plan_id', 'submitted_plans.path as plan_path', 'submitted_plans.verified as plan_is_verified'
            );

        if ($startDate && $endDate) {
            $query->whereBetween('final_document.created_at', [$startDate, $endDate]);
        }

        if ($courseId !== null) {
            $query->where('user.course_id', $courseId);
        }

        $results = $query->limit($limit)->offset($offset)->get();
        
        // Converter os resultados para arrays
        return $results->map(function($item) {
            return (array) $item;
        })->toArray();
    }
}
