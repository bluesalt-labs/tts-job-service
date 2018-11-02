<?php

namespace App\Models;

use App\Helpers\S3Storage;
use App\Helpers\TextToSpeech;
use Illuminate\Database\Eloquent\Model;

class TTSItem extends Model
{
    protected $table        = 'tts_items';
    protected $fillable     = ['name', 'status', 'text_file', 'audio_file', 'voice_id', 'output_format'];

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
            $ttsItem->generateTextFilePath();
            $ttsItem->generateAudioFilePath();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the name attribute for this model.
     *
     * @param string|null $name
     */
    public function setNameAttribute(string $name = null) {
        if($name) {
            $this->attributes['name'] = $name;
        }

        $this->attributes['name'] = static::generateRandomName();
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
                $format = TextToSpeech::TEXT_TYPE_DEFAULT;
                break;
        }

        $this->attributes['output_format'] = $format;
    }

    public function getTextFileAttribute() {
        if(!$this->attributes['text_file']) {
            $this->generateTextFilePath();
        }
    }

    /**
     * Create or update the contents of this TTSItem's text_file
     *
     * @param $text
     */
    public function setItemText($text) {
        $this->generateTextFilePath();

        // todo: create or update file and set the file's text
        // S3Storage::create();
        $this->text_file = '';
    }

    public function setItemTextFileAttribute($filepath) {
        // todo?
        $this->attributes['text_file'] = $filepath;
    }

    /**
     * Get the contents of this TTSItem's text_file
     *
     * @return string
     */
    public function getItemText() {
        // todo
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
            $this->attributes['text_file'] = ''; // todo
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
            $this->attributes['audio_file'] = ''; // todo
        }
    }

    private static function generateRandomName() {

    }

}
