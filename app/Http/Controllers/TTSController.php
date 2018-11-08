<?php

namespace App\Http\Controllers;

use App\Helpers\TextToSpeech;
use Illuminate\Http\Request;

class TTSController extends Controller
{
    /**
     * Get available voices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVoices() {
        return response()->json(TextToSpeech::getAvailableVoices());
    }

    /**
     * Get configured SSML replacements.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSSMLReplacements() {
        return response()->json(TextToSpeech::getSSMLReplacements());
    }

    /**
     * Get available audio output formats.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOutputFormats() {
        return response()->json(TextToSpeech::getOutputFormats());
    }

}
