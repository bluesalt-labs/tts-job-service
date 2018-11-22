<?php

namespace App\Models;

use App\Helpers\S3Storage;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Model;

class AudioItem extends Model
{
    protected $table        = 'audio_items';
    protected $fillable     = ['name', 'user_id', 'status', 'text_file', 'audio_file', 'voice_id', 'output_format'];

    const STATUS_DEFAULT    = 'Created';
    const STATUS_PENDING    = 'Pending';
    const STATUS_PROCESSED  = 'Processed';
    const STATUS_FAILED     = 'Failed';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requestItem() {
        return $this->belongsTo(RequestItem::class);
    }

    /**
     * @return array
     */
    public function getAudioItemParts() {
        if($this->requestItem()->exists()) {
            return $this->requestItem->audioItemParts()->where('voice', $this->voice)->get();
        }

        return [];
    }

    /**
     * Determines if associated AudioItemParts exist and have been processed.
     *
     * @return bool
     */
    public function haveAudioItemPartsBeenProcessed() {
        if($this->requestItem()->exists()) {
            return !!(
                ($this->requestItem
                    ->audioItemParts()
                    ->where('voice', $this->voice)
                    ->where('status', '!=', AudioItemPart::STATUS_PROCESSED)
                    ->count()
                ) > 0
            );
        }

        return false;
    }


    public function process() {
        if( $this->haveAudioItemPartsBeenProcessed() ) {
            // todo: combine audio file parts into 1 file
            // todo: upload audio file to s3
            // todo: should the audioItemParts be deleted?
        } else {

        }
    }

    /**
     * // todo
     * Generate the audio file path for this model.
     *
     * @param bool $force
     */
    private function generateAudioFilePath($force = false) {
        //if( $force ||
        //    !array_key_exists('audio_file', $this->attributes) ||
        //    !$this->attributes['audio_file']
        //) {
        //    $this->attributes['audio_file'] = 'audio/'.$this->unique_id.'.'.$this->output_format;
        //}
    }

}
