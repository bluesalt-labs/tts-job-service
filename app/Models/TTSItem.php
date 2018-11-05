<?php

namespace App\Models;

use App\Helpers\S3Storage;
use App\Helpers\TextToSpeech;
use App\Jobs\TTSJob;
use Illuminate\Database\Eloquent\Model;

class TTSItem extends Model
{
    protected $table        = 'tts_items';
    protected $fillable     = ['unique_id', 'name', 'user_id', 'status', 'text_file', 'audio_file', 'voice_id', 'output_format'];

    const STATUS_DEFAULT    = 'Created';
    const STATUS_PENDING    = 'Pending';
    const STATUS_PROCESSED  = 'Processed';
    const STATUS_FAILED     = 'Failed';

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot() {
        parent::boot();

        static::creating(function(TTSItem $ttsItem) {
            $ttsItem->generateUniqueID();
        });

        static::saving(function(TTSItem $ttsItem) {
            $ttsItem->generateTextFilePath();
            $ttsItem->generateAudioFilePath();
        });

        static::deleting(function(TTSItem $ttsItem) {
            $s3 = new S3Storage();

            $textFilePath   = (
                array_key_exists('text_file', $ttsItem->attributes) ?
                    $ttsItem->attributes['text_file'] : null
            );

            if($textFilePath) { $s3->delete($textFilePath); }

            $audioFilePath  = (
            array_key_exists('audio_file', $ttsItem->attributes) ?
                $ttsItem->attributes['audio_file'] : null
            );

            if($audioFilePath) { $s3->delete($audioFilePath); }
        });
    }

    /**
     * Create new model(s) and dispatch associated TTSJob(s).
     *
     * @param string $text
     * @param array $voices
     * @param string $outputFormat
     * @param string|null $name
     * @param User|null $user
     * @return array
     */
    public static function createItems($text, $voices, $outputFormat = 'mp3', $name = null, User $user = null) {
        $output = [
            'items'     => [],
            'messages'  => [],
        ];

        $tts = new TextToSpeech();
        $voiceIDs = [];

        foreach($voices as $voiceKey) {
            $voiceID = $tts->getVoiceNameByKey( intval($voiceKey) );

            if(!$voiceID) {
                $output['messages'][] = "Voice '$voiceKey' is invalid.";
            } else {
                $voiceIDs[] = $voiceID;
            }
        }

        if(sizeof($voiceIDs) > 0) {
            $text = TextToSpeech::cleanString($text);

            foreach($voiceIDs as $voice) {
                $ttsItem = new TTSItem([
                    'name'          => $name,
                    'user_id'       => ($user ? $user->getKey() : null),
                    'status'        => TTSItem::STATUS_DEFAULT,
                    'voice_id'      => $voice,
                    'output_format' => $outputFormat,
                ]);

                $ttsItem->setItemText($text);
                $ttsItem->save();

                $output['items'][] = $ttsItem->toArray();
                dispatch( new TTSJob($ttsItem) );
            }
        }

        return $output;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
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
     * Set the name attribute for this model.
     *
     * @param string|null $name
     */
    public function setNameAttribute(string $name = null) {
        if($name) {
            $this->attributes['name'] = $name;
            // todo: append voice to name
        }

        $this->attributes['name'] = $this->unique_id;
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
     *
     *
     * @return string
     */
    public function getUniqueIdAttribute() {
        $this->generateUniqueID();
        return $this->attributes['unique_id'];
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
     * Generate the audio file for this item.
     *
     * @return bool
     */
    public function generateAudio() {
        // if the audio is already generated, return response
        $this->status = static::STATUS_PENDING;
        // get the contents of the text file


        // set the status to static::STATUS_PROCESSED if audio generation was successful.
        // return true;
        // OR
        // set the status to static::STATUS_FAILED if audio generation was not successful.
        $this->status = static::STATUS_FAILED;

        return $this->save();
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
     * Generate the audio file path for this model.
     *
     * @param bool $force
     */
    private function generateAudioFilePath($force = false) {
        if( $force ||
            !array_key_exists('audio_file', $this->attributes) ||
            !$this->attributes['audio_file']
        ) {
            $path = 'audio/'.$this->unique_id;

            $this->attributes['audio_file'] = $path.'.'.$this->output_format;
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

}
