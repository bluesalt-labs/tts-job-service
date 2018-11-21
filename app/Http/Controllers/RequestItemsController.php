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


    public function createRequestItem(Request $request) {
        $output = [];

        // todo

        return response()->json($output);
    }


    public function getRequestItemStatus(Request $request) {
        $output = [];

        // todo

        return response()->json($output);
    }

}
