<?php

namespace App\Helpers;

use Aws\Polly\PollyClient;
use http\Exception\InvalidArgumentException;

/**
 * Class TextToSpeech
 *
 * Helper Class for converting text to spoken word audio through AWS Polly TextToSpeech Engine.
 *
 * @package App\Helpers
 */
class TextToSpeech
{
    const POLLY_VERSION         = 'latest';
    const MAX_REQUEST_CHARS     = 3000;
    const TEXT_TYPE_DEFAULT     = 'ssml';
    const TEXT_TYPE_SSML        = 'ssml';

    const OUTPUT_FORMAT_DEFAULT = 'mp3';
    const OUTPUT_FORMAT_MP3     = 'mp3';
    const OUTPUT_FORMAT_OGG     = 'ogg_vorbis';
    const OUTPUT_FORMAT_PCM     = 'pcm';

    protected $polly;
    protected $availableVoices;
    protected $ssmlReplacements;

    /**
     * TextToSpeech constructor.
     */
    public function __construct() {
        $this->polly = static::getPollyClient();
        $this->availableVoices = static::getAvailableVoices();
    }

    /**
     * @param string $text
     * @param int $voiceKey
     * @param array $options
     * @return array
     */
    public function sendRequest($text, $voiceKey, $options = []) {
        $options = static::getRequestOptions($text, $voiceKey, $options);

        // If the options are invalid, end execution and return an error
        // todo: figure out how I want to do this.
        if(gettype($options) !== 'array') {
            throw new InvalidArgumentException("Required parameters are missing or invalid");
        }

        $options['Text'] = $this->textToSSML( $options['Text'] );

        return $this->polly->synthesizeSpeech($options)->toArray(); // todo don't return as array?
    }

    /**
     * Takes any request options and returns only the options needed.
     * Sets default values for optional missing parameters.
     * Returns null if required attributes are missing.
     *
     * @param string $text
     * @param string $voiceKey
     * @param array $options
     * @return array|null
     */
    public static function getRequestOptions($text, $voiceKey, $options) {
        // Check for required parameters
        if( !strlen($text) >  0 ||
            !strlen($voiceKey) > 0 // todo: actually validate voice key?
        ) { return null; }

        $outputFormat = (array_key_exists('OutputFormat', $options) ? $options['OutputFormat'] : static::OUTPUT_FORMAT_DEFAULT);
        $textType     = (array_key_exists('TextType', $options) ? $options['TextType'] : static::TEXT_TYPE_DEFAULT);

        $output = [
            'Text'          => $text,
            'OutputFormat'  => $outputFormat,
            'TextType'      => $textType,
            'VoiceId'       => $voiceKey,
        ];

        return $output;
    }

    /**
     * @return PollyClient
     */
    private static function getPollyClient() {
        $credentials = [
            'version'   => static::POLLY_VERSION,
            'region'    => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'credentials' => [
                'key'         => env('AWS_ACCESS_KEY_ID'),
                'secret'      => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ];

        return new PollyClient($credentials);
    }

    /**
     * Get all audio output formats
     *
     * @return array
     */
    public static function getOutputFormats() {
        return [
            // polly format => file extension
            static::OUTPUT_FORMAT_DEFAULT   => 'mp3',
            static::OUTPUT_FORMAT_MP3       => 'mp3',
            static::OUTPUT_FORMAT_OGG       => 'ogg',
            static::OUTPUT_FORMAT_PCM       => 'pcm',
        ];
    }

    /**
     * Strips input string of HTML, extra whitespace, etc.
     *
     * @param $text
     * @return string
     */
    public static function cleanString($text) {
        return strip_tags( preg_replace(["/:|<\/(li|p)>/", "/&#?[a-z0-9]+;/i", "/\s/"], [",", ' ', ' '], trim($text)) );
    }

    /**
     * Splits a string of text into MAX_REQUEST_CHARS sized chunks.
     *
     * @param string $cleanText
     * @return array
     */
    public static function getTextRequestParts($cleanText) {
        $output = [];
        $textBuffer = '';

        // Convert all white space to a single space character
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);

        if(strlen($cleanText) < static::MAX_REQUEST_CHARS) {
            $output = [$cleanText];
            return $output;
        }

        // Split input by sentences
        $textParts = explode('. ', $cleanText);

        for($i = count($textParts); $i >= 0; $i--) {
            // Get the first sentence from the array
            $part = array_shift($textParts).'. ';

            // If the text buffer isn't full or almost full, add the sentence to the text buffer
            if( (strlen($textBuffer) + strlen($part)) < static::MAX_REQUEST_CHARS) {
                $textBuffer .= $part;
            } else {
                // If the text buffer if full, add it to the output and clear it.
                $output[]   = $textBuffer;
                $textBuffer = $part;
            }
        }

        return $output;
    }

    /**
     * Adds SSML replacements to clean request text string.
     *
     * @param string $text
     * @return string
     */
    private function textToSSML($text) {
        $cleanString = $text;

        foreach(static::getSSMLReplacements() as $acronym => $replacement) {
            $cleanString = str_replace($acronym, $replacement, $cleanString);
        }

        return '<speak><amazon:auto-breaths duration="short">'.$cleanString.'</amazon:auto-breaths></speak>';
    }

    /**
     * Determine if a string is too long for a single request.
     *
     * @param string $text
     * @return bool
     */
    public static function isTextTooLong($text) {
        return !!(strlen($text) > static::MAX_REQUEST_CHARS);
    }

    /**
     * Gets voice name by specified voiceKey. Returns null if voiceKey is invalid.
     *
     * @param int $voiceKey
     * @return string|null
     */
    public function getVoiceNameByKey($voiceKey) {
        if( array_key_exists(intval($voiceKey), $this->availableVoices) ) {
            return $this->availableVoices[$voiceKey]['name'];
        }

        return null;
    }

    /**
     * Returns Voice Data for specified voice by voiceKey
     *
     * @param int $voiceKey
     * @return array
     */
    public static function getVoiceDataByKey($voiceKey) {
        $voices = static::getAvailableVoices();

        if( array_key_exists($voiceKey, $voices) ) {
            return $voices[$voiceKey];
        }

        return null;
    }

    /**
     * Get array of SSML Replacements.
     *
     * @return array
     */
    public static function getSSMLReplacements() {
        // todo: should these be stored in a database table?
        // todo: check out $this->polly->getLexicon()

        return [
            "("             => '<s>(',
            ")"             => ')</s>',
            ")</s>."        => ')</s>',
            ")</s>;"        => ')</s>',
            ")</s>:"        => ')</s>',
            "EPPP"          => 'E Triple P',
            "CPLEE"         => 'See Plea',
            "MCMI-III"      => '<say-as interpret-as="spell-out">MCMI</say-as> 3',
            "MMPI-2"        => '<say-as interpret-as="spell-out">MMPI</say-as> 2',
            "CBCL"          => '<say-as interpret-as="spell-out">CBCL</say-as>',
            "(ODD)"         => '(<say-as interpret-as="spell-out">ODD</say-as>)',
            " ODD "         => ' <say-as interpret-as="spell-out">ODD</say-as> ',
            "BDI-II"        => '<say-as interpret-as="spell-out">BDI</say-as> 2',
            "ITPA-III"      => '<say-as interpret-as="spell-out">ITPA</say-as> 3',
            " DSM "         => '<say-as interpret-as="spell-out">DSM</say-as>',
            "DSM-IV-TR"     => '<say-as interpret-as="spell-out">DSM</say-as> 4 <say-as interpret-as="spell-out">TR</say-as>',
            "DSM-IV"        => '<say-as interpret-as="spell-out">DSM</say-as> 4',
            "DSM-V"         => '<say-as interpret-as="spell-out">DSM</say-as> 5',
            "DSM-5"         => '<say-as interpret-as="spell-out">DSM</say-as> 5',
            "APA"           => '<say-as interpret-as="spell-out">APA</say-as>',
            "PTSD"          => '<say-as interpret-as="spell-out">PTSD</say-as>',
        ];
    }

    /**
     * Get data for all the AWS Polly voices available.
     *
     * @return array
     */
    public static function getAvailableVoices() {
        return [
            1   => [
                "preferred" => false,
                "gender"	=> "m",
                "name"	    => "Russell",
                "language"  => "en-AU",
            ],
            2   => [
                "preferred" => false,
                "gender"	=> "f",
                "name"	    => "Nicole",
                "language"  => "en-AU",
            ],
            3   => [
                "preferred" => true,
                "gender"	=> "m",
                "name"	    => "Brian",
                "language"	=> "en-GB",
            ],
            4   => [
                "preferred" => true,
                "gender"	=> "f",
                "name"      => "Amy",
                "language"	=> "en-GB",
            ],
            5   => [
                "preferred" => false,
                "gender"	=> "f",
                "name"	    => "Emma",
                "language"  => "en-GB",
            ],
            6   => [
                "preferred" => false,
                "gender"	=> "f",
                "name"	    => "Aditi",
                "language"  => "en-IN",
            ],
            7   => [
                "preferred" => false,
                "gender"	=> "f",
                "name"	    => "Raveena",
                "language"  => "en-IN",
            ],
            8   => [
                "preferred" => false,
                "gender"	=> "m",
                "name"	    => "Joey",
                "language"  => "en-US",
            ],
            9   => [
                "preferred" => false,
                "gender"	=> "m",
                "name"	    => "Justin",
                "language"  => "en-US",
            ],
            10  => [
                "preferred" => true,
                "gender"	=> "m",
                "name"	    => "Matthew",
                "language"  => "en-US",
            ],
            11  => [
                "preferred" => false,
                "gender"	=> "f",
                "name"	    => "Ivy",
                "language"  => "en-US",
            ],
            12  => [
                "preferred" => true,
                "gender"	=> "f",
                "name"	    => "Joanna",
                "language"  => "en-US",
            ],
            13  => [
                "preferred" => false,
                "gender"	=> "f",
                "name"	    => "Kendra",
                "language"  => "en-US",
            ],
            14  => [
                "preferred" => false,
                "gender"	=> "f",
                "name"	    => "Kimberly",
                "language"  => "en-US",
            ],
            15  => [
                "preferred" => false,
                "gender"	=> "f",
                "name"	    => "Salli",
                "language"  => "en-US",
            ],
            16  => [
                "preferred" => false,
                "gender"	=> "m",
                "name"	    => "Geraint",
                "language"  => "en-GB-WLS",
            ],
        ];
    }
}