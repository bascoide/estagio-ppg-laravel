<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Field;
use App\Models\FinalDocument;
use App\Models\SubmittedPlan;
use App\Models\TypeCourse;
use Exception;
use Illuminate\Http\Request;
use ZipArchive;

class DocumentController extends Controller
{
    public function printDocument(Request $request)
    {
        $documentId = $request->input('final_document_id');

        if (!$documentId) {
            abort(400, 'ID do documento não fornecido');
        }
        

        $document = \DB::table('final_document')->where('id', $documentId)->first();

        if (!$document) {
            abort(404, 'Documento não encontrado');
        }
        
        $filePath = public_path('uploads/generated_docs/' . $document->pdf_path);
        if (!file_exists($filePath)) {
            abort(404, 'Documento não encontrado');
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            ]);
        }
        
        if ($extension === 'docx') {
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
        }
        
        abort(415, 'Formato de ficheiro não suportado');
        
    }

    public function printDocumentForm(Request $request)
    {
        $documentId = $request->input('document_id');

        if (!$documentId) {
            abort(400, 'ID do documento não fornecido');
        }

        $document = \DB::table('document')->where('id', $documentId)->first();

        if (!$document) {
            abort(404, 'Documento não encontrado');
        }

        $filePath = public_path('uploads/schema/' . $document->docx_path);
        if (!file_exists($filePath)) {
            abort(404, 'Caminho não encontrado');
        }

        // Clone the DOCX to a temp file
        $clonedFilePath = public_path('uploads/schema/temp_document.docx');
        copy($filePath, $clonedFilePath);

        $this->replaceToEmpty($clonedFilePath, $document->type);

        $formController = new FormController();
        $pdfPath = $formController->convertToPdf($clonedFilePath);

        $path = public_path('uploads/schema/' . $pdfPath);

        $response = response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);

        // Delete temp PDF after streaming
        register_shutdown_function(function () use ($path) {
            if (file_exists($path)) {
                unlink($path);
            }
        });

        return $response;
    }

    private function replaceToEmpty(string $clonedFilePath, string $type): bool
    {
        $emptySpace = $type === 'Plano' ? '           ' : '___________';
        $zip = new ZipArchive();

        if ($zip->open($clonedFilePath) !== true) {
            throw new Exception('Falha ao abrir ficheiro DOCX para modificação.');
        }

        $replaceCount = 0;
        $filesToProcess = ['word/document.xml'];
        for ($i = 1; $i <= 3; $i++) {
            $filesToProcess[] = "word/header{$i}.xml";
            $filesToProcess[] = "word/footer{$i}.xml";
        }

        foreach ($filesToProcess as $fileName) {
            $xml = $zip->getFromName($fileName);
            if ($xml === false) {
                continue;
            }

            $pattern = '/\{(?:[^<{}]+|<[^>]+>)*?\|\s*(?:[^<{}]+|<[^>]+>)*?\}/';
            preg_match_all($pattern, $xml, $matches, PREG_SET_ORDER);

            $processedXml = $xml;
            foreach ($matches as $match) {
                $fullPlaceholder = $match[0];
                $cleanPlaceholder = preg_replace('/<[^>]+>/', '', $fullPlaceholder);

                if (preg_match('/\{\s*([^|}]+?)\s*\|\s*([^}]+?)\s*\}/', $cleanPlaceholder, $parts)) {
                    $fieldType = trim($parts[2]);

                    if (in_array(strtolower($fieldType), ['title', 'group', 'na start', 'na end'], true)) {
                        $processedXml = str_replace($fullPlaceholder, '', $processedXml, $count);
                    } elseif (in_array(strtolower($fieldType), ['radio', 'checkbox'], true)) {
                        $processedXml = str_replace($fullPlaceholder, '☐', $processedXml, $count);
                    } else {
                        $processedXml = str_replace($fullPlaceholder, $emptySpace, $processedXml, $count);
                    }
                    $replaceCount += $count ?? 0;
                }
            }

            if ($processedXml !== $xml) {
                $zip->deleteName($fileName);
                $zip->addFromString($fileName, $processedXml);
            }
        }

        $zip->close();
        return $replaceCount > 0;
    }

    public function viewPlan(Request $request)
    {
        if ($request->has('plan_id')) {
            $planId = (int) $request->input('plan_id');
            \DB::table('submitted_plans')->where('id', $planId)->update(['verified' => true]);
        }

        if ($request->has('plan_path')) {
            $filePath = public_path($request->input('plan_path'));
            if (!file_exists($filePath)) {
                abort(404, 'File not found');
            }
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            ]);
        }

        abort(400, 'Parâmetros em falta');
    }

    public function viewAddition(Request $request)
    {
        if ($request->has('addition_path')) {
            $filePath = public_path($request->input('addition_path'));
            if (!file_exists($filePath)) {
                abort(404, 'File not found');
            }
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            ]);
        }

        abort(400, 'Parâmetros em falta');
    }

    public function downloadDocument(Request $request)
    {
        $documentId = $request->input('id');

        if (!$documentId) {
            abort(400, 'ID do documento não fornecido');
        }

        $document = \DB::table('document')->where('id', $documentId)->first();

        if (!$document) {
            abort(404, 'Documento não encontrado');
        }

        $filePath = public_path('uploads/schema/' . $document->docx_path);
        if (!file_exists($filePath)) {
            abort(404, 'Documento não encontrado');
        }

        return response()->download($filePath, basename($filePath), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function uploadDocumentForm()
    {
        return view('adminDashboard.uploadDocuments');
    }

    public function createNewDocumentAndFields(Request $request)
    {
        $selectedCourseTypes = $request->input('courseTypes', []);
        if (empty($selectedCourseTypes)) {
            session()->flash('error', 'Selecione pelo menos um tipo de curso!');
            return redirect()->route('upload-document-form');
        }

        if (!$request->hasFile('documentFile') || !$request->filled('documentName') || !$request->filled('documentType')) {
            session()->flash('error', 'Todos os campos são obrigatórios!');
            return redirect()->route('upload-document-form');
        }

        try {
            $uploadDir = public_path('uploads/schema/');
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
                throw new Exception('Falha ao criar diretório de upload');
            }

            $documentName = $request->input('documentName');
            $documentType = $request->input('documentType');
            $file = $request->file('documentFile');

            // Validate MIME type
            $allowedMimes = [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/octet-stream'
            ];
            

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                throw new Exception('Apenas ficheiros .docx permitidos!');
            }

            $fileName = $documentName . '.docx';
            $targetPath = $uploadDir . $fileName;
            $file->move($uploadDir, $fileName);

            // Create document in DB
            $documentId = \DB::table('document')->insertGetId([
                'docx_path' => $fileName,
                'name'      => $documentName,
                'type'      => $documentType,
                'is_active' => true,
            ]);

            // Attach course types (pivot)
            foreach ($selectedCourseTypes as $typeId) {
                if (!TypeCourse::find($typeId)) {
                    throw new Exception('Tipo de curso com ID ' . $typeId . ' não encontrado!');
                }
                \DB::table('document_type_course')->insert([
                    'document_id'    => $documentId,
                    'type_course_id' => $typeId,
                ]);
            }

            $this->extractAndProcessDocument($targetPath, $documentId);

            (new LogsController())->logAction('upload-document');
            session()->flash('message', 'Documento carregado com sucesso!');
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('upload-document-form');
    }

    private function extractAndProcessDocument(string $filePath, int $documentId): void
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception('Falha ao abrir ficheiro DOCX');
        }

        $text = '';
        $mainDoc = $zip->locateName('word/document.xml');
        if ($mainDoc !== false) {
            $xml = $zip->getFromIndex($mainDoc);
            if ($xml !== false) {
                $text .= strip_tags($xml);
            }
        }

        for ($i = 1; $i <= 3; $i++) {
            $headerIndex = $zip->locateName("word/header{$i}.xml");
            if ($headerIndex !== false) {
                $xml = $zip->getFromIndex($headerIndex);
                if ($xml !== false) {
                    $text .= strip_tags($xml);
                }
            }
        }

        for ($i = 1; $i <= 3; $i++) {
            $footerIndex = $zip->locateName("word/footer{$i}.xml");
            if ($footerIndex !== false) {
                $xml = $zip->getFromIndex($footerIndex);
                if ($xml !== false) {
                    $text .= strip_tags($xml);
                }
            }
        }

        $zip->close();

        $text = preg_replace('/\s+/', ' ', $text) ?? '';
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $pattern = '/\{\s*([^|]+?)\s*\|\s*([^}]+?)\s*\}/';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        $usedFieldNames = [];
        foreach ($matches as $match) {
            if (!isset($match[1], $match[2])) {
                continue;
            }

            $name     = trim($match[1]);
            $dataType = trim($match[2]);

            if (in_array($name, $usedFieldNames, true)) {
                continue;
            }

            $result = \DB::table('field')->insert([
                'document_id' => $documentId,
                'name'        => $name,
                'data_type'   => $dataType,
            ]);

            if (!$result) {
                throw new Exception("Falha ao criar campo: $name");
            }

            $usedFieldNames[] = $name;
        }
    }

    public function extractTextFromDocx(string $filePath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception('Falha ao abrir ficheiro DOCX');
        }

        $text  = '';
        $index = $zip->locateName('word/document.xml');

        if ($index !== false) {
            $xml = $zip->getFromIndex($index);
            if ($xml !== false) {
                $text = strip_tags($xml);
                $text = preg_replace('/\s+/', ' ', $text) ?? '';
                $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        $zip->close();
        return $text;
    }

    public function deactivateDocument(Request $request)
    {
        $documentId   = (int) $request->input('id');
        $documentName = $request->input('name');

        try {
            \DB::table('document')->where('id', $documentId)->update(['is_active' => false]);

            (new LogsController())->logAction('deactivation-document');
            session()->flash('message', 'Documento ' . $documentName . ' apagado com sucesso!');
        } catch (Exception $e) {
            session()->flash('error', 'Erro ao apagar o documento!');
        }

        return redirect()->route('show-documents');
    }

    public function activateDocument(Request $request)
    {
        $documentId   = (int) $request->input('id');
        $documentName = $request->input('name');

        try {
            \DB::table('document')->where('id', $documentId)->update(['is_active' => true]);

            (new LogsController())->logAction('restore-document');
            session()->flash('message', 'Documento ' . $documentName . ' restaurado com sucesso!');
        } catch (Exception $e) {
            session()->flash('error', 'Erro ao restaurar o documento!');
        }

        return redirect()->route('show-documents');
    }
}
