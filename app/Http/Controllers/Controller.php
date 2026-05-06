<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function resolvePublicUploadFile(string $directory, string $fileName): string
    {
        if ($fileName === '' || basename($fileName) !== $fileName) {
            abort(404, 'Ficheiro nao encontrado');
        }

        $basePath = realpath(public_path($directory));
        if ($basePath === false) {
            abort(404, 'Diretorio nao encontrado');
        }

        $filePath = realpath($basePath . DIRECTORY_SEPARATOR . $fileName);
        if ($filePath === false || !$this->isPathInside($filePath, $basePath)) {
            abort(404, 'Ficheiro nao encontrado');
        }

        return $filePath;
    }

    protected function resolveStoredPublicFile(string $relativePath, array $allowedPrefixes): string
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
            abort(404, 'Ficheiro nao encontrado');
        }

        $publicPath = realpath(public_path());
        $filePath = realpath(public_path($normalizedPath));

        if ($publicPath === false || $filePath === false || !$this->isPathInside($filePath, $publicPath)) {
            abort(404, 'Ficheiro nao encontrado');
        }

        return $filePath;
    }

    private function isPathInside(string $path, string $basePath): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        return $path === $basePath || str_starts_with($path, $basePath . DIRECTORY_SEPARATOR);
    }
}
