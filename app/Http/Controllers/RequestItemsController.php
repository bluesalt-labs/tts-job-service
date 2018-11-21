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


    /**
     * List the RequestItems
     * When users are implemented, only request items that belong to the user are listed.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
        //$items = RequestItem::where('user_id', $request->user()->id);

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


    public function createRequestItem(Request $request) {
        $output = [
            'success'       => false,
            'request_item'  => null,
            //'audio_items'   => [], // todo?
            'messages'      => [],
        ];

        /** @var \App\Models\User $user */
        $user = $request->user();
        // todo: can user create request items? actually, that's probably middleware...

        $text       = $request->get('text');
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
            $voices = [$voices];
        }

        if($text && $voices) {
            $createRequestItemResponse = RequestItem::createItem($text, $voices, $name, $outFormat, $user);
        }

        // todo

        return response()->json($output);
    }


    public function getRequestItemStatus(Request $request) {
        $output = [];

        // todo

        return response()->json($output);
    }


    public function regenerateRequestItem($itemID) {
        $output = [];

        // todo

        return response()->json($output);
    }

}
