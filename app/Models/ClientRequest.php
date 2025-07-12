<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use App\Traits\CanApprove;
use App\Traits\HasEditRequest;
use App\Traits\HasRoleScopeTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRequest extends Model
{
    use HasFactory;
    use CanApprove;
    use HasEditRequest;
    use HasRoleScopeTrait;


    protected $fillable = [
        'user_id',
        'client_id',
        'client_request_type_id',
        'request_cost',
        'expected_revenue',
        'response_date',
        'from_date',
        'to_date',
        'rx_rate',
        'approved',
        'ordered_before',
        'description',
        'attachments',
        'zip_file',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function requestType()
    {
        return $this->belongsTo(ClientRequestType::class, 'client_request_type_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the client request has attachments
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * Get the number of attachments
     */
    public function getAttachmentsCountAttribute(): int
    {
        return count($this->attachments ?? []);
    }

    /**
     * Get download URL for a specific attachment
     */
    public function getAttachmentDownloadUrl(string $filename): string
    {
        return route('client-requests.attachments.download', ['clientRequest' => $this->id, 'filename' => $filename]);
    }

    /**
     * Get stream URL for a specific attachment (for preview)
     */
    public function getAttachmentStreamUrl(string $filename): string
    {
        return route('client-requests.attachments.stream', ['clientRequest' => $this->id, 'filename' => $filename]);
    }

        /**
     * Generate zip file for all attachments
     */
    public function generateZipFile(): ?string
    {
        if (empty($this->attachments)) {
            return null;
        }

        try {
            $zipFileName = 'client-request-' . $this->id . '-attachments-' . now()->format('Y-m-d-H-i-s') . '.zip';
            $zipPath = 'client-requests-zips/' . $zipFileName;

            $zip = new \ZipArchive();
            $tempZipPath = storage_path('app/temp/' . $zipFileName);

            // Create temp directory if it doesn't exist
            $tempDir = dirname($tempZipPath);
            if (!file_exists($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    return null;
                }
            }

            if ($zip->open($tempZipPath, \ZipArchive::CREATE) !== TRUE) {
                return null;
            }

            $storage = \Illuminate\Support\Facades\Storage::disk('private');
            $filesAdded = false;

            foreach ($this->attachments as $filename) {
                $filePath = 'client-requests/' . $filename;
                if ($storage->exists($filePath)) {
                    $fileContent = $storage->get($filePath);
                    if ($fileContent !== false) {
                        $zip->addFromString($filename, $fileContent);
                        $filesAdded = true;
                    }
                }
            }

            $zip->close();

            // If no files were added, return null
            if (!$filesAdded) {
                if (file_exists($tempZipPath)) {
                    unlink($tempZipPath);
                }
                return null;
            }

            // Check if temp file exists before reading it
            if (!file_exists($tempZipPath)) {
                return null;
            }

            // Move zip to storage
            $zipContent = file_get_contents($tempZipPath);
            if ($zipContent === false) {
                return null;
            }

            $storage->put($zipPath, $zipContent);

            // Clean up temp file
            if (file_exists($tempZipPath)) {
                unlink($tempZipPath);
            }

            return $zipPath;
        } catch (\Exception $e) {
            // Clean up temp file if it exists
            if (isset($tempZipPath) && file_exists($tempZipPath)) {
                unlink($tempZipPath);
            }
            return null;
        }
    }

    /**
     * Get zip file download URL
     */
    public function getZipFileDownloadUrl(): ?string
    {
        if (!$this->zip_file) {
            return null;
        }

        return route('client-requests.zip.download', ['clientRequest' => $this->id]);
    }
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);

        // Clean up attachments when record is deleted
        static::deleting(function ($clientRequest) {
            if (!empty($clientRequest->attachments)) {
                foreach ($clientRequest->attachments as $filename) {
                    \Illuminate\Support\Facades\Storage::disk('private')->delete('client-requests/' . $filename);
                }
            }
        });

        // Clean up old attachments when attachments are updated
        static::updating(function ($clientRequest) {
            if ($clientRequest->isDirty('attachments')) {
                $oldAttachments = $clientRequest->getOriginal('attachments') ?? [];
                $newAttachments = $clientRequest->attachments ?? [];

                $removedFiles = array_diff($oldAttachments, $newAttachments);

                foreach ($removedFiles as $filename) {
                    \Illuminate\Support\Facades\Storage::disk('private')->delete('client-requests/' . $filename);
                }
            }
        });
    }
}
