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
    'prefix'        => 'v1/items',
    //'middleware'    => 'auth',
], function() use ($router) {
    $router->post('create', 'TTSItemController@submitJobRequest');
    $router->get('{item_id}/regenerate', 'TTSItemController@regenerateItem');
    $router->get('{item_id}/status', 'TTSItemController@getItemStatus');
    $router->get('{item_id}/text', 'TTSItemController@getItemText');
    $router->get('{item_id}/audio', 'TTSItemController@downloadItemAudio');
    $router->get('{item_id}/audio/download', 'TTSItemController@downloadItemAudio');
    $router->get('{item_id}/audio/stream', 'TTSItemController@getItemAudio');
    $router->delete('{item_id}/delete', 'TTSItemController@deleteItem');
});