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
