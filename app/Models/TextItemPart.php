<?php

namespace App\Models;

use App\Helpers\TextToSpeech;

use Illuminate\Database\Eloquent\Model;

class TextItemPart extends Model
{
    protected $table = 'text_item_parts';
    protected $fillable = ['request_item_id', 'item_index', 'item_content'];

    // todo: it'd be awesome for this model to validate the item_index.
    //       basically make sure there isn't another TextItemPart
    //       with the same request_item_id and item_index.

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requestItem() {
        return $this->belongsTo(RequestItem::class);
    }


    /**
     * Get the AudioItemPart models associated with this TextItemPart.
     *
     * @return array
     */
    public function getAudioItemParts() {
        if($this->requestItem()->exists()) {
            return $this->requestItem->audioItemParts()->where('item_index', $this->item_index)->get();
        }

        return [];
    }

}
