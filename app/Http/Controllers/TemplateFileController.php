<?php

namespace App\Http\Controllers;

use App\Models\TemplateFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemplateFileController extends Controller
{
    /**
     * Download a template file
     */
    public function download(TemplateFile $templateFile): StreamedResponse
    {
        // Check if file exists in public storage
        if (!Storage::disk('public')->exists($templateFile->path)) {
            abort(404, 'File not found');
        }

        // Get file content and info
        $fileContent = Storage::disk('public')->get($templateFile->path);
        $mimeType = Storage::disk('public')->mimeType($templateFile->path) ?: 'application/octet-stream';
        
        // Create streamed response for file download
        return response()->streamDownload(
            function () use ($fileContent) {
                echo $fileContent;
            },
            $templateFile->name,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $templateFile->name . '"'
            ]
        );
    }
}