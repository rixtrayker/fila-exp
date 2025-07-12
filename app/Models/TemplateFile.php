<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use function phar\update;

class TemplateFile extends Model
{
    protected $fillable = [
        'name',
        'path',
        'size',
        'mime_type',
        'uploaded_by',
        'country_id',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->isDirty('path')) {
                Storage::disk('private')->delete($model->getOriginal('path'));
            }
        });

        static::deleting(function ($model) {
            Storage::disk('private')->delete($model->path);
        });
    }

    public function getDownloadUrlAttribute()
    {
        return route('template-files.download', $this->id);
    }
}