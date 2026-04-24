<?php

namespace App\Http\Controllers;

use App\Models\FinalDocument;
use App\Models\FieldValue;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserUploadFinalDocumentController extends Controller
{
    public function index(Request $request)
    {
        $finalDocumentId = (int) $request->query('final_document_id', 0);

        return view('userUploadFinalDocument', [
            'finalDocumentId' => $finalDocumentId
        ]);
    }

    public function uploadFinalDocument(Request $request)
    {
        $finalDocumentId = (int) $request->query('final_document_id', 0);

        if (!$request->hasFile('document')) {
            return redirect('/user-upload-final-document-form?final_document_id=' . $finalDocumentId)
                ->with('error', 'Nenhum arquivo foi enviado.');
        }

        $file     = $request->file('document');
        $fileType = strtolower($file->getClientOriginalExtension());

        if ($fileType !== 'pdf') {
            return redirect('/user-upload-final-document-form?final_document_id=' . $finalDocumentId)
                ->with('error', 'Apenas arquivos PDF são permitidos.');
        }

        $oldFinalDocument = FinalDocument::find($finalDocumentId);
        if (!$oldFinalDocument) {
            return redirect('/user-upload-final-document-form')->with('error', 'Documento não encontrado.');
        }

        // Autenticar utilizador com email+password
        $email    = $request->input('email', '');
        $password = $request->input('password', '');

        if (empty($email) || empty($password)) {
            return redirect('/user-upload-final-document-form?final_document_id=' . $finalDocumentId)
                ->with('error', 'Email e senha são obrigatórios!');
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return redirect('/user-upload-final-document-form?final_document_id=' . $finalDocumentId)
                ->with('error', 'Email ou senha inválidos!');
        }

        if ($user->id !== $oldFinalDocument->user_id) {
            return redirect('/user-upload-final-document-form?final_document_id=' . $finalDocumentId)
                ->with('error', 'Não tem permissão para fazer upload deste documento!');
        }

        $uploadDir = public_path('uploads/generated_docs/');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename   = uniqid() . '_' . $file->getClientOriginalName();
        $file->move($uploadDir, $filename);

        $newFinalDocument = FinalDocument::create([
            'user_id'     => $oldFinalDocument->user_id,
            'pdf_path'    => $filename,
            'document_id' => $oldFinalDocument->document_id,
            'status'      => 'Por validar',
            'plan_id'     => $oldFinalDocument->plan_id,
        ]);

        $existingFieldValues = FieldValue::where('final_document_id', $oldFinalDocument->id)->get();
        if ($newFinalDocument && $existingFieldValues->isNotEmpty()) {
            FieldValue::insert($existingFieldValues->map(function ($fieldValue) use ($newFinalDocument) {
                return [
                    'document_id'       => $fieldValue->document_id,
                    'user_id'           => $fieldValue->user_id,
                    'field_id'          => $fieldValue->field_id,
                    'value'             => $fieldValue->value,
                    'final_document_id' => $newFinalDocument->id,
                ];
            })->toArray());
        }

        return redirect('/user-upload-final-document-form')->with('message', 'Upload realizado com sucesso!');
    }
}
