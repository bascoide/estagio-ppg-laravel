<?php

namespace App\Http\Controllers;

use App\Models\FinalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    public function destroy(FinalDocument $submission)
    {
        if ((int) $submission->user_id !== (int) session('user_id')) {
            abort(403, 'Não tem permissão para eliminar este documento.');
        }

        $documentName = $submission->document->name ?? 'Documento';
        $filesToDelete = $this->collectFilesForDeletion($submission);
        $plan = $submission->plan;

        try {
            DB::beginTransaction();

            $submission->delete();

            if ($plan && !FinalDocument::where('plan_id', $plan->id)->exists()) {
                $plan->delete();
            }

            DB::commit();
            $this->deletePublicFiles($filesToDelete);

            return redirect()
                ->route('user-submissions')
                ->with('message', $documentName . ' eliminado com sucesso.');
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return back()->with('error', 'Não foi possível eliminar o documento.');
        }
    }

    private function collectFilesForDeletion(FinalDocument $submission): array
    {
        $submission->loadMissing(['plan', 'additions', 'document']);

        $files = [];
        $files[] = $this->publicFileFromBasename((string) $submission->pdf_path, 'uploads/generated_docs');

        foreach ($submission->additions as $addition) {
            $files[] = $this->publicFileFromRelativePath((string) $addition->path, ['uploads/submittedAdditions']);
        }

        if ($submission->plan && !FinalDocument::where('plan_id', $submission->plan_id)->where('id', '!=', $submission->id)->exists()) {
            $files[] = $this->publicFileFromRelativePath((string) $submission->plan->path, ['uploads/submittedPlans']);
        }

        return array_values(array_filter($files));
    }

    private function publicFileFromBasename(string $fileName, string $directory): ?string
    {
        if ($fileName === '' || basename($fileName) !== $fileName) {
            return null;
        }

        return $this->publicFileFromRelativePath(trim($directory, '/') . '/' . $fileName, [$directory]);
    }

    private function publicFileFromRelativePath(string $relativePath, array $allowedPrefixes): ?string
    {
        $normalizedPath = str_replace('\\', '/', ltrim($relativePath, '/'));

        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            $normalizedPrefix = trim(str_replace('\\', '/', $prefix), '/') . '/';
            if (str_starts_with($normalizedPath, $normalizedPrefix)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed || in_array('..', explode('/', $normalizedPath), true)) {
            return null;
        }

        $publicPath = realpath(public_path());
        $filePath = realpath(public_path($normalizedPath));

        if ($publicPath === false || $filePath === false || !$this->isPathInside($filePath, $publicPath)) {
            return null;
        }

        return $filePath;
    }

    private function deletePublicFiles(array $files): void
    {
        foreach (array_unique($files) as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function isPathInside(string $path, string $basePath): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        return $path === $basePath || str_starts_with($path, $basePath . DIRECTORY_SEPARATOR);
    }
}
