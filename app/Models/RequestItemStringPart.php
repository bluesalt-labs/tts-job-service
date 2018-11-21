<?php

namespace App\Models;

use App\Helpers\S3Storage;
use App\Helpers\TextToSpeech;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class RequestItemStringPart extends Model
{
    protected $table = 'tts_items';
    protected $fillable = ['unique_id', 'name', 'user_id', 'status', 'text_file', 'audio_file', 'voice_id', 'output_format'];

    const STATUS_DEFAULT = 'Created';
    const STATUS_PENDING = 'Pending';
    const STATUS_PROCESSED = 'Processed';
    const STATUS_FAILED = 'Failed';



    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requestItem() {
        return $this->belongsTo(RequestItem::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function audioItemParts() {
        return $this->belongsToMany(AudioItemPart::class);
    }

}
