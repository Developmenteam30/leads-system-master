<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DialerDocument extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the owning documentable model.
     */
    public function documentable()
    {
        return $this->morphTo();
    }

    public function documentType()
    {
        return $this->hasOne(DialerDocumentType::class, 'id', 'document_type_id');
    }

    public function getTemporaryDownloadUrl($expiration): string
    {
        return Storage::disk('s3')->temporaryUrl(
            $this->file_path,
            $expiration,
            [
                'ResponseContentDisposition' => 'attachment; filename='.$this->title,
            ]
        );
    }
}
