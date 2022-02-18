<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/admin'], function () use ($router) {
    //Auth
    $router->post('/login', 'Admin\Auth@login');
    $router->post('/forgot-password', 'Api\NewPasswordController@forgotPassword');
    $router->post('/reset-password', 'Api\NewPasswordController@reset');
    $router->group(['middleware' => 'auth:user'], function () use ($router) {
        //Auth
        $router->post('/register', 'Admin\Auth@register');
        $router->post('/logout', 'Admin\Auth@logout');
        $router->post('/change-password', 'Admin\Auth@changePassword');

        $router->get('/profile', 'Admin\Get\ProfileController@profile');
        $router->get('/profile-image/{imageName}', 'Client\Get\ImageProfileController@getImage');
        $router->get('/games-data', 'GameController@getGameData');
        //post
        $router->post('/post-games', 'GameController@create');
        $router->post('/post-ranks', 'RankController@create');
        //Put
        $router->post('/update-games/{id}', 'GameController@update');
        $router->post('/update-profile', 'Client\Put\UpdateProfileController@update');
        $router->post('/update-profile-image', 'Client\Put\UpdateImageProfileController@update');
        //Del
        $router->delete('/delete/{id}', 'Customers\CustomerController@destroy');
    });
});
