<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
use GuzzleHttp\Client;
Route::get('/', function () {
    return view('home');
});

Route::get('/backend', function () {
    return view('dashboard');
});

// Route::get('/admin', function () {
//     return view('admin');
// });



Route::group([
    'prefix' => 'dashboard',
    'module' => 'User',
    'as' => 'admin::',
    'middleware' => ['auth']
], function () {
    Route::get('/', ['as' => 'dashboard', function () {
        return view('admin');
    }]);
});

Entrust::routeNeedsRole('/dashboard/*', ['root', 'admin', 'operator']);

Route::get('auth/login', [
   'as' => 'login',
   'uses' => 'Auth\AuthController@getLogin'
]);
Route::post('auth/login', ['as' => 'login', 'uses' => 'Auth\AuthController@postLogin']);
Route::get('auth/logout', ['as' => 'logout', 'uses' => 'Auth\AuthController@getLogout']);

Route::get('auth/register', ['as' => 'register', 'uses' => 'Auth\AuthController@getRegister']);
Route::post('auth/register', ['as' => 'register', 'uses' => 'Auth\AuthController@doRegister']);

/**
 * Success Register
 */
Route::get('auth/success', ['as' => 'auth.success.get', 'uses' => 'UserController@confirmation']);

/**
 * chat client example
 * this is dummy and parameters still hardcoded
 */
Route::get('/chat-client', function () {
    return view('chat-client');
});

Route::get('/chat-client-2', function () {
    return view('chat-client-2');
});

Route::get('/greetings', function () {
    return view('/emails/greeting');
});

Route::get('/api/random-user', function () {
    $query_string   = request()->query;
    $client = new Client([
        'base_uri' => 'https://randomuser.me/api',
    ]);

    $response   = $client->request('GET', '?format=json', [
        'header' => [
            'Content-Type' => 'application/json'
        ], 'Accept'     => 'application/json',
    ]);
    $result     = json_decode($response->getBody()->getContents());

    switch ($query_string->get('picture')) {
        case 'thumbnail':
            return $result->results[0]->user->picture->thumbnail;
            break;
        case 'large':
            return $result->results[0]->user->picture->large;
            break;
        case 'medium':
            return $result->results[0]->user->picture->medium;
            break;
        default:
            return $result->results[0]->user->picture->thumbnail;
            break;
    }
});