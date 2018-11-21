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
     * // todo: I mean, does this model care about this?
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function audioItemParts() {
        return $this->belongsToMany(AudioItemPart::class);
    }

}
