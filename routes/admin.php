<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/admin'], function () use ($router) {
    //Auth
    $router->post('/login', 'Admin\Auth@login');
    $router->group(['middleware' => 'auth:user'], function () use ($router) {
        //Auth
        $router->post('/register', 'Admin\Auth@register');
        $router->post('/logout', 'Admin\Auth@logout');
        $router->post('/change-password', 'Admin\Auth@changePassword');
        $router->get('/get-all-admin', 'Admin\UserController@getAdmin');
        $router->get('/get-all-end-user', 'Admin\UserController@getEndUser');
        $router->get('/profile', 'Admin\Get\ProfileController@profile');
        $router->get('/profile-image/{imageName}', 'Client\Get\ImageProfileController@getImage');
        $router->get('/games-data', 'GameController@getGameData');
        $router->post('/post-games', 'GameController@create');
        $router->post('/post-ranks', 'RankController@create');
        $router->get('/get-all-scrims', 'Admin\ScrimController@getScrims');
        $router->get('/get-scrim-by-id/{idScrim}', 'Admin\ScrimController@getScrimById');
        $router->get('/get-scrim-by-user/{idUser}', 'Admin\ScrimController@getScrimByIdUser');
        $router->get('get-scrim-by-game-account/{idGameAccount}', 'Admin\ScrimController@getScrimByIdGameAccount');
        $router->get('/get-scrim-by-game/{idGame}', 'Admin\ScrimController@getScrimByIdGame');
        $router->get('/get-request-eo', 'Admin\EoController@getRequestEo');
        $router->post('/accept-request-eo/{idEo}', 'Admin\EoController@acceptRequestEo');
        $router->post('/reject-request-eo/{idEo}', 'Admin\EoController@rejectRequestEo');
        $router->post('/update-games/{id}', 'GameController@update');
        $router->post('/update-profile', 'Client\Put\UpdateProfileController@update');
        $router->post('/update-profile-image', 'Client\Put\UpdateImageProfileController@update');
        $router->get('/get-all-teams-by-game/{idGame}', 'Admin\TeamController@getTeams');
        $router->get('/get-team-detail/{idGame}/{idTeam}', 'Admin\TeamController@getTeamDetail');
    });
});
