<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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
$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('/forgot-password', 'Api\NewPasswordController@forgotPassword');
    $router->post('/reset-password', 'Api\NewPasswordController@reset');
    $router->get('/email-verification', 'Api\EmailVerificationController@emailVerify');
    $router->get('/auth/redirect', 'SocialAuth\SocialAccountController@redirectToGoogle');
    $router->get('/auth/callback', 'SocialAuth\SocialAccountController@callbackFromGoogle');
    $router->get('/avatar/{imageName}', 'Api\ImageController@getImage');
    $router->get('/picture-game/{imageName}', 'Api\ImageController@getPicture');
    $router->get('/picture-team/{imageName}', 'Api\ImageController@getPictureTeam');
    $router->get('/picture-scrim/{imageName}', 'Api\ImageController@getPictureScrim');
    $router->get('/picture-scrim-progress/{imageName}', 'Api\ImageController@getPictureScrimProgress');
    $router->get('/picture-sponsor-tournament/{imageName}', 'Api\ImageController@getPictureSponsorTournament');
    $router->get('/picture-tournament/{imageName}','Api\ImageController@getPictureTournament');
    $router->get('/logo-rank/{imageName}', 'Api\ImageController@getLogoRank');
    $router->get('/banner-game/top/{imageName}', 'Api\ImageController@getBannerTop');
    $router->get('/banner-game/bottom/{imageName}', 'Api\ImageController@getBannerBottom');
});


