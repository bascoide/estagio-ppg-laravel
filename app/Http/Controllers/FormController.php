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

        $typeCourseId = $userCourse->type_course_id;
        $documents = Document::join('document_type_course', 'document.id', '=', 'document_type_course.document_id')
            ->where('document_type_course.type_course_id', $typeCourseId)
            ->select('document.*')
            ->get()
            ->toArray();
        $filledPlanId = (int) $request->input('filled_plan_id', 0);
        
        
        $finalDocument = null;
        $fieldValues = [];

        if ($filledPlanId > 0) {
        $finalDocument = FinalDocument::where('id', $filledPlanId)
            ->where('user_id', $userId)
            ->first();

            if ($finalDocument) {
                $fieldValues = FieldValue::where('final_document_id', $filledPlanId)
                    ->get()
                    ->keyBy('field_id')
                    ->toArray();
            }
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
            ->select('document.*')
            ->get()
            ->toArray();

        $documentId = $request->query('document') ? (int) $request->query('document') : null; 

        if (!$documentId || $documentId <= 0) {
            return redirect('/form');
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

        try {
            $userId = (int) session('user_id');
            $submittedData = [
                'field_ids'    => $request->input('field_ids', []),
                'field_names'  => $request->input('field_names', []),
                'field_values' => $request->input('field_values', []),
            ];

            $planId = $this->processFileUpload($request);

            $finalDocumentId = $this->createFinalDocument($documentId, $userId, $submittedData, $planId);

            if (!$finalDocumentId) {
                throw new Exception('Falha ao criar documento final');
            }

            $this->createVariousFieldValues($documentId, $userId, $submittedData, $finalDocumentId);
            $this->sendSuccessNotification($userId, $finalDocumentId, $planId !== null);

            return redirect('/form');
        } catch (Exception $e) {
            return redirect('/form')->with('error', $e->getMessage());
        }
    }

    private function processFileUpload(Request $request): ?int
    {
        if (!$request->hasFile('planFile') || $request->file('planFile')->getError() !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $request->file('planFile');

        if ($file->getMimeType() !== 'application/pdf') {
            throw new Exception('Apenas ficheiros .pdf permitidos!');
        }

        $uploadDir = public_path('uploads/submittedPlans/');
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception('Falha ao criar diretório de upload');
        }

        $fileName = uniqid('doc_', true) . '.pdf';
        $file->move($uploadDir, $fileName);

        $filePath = '/uploads/submittedPlans/' . $fileName;

        $plan = SubmittedPlan::create(['path' => $filePath, 'verified' => false]);
        return $plan->id;
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

    public function createFinalDocument(int $documentId, int $userId, array $submittedData, ?int $planId = null): int
    {
        $docxPath = $this->generateFinalDocx($documentId, $submittedData);
        $pdfPath  = $this->convertToPdf($docxPath);

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

    public function regenerateFinalDocumentPdf(FinalDocument $finalDocument): string
    {
        $submittedData = $this->buildSubmittedDataFromFinalDocument($finalDocument);
        $docxPath = $this->generateFinalDocx($finalDocument->document_id, $submittedData);
        $newPdfPath = $this->convertToPdf($docxPath);

        $oldPdfPath = $finalDocument->pdf_path
            ? public_path('uploads/generated_docs/' . $finalDocument->pdf_path)
            : null;

        $finalDocument->update(['pdf_path' => $newPdfPath]);

        if ($oldPdfPath && is_file($oldPdfPath) && basename($oldPdfPath) !== $newPdfPath) {
            @unlink($oldPdfPath);
        }

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

        $fieldData = array_combine($formData['field_ids'], $formData['field_values']);
        if ($fieldData === false) return;

        $records = [];
        foreach ($fieldData as $fieldId => $value) {
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

    private function generateFinalDocx(int $documentId, array $submittedValues): string
    {
        $document = Document::find($documentId);
        if (!$document) throw new Exception('Documento não encontrado');

        $basePath     = public_path('uploads');
        $templatePath = $basePath . '/schema/' . $document->docx_path;
        $outputDir    = $basePath . '/generated_docs/';

        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
            throw new Exception('Falha ao criar diretório output');
        }

        $outputDocxPath = $outputDir . 'document_' . $documentId . '_' . time() . '.docx';
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

        $command = sprintf(
            '%s --headless --convert-to pdf --outdir %s %s',
            escapeshellarg($sofficeBinary),
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
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files\\OpenOffice 4\\program\\soffice.exe',
            'C:\\Program Files (x86)\\OpenOffice 4\\program\\soffice.exe',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'soffice') {
                @exec('where soffice', $output, $returnCode);
                if ($returnCode === 0 && !empty($output[0])) {
                    return trim($output[0]);
                }
                continue;
            }

            if (is_file($candidate)) {
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
                            $value = htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
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
        return $replaceCount > 0;
    }
}
