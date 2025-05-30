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
        // If direct URL is stored, return it
        if ($this->url) {
            return $this->url;
        }
        
        // For R2/S3 storage, construct the URL based on bucket and region
        $disk = $this->disk ?? 'r2';
        $driver = config("filesystems.disks.{$disk}.driver");
        
        if ($driver === 's3') {
            $bucket = config("filesystems.disks.{$disk}.bucket");
            $endpoint = config("filesystems.disks.{$disk}.endpoint");
            
            // If custom endpoint is used (like Cloudflare R2), construct URL directly
            if ($endpoint) {
                return rtrim($endpoint, '/') . '/' . $bucket . '/' . $this->filename;
            }
            
            // For standard S3
            $region = config("filesystems.disks.{$disk}.region", 'us-east-1');
            return "https://{$bucket}.s3.{$region}.amazonaws.com/{$this->filename}";
        }
        
        // Fallback to just returning the filename
        return $this->filename;
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
