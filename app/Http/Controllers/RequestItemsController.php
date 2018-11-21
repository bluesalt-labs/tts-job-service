<?php

namespace App\Http\Controllers;

use App\Models\RequestItem;
use Illuminate\Http\Request;

class RequestItemsController extends Controller
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


    public function listRequestItems(Request $request) {
        $output = [
            'success'   => false,
            'items'     => [],
            'messages'  => [],
        ];

        /**
         * @var RequestItem $item
         */
        $items = RequestItem::all();

        if(!$items) {
            $output['messages'][] = "No RequestItems items found.";
            return response()->json($output);
        }

        if(count($items) > 0) {
            $output['success']  = true;
        }

        foreach($items as $item) {
            $output['items'][] = [
                'item_id'       => $item->id,
                'unique_id'     => $item->unique_id,
                'name'          => $item->name,
                'status'        => $item->status,
            ];
        }

        return response()->json($output);
    }

    /**
     * Submit a new job request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRequestItem(Request $request) {
        $output = [
            'success'   => false,
            'items'     => [],
            'messages'  => [],
        ];

        $text       = strval($request->get('text'));
        $voices     = $request->get('voices');
        $name       = $request->get('name');
        $outFormat  = $request->get('output_format');
        $user       = $request->user;

        if(!$text) {
            $output['messages'][] = "Required 'text' attribute is invalid or not present.";
        }

        if(!$voices) {
            $output['messages'][] = "Required 'voices' attribute is invalid or not present.";
        }

        if(gettype($voices) !== 'array') {
            $voices = [$voices];
        }

        if($text && $voices) {
            $createItemsResponse = TTSItem::createItems($text, $voices, $outFormat, $name, $user);

            if(sizeof($createItemsResponse['items'] > 0)) {
                $output['success']  = true;
                $output['items']    = $createItemsResponse['items'];
            }

            foreach($createItemsResponse['messages'] as $message) {
                $output['messages'][] = $message;
            }
        }

        return response()->json($output);
    }

    public function regenerateItem($itemID) {
        $output = [
            'success'   => false,
            'messages'  => [],
        ];

        /**
         * @var TTSItem $item
         */
        $item = TTSItem::where('id', $itemID)->orWhere('unique_id', $itemID)->first();

        if(!$item) {
            $output['messages'][] = "Item ID: '$itemID' not found.";
            return response()->json($output);
        }

        $output['success'] = $item->regenerateAudio();

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
            'unique_id'     => null,
            'name'          => null,
            'status'        => null,
            'audio_url'     => null,
            'messages'      => [],
        ];

        /**
         * @var TTSItem $item
         */
        $item = TTSItem::where('id', $itemID)->orWhere('unique_id', $itemID)->first();

        if(!$item) {
            $output['messages'][] = "Item ID: '$itemID' not found.";
            return response()->json($output);
        }

        $output['item_id']      = $item->id;
        $output['unique_id']    = $item->unique_id;
        $output['name']         = $item->name;
        $output['status']       = $item->status;
        $output['audio_url']    = $item->audio_file;
        $output['messages']     = array_merge($output['messages'], explode("\n", $item->status_message));

        return response()->json($output);
    }

    /**
     * Get the text content of a TTSItem by itemID
     *
     * @param $itemID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItemText($itemID) {
        $output = [
            'item_id'   => null,
            'unique_id' => null,
            'text'      => null,
            'messages'  => [],
        ];

        /**
         * @var TTSItem $item
         */
        $item = TTSItem::where('id', $itemID)->orWhere('unique_id', $itemID)->first();

        if(!$item) {
            $output['messages'][] = "Item ID: '$itemID' not found.";
            return response()->json($output);
        }

        $output['item_id']      = $item->id;
        $output['unique_id']    = $item->unique_id;
        $output['text']         = $item->getItemText();
        $output['messages']     = array_merge($output['messages'], explode("\n", $item->status_message));

        return response()->json($output);
    }

    /**
     * Get the audio stream of a TTSItem
     *
     * @param $itemID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function getItemAudio($itemID) {
        $output = [
            'success'   => false,
            'messages'  => [],
        ];

        /**
         * @var TTSItem $item
         */
        $item = TTSItem::where('id', $itemID)->orWhere('unique_id', $itemID)->first();

        if(!$item) {
            $output['messages'][] = "Item ID: '$itemID' not found.";
            return response()->json($output);
        }

        return $item->getAudioStream();
    }

    /**
     * Download the audio file of a TTSItem
     *
     * @param $itemID
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function downloadItemAudio($itemID) {
        $output = [
            'success'   => false,
            'messages'  => [],
        ];

        /**
         * @var TTSItem $item
         */
        $item = TTSItem::where('id', $itemID)->orWhere('unique_id', $itemID)->first();

        if(!$item) {
            $output['messages'][] = "Item ID: '$itemID' not found.";
            return response()->json($output);
        }

        return $item->downloadAudioFile();
    }

    public function deleteItem($itemID) {
        $output = [
            'success'   => false,
            'messages'  => [],
        ];

        /**
         * @var TTSItem $item
         */
        $item = TTSItem::where('id', $itemID)->orWhere('unique_id', $itemID)->first();

        if(!$item) {
            $output['messages'][] = "Item ID: '$itemID' not found.";
            return response()->json($output);
        }

        try {
            $output['success'] = $item->delete();
        } catch (\Exception $e) {
            $output['success'] = false;
            $output['messages'][] = $e->getMessage();
        }

        return response()->json($output);
    }

}
