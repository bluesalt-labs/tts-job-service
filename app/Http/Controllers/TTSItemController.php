<?php

namespace App\Http\Controllers;

use App\Helpers\TextToSpeech;
use App\Jobs\TTSJob;
use App\Models\TTSItem;
use Illuminate\Http\Request;

class TTSItemController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Index page. Shows the application name.
     *
     * @return string
     */


    /**
     * Submit a new job request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitJobRequest(Request $request) {
        $output = [
            'success'   => false,
            'items'     => [],
            'messages'  => [],
        ];

        $text       = strval($request->get('text'));
        $voices     = $request->get('voices');
        $name       = $request->get('name');
        $outFormat  = $request->get('output_format');

        if(!$text) {
            $output['messages'][] = "Required 'text' attribute is invalid or not present.";
        }

        if(!$voices) {
            $output['messages'][] = "Required 'voices' attribute is invalid or not present.";
        }

        if(gettype($voices) !== 'array') {
            // todo: good enough?
            $voices = [$voices];
        }

        if($text && $voices) {
            $tts  = new TextToSpeech();
            $text = TextToSpeech::cleanString($text);

            foreach($voices as $voice) {
                // todo: should this be in the model? :
                $voiceID = $tts->getVoiceNameByKey( intval($voice) );

                if(!$voiceID) {
                    $output['messages'][] = "Voice '$voice' is invalid.";
                    break;
                }

                $ttsItem = new TTSItem([
                    'name'          => $name,
                    'status'        => TTSItem::STATUS_DEFAULT,
                    'voice_id'      => $voiceID,
                    'output_format' => $outFormat,
                ]);

                $ttsItem->setItemText($text);
                $ttsItem->save();

                $output['items'][] = $ttsItem->toArray(); // todo: get visible attributes.
                $this->dispatch( new TTSJob($ttsItem) );
            }

            $output['success'] = true;
        }

        return response()->json($output);
    }

    /**
     * Get the status of a TTSItem by itemID
     *
     * @param $itemID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItemStatus($itemID) {
        $output = [
            'item_id'       => null,
            'job_id'        => null,
            'item_status'   => null,
            'job_status'    => null,
            'messages'      => [],
        ];

        $item = TTSItem::find($itemID);

        if(!$item) {
            $output['messages'][] = "Item ID: '$itemID' not found.";
            return response()->json($output);
        }

        $output['item_id']      = $item->id;
        $output['item_status']  = $item->status;

        return response()->json($output);
    }

}
