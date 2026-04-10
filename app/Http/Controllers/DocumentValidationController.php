<?php

namespace App\Http\Controllers;

use App\Models\FinalDocument;
use App\Models\PresidentEmail;
use App\Models\PresidentValidatedDocument;
use App\Services\EmailService;
use Exception;
use Illuminate\Http\Request;

class DocumentValidationController extends Controller
{
    public function presidentValidationPage(Request $request)
    {
        $uuid = $request->query('uuid');
        if ($uuid) {
            $isValidated = PresidentValidatedDocument::where('uuid', $uuid)->value('is_validated');
            if ($isValidated) {
                return redirect('/president-upload-final-document-form')->with('error', 'O documento já foi validado.');
            }
        }
        return view('adminDashboard.presidentUploadFinalDocument', compact('uuid'));
    }

    public function validateDocument(Request $request)
    {
        $finalDocumentId   = (int) $request->input('final_document_id');
        $presidencialEmail = $request->input('presidencial_email');
        $adminName         = session('admin_name', 'Admin');

        // Criar uuid + request + email de presidente
        $data     = random_bytes(16);
        $data[6]  = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8]  = chr(ord($data[8]) & 0x3f | 0x80);
        $uuid     = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        PresidentValidatedDocument::create([
            'uuid'              => $uuid,
            'final_document_id' => $finalDocumentId,
        ]);

        PresidentEmail::firstOrCreate(['email' => $presidencialEmail]);
        FinalDocument::where('id', $finalDocumentId)->update(['status' => 'Inativo']);

        (new EmailService())->sendPresidentialValidationEmail($presidencialEmail, $finalDocumentId, $adminName);
        (new LogsController())->logAction('validate-document', $finalDocumentId);

        return redirect('/need-validation-documents')->with('message', 'Documento validado com sucesso!');
    }

    public function presidentFinalDocument(Request $request)
    {
        try {
            if (!$request->hasFile('document') || $request->file('document')->getError() !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no upload do arquivo');
            }

            $file = $request->file('document');
            if ($file->getMimeType() !== 'application/pdf') {
                throw new Exception('Arquivo deve ser PDF');
            }

            $uploadDir = public_path('uploads/generated_docs');
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filename = uniqid('president_doc_') . '.pdf';
            $file->move($uploadDir, $filename);

            $uuid     = $request->input('verified_uuid');
            $pvd      = PresidentValidatedDocument::with('finalDocument.user')->where('uuid', $uuid)->first();

            if (!$pvd) throw new Exception('UUID inválido');

            $userEmail       = $pvd->finalDocument->user->email;
            $userId          = $pvd->finalDocument->user_id;
            $documentId      = $pvd->finalDocument->document_id;
            $finalDocumentId = $pvd->final_document_id;
            $planId          = $pvd->finalDocument->plan_id;

            (new EmailService())->sendAcceptedValidationEmail($userEmail, $finalDocumentId);

            FinalDocument::create([
                'user_id'     => $userId,
                'pdf_path'    => $filename,
                'document_id' => $documentId,
                'status'      => 'Validado',
                'plan_id'     => $planId,
            ]);

            $pvd->update(['is_validated' => true]);

            return redirect('/president-upload-final-document-form')->with('message', 'Documento finalizado com sucesso!');
        } catch (Exception $e) {
            return redirect('/president-upload-final-document-form')->with('error', 'Erro: ' . $e->getMessage());
        }
    }

    public function invalidateDocument(Request $request)
    {
        try {
            $finalDocumentId = (int) $request->input('final_document_id');
            $reason          = $request->input('rejection_reason', '');
            $email           = $request->input('email');

            (new EmailService())->sendRejectedValidationEmail($email, $finalDocumentId, $reason);
            FinalDocument::where('id', $finalDocumentId)->update(['status' => 'Invalidado']);
            (new LogsController())->logAction('invalidate-document', $finalDocumentId);

            return redirect('/need-validation-documents')->with('message', 'Documento rejeitado com sucesso!');
        } catch (Exception $e) {
            return redirect('/need-validation-documents')->with('error', 'Erro ao rejeitar o documento: ' . $e->getMessage());
        }
    }

    public function listPresidents(Request $request)
    {
        if ($request->isMethod('POST') && $request->has('new_president_email')) {
            try {
                PresidentEmail::firstOrCreate(['email' => trim($request->input('new_president_email'))]);
                $presidentEmails = PresidentEmail::all()->toArray();
                return view('adminDashboard.listPresidentEmails', compact('presidentEmails'))
                    ->with('message', 'Email presidencial adicionado com sucesso!');
            } catch (Exception $e) {
                return view('adminDashboard.listPresidentEmails', ['presidentEmails' => PresidentEmail::all()->toArray()])
                    ->with('error', 'Erro! ' . $e->getMessage());
            }
        }

        $presidentEmails = PresidentEmail::all()->toArray();
        return view('adminDashboard.listPresidentEmails', compact('presidentEmails'));
    }

    public function deletePresidentEmail(Request $request)
    {
        try {
            PresidentEmail::destroy((int) $request->input('email_id'));
            return back()->with('message', 'Email presidencial removido com sucesso!');
        } catch (Exception $e) {
            return back()->with('error', 'Erro! ' . $e->getMessage());
        }
    }
}
