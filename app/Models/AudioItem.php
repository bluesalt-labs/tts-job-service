<?php

namespace App\Models;

use App\Helpers\S3Storage;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Model;

class AudioItem extends Model
{
    protected $table        = 'audio_items';
    protected $fillable     = [];

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
     * @return int
     */
    public function countCompletedAudioItemParts() {
        if($this->requestItem()->exists()) {
            return $this->requestItem
                ->audioItemParts()
                ->where('voice', $this->voice)
                ->where('status', '!=', AudioItemPart::STATUS_COMPLETE)
                ->count();
        }

        return 0;
    }

    /**
     * @param bool $onlyComplete
     * @return array
     */
    public function getAudioItemParts($onlyComplete = true) {
        if($this->requestItem()->exists()) {
            $result = $this->requestItem->audioItemParts()->where('voice', $this->voice);

            if($onlyComplete) {
                $result->where('status', '!=', AudioItemPart::STATUS_COMPLETE);
            }

            return $result->get();
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
                    ->where('status', '!=', AudioItemPart::STATUS_COMPLETE)
                    ->count()
                ) > 0
            );
        }

        return false;
    }

    /**
     * Process this AudioItem. Run by \App\Jobs\FinalizeAudioItemJob
     *
     * @return bool
     */
    public function process() {
        $this->updateLogAndStatus('Process AudioItem initiated...');

        if($this->status === static::STATUS_PENDING) {
            $this->updateLogAndStatus(
                "Count not process AudioItem: status is '".$this->status."'",
                null,
                'warning'
            );
            return false;
        }

        if( !$this->haveAudioItemPartsBeenProcessed() ) {
            $this->updateLogAndStatus("Could not process AudioItem: AudioItemParts haven't finished processing.");
            return false;
        }

        $this->updateLogAndStatus('Started AudioItem generation ...', static::STATUS_PENDING);

        // Retrieve paths to audio files and make sure the files exist.
        $this->updateLogAndStatus('Retrieving AudioItemParts ...');

        if( !($this->countCompletedAudioItemParts() > 0) ) {
            $this->updateLogAndStatus(
                'Could not retrieve any AudioItemParts',
                static::STATUS_FAILED,
                'error'
            );
            return false;
        }

        $audioFilePaths = [];

        foreach($this->getAudioItemParts() as $audioItemPart) {
            //$audioItemPart->getAudioCacheFilePath();


            // todo: retrieve audio file paths
            // todo: make sure the files exist
            // todo: create the temp output path

            // todo: run this:
            //exec('cat '.implode(' ', $fullPathCacheFiles).' > '. $tempOutputFullPath);
                // see \App\Models\TTSItem line 406
            // todo: upload output file to S3
            // todo: mark AudioItem as complete if everything was successful
            // todo: should the audioItemParts be deleted?

        }

        return true;
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
                    "Can't set AudioItem status to '$setStatusTo'"
                );
        }

        $this->save();

        // Log message
        RequestItemLog::$logType($this, $message);
    }

}
