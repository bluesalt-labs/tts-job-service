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
    'prefix'        => 'api/v1',
    //'middleware'    => 'auth',
], function() use ($router) {

    // Get available voices
    $router->get('voices', [
        'as'    => 'voices',
        'uses'  => 'Controller@getVoices',
    ]);

    // Get configured SSML replacements.
    $router->get('ssml-replacements', [
        'as'    => 'ssml-replacements',
        'uses'  => 'Controller@getSSMLReplacements',
    ]);

    // Get configured audio output formats.
    $router->get('output-formats', [
        'as'    => 'output-formats',
        'uses'  => 'Controller@getOutputFormats',
    ]);

});

$router->group([
    'prefix'        => 'api/v1/request-items',
    //'middleware'    => 'auth',
    'as'            => 'request-items.',
    ], function() use ($router) {

    // List all request items
    $router->get('/', [
        'as'    => 'list',
        'uses'  => 'RequestItemsController@listRequestItems',
    ]);

    // Create a new RequestItem
    $router->post('create', [
        'as'    => 'create',
        'uses'  => 'RequestItemsController@createRequestItem',
    ]);

    // Get the status of a RequestItem
    $router->get('{item_id}/status', [
        'as'    => 'status',
        'uses'  => 'RequestItemsController@getRequestItemStatus',
    ]);



});


$router->group([
    'prefix'        => 'api/v1/items',
    //'middleware'    => 'auth',
    'as'            => 'items.',
], function() use ($router) {

    // List all items
    $router->get('/', [
        'as'    => 'list',
        'uses'  => 'TTSItemController@listItems',
    ]);

    // Create a new TTSItem
    $router->post('create', [
        'as'    => 'create',
        'uses'  => 'TTSItemController@submitJobRequest',
    ]);

    // Regenerate a TTSItem
    $router->get('{item_id}/regenerate', [
        'as'    => 'regenerate',
        'uses'  => 'TTSItemController@regenerateItem',
    ]);

    // Get the status of a TTSItem
    $router->get('{item_id}/status', [
        'as'    => 'status',
        'uses'  => 'TTSItemController@getItemStatus',
    ]);

    // Get the text content of a TTSItem
    $router->get('{item_id}/text', [
        'as'    => 'text',
        'uses'  => 'TTSItemController@getItemText',
    ]);

    // Download the audio file of a TTSItem if available
    $router->get('{item_id}/audio', [
        'as'    => 'audio',
        'uses'  => 'TTSItemController@downloadItemAudio',
    ]);

    // Download the audio file of a TTSItem if available
    $router->get('{item_id}/audio/download', [
        'as'    => 'audio.download',
        'uses'  => 'TTSItemController@downloadItemAudio',
    ]);

    // Stream the audio file of a TTSItem if available
    $router->get('{item_id}/audio/stream', [
        'as'    => 'audio.stream',
        'uses'  => 'TTSItemController@getItemAudio',
    ]);

    // Delete a TTSItem
    $router->delete('{item_id}/delete', [
        'as'    => 'delete',
        'uses'  => 'TTSItemController@deleteItem',
    ]);

});
