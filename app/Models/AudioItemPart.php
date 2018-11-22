<?php

namespace App\Models;

use App\Helpers\S3Storage;
use App\Helpers\TextToSpeech;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class AudioItemPart extends Model
{
    protected $table = 'audio_item_parts';
    protected $fillable = ['request_item_id', 'item_index', 'voice', 'audio_file'];

    const STATUS_DEFAULT    = 'Created';
    const STATUS_PENDING    = 'Pending';
    const STATUS_COMPLETE   = 'Complete';
    const STATUS_FAILED     = 'Failed';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requestItem() {
        return $this->belongsTo(RequestItem::class);
    }

    /**
     * Get the TextItemPart model associated with this AudioItemPart.
     *
     * @return array|null
     */
    public function getTextItemPart() {
        if($this->requestItem()->exists()) {
            return $this->requestItem->textItemParts()->where('item_index', $this->item_index)->first();
        }

        return null;
    }

    /**
     * Process this AudioItemPart. Run by \App\Jobs\GenerateAudioItemPartJob
     *
     * @return bool
     */
    public function process() {
        $this->updateLogAndStatus('Process AudioItemPart #'.$this->item_index.' initiated...');

        if($this->status === static::STATUS_PENDING) {
            $this->updateLogAndStatus(
                "Count not process AudioItemPart: status is '".$this->status."'",
                null,
                'warning'
            );
            return false;
        }

        $this->updateLogAndStatus(
            'Started AudioItemPart #'.$this->item_index.' generation ...',
            static::STATUS_PENDING
        );

        /** @var TextItemPart $textItemPart */
        $textItemPart = $this->getTextItemPart();

        if(!$textItemPart) {
            $this->updateLogAndStatus(
                "Could not retrieve TextItemPart for AudioItemPart #".$this->item_index,
                static::STATUS_FAILED
            );
            return false;
        }

        // Attempt the Polly request
        try {
            $tts = new TextToSpeech();
            $audioFileKey = $this->getAudioCacheFilePath();

            $ttsRequestData = $tts->sendRequest(
                $textItemPart->item_content,
                $this->voice,
                ['OutputFormat' => $this->requestItem->output_format]
            );

            $this->updateLogAndStatus('TextToSpeech request sent.');
            $this->updateLogAndStatus('storing audio file #'.$this->item_index.' ...');

            $stored = Storage::disk('local')->put($audioFileKey, $ttsRequestData['AudioStream']);

            $this->updateLogAndStatus(
                'Audio file #'.$this->item_index.' storage '.($stored ? 'success.' : 'failure!'),
                ($stored ? static::STATUS_COMPLETE : static::STATUS_FAILED),
                ($stored ? 'info' : 'error')
            );

            return !!$stored;

        } catch(\Throwable $e) {
            $this->updateLogAndStatus(
                $e->getMessage(),
                static::STATUS_FAILED,
                'error'
            );
            return false;
        }
    }

    /**
     * Get a cache audio file key for this model.
     *
     * @return string
     */
    public function getAudioCacheFilePath() {
        if( array_key_exists('audio_file', $this->attributes) || !$this->attributes['audio_file']) {
            $filename = intval($this->item_index).'.'.$this->requestItem->output_format;

            $this->attributes['audio_file'] = 'audio/cache/'.$this->requestItem->unique_id.'/'.$filename;
        }

        return $this->attributes['audio_file'];
    }

    /**
     * Update the log and/or set this AudioItemPart's status.
     *
     * @param string $message
     * @param null|string $setStatusTo
     * @param null|string $logType
     */
    private function updateLogAndStatus($message, $setStatusTo = null, $logType = null) {
        // Validate log type or set to default
        if($logType === null || !RequestItemLog::isValidType($logType)) { $logType = RequestItemLog::getDefaultType(); }

        // Validate and set status
        switch($setStatusTo) {
            case static::STATUS_DEFAULT:
            case static::STATUS_PENDING:
            case static::STATUS_COMPLETE:
            case static::STATUS_FAILED:
                $this->status = $setStatusTo;
                break;
            case null:
                // nothing to do
                break;
            default:
                // Status type is not valid
                RequestItemLog::warning(
                    $this->requestItem,
                    "Can't set AudioItemPart #".$this->item_index." status to '$setStatusTo'"
                );
        }

        $this->save();

        // Log message
        RequestItemLog::$logType($this, $message);
    }

}
