<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use App\Models\Course;
use App\Models\FinalDocument;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;

class ProfessorController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::all();
        $searchName = null;
        $selectedCourse = null;
        $professors = [];

        if ($request->isMethod('get') && ($request->filled('search') || $request->has('course_id'))) {
            $searchName = $request->input('search');
            $selectedCourse = $request->input('course_id') !== '' ? (int) $request->input('course_id') : null;

            $query = Professor::query();

            if ($searchName) {
                $query->where('name', 'like', '%' . $searchName . '%');
            }

            if ($selectedCourse !== null) {
                $query->whereHas('courses', fn($q) => $q->where('course.id', $selectedCourse));
            }

            $professors = $query->get();
        } else {
            $professors = Professor::all();
        }

        return view('adminDashboard.professorSearch', compact('courses', 'searchName', 'selectedCourse', 'professors'));
    }

    public function professorDocuments(Request $request)
    {
        if (!$request->has('professor_id')) {
            return redirect()->route('professor-search');
        }

        try {
            $professorId = (int) $request->input('professor_id');
            $professor = Professor::find($professorId);
            $courses = \DB::table('course')
                ->join('professor_course', 'course.id', '=', 'professor_course.course_id')
                ->where('professor_course.professor_id', $professorId)
                ->select('course.*')
                ->distinct()
                ->get();

            $documentsFilledAt = \DB::table('professor_course')
                ->where('professor_id', $professorId)
                ->pluck('created_at');

            $schoolYears = [];
            foreach ($documentsFilledAt as $filledAt) {
                $documentDate = new DateTime($filledAt);
                if ((int) $documentDate->format('m') >= 8) {
                    $schoolYearStart = $documentDate->format('Y');
                    $schoolYearEnd = (int) $documentDate->format('Y') + 1;
                } else {
                    $schoolYearStart = (int) $documentDate->format('Y') - 1;
                    $schoolYearEnd = $documentDate->format('Y');
                }
                $schoolYears[] = "$schoolYearStart/$schoolYearEnd";
            }

            $schoolYears = array_unique($schoolYears);

            if (empty($schoolYears)) {
                $currentYear = (int) date('Y');
                if ((int) date('n') >= 8) {
                    $schoolYears = ["$currentYear/" . ($currentYear + 1)];
                } else {
                    $schoolYears = [($currentYear - 1) . "/$currentYear"];
                }
            }

            return view('adminDashboard.createProfessorDocument', compact('professor', 'courses', 'schoolYears'));
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            return redirect()->route('professor-search');
        }
    }

    public function createReport(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('professor-search');
        }

        if (!$request->has('professor_id') || !$request->has('school_year')) {
            return redirect()->route('professor-search');
        }

        $professorId = (int) $request->input('professor_id');
        $schoolYear = $request->input('school_year');
        $courseId = $request->filled('course_id') ? (int) $request->input('course_id') : null;

        $professor = Professor::find($professorId);
        $professorName = $professor ? $professor->name : '';

        $courseName = $courseId
            ? \DB::table('course')->where('id', $courseId)->value('name') ?? ''
            : '';

        // Get interns for the report
        $years = explode('/', $schoolYear);
        $startYear = $years[0];
        $endYear = $years[1] ?? ((int) $startYear + 1);

        $internsQuery = \DB::table('user')
            ->join('professor_course', 'professor_course.intern_id', '=', 'user.id')
            ->join('course', 'professor_course.course_id', '=', 'course.id')
            ->join('final_document', 'final_document.user_id', '=', 'user.id')
            ->where('professor_course.professor_id', $professorId)
            ->where('final_document.status', 'Validado')
            ->where(function ($q) use ($startYear, $endYear) {
                $q->whereRaw('(YEAR(professor_course.created_at) = ? AND MONTH(professor_course.created_at) >= 9)', [$startYear])
                  ->orWhereRaw('(YEAR(professor_course.created_at) = ? AND MONTH(professor_course.created_at) < 9)', [$endYear]);
            })
            ->select('user.id', 'user.name as intern_name')
            ->distinct();

        if ($courseId !== null) {
            $internsQuery->where('professor_course.course_id', $courseId);
        }

        $interns = $internsQuery->get()->toArray();

        // Build PHPWord report
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection();
        $section->addText(
            'Relatório de Estágio',
            ['name' => 'Times New Roman', 'size' => 16, 'bold' => true],
            ['align' => 'center']
        );
        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 80,
        ];
        $phpWord->addTableStyle('InternTable', $tableStyle);
        $table = $section->addTable('InternTable');

        $table->addRow();
        $table->addCell(2000)->addText('Ano letivo', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['align' => 'center']);
        $table->addCell(2000)->addText('Curso', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['align' => 'center']);
        $table->addCell(2000)->addText('Aluno', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['align' => 'center']);
        $table->addCell(2000)->addText('Tema', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['align' => 'center']);

        $internCount = 0;
        foreach ($interns as $intern) {
            $internCount++;
            $cleanedCourseName = $this->cleanCourseName($courseName);
            $table->addRow();
            $table->addCell()->addText($schoolYear, ['name' => 'Times New Roman', 'size' => 11, 'bold' => false]);
            $table->addCell()->addText($cleanedCourseName, ['name' => 'Times New Roman', 'size' => 11, 'bold' => false]);
            $table->addCell()->addText($intern->intern_name ?? $intern['intern_name'] ?? '', ['name' => 'Times New Roman', 'size' => 11, 'bold' => false]);
            $table->addCell()->addText('Relatório de estágio', ['name' => 'Times New Roman', 'size' => 11, 'bold' => false]);
        }

        $filename1 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Relatório_estágio.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filename1);

        $filename2 = $this->createFinalReport($professorName, $courseName, $schoolYear, $internCount);

        session()->flash('message', 'Relatório criado com sucesso!');

        $this->downloadMultipleFiles([$filename1, $filename2], $professorName . '.zip');
    }

    private function createFinalReport(string $professorName, string $courseName, string $schoolYear, int $internCount): string
    {
        $templatePath = public_path('templates/template_final_report.docx');
        $template = new TemplateProcessor($templatePath);

        $template->setValue('professor_name', $professorName);
        $template->setValue('course_name', $courseName);
        $template->setValue('school_year', $schoolYear);
        $template->setValue('intern_count', $internCount);

        $currentDate = (new DateTime())->format('d/m/Y');
        $template->setValue('current_date', $currentDate);

        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Declaração_orientação_estágios.docx';
        $template->saveAs($outputPath);

        return $outputPath;
    }

    private function downloadMultipleFiles(array $filePaths, string $zipName = 'reports.zip'): void
    {
        foreach ($filePaths as $filePath) {
            if (!file_exists($filePath)) {
                throw new Exception("File not found: $filePath");
            }
        }

        $tempZip = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive();

        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            unlink($tempZip);
            throw new Exception('Cannot create zip file');
        }

        foreach ($filePaths as $filePath) {
            $zip->addFile($filePath, basename($filePath));
        }

        if (!$zip->close()) {
            unlink($tempZip);
            throw new Exception('Cannot finalize zip file');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($tempZip));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($tempZip);

        unlink($tempZip);
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        exit;
    }

    public function createStatusExcel(Request $request)
    {
        if (ob_get_length()) {
            ob_end_clean();
        }

        $teacherName = $request->input('teacher_name');
        $professorId = (int) $request->input('professor_id', 0);

        $latestDocuments = \DB::table('final_document')
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('user_id', 'document_id', 'plan_id');

        $documents = \DB::table('final_document')
            ->joinSub($latestDocuments, 'latest_documents', function ($join) {
                $join->on('final_document.id', '=', 'latest_documents.latest_id');
            })
            ->join('user', 'final_document.user_id', '=', 'user.id')
            ->join('professor_course', function ($join) {
                $join->on('professor_course.intern_id', '=', 'user.id')
                    ->on('professor_course.course_id', '=', 'user.course_id');
            })
            ->join('professor', 'professor_course.professor_id', '=', 'professor.id')
            ->when($professorId > 0, function ($query) use ($professorId) {
                $query->where('professor.id', $professorId);
            }, function ($query) use ($teacherName) {
                $query->where('professor.name', $teacherName);
            })
            ->whereIn('final_document.status', ['Aceite', 'Validado'])
            ->select(
                'final_document.status',
                'final_document.created_at as delivered_at',
                'user.name as student_name',
                'user.email'
            )
            ->distinct()
            ->orderBy('user.name')
            ->get()
            ->toArray();

        if (empty($documents)) {
            session()->flash('error', 'Este professor não tem nenhum aluno com documentos aceites ou válidos!');
            return redirect()->route('professor-search');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Status');
        $sheet->setCellValue('B1', 'Data de Entrega');
        $sheet->setCellValue('C1', 'Nome do Aluno');
        $sheet->setCellValue('D1', 'Email');

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'D3D3D3'],
            ],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($documents as $document) {
            $doc = (array) $document;
            $sheet->setCellValue('A' . $row, $doc['status']);
            $sheet->setCellValue('B' . $row, $doc['delivered_at']);
            $sheet->setCellValue('C' . $row, $doc['student_name']);
            $sheet->setCellValue('D' . $row, $doc['email']);

            $rowStyle = [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => $doc['status'] === 'Aceite' ? 'ea9927' : '6efc16'],
                ],
            ];
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($rowStyle);

            $row++;
        }

        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="protocolos_alunos ' . $teacherName . '.xlsx"');
        header('Cache-Control: max-age=0');
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }

    private function defineSchoolYear(?string $documentFilledAt): ?string
    {
        if (!$documentFilledAt) {
            return null;
        }

        try {
            $date = new DateTime($documentFilledAt);
            $year = (int) $date->format('Y');
            $month = (int) $date->format('m');

            return ($month >= 9)
                ? sprintf('%d/%d', $year, $year + 1)
                : sprintf('%d/%d', $year - 1, $year);
        } catch (Exception $e) {
            error_log('Error calculating school year: ' . $e->getMessage());
            return null;
        }
    }

    private function cleanCourseName(string $courseName): string
    {
        $prefixes = [
            'Licenciatura em ',
            'Mestrado em ',
            'Pós-Graduação em ',
            'Pós-Graduação ',
            'CTeSP de ',
            'CTeSP em ',
        ];
        return str_ireplace($prefixes, '', $courseName);
    }
}
