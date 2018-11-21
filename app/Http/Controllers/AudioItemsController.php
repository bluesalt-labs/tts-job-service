<?php

namespace App\Http\Controllers;

use App\Models\RequestItem;
use App\Models\AudioItem;
use Illuminate\Http\Request;

use Laravel\Lumen\Routing\Controller as BaseController;

class AudioItemsController extends BaseController
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


    public function listAudioItems(Request $request) {
        $output = [
            'success'   => false,
            'items'     => [],
            'messages'  => [],
        ];

        /**
         * @var AudioItem $item
         */
        $items = AudioItem::all();

        if(!$items) {
            $output['messages'][] = "No AudioItems items found.";
            return response()->json($output);
        }

        if(count($items) > 0) {
            $output['success']  = true;
        }

        foreach($items as $item) {
            $output['items'][] = [
                'item_id'       => $item->id,
                // todo
                'name'          => $item->name,
                'status'        => $item->status,
            ];
        }


        return response()->json($output);
    }

    //



}
