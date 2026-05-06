<?php

namespace App\Http\Controllers;

use App\Models\FinalDocument;
use App\Models\FieldValue;
use App\Models\PresidentEmail;
use App\Models\PresidentValidatedDocument;
use App\Services\EmailService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentValidationController extends Controller
{
    private const PRESIDENT_LINK_EXPIRY_DAYS = 7;

    public function presidentValidationPage(Request $request)
    {
        $uuid = trim((string) $request->query('uuid', ''));

        if ($uuid === '') {
            return view('adminDashboard.presidentUploadFinalDocument', ['uuid' => null])
                ->with('error', 'Nenhum documento foi encontrado');
        }

        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $uuid)) {
            return view('adminDashboard.presidentUploadFinalDocument', ['uuid' => null])
                ->with('error', 'Documento nao encontrado.');
        }

        $presidentDocument = PresidentValidatedDocument::where('uuid', $uuid)->first();
        if (!$presidentDocument) {
            return view('adminDashboard.presidentUploadFinalDocument', ['uuid' => null])
                ->with('error', 'Documento nao encontrado.');
        }

        if ($presidentDocument->is_validated) {
            return redirect('/president-upload-final-document-form')->with('error', 'O documento ja foi validado.');
        }

        if ($this->presidentLinkExpired($presidentDocument)) {
            return redirect('/president-upload-final-document-form')->with('error', 'Este link expirou. Solicite um novo link de validacao.');
        }

        if ($uuid !== '') {
            $isValidated = PresidentValidatedDocument::where('uuid', $uuid)->value('is_validated');
            if ($isValidated) {
                return redirect('/president-upload-final-document-form')->with('error', 'O documento jÃ¡ foi validado.');
            }
        }

        if (!PresidentValidatedDocument::where('uuid', $uuid)->exists()) {
            return view('adminDashboard.presidentUploadFinalDocument', ['uuid' => null])
                ->with('error', 'Documento nao encontrado.');
        }

        return view('adminDashboard.presidentUploadFinalDocument', compact('uuid'));
    }

    public function validateDocument(Request $request)
    {
        try {
            $finalDocumentId   = (int) $request->input('final_document_id');
            $presidencialEmail = strtolower(trim((string) $request->input('presidencial_email')));
            $adminName         = session('admin_name', 'Admin');

            if (!filter_var($presidencialEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email presidencial invalido.');
            }

            $finalDocument = FinalDocument::find($finalDocumentId);
            if (!$finalDocument) {
                throw new Exception('Documento nÃ£o encontrado.');
            }

            DB::beginTransaction();

            $data     = random_bytes(16);
            $data[6]  = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8]  = chr(ord($data[8]) & 0x3f | 0x80);
            $uuid     = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

            PresidentValidatedDocument::create([
                'uuid'              => $uuid,
                'final_document_id' => $finalDocumentId,
                'expires_at'        => now()->addDays(self::PRESIDENT_LINK_EXPIRY_DAYS),
            ]);

            PresidentEmail::firstOrCreate(['email' => $presidencialEmail]);
            $finalDocument->update(['status' => 'Inativo']);

            if (!(new EmailService())->sendPresidentialValidationEmail($presidencialEmail, $finalDocumentId, $adminName)) {
                throw new Exception('Falha ao enviar email para validaÃ§Ã£o presidencial.');
            }

            DB::commit();
            (new LogsController())->logAction('validate-document', $finalDocumentId);

            return redirect('/need-validation-documents')->with('message', 'Documento validado com sucesso!');
        } catch (Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return redirect('/need-validation-documents')->with('error', $e->getMessage());
        }
    }

    public function presidentFinalDocument(Request $request)
    {
        $filename = null;

        try {
            if (!$request->hasFile('document') || $request->file('document')->getError() !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no upload do arquivo');
            }

            $uuid = trim((string) $request->input('verified_uuid'));
            if (!preg_match('/^[0-9a-fA-F-]{36}$/', $uuid)) {
                throw new Exception('UUID invalido');
            }

            $pvd  = PresidentValidatedDocument::with('finalDocument.user')->where('uuid', $uuid)->first();

            if (!$pvd) {
                throw new Exception('UUID invÃ¡lido');
            }

            if ($pvd->is_validated) {
                throw new Exception('O documento jÃ¡ foi validado.');
            }

            if ($this->presidentLinkExpired($pvd)) {
                throw new Exception('Este link expirou. Solicite um novo link de validacao.');
            }

            $file = $request->file('document');
            if (!$file->isValid() || $file->getMimeType() !== 'application/pdf' || $file->getSize() > 10 * 1024 * 1024) {
                throw new Exception('Arquivo deve ser PDF');
            }

            $uploadDir = public_path('uploads/generated_docs');
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new Exception('Falha ao criar diretorio de upload');
            }

            $filename = uniqid('president_doc_') . '.pdf';
            $file->move($uploadDir, $filename);

            $userEmail       = $pvd->finalDocument->user->email;
            $userId          = $pvd->finalDocument->user_id;
            $documentId      = $pvd->finalDocument->document_id;
            $finalDocumentId = $pvd->final_document_id;
            $planId          = $pvd->finalDocument->plan_id;

            DB::beginTransaction();

            $validatedDocument = FinalDocument::create([
                'user_id'     => $userId,
                'pdf_path'    => $filename,
                'document_id' => $documentId,
                'status'      => 'Validado',
                'plan_id'     => $planId,
            ]);

            $existingFieldValues = FieldValue::where('final_document_id', $finalDocumentId)->get();
            if ($validatedDocument && $existingFieldValues->isNotEmpty()) {
                FieldValue::insert($existingFieldValues->map(function ($fieldValue) use ($validatedDocument) {
                    return [
                        'document_id'       => $fieldValue->document_id,
                        'user_id'           => $fieldValue->user_id,
                        'field_id'          => $fieldValue->field_id,
                        'value'             => $fieldValue->value,
                        'final_document_id' => $validatedDocument->id,
                    ];
                })->toArray());
            }

            if (!(new EmailService())->sendAcceptedValidationEmail($userEmail, $validatedDocument->id)) {
                throw new Exception('Falha ao enviar email de validaÃ§Ã£o.');
            }

            $pvd->update(['is_validated' => true]);
            DB::commit();

            return redirect('/president-upload-final-document-form')->with('message', 'Documento finalizado com sucesso!');
        } catch (Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($filename && file_exists(public_path('uploads/generated_docs/' . $filename))) {
                @unlink(public_path('uploads/generated_docs/' . $filename));
            }

            return redirect('/president-upload-final-document-form')->with('error', 'Erro: ' . $e->getMessage());
        }
    }

    public function invalidateDocument(Request $request)
    {
        try {
            $finalDocumentId = (int) $request->input('final_document_id');
            $reason          = $request->input('rejection_reason', '');

            $finalDocument = FinalDocument::with('user')->find($finalDocumentId);
            if (!$finalDocument) {
                throw new Exception('Documento nÃ£o encontrado.');
            }

            $email = $finalDocument->user->email ?? '';

            DB::beginTransaction();
            $finalDocument->update(['status' => 'Invalidado']);

            if (!(new EmailService())->sendRejectedValidationEmail($email, $finalDocumentId, $reason)) {
                throw new Exception('Falha ao enviar email de invalidaÃ§Ã£o.');
            }

            DB::commit();
            (new LogsController())->logAction('invalidate-document', $finalDocumentId);

            return redirect('/need-validation-documents')->with('message', 'Documento rejeitado com sucesso!');
        } catch (Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return redirect('/need-validation-documents')->with('error', 'Erro ao rejeitar o documento: ' . $e->getMessage());
        }
    }

    public function listPresidents(Request $request)
    {
        if ($request->isMethod('POST') && $request->has('new_president_email')) {
            try {
                $email = strtolower(trim((string) $request->input('new_president_email')));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email invalido.');
                }

                PresidentEmail::firstOrCreate(['email' => $email]);
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

    private function presidentLinkExpired(PresidentValidatedDocument $presidentDocument): bool
    {
        return $presidentDocument->expires_at === null || $presidentDocument->expires_at->isPast();
    }
}
