<?php

namespace App\Models;

use App\Helpers\S3Storage;
use App\Helpers\TextToSpeech;
use App\Jobs\TTSJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

                $job = ( new TTSJob($ttsItem) )->delay(10);
                dispatch($job);
            }
        }

        return $output;
    }

    /**
     * Regenerate the text to speech audio from the still cached text file.
     */
    public function regenerateAudio() {
        $s3 = new S3Storage();

        $textFilePath   = (
        array_key_exists('text_file', $this->attributes) ?
            $this->attributes['text_file'] : null
        );

        if(!$textFilePath || !$s3->exists($textFilePath)) {
            return false;
        }

        $audioFilePath  = (
        array_key_exists('audio_file', $this->attributes) ?
            $this->attributes['audio_file'] : null
        );

        // todo: Should we not do this?
        if($audioFilePath) { $s3->delete($audioFilePath); }

        $job = ( new TTSJob($this) )->delay(10);
        dispatch($job);

        return true;
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
        } else {
            $this->attributes['name'] = $this->unique_id;
        }
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
            $this->attributes['audio_file'] = 'audio/'.$this->unique_id.'.'.$this->output_format;
        }
    }

    /**
     * Get a cache audio file key for this model.
     *
     * @param int|null $partIndex
     * @return string
     */
    private function getAudioCacheFilePath($partIndex = null) {
        // Make sure the audio output file path exists
        $this->generateAudioFilePath();

        $filename = ($partIndex !== null ? intval($partIndex) : 'combined').'.'.$this->output_format;
        return 'audio/cache/'.$this->unique_id.'/'.$filename;
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
     * Generate the audio file for this item.
     *
     * @return bool
     */
    public function generateAudio() {
        $this->setStatusAndMessage('Generate Audio started.', 'info');

        // if the audio is currently generating, return response
        if($this->status === static::STATUS_PENDING) {
            $this->setStatusAndMessage(
                "Could not generate audio: status is '".$this->status."'",
                'warning'
            );
            return false;
        }

        // Set this item's status to pending
        $this->setStatusAndMessage(null, 'info', static::STATUS_PENDING);

        // get the contents of the text file
        $text = $this->getItemText();

        // Fail if text string is empty
        if( !(strlen($text) > 0) ) {
            $this->setStatusAndMessage(
                'Item text is empty',
                'error',
                static::STATUS_FAILED
            );
            return false;
        }

        // Split the text into single request size parts
        $textParts = TextToSpeech::getTextRequestParts($text);

        // Fail if an empty array was retrieved
        if( !(sizeof($textParts) > 0) ) {
            $this->setStatusAndMessage(
                'Item text could not be split.',
                'error',
                static::STATUS_FAILED
            );
            return false;
        }

        // Process the text parts
        $tts = new TextToSpeech();
        $cacheFiles = [];


        // Generate and cache all the audio file parts.
        foreach($textParts as $key => $requestString) {
            try {
                $this->setStatusAndMessage('Sending Polly request.', 'info');
                $ttsRequestData = $tts->sendRequest($requestString, $this->voice_id);
                $this->setStatusAndMessage('Polly request sent.', 'info');

                $audioFileKey = $this->getAudioCacheFilePath($key);

                $this->setStatusAndMessage('Storing audio file '.$key, 'info');
                $stored = Storage::disk('local')->put($audioFileKey, $ttsRequestData['AudioStream']);
                $this->setStatusAndMessage(
                    'Audio file '.$key.' storage '.($stored ? 'success.' : 'failure!'),
                    ($stored ? 'info' : 'error')
                );

                $cacheFiles[] = $audioFileKey;
            } catch(\Exception $e) {
                // Log error and return if sending request fails.
                $this->setStatusAndMessage(
                    $e->getMessage(),
                    'error',
                    static::STATUS_FAILED
                );

                // Log text that was sent in the request.
                $this->setStatusAndMessage(
                    $requestString,
                    'debug'
                );

                return false;
            }
        }

        $tempOutputPath = $this->getAudioCacheFilePath();
        $tempOutputFullPath = storage_path('app/'.$tempOutputPath);

        // Combine the cached audio files, or rename if there's only 1 file.
        if(sizeof($cacheFiles) === 1) {
            Storage::disk('local')->move( $cacheFiles[0], $tempOutputPath);
        } else {
            // get full filename paths
            $fullPathCacheFiles = [];
            foreach($cacheFiles as $path) {
                $fullPathCacheFiles[] = storage_path('app/'.$path);
            }

            // Combine the files.
            exec('cat '.implode(' ', $fullPathCacheFiles).' > '. $tempOutputFullPath);
        }

        // Return error if temp output file does not exist.
        if( !Storage::disk('local')->exists($tempOutputPath) ) {
            $this->setStatusAndMessage(
                "Could not store output file to '$tempOutputPath'.",
                'error',
                static::STATUS_FAILED
            );
            return false;
        }

        // Delete audio cache files if they still exist
        if(sizeof($cacheFiles) === 1) {
            $this->setStatusAndMessage('Deleting cached audio file parts.', 'info');
            Storage::disk('local')->delete($cacheFiles);
        }

        // Move cache file to s3.
        $this->setStatusAndMessage('Moving output file to s3.', 'info');
        $s3 = new S3Storage();
        $s3->putFile($tempOutputFullPath, $this->audio_file, true);

        // todo: try/catch? make sure file was uploaded

        $this->setStatusAndMessage('Output file moved.', 'info');
        $this->setStatusAndMessage("Deleting local output cache directory (".dirname($tempOutputPath).").", 'info');
        Storage::disk('local')->deleteDirectory( dirname($tempOutputPath) );

        // hold off on combining audio until specific api request made?

        $this->setStatusAndMessage(
            'Done',
            'info',
            static::STATUS_PROCESSED,
            false
        );

        return true;
    }

    /**
     * Stream the item's audio file.
     * todo: this doesn't work perfectly, fix it.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function getAudioStream() {
        $s3 = new S3Storage();
        $s3->registerStreamWrapper();

        try {
            $object = $s3->get($this->audio_file);

            $length         = $object['ContentLength'];
            $acceptRanges   = $object['AcceptRanges'];
            $start          = 0;
            $end            = $length - 1;

            if (isset($_SERVER['HTTP_RANGE'])) {
                $range = $_SERVER['HTTP_RANGE'];
                list($param, $range) = explode('=', $range);

                if(strtolower(trim($param)) === $acceptRanges) {
                    $range = explode(',', $range);      // get the first range if there is more than 1
                    $range = explode('-', $range[0]);   // separate start and end of range

                    if(count($range) === 2) {                    // continue if we have 2 parameters

                        // Make sure the start range is valid
                        $newStart   = intval( $range[0] ) >= 0 ? intval( $range[0] ) : $start;
                        $newEnd     = intval( $range[1] ) >= 1 ? intval( $range[1] ) : $end;
                        $newLength  = $end + 1;

                        if($newLength !== $length) {
                            $start  = $newStart;
                            $end    = $newEnd;
                            $length = $newLength;

                            header('HTTP/1.1 206 Partial Content');
                        }
                    }
                }
            }

            return response($object['Body'], 200, [
                'Content-Type'          => $object['ContentType'],
                'Content-Length'        => $object['ContentLength'],
                'Content-Disposition'   => "inline; filename='".$this->name.'_'.$this->voice_id.'.'.strtolower($this->output_format)."'",
                'Accept-Ranges: 0-'.$object['ContentLength'],
                'Content-Range: bytes '.$start.'-'.$end.'/'.$length,
            ]);

            //return response($object['body'], '200')->header('Content-Type', $result['ContentType']);
        } catch(\Exception $e) {
            return response()->json([
                'success'   => false,
                'messages'  => [
                    $e->getMessage()
                ],
            ]);
        }
    }

    /**
     * Download the item's audio file.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function downloadAudioFile() {
        $s3 = new S3Storage();

        try {
            $object = $s3->get($this->audio_file);

            return response()->make($object['Body'], 200, [
                'Content-Type'          => $object['ContentType'],
                'Content-Disposition'   => "attachment; filename='".$this->name.'_'.$this->voice_id.'.'.strtolower($this->output_format)."'",
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'success'   => false,
                'messages'  => [
                    $e->getMessage()
                ],
            ]);
        }
    }

    /**
     * Set status of item, add to status_message (if specified), and output message via Log facade (if valid log type).
     *
     * @param $message
     * @param $logType
     * @param null $status
     * @param bool $newLine
     */
    private function setStatusAndMessage($message, $logType, $status = null, $newLine = true) {
        $this->status_message .= $message;

        // Validate status
        switch($status) {
            case static::STATUS_DEFAULT:
            case static::STATUS_PENDING:
            case static::STATUS_PROCESSED:
            case static::STATUS_FAILED:
                $this->status = $status;
                break;
            case null:
                break; // nothing to do
            default:
                // Status type is not valid
                $this->status_message .= "\nCan't set status to '$status'";
        }

        // Validate logType
        if($message) {
            switch($logType) {
                case 'emergency':   Log::emergency($message, ['id' => $this->getKey()]); break;
                case 'alert':       Log::alert($message,     ['id' => $this->getKey()]); break;
                case 'critical':    Log::critical($message,  ['id' => $this->getKey()]); break;
                case 'error':       Log::error($message,     ['id' => $this->getKey()]); break;
                case 'warning':     Log::warning($message,   ['id' => $this->getKey()]); break;
                case 'notice':      Log::notice($message,    ['id' => $this->getKey()]); break;
                case 'info':        Log::info($message,      ['id' => $this->getKey()]); break;
                case 'debug':       Log::debug($message,     ['id' => $this->getKey()]); break;
                default:
                    $this->status_message .= "\nInvalid log type: $logType'";
            }

            if($newLine) { $this->status_message .= "\n"; }
        }

        $this->save();
    }

}
