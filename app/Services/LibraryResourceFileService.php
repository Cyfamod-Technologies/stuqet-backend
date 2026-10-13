<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LibraryResourceFileService
{
    public function processUpload(UploadedFile $file): array
    {
        $baseToken = Str::uuid()->toString();
        $extension = strtolower($file->getClientOriginalExtension());
        $originalDirectory = public_path('uploads/library/originals');
        File::ensureDirectoryExists($originalDirectory);

        $originalFilename = $baseToken . '.' . $extension;
        $file->move($originalDirectory, $originalFilename);

        $relativeOriginalPath = 'uploads/library/originals/' . $originalFilename;
        $absoluteOriginalPath = public_path($relativeOriginalPath);

        $details = [
            'file_original_name' => $file->getClientOriginalName(),
            'file_path' => $relativeOriginalPath,
            'file_extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'file_size' => (int) $file->getSize(),
            'cover_image_path' => null,
            'preview_page_paths' => [],
            'preview_pdf_path' => null,
            'page_count' => null,
        ];

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return array_merge($details, $this->buildImagePreviewAssets($absoluteOriginalPath, $baseToken, $extension));
        }

        if ($extension === 'pdf') {
            return array_merge($details, $this->buildPdfPreviewAssets($absoluteOriginalPath, $baseToken));
        }

        if ($extension === 'docx') {
            $previewPdfPath = $this->convertDocxToPdf($absoluteOriginalPath, $baseToken);
            if ($previewPdfPath) {
                $details['preview_pdf_path'] = $previewPdfPath;
                return array_merge(
                    $details,
                    $this->buildPdfPreviewAssets(public_path($previewPdfPath), $baseToken)
                );
            }
        }

        return $details;
    }

    protected function buildPdfPreviewAssets(string $absolutePdfPath, string $baseToken): array
    {
        $previewDirectory = public_path('uploads/library/previews');
        File::ensureDirectoryExists($previewDirectory);

        $previewPrefix = $previewDirectory . DIRECTORY_SEPARATOR . $baseToken;
        $command = 'pdftoppm -png -f 1 -l 3 ' . escapeshellarg($absolutePdfPath) . ' ' . escapeshellarg($previewPrefix) . ' 2>/dev/null';
        @shell_exec($command);

        $previewPaths = [];
        for ($page = 1; $page <= 3; $page++) {
            $previewFile = 'uploads/library/previews/' . $baseToken . '-' . $page . '.png';
            if (file_exists(public_path($previewFile))) {
                $previewPaths[] = $previewFile;
            }
        }

        return [
            'cover_image_path' => $previewPaths[0] ?? null,
            'preview_page_paths' => $previewPaths,
            'page_count' => $this->extractPdfPageCount($absolutePdfPath),
        ];
    }

    protected function buildImagePreviewAssets(string $absoluteImagePath, string $baseToken, string $extension): array
    {
        $previewDirectory = public_path('uploads/library/previews');
        $coverDirectory = public_path('uploads/library/covers');

        File::ensureDirectoryExists($previewDirectory);
        File::ensureDirectoryExists($coverDirectory);

        $previewRelativePath = 'uploads/library/previews/' . $baseToken . '-1.' . $extension;
        $coverRelativePath = 'uploads/library/covers/' . $baseToken . '-cover.' . $extension;

        $this->resizeImage($absoluteImagePath, public_path($previewRelativePath), 1400, 1400, $extension);
        $this->resizeImage($absoluteImagePath, public_path($coverRelativePath), 500, 700, $extension);

        return [
            'cover_image_path' => $coverRelativePath,
            'preview_page_paths' => [$previewRelativePath],
            'page_count' => 1,
        ];
    }

    protected function convertDocxToPdf(string $absoluteDocxPath, string $baseToken): ?string
    {
        $tempDirectory = storage_path('app/library-preview-temp');
        File::ensureDirectoryExists($tempDirectory);

        $command = 'HOME=/tmp libreoffice --headless --convert-to pdf --outdir '
            . escapeshellarg($tempDirectory) . ' '
            . escapeshellarg($absoluteDocxPath) . ' 2>/dev/null';
        @shell_exec($command);

        $convertedFile = $tempDirectory . DIRECTORY_SEPARATOR . pathinfo($absoluteDocxPath, PATHINFO_FILENAME) . '.pdf';
        if (!file_exists($convertedFile)) {
            return null;
        }

        $targetDirectory = public_path('uploads/library/previews_pdf');
        File::ensureDirectoryExists($targetDirectory);
        $targetRelativePath = 'uploads/library/previews_pdf/' . $baseToken . '.pdf';
        File::move($convertedFile, public_path($targetRelativePath));

        return $targetRelativePath;
    }

    protected function extractPdfPageCount(string $absolutePdfPath): ?int
    {
        $output = @shell_exec('pdfinfo ' . escapeshellarg($absolutePdfPath) . ' 2>/dev/null');
        if (!$output) {
            return null;
        }

        if (preg_match('/Pages:\s+(\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function resizeImage(string $sourcePath, string $targetPath, int $maxWidth, int $maxHeight, string $extension): void
    {
        [$width, $height] = getimagesize($sourcePath);

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $targetWidth = (int) round($width * $ratio);
        $targetHeight = (int) round($height * $ratio);

        $destination = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($extension === 'png') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
        }

        $source = $extension === 'png'
            ? imagecreatefrompng($sourcePath)
            : imagecreatefromjpeg($sourcePath);

        imagecopyresampled($destination, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        if ($extension === 'png') {
            imagepng($destination, $targetPath);
        } else {
            imagejpeg($destination, $targetPath, 88);
        }

        imagedestroy($source);
        imagedestroy($destination);
    }
}
