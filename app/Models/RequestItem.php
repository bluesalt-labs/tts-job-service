<?php

namespace App\Models;

use App\Helpers\S3Storage;
use App\Helpers\TextToSpeech;

use App\Models\RequestItemStringPart;
use App\Models\AudioItem;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class RequestItem extends Model
{
    protected $table = 'audio_items';
    protected $fillable = ['unique_id', 'output_name', 'user_id', 'status', 'text_file', 'audio_file', 'voice_id', 'output_format'];

    const STATUS_DEFAULT = 'Queued';
    // todo
    const STATUS_COMPLETE = 'Complete';
    const STATUS_FAILED = 'Failed';




    public function process() {
        // todo
    }
}
