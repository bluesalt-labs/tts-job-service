<?php

namespace App\Models;

use App\Helpers\S3Storage;
use App\Helpers\TextToSpeech;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class AudioItemPart extends Model
{
    protected $table = 'tts_items';
    protected $fillable = [ 'status', 'text_file', 'audio_file', 'voice_id', 'output_format'];

    const STATUS_DEFAULT = 'Created';
    const STATUS_PENDING = 'Pending';
    const STATUS_PROCESSED = 'Processed';
    const STATUS_FAILED = 'Failed';

}