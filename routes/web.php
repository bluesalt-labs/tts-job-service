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

$router->get('/', 'ApiController@index');

//$router->group(['middleware' => 'auth'], function() use ($router) {
$router->group([], function() use ($router) {
    $router->post('submit-job-request', 'ApiController@submitJobRequest');
    $router->get('get-job-status/{job_id}', 'ApiController@getJobStatus');
});