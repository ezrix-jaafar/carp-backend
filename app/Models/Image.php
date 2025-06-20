<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'carpet_id',
        'url',
        'filename',
        'disk',
        'size',
        'mime_type',
        'uploaded_by',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
    ];
    
    /**
     * Get the carpet that owns the image.
     */
    public function carpet(): BelongsTo
    {
        return $this->belongsTo(Carpet::class);
    }
    
    /**
     * Get the uploader of the image.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    /**
     * Get the full URL for the image.
     *
     * @return string
     */
    public function getFullUrl(): string
    {
        // Case 1: 'url' field is already an absolute HTTP(S) URL.
        if ($this->url && (\Illuminate\Support\Str::startsWith($this->url, 'http://') || \Illuminate\Support\Str::startsWith($this->url, 'https://'))) {
            return $this->url;
        }

        $currentDiskName = $this->disk ?? config('filesystems.default');
        $pathForStorage = $this->url ?: $this->filename; // Prefer 'url' if set (even if relative), else 'filename'

        if (empty($pathForStorage)) {
            \Illuminate\Support\Facades\Log::error("Image ID {$this->id}: Both url and filename are empty for disk '{$currentDiskName}'.");
            return ''; // Or a placeholder image URL
        }

        // Case 2: Use Storage facade to get the URL. This handles S3, R2, public local disk, etc., based on filesystem config.
        // It respects visibility, 'url' config in filesystems.php, etc.
        if (config("filesystems.disks.{$currentDiskName}")) {
            // If $pathForStorage is an absolute path on the server for a local disk, 
            // Storage::url() might not work as expected. It expects path relative to disk root.
            // Assuming $pathForStorage (from $this->url or $this->filename) is stored as a relative path within its disk.
            return \Illuminate\Support\Facades\Storage::disk($currentDiskName)->url($pathForStorage);
        }

        // Fallback / Error: If disk is not configured.
        \Illuminate\Support\Facades\Log::error("Image ID {$this->id}: Disk '{$currentDiskName}' not found in filesystem configuration.");
        return ''; // Or a placeholder image URL
    }
    
    /**
     * Delete the image from storage when the model is deleted.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function (self $image) {
            if ($image->disk && $image->filename) {
                Storage::disk($image->disk)->delete($image->filename);
            }
        });
    }
}
