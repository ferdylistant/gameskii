<?php
use Laravel\Socialite\Facades\Socialite;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    //Auth
    $router->post('/register', 'EndUser\Auth@register');
    $router->post('/login', 'EndUser\Auth@login');
    $router->post('/forgot-password', 'Api\NewPasswordController@forgotPassword');
    $router->post('/reset-password', 'Api\NewPasswordController@reset');
    $router->get('/email-verification', 'Api\EmailVerificationController@emailVerify');
    $router->get('/auth/redirect', 'SocialAuth\SocialAccountController@redirectToGoogle');
    $router->get('/auth/callback', 'SocialAuth\SocialAccountController@callbackFromGoogle');
    $router->get('/avatar/{imageName}', 'Api\ImageController@getImage');
    $router->get('/picture-game/{imageName}', 'Api\ImageController@getPicture');
    $router->get('/picture-team/{imageName}', 'Api\ImageController@getPictureTeam');
    $router->get('/picture-scrim/{imageName}', 'Api\ImageController@getPictureScrim');
    $router->get('/logo-rank/{imageName}', 'Api\ImageController@getLogoRank');
    $router->get('/banner-game/top/{imageName}', 'Api\ImageController@getBannerTop');
    $router->get('/banner-game/bottom/{imageName}', 'Api\ImageController@getBannerBottom');

    $router->group(['middleware' => 'auth:user'], function () use ($router) {
        $router->group(['middleware' => 'session'], function () use ($router) {
            $router->post('/create-game-account', 'EndUser\GameAccountController@create');
            // $router->post('/login-game-account', 'EndUser\GameAccountController@login');
            $router->post('/choose-game/{idGame}', 'GameController@postGame');
            $router->get('/profile', 'EndUser\ProfileController@getProfile');
            $router->get('/dashboard', 'EndUser\DashboardGame@getDashboard');
            $router->post('/add-friend/{idGameAccount}', 'EndUser\SocialFollowController@addFriend');
            $router->post('/accept-friend/{idGameAccount}', 'EndUser\SocialFollowController@acceptFriend');
            $router->post('/reject-friend/{idGameAccount}', 'EndUser\SocialFollowController@rejectFriend');
            $router->post('/remove-friend/{idGameAccount}', 'EndUser\SocialFollowController@unfollow');
            $router->get('/get-friends', 'EndUser\SocialFollowController@getFriends');
            $router->get('/get-friend-request', 'EndUser\SocialFollowController@getFriendRequest');
            $router->get('/notifications', 'EndUser\NotificationController@getNotif');
            $router->get('/notifications-count', 'EndUser\NotificationController@getCount');
            $router->post('/mark-as-read-notification/{idNotification}', 'EndUser\NotificationController@markAsReadOneNotification');
            $router->post('/create-team', 'TeamController@createTeam');
            $router->post('/add-player-team/{idTeam}/{idGameAccount}', 'TeamController@addMembers');
            $router->post('/join-team/{idTeam}', 'TeamController@joinTeam');
            $router->post('/accept-team/{idTeam}', 'TeamController@acceptInvitation');
            $router->post('/reject-team/{idTeam}', 'TeamController@rejectInvitation');
            $router->post('/leave-team/{idTeam}', 'TeamController@leaveTeam');
            $router->get('/get-myteams', 'TeamController@getMyTeams');
            $router->get('/get-team/{idTeam}', 'TeamController@getTeam');
            $router->get('/get-all-teams', 'TeamController@getAllTeams');
            $router->post('/create-scrim', 'ScrimController@createScrim');
            $router->post('/add-scrim-team/{idScrim}/{idTeam}', 'ScrimController@addTeam');
            $router->post('/join-scrim/{idScrim}', 'ScrimController@joinScrim');
            $router->post('/accept-scrim/{idScrim}', 'ScrimController@acceptInvitation');
            $router->post('/reject-scrim/{idScrim}', 'ScrimController@rejectInvitation');
            $router->post('/logout', 'EndUser\Auth@logout');
        });
        //Auth
        $router->post('/change-password', 'Admin\Auth@changePassword');
        $router->get('/games-data', 'GameController@getGameData');
        //post
        //Put
        $router->post('/update-profile', 'Client\Put\UpdateProfileController@update');
        $router->post('/update-profile-image', 'Client\Put\UpdateImageProfileController@update');
        //Del
        $router->delete('/delete/{id}', 'Customers\CustomerController@destroy');
    });
});
