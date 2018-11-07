<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function() use ($router){
    return env('APP_NAME', 'Laravel Lumen');
});

$router->group([
    'prefix'        => 'api/v1/items',
    //'middleware'    => 'auth',
    'as'            => 'items.',
], function() use ($router) {

    // Create a new TTSItem
    $router->post('create', [
        'as'    => 'create',
        'uses'  => 'TTSItemController@submitJobRequest'
    ]);

    // Regenerate a TTSItem
    $router->get('{item_id}/regenerate', [
        'as'    => 'regenerate',
        'uses'  => 'TTSItemController@regenerateItem'
    ]);

    // Get the status of a TTSItem
    $router->get('{item_id}/status', [
        'as'    => 'status',
        'uses'  => 'TTSItemController@getItemStatus'
    ]);

    // Get the text content of a TTSItem
    $router->get('{item_id}/text', [
        'as'    => 'text',
        'uses'  => 'TTSItemController@getItemText'
    ]);

    // Download the audio file of a TTSItem if available
    $router->get('{item_id}/audio', [
        'as'    => 'audio',
        'uses'  => 'TTSItemController@downloadItemAudio'
    ]);

    // Download the audio file of a TTSItem if available
    $router->get('{item_id}/audio/download', [
        'as'    => 'audio.download',
        'uses'  => 'TTSItemController@downloadItemAudio'
    ]);

    // Stream the audio file of a TTSItem if available
    $router->get('{item_id}/audio/stream', [
        'as'    => 'audio.stream',
        'uses'  => 'TTSItemController@getItemAudio'
    ]);

    // Delete a TTSItem
    $router->delete('{item_id}/delete', [
        'as'    => 'delete',
        'uses'  => 'TTSItemController@deleteItem'
    ]);

});
