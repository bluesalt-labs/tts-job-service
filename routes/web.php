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

//$router->group(['middleware' => 'auth'], function() use ($router) {
$router->group([], function() use ($router) {
    $router->post('submit-job-request', 'TTSItemController@submitJobRequest');
    $router->get('regenerate-item/{item_id}', 'TTSItemController@regenerateItem');
    $router->get('get-item-status/{item_id}', 'TTSItemController@getItemStatus');
    $router->get('get-item-audio/{item_id}', 'TTSItemController@getItemAudio');
    $router->delete('delete-item/{item_id}', 'TTSItemController@deleteItem');
});