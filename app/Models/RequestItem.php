<?php

namespace App\Models;

use App\Helpers\S3Storage;
use App\Helpers\TextToSpeech;

use App\Jobs\FinalizeAudioItemJob;
use App\Jobs\GenerateAudioItemPartJob;
use App\Jobs\ProcessRequestItemJob;
use App\Models\TextItemPart;
use App\Models\AudioItem;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class RequestItem extends Model
{
    protected $table = 'request_items';
    protected $fillable = [
        'unique_id', 'user_id', 'status', 'output_name',
        'text_file', 'voices', 'output_format' /*, 'log_data' */
    ];

    const STATUS_DEFAULT    = 'Created';
    const STATUS_PENDING    = 'Pending';
    const STATUS_COMPLETE   = 'Processed';
    const STATUS_FAILED     = 'Failed';

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot() {
        parent::boot();

        static::creating(function(RequestItem $requestItem) {
            $requestItem->generateUniqueID();
        });

        static::saving(function(RequestItem $requestItem) {
            $requestItem->generateTextFilePath();
            //$ttsItem->generateAudioFilePath();
        });

        static::deleting(function(RequestItem $requestItem) {
            // todo: 1. should deleting be allowed?
            // todo: 2. should associated models be deleted too?
            $s3 = new S3Storage();

            $textFilePath   = (
            array_key_exists('text_file', $requestItem->attributes) ?
                $requestItem->attributes['text_file'] : null
            );

            if($textFilePath) { $s3->delete($textFilePath); }

            //$audioFilePath  = (
            //array_key_exists('audio_file', $ttsItem->attributes) ?
            //    $ttsItem->attributes['audio_file'] : null
            //);
            //if($audioFilePath) { $s3->delete($audioFilePath); }
        });
    }

    /**
     * Create a new TextToSpeech RequestItem and dispatch associated job.
     *
     * @param string $text
     * @param array $voiceKeys
     * @param null|string $name
     * @param string $outputFormat
     * @param User|null $user
     * @return array
     */
    public static function createItem($text, $voiceKeys, $name = null, $outputFormat = TextToSpeech::OUTPUT_FORMAT_DEFAULT, User $user = null) {
        $output = [
            'success'   => false,
            'item'      => null,
            'messages'  => [],
        ];

        $tts = new TextToSpeech();
        $voiceNames = [];

        foreach($voiceKeys as $voiceKey) {
            $voiceName = $tts->getVoiceNameByKey( intval($voiceKey) );

            if(!$voiceName) {
                $output['messages'][] = "Voice '$voiceKey' is invalid.";
            } else {
                $voiceNames[] = $voiceName;
            }
        }

        if(sizeof($voiceNames) > 0) {
            $text = TextToSpeech::cleanString($text);

            $requestItem = new static([
                'output_name'   => $name,
                'user_id'       => ($user ? $user->getKey() : null),
                'status'        => static::STATUS_DEFAULT,
                'voices'        => $voiceNames,
                'output_format' => $outputFormat,
            ]);

            $requestItem->setItemText($text);
            $output['success'] = $requestItem->save();

            $output['item'] = $requestItem->toArray();

            dispatch( new ProcessRequestItemJob($requestItem) );
        }

        return $output;
    }

    /**
     * Process this RequestItem. Run by \App\Jobs\ProcessRequestItemJob
     *
     * @return bool
     */
    public function process() {
        // todo: check that the items created below don't already exist
        // i.e. make sure this hasn't already happened.
        // also, if it's restarted, maybe skip that part of the text and continue?

        $this->updateLogAndStatus(
            'Process RequestItem initiated...',
            static::STATUS_PENDING
        );

        // get the contents of the text file
        $text = $this->getItemText();

        // Fail if text string is empty
        if( !(strlen($text) > 0) ) {
            $this->updateLogAndStatus(
                'Item text is empty',
                static::STATUS_FAILED,
                'error'
            );
            return false;
        }

        // Split the text into single request size parts
        $textParts = TextToSpeech::getTextRequestParts($text);

        // Fail if an empty array was retrieved
        if( !(sizeof($textParts) > 0) ) {
            $this->updateLogAndStatus(
                'Item text could not be split.',
                'error',
                static::STATUS_FAILED
            );
            return false;
        }

        $textPartIndex = 0;

        // Create TextItemPart model for each textPart
        foreach($textParts as $textPartString) {
            $textItemPart = new TextItemPart([
                'request_item_id'   => $this->id,
                'item_index'        => $textPartIndex,
                'item_content'      => $textPartString,
            ]);

            $textPartSaveSuccess = $textItemPart->save();

            if(!$textPartSaveSuccess) {
                // todo: log error
            }

            // Create the associated AudioItemParts for each requested voice.
            foreach($this->voices as $voice) {
                $audioItemPart = new AudioItemPart([
                    'request_item_id'   => $this->id,
                    'item_index'        => $textItemPart->item_index,
                    'voice'             => $voice,
                ]);

                $audioPartSaveSuccess = $audioItemPart->save();

                if(!$audioPartSaveSuccess) {
                    // todo: log error
                }

                dispatch( new GenerateAudioItemPartJob($audioItemPart) );
            }

            $textPartIndex++;
        }

        // Create and dispatch the AudioItem for each requested voice.
        foreach($this->voices as $voice) {
            $audioItem = new AudioItem([
                'request_item_id'   => $this->id,
                'voice'             => $voice,
            ]);

            $audioItemSaveSuccess = $audioItem->save();

            if(!$audioItemSaveSuccess) {
                // todo: log error
            }

            dispatch( new FinalizeAudioItemJob( $audioItem ))->delay($textPartIndex * 5);
        }

        return true;
    }

    // todo addVoice() { /* add a new AudioItem, AudioItemParts, and associate with TextItemParts */ }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function textItemParts() {
        return $this->hasMany(TextItemPart::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function audioItemParts() {
        return $this->hasMany(AudioItemPart::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function audioItem() {
        return $this->hasMany(AudioItem::class);
    }

    /**
     *
     *
     * @param string $value
     * @return string
     */
    public function setUniqueIdAttribute($value) {
        $this->generateUniqueID();
        return $this->attributes['unique_id'];
    }

    /**
     *
     *
     * @return string
     */
    public function getUniqueIdAttribute() {
        $this->generateUniqueID();
        return $this->attributes['unique_id'];
    }

    /**
     * Set the name attribute for this model.
     *
     * @param string|null $name
     */
    public function setOutputNameAttribute(string $name = null) {
        if($name) {
            $this->attributes['output_name'] = $name;
        } else {
            $this->attributes['output_name'] = $this->unique_id;
        }
    }

    /**
     * Get the name attribute for this model (and set to unique_id if it isn't already set).
     *
     * @return string
     */
    public function getOutputNameAttribute() {
        $this->generateUniqueID();

        if( !array_key_exists('output_name', $this->attributes) || !$this->attributes['output_name'] ) {
            $this->generateUniqueID();
            $this->attributes['output_name'] = $this->attributes['unique_id'];
        }

        return $this->attributes['output_name'];
    }

    /**
     * Set the output_format attribute for this model.
     *
     * @param string|null $outputFormat
     */
    public function setOutputFormatAttribute(string $outputFormat = null) {
        $format = null;

        switch ( strtolower($outputFormat) ) {
            case TextToSpeech::OUTPUT_FORMAT_MP3:
                $format = 'mp3';
                break;
            case TextToSpeech::OUTPUT_FORMAT_OGG:
            case 'ogg':
                $format = 'ogg';
                break;
            case TextToSpeech::OUTPUT_FORMAT_PCM:
                $format = 'pcm';
                break;
            default:
                $format = TextToSpeech::OUTPUT_FORMAT_DEFAULT;
                break;
        }

        $this->attributes['output_format'] = $format;
    }

    /**
     *
     *
     * @return string
     */
    public function getTextFileAttribute() {
        $this->generateTextFilePath();
        return $this->attributes['text_file'];
    }

    /**
     *
     *
     * @param string $filepath
     */
    public function setTextFileAttribute($filepath) {
        // todo?
        $this->attributes['text_file'] = $filepath;
    }

    /**
     * Create or update the contents of this TTSItem's text_file
     *
     * @param $text
     */
    public function setItemText($text) {
        $s3 = new S3Storage();
        $s3->put($this->text_file, $text);
    }

    /**
     * Get the contents of this TTSItem's text_file
     *
     * @return string|null
     */
    public function getItemText() {
        $s3 = new S3Storage();
        return $s3->getBody($this->text_file);
    }

    /**
     * Generate the text file path for this model.
     *
     * @param bool $force
     */
    private function generateTextFilePath($force = false) {
        if( $force ||
            !array_key_exists('text_file', $this->attributes) ||
            !$this->attributes['text_file']
        ) {
            $path = 'text/'.$this->unique_id;

            $this->attributes['text_file'] = $path.'.txt';
        }
    }

    /**
     * Generates a 16 char unique identifier string and sets the value of 'unique_id' attribute.
     * NOTE: This has a slim possibility of generating a duplicate unique ID. The save function
     * would throw an error if that happens. I'm choosing to ignore this issue because the chances
     * of this happening are very small.
     */
    private function generateUniqueID() {
        $uniqueIDLength = 16;

        if( !array_key_exists('unique_id', $this->attributes) || !$this->attributes['unique_id'] ) {
            try {
                // Try to generate a unique id using the random_bytes function
                $this->attributes['unique_id'] = bin2hex(random_bytes($uniqueIDLength));
            } catch(\Exception $e) {
                // If that throws an error, generate a unique ID with a different method
                $uniqueID = '';
                $pool = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));

                for($i = 0; $i < $uniqueIDLength; $i++) {
                    $uniqueID .= $pool[mt_rand(0, count($pool) - 1)];
                }

                $this->attributes['unique_id'] = $uniqueID;
            }
        }
    }

    /**
     * Update the log and/or set this RequestItem's status.
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
                RequestItemLog::warning($this, "Can't set status to '$setStatusTo'");
        }

        $this->save();

        // Log message
        RequestItemLog::$logType($this, $message);
    }

}
