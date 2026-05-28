<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Document;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FinalDocument;
use App\Models\Professor;
use App\Models\SubmittedPlan;
use App\Models\User;
use App\Services\EmailService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class FormController extends Controller
{
    public function index()
    {
        return view('guiaForm');
    }

    public function form(Request $request)
    {
        $userId = session('user_id');
        $userCourse = Course::join('user', 'user.course_id', '=', 'course.id')
            ->where('user.id', $userId)
            ->select('course.*')
            ->first();

        if (!$userCourse) {
            return redirect('/guia-form')->with('error', 'Curso do utilizador não encontrado.');
        }

        $typeCourseId = $userCourse->type_course_id;
        $documents = Document::join('document_type_course', 'document.id', '=', 'document_type_course.document_id')
            ->where('document_type_course.type_course_id', $typeCourseId)
            ->where('document.is_active', true)
            ->select('document.*')
            ->get()
            ->toArray();
        $filledPlanId = (int) $request->input('filled_plan_id', 0);
        
        
        $finalDocument = null;
        $fieldValues = [];

        if ($filledPlanId > 0) {
            $finalDocument = $this->findUsableFilledPlan($filledPlanId, (int) $userId);

            if (!$finalDocument) {
                return redirect()->route('user-submissions')
                    ->with('error', 'Este plano já não está disponível. Comece um novo plano.');
            }

            $fieldValues = FieldValue::where('final_document_id', $filledPlanId)
                ->get()
                ->keyBy('field_id')
                ->toArray();
        }

        return view('form', compact('documents', 'finalDocument', 'fieldValues', 'filledPlanId'));
    }

    public function generateForm(Request $request)
    {
        
        
        $userId = session('user_id');
        $userCourse = Course::join('user', 'user.course_id', '=', 'course.id')
            ->where('user.id', $userId)
            ->select('course.*')
            ->first();

        if (!$userCourse) {
            return redirect('/form')->with('error', 'Curso do utilizador não encontrado.');
        }

        $typeCourseId = $userCourse->type_course_id;
        $userCourseName = $userCourse->name;
        $courseId = $userCourse->id;

        $availableProfessors = Professor::join('professor_course', 'professor.id', '=', 'professor_course.professor_id')
            ->where('professor_course.course_id', $courseId)
            ->distinct()
            ->select('professor.name')
            ->get()
            ->toArray();

        $documents = Document::join('document_type_course', 'document.id', '=', 'document_type_course.document_id')
            ->where('document_type_course.type_course_id', $typeCourseId)
            ->where('document.is_active', true)
            ->select('document.*')
            ->get()
            ->toArray();

        $filledPlanId = (int) $request->query('filled_plan_id', 0);
        $filledPlan = null;

        if ($filledPlanId > 0) {
            $filledPlan = $this->findUsableFilledPlan($filledPlanId, (int) $userId);

            if (!$filledPlan) {
                return redirect()->route('user-submissions')
                    ->with('error', 'Este plano já não está disponível. Comece um novo plano.');
            }
        }

        $documentId = $request->query('document') ? (int) $request->query('document') : null; 

        if (!$documentId || $documentId <= 0) {
            return redirect('/form');
        }

        if (!$this->isDocumentAvailableForTypeCourse($documentId, $typeCourseId)) {
            return redirect('/form')->with('error', 'Documento inválido ou indisponível para o seu curso.');
        }

        $selectedDocument = Document::find($documentId);
        if (!$selectedDocument) {
            return redirect('/form')->with('error', 'Documento não encontrado.');
        }

        if ($filledPlan && $selectedDocument->type !== 'Protocolo') {
            return redirect()->route('form', ['filled_plan_id' => $filledPlanId])
                ->with('error', 'Selecione um protocolo para continuar a partir do plano.');
        }

        if (!$filledPlan && $selectedDocument->type !== 'Plano') {
            return redirect('/form')->with('error', 'Comece por selecionar um plano.');
        }

        $fields = Field::where('document_id', $documentId)->get()->toArray();

        return view('form', compact('documents', 'fields', 'userCourseName', 'availableProfessors', 'documentId'));
    }

    public function submitForm(Request $request)
    {
        $documentId = (int) $request->input('document_id', 0);
        if ($documentId <= 0) {
            return redirect('/form')->with('error', 'ID do documento inválido');
        }

        $finalDocumentId = null;
        $generatedPdfPath = null;
        $uploadedPlanPath = null;

        try {
            $userId = (int) session('user_id');
            $userCourse = Course::join('user', 'user.course_id', '=', 'course.id')
                ->where('user.id', $userId)
                ->select('course.*')
                ->first();

            if (!$userCourse) {
                throw new Exception('Curso do utilizador não encontrado.');
            }

            if (!$this->isDocumentAvailableForTypeCourse($documentId, (int) $userCourse->type_course_id)) {
                throw new Exception('Documento inválido ou indisponível para o seu curso.');
            }

            $document = Document::find($documentId);
            if (!$document) {
                throw new Exception('Documento não encontrado.');
            }

            $filledPlanId = (int) $request->input('filled_plan_id', 0);
            if ($document->type !== 'Plano' && !$this->findUsableFilledPlan($filledPlanId, $userId)) {
                throw new Exception('O plano associado já não está disponível. Comece um novo plano.');
            }

            $submittedData = [
                'field_ids'    => $request->input('field_ids', []),
                'field_names'  => $request->input('field_names', []),
                'field_values' => $request->input('field_values', []),
            ];

            DB::beginTransaction();

            $planId = $this->processFileUpload($request, $uploadedPlanPath, $document->type !== 'Plano');
            $finalDocumentId = $this->createFinalDocument($documentId, $userId, $submittedData, $planId, $generatedPdfPath);

            if (!$finalDocumentId) {
                throw new Exception('Falha ao criar documento final');
            }

            $this->createVariousFieldValues($documentId, $userId, $submittedData, $finalDocumentId);
            $this->sendSuccessNotification($userId, $finalDocumentId, $planId !== null);

            DB::commit();

            return redirect('/form');
        } catch (Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->cleanupFailedSubmission($finalDocumentId, $uploadedPlanPath, $generatedPdfPath);

            return redirect('/form')->with('error', $e->getMessage());
        }
    }

    private function processFileUpload(Request $request, ?string &$uploadedPlanPath = null, bool $required = false): ?int
    {
        if (!$request->hasFile('planFile') || $request->file('planFile')->getError() !== UPLOAD_ERR_OK) {
            if ($required) {
                throw new Exception('O upload do plano assinado e obrigatorio.');
            }

            return null;
        }

        $file = $request->file('planFile');

        if (!$file->isValid() || $file->getMimeType() !== 'application/pdf' || $file->getSize() > 10 * 1024 * 1024) {
            throw new Exception('Apenas ficheiros .pdf permitidos!');
        }

        $uploadDir = public_path('uploads/submittedPlans/');
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new Exception('Falha ao criar diretório de upload');
        }

        $fileName = uniqid('doc_', true) . '.pdf';
        $file->move($uploadDir, $fileName);

        $filePath = '/uploads/submittedPlans/' . $fileName;
        $uploadedPlanPath = public_path(ltrim($filePath, '/'));

        $plan = SubmittedPlan::create(['path' => $filePath, 'verified' => false]);
        return $plan->id;
    }

    private function cleanupFailedSubmission(?int $finalDocumentId, ?string $uploadedPlanPath, ?string $generatedPdfPath): void
    {
        if ($generatedPdfPath && is_file($generatedPdfPath)) {
            @unlink($generatedPdfPath);
        } elseif ($finalDocumentId) {
            $finalDocument = FinalDocument::find($finalDocumentId);
            if ($finalDocument && $finalDocument->pdf_path && basename((string) $finalDocument->pdf_path) === (string) $finalDocument->pdf_path) {
                $pdfPath = public_path('uploads/generated_docs/' . $finalDocument->pdf_path);
                if (is_file($pdfPath)) {
                    @unlink($pdfPath);
                }
            }
        }

        if ($uploadedPlanPath && is_file($uploadedPlanPath)) {
            @unlink($uploadedPlanPath);
        }
    }

    private function sendSuccessNotification(int $userId, int $finalDocumentId, bool $hasPlanFile): void
    {
        if ($hasPlanFile) {
            session()->flash('message', 'Protocolo submetido com sucesso!');
        } else {
            session()->flash('message', 'Plano submetido com sucesso! Irá receber um email, proceda através do mesmo.');
            $userEmail = User::find($userId)->email;
            (new EmailService())->sendPlanEmail($userEmail, $finalDocumentId);
        }
    }

    public function createFinalDocument(int $documentId, int $userId, array $submittedData, ?int $planId = null, ?string &$generatedPdfPath = null): int
    {
        $docxPath = $this->generateFinalDocx($documentId, $submittedData);
        $pdfPath  = $this->convertToPdf($docxPath);
        $generatedPdfPath = public_path('uploads/generated_docs/' . $pdfPath);

        $finalDocument = FinalDocument::create([
            'user_id'     => $userId,
            'pdf_path'    => $pdfPath,
            'document_id' => $documentId,
            'status'      => 'Pendente',
            'plan_id'     => $planId,
        ]);

        if (!$finalDocument) {
            throw new Exception('Falha ao criar final document record');
        }

        return $finalDocument->id;
    }

    public function regenerateFinalDocumentPdf(FinalDocument $finalDocument, ?string &$oldPdfPath = null): string
    {
        $submittedData = $this->buildSubmittedDataFromFinalDocument($finalDocument);
        $docxPath = $this->generateFinalDocx($finalDocument->document_id, $submittedData);
        $newPdfPath = $this->convertToPdf($docxPath);

        if ($finalDocument->pdf_path && basename((string) $finalDocument->pdf_path) === (string) $finalDocument->pdf_path) {
            $oldPdfPath = public_path('uploads/generated_docs/' . $finalDocument->pdf_path);
        }

        $finalDocument->update(['pdf_path' => $newPdfPath]);

        return $newPdfPath;
    }

    private function buildSubmittedDataFromFinalDocument(FinalDocument $finalDocument): array
    {
        $fieldValues = FieldValue::where('final_document_id', $finalDocument->id)
            ->join('field', 'field_value.field_id', '=', 'field.id')
            ->where('field.document_id', $finalDocument->document_id)
            ->orderBy('field.id')
            ->get([
                'field_value.field_id',
                'field.name as field_name',
                'field_value.value',
            ]);

        return [
            'field_ids'    => $fieldValues->pluck('field_id')->map(fn($id) => (string) $id)->toArray(),
            'field_names'  => $fieldValues->pluck('field_name')->toArray(),
            'field_values' => $fieldValues->pluck('value')->map(fn($value) => (string) $value)->toArray(),
        ];
    }

    private function createVariousFieldValues(int $documentId, int $userId, array $formData, int $finalDocumentId): void
    {
        if (empty($formData['field_ids']) || empty($formData['field_values'])) {
            return;
        }

        if (count($formData['field_ids']) !== count($formData['field_values'])) {
            throw new Exception('Dados do formulário inválidos.');
        }

        $fieldData = array_combine($formData['field_ids'], $formData['field_values']);
        if ($fieldData === false) return;

        $validFieldIds = Field::where('document_id', $documentId)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $records = [];
        foreach ($fieldData as $fieldId => $value) {
            if (!in_array((int) $fieldId, $validFieldIds, true)) {
                throw new Exception('Campo inválido para o documento selecionado.');
            }

            $records[] = [
                'document_id'       => $documentId,
                'user_id'           => $userId,
                'field_id'          => (int) $fieldId,
                'value'             => (string) $value,
                'final_document_id' => $finalDocumentId,
            ];
        }

        FieldValue::insert($records);
    }

    private function isDocumentAvailableForTypeCourse(int $documentId, int $typeCourseId): bool
    {
        return Document::join('document_type_course', 'document.id', '=', 'document_type_course.document_id')
            ->where('document.id', $documentId)
            ->where('document.is_active', true)
            ->where('document_type_course.type_course_id', $typeCourseId)
            ->exists();
    }

    private function findUsableFilledPlan(int $filledPlanId, int $userId): ?FinalDocument
    {
        if ($filledPlanId <= 0) {
            return null;
        }

        return FinalDocument::with('document')
            ->where('id', $filledPlanId)
            ->where('user_id', $userId)
            ->whereNotIn('status', ['Cancelado', 'Inativo'])
            ->whereHas('document', fn ($query) => $query->where('type', 'Plano'))
            ->first();
    }

    private function generateFinalDocx(int $documentId, array $submittedValues): string
    {
        $document = Document::find($documentId);
        if (!$document) throw new Exception('Documento não encontrado');

        $basePath     = public_path('uploads');
        $templatePath = $this->resolvePublicUploadFile('uploads/schema', (string) $document->docx_path);
        $outputDir    = $basePath . '/generated_docs/';

        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
            throw new Exception('Falha ao criar diretório output');
        }

        $outputDocxPath = $outputDir . uniqid('document_' . $documentId . '_', true) . '.docx';
        if (!copy($templatePath, $outputDocxPath)) {
            throw new Exception('Falha ao copiar template');
        }

        $this->replacePlaceholders($templatePath, $outputDocxPath, $submittedValues);

        return $outputDocxPath;
    }

    public function convertToPdf(string $docxPath): string
    {
        $outputDir = dirname($docxPath);
        $pdfPath   = preg_replace('/\.docx$/', '.pdf', $docxPath);
        $sofficeBinary = $this->resolveSofficeBinary();

        if ($sofficeBinary === null) {
            throw new Exception('LibreOffice não foi encontrado. Configure o comando soffice no PATH ou instale o LibreOffice.');
        }

        $profileDir = storage_path('framework/libreoffice');
        if (!is_dir($profileDir) && !mkdir($profileDir, 0775, true)) {
            throw new Exception('Falha ao criar diretório temporário do LibreOffice.');
        }

        $command = sprintf(
            '%s -env:UserInstallation=%s --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($sofficeBinary),
            escapeshellarg($this->pathToFileUri($profileDir)),
            escapeshellarg($outputDir),
            escapeshellarg($docxPath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($pdfPath)) {
            $details = trim(implode(PHP_EOL, $output));
            throw new Exception('Falha ao converter documento para PDF' . ($details !== '' ? ': ' . $details : ''));
        }

        if (file_exists($docxPath)) {
            unlink($docxPath);
        }

        return basename($pdfPath);
    }

    private function resolveSofficeBinary(): ?string
    {
        $candidates = [
            'soffice',
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\OpenOffice 4\\program\\soffice.exe',
            'C:\\Program Files (x86)\\OpenOffice 4\\program\\soffice.exe',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'soffice') {
                if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
                    @exec('where soffice', $output, $returnCode);
                } else {
                    @exec('command -v soffice', $output, $returnCode);
                }

                if ($returnCode === 0 && !empty($output[0])) {
                    return trim($output[0]);
                }

                continue;
            }

            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function replacePlaceholders(string $templateDocument, string $outputPath, array $submittedValues): bool
    {
        if (!copy($templateDocument, $outputPath)) {
            throw new Exception('Falha ao criar cópia de trabalho do documento base.');
        }

        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== true) {
            throw new Exception('Falha ao abrir ficheiro DOCX para modificação.');
        }

        $replaceCount   = 0;
        $filesToProcess = ['word/document.xml'];
        for ($i = 1; $i <= 3; $i++) {
            $filesToProcess[] = "word/header{$i}.xml";
            $filesToProcess[] = "word/footer{$i}.xml";
        }

        foreach ($filesToProcess as $fileName) {
            $xml = $zip->getFromName($fileName);
            if ($xml === false) continue;

            $pattern = '/\{(?:[^<{}]+|<[^>]+>)*?\|\s*(?:[^<{}]+|<[^>]+>)*?\}/';
            preg_match_all($pattern, $xml, $matches, PREG_SET_ORDER);

            $processedXml = $xml;
            foreach ($matches as $match) {
                $fullPlaceholder  = $match[0];
                $cleanPlaceholder = preg_replace('/<[^>]+>/', '', $fullPlaceholder);

                if (!preg_match('/\{\s*([^|}]+?)\s*\|\s*([^}]+?)\s*\}/', $cleanPlaceholder, $parts)) continue;

                $fieldName = trim($parts[1]);
                $fieldType = trim($parts[2]);

                if (in_array(strtolower($fieldType), ['title', 'group']) || in_array($fieldType, ['NA start', 'NA end'])) {
                    $processedXml = str_replace($fullPlaceholder, '', $processedXml, $count);
                    $replaceCount += $count;
                    continue;
                }

                foreach ($submittedValues['field_names'] as $index => $submittedName) {
                    if (trim($submittedName) === $fieldName && isset($submittedValues['field_values'][$index])) {
                        $value = (string) $submittedValues['field_values'][$index];
                        if ($value === 'true') {
                            $value = '☒';
                        } elseif ($value === 'false') {
                            $value = '☐';
                        } else {
                            $value = htmlspecialchars($value, ENT_XML1 | ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
                        }

                        $processedXml = str_replace($fullPlaceholder, $value, $processedXml, $count);
                        $replaceCount += $count;
                        break;
                    }
                }
            }

            if ($processedXml !== $xml) {
                $zip->deleteName($fileName);
                $zip->addFromString($fileName, $processedXml);
            }
        }

        $zip->close();
        $this->validateDocxXml($outputPath, $filesToProcess);

        return $replaceCount > 0;
    }

    private function validateDocxXml(string $docxPath, array $filesToProcess): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new Exception('Falha ao abrir ficheiro DOCX gerado para validação.');
        }

        $previous = libxml_use_internal_errors(true);

        foreach ($filesToProcess as $fileName) {
            $xml = $zip->getFromName($fileName);
            if ($xml === false) continue;

            libxml_clear_errors();
            simplexml_load_string($xml);

            $errors = libxml_get_errors();
            if (!empty($errors)) {
                $zip->close();
                libxml_clear_errors();
                libxml_use_internal_errors($previous);

                $message = trim($errors[0]->message);
                throw new Exception("DOCX gerado ficou com XML inválido em {$fileName}: {$message}");
            }
        }

        $zip->close();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }

    private function pathToFileUri(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);

        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            return 'file:///' . ltrim(str_replace(' ', '%20', $path), '/');
        }

        return 'file://' . str_replace(' ', '%20', $path);
    }
}
