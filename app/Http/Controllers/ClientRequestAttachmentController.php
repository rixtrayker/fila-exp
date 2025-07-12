<?php

namespace App\Http\Controllers;

use App\Models\ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientRequestAttachmentController extends Controller
{
    /**
     * Download a specific attachment from a client request
     */
    public function download(Request $request, ClientRequest $clientRequest, string $filename): StreamedResponse
    {
        // Check if user has permission to access this client request
        if (!auth()->user()->hasRole('super-admin') &&
            !auth()->user()->hasRole('admin') &&
            $clientRequest->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this attachment.');
        }

        // Check if the file exists in the attachments array
        $attachments = $clientRequest->attachments ?? [];
        if (!in_array($filename, $attachments)) {
            abort(404, 'Attachment not found.');
        }

        // Check if file exists in private storage
        $filePath = 'client-requests/' . $filename;
        if (!Storage::disk('private')->exists($filePath)) {
            abort(404, 'File not found.');
        }

        // Get file content and info
        $fileContent = Storage::disk('private')->get($filePath);
        $mimeType = Storage::disk('private')->mimeType($filePath) ?: 'application/octet-stream';

        // Create streamed response for file download
        return response()->streamDownload(
            function () use ($fileContent) {
                echo $fileContent;
            },
            $filename,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]
        );
    }

    /**
     * Stream a specific attachment from a client request (for preview)
     */
    public function stream(Request $request, ClientRequest $clientRequest, string $filename)
    {
        // Check if user has permission to access this client request
        if (!auth()->user()->hasRole('super-admin') &&
            !auth()->user()->hasRole('admin') &&
            $clientRequest->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this attachment.');
        }

        // Check if the file exists in the attachments array
        $attachments = $clientRequest->attachments ?? [];
        if (!in_array($filename, $attachments)) {
            abort(404, 'Attachment not found.');
        }

        // Check if file exists in private storage
        $filePath = 'client-requests/' . $filename;
        if (!Storage::disk('private')->exists($filePath)) {
            abort(404, 'File not found.');
        }

        // Get file content and info
        $fileContent = Storage::disk('private')->get($filePath);
        $mimeType = Storage::disk('private')->mimeType($filePath) ?: 'application/octet-stream';

        // Return file for streaming/preview
        return response($fileContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }

    /**
     * Download zip file containing all attachments from a client request
     */
    public function downloadZip(Request $request, ClientRequest $clientRequest): StreamedResponse
    {
        // Check if user has permission to access this client request
        if (!auth()->user()->hasRole('super-admin') &&
            !auth()->user()->hasRole('admin') &&
            $clientRequest->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this attachment.');
        }

        // Check if zip file exists
        if (!$clientRequest->zip_file || !Storage::disk('private')->exists($clientRequest->zip_file)) {
            // Generate zip file if it doesn't exist
            $zipPath = $clientRequest->generateZipFile();
            if (!$zipPath) {
                abort(404, 'No attachments found or failed to create zip file.');
            }
            $clientRequest->update(['zip_file' => $zipPath]);
        }

        // Get zip file content
        $fileContent = Storage::disk('private')->get($clientRequest->zip_file);
        $zipFileName = basename($clientRequest->zip_file);

        // Create streamed response for zip file download
        return response()->streamDownload(
            function () use ($fileContent) {
                echo $fileContent;
            },
            $zipFileName,
            [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"'
            ]
        );
    }
}
