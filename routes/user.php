<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    //Auth
    $router->post('/auth/login-with-google', 'SocialAuth\SocialAccountController@requestIdToken');
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
            $router->get('/search-game-account', 'EndUser\GameAccountController@searchAccount');
            // $router->post('/login-game-account', 'EndUser\GameAccountController@login');
            $router->post('/choose-game/{idGame}', 'GameController@postGame');
            $router->get('/get-profile', 'EndUser\ProfileController@getProfile');
            $router->post('/update-profile', 'EndUser\ProfileController@updateProfile');
            $router->post('/change-password', 'EndUser\ProfileController@changePassword');
            $router->get('/dashboard', 'EndUser\DashboardGame@getDashboard');
            $router->post('/add-friend/{idGameAccount}', 'EndUser\SocialFollowController@addFriend');
            $router->post('/accept-friend/{idGameAccount}', 'EndUser\SocialFollowController@acceptFriend');
            $router->post('/reject-friend/{idGameAccount}', 'EndUser\SocialFollowController@rejectFriend');
            $router->post('/remove-friend/{idGameAccount}', 'EndUser\SocialFollowController@unfollow');
            $router->get('/get-friends', 'EndUser\SocialFollowController@getFriends');
            $router->get('/get-friend-request', 'EndUser\SocialFollowController@getListFriendRequest');
            $router->get('/notifications', 'EndUser\NotificationController@getNotif');
            $router->get('/notifications-count', 'EndUser\NotificationController@getCount');
            $router->post('/mark-as-read-notification/{idNotification}', 'EndUser\NotificationController@markAsReadOneNotification');
            $router->post('/mark-all-as-read-notification', 'EndUser\NotificationController@markAsReadAllNotifications');
            $router->post('/create-team', 'EndUser\TeamController@createTeam');
            $router->post('/add-player-team/{idTeam}/{idGameAccount}', 'EndUser\TeamController@addMembers');
            $router->post('/join-team/{idTeam}', 'EndUser\TeamController@joinTeam');
            $router->post('/accept-join-team/{idTeam}/{idGameAccount}', 'EndUser\TeamController@acceptJoinTeam');
            $router->post('/accept-invitation-member/{idTeam}', 'EndUser\TeamController@acceptInvitationMember');
            $router->post('/accept-team/{idTeam}', 'EndUser\TeamController@acceptInvitation');
            $router->post('/reject-join-team/{idTeam}/{idGameAccount}', 'EndUser\TeamController@rejectJoinTeam');
            $router->post('/reject-invitation-member/{idTeam}', 'EndUser\TeamController@rejectInvitationMember');
            $router->post('/leave-team/{idTeam}', 'EndUser\TeamController@leaveTeam');
            $router->get('/list-invitation-master', 'EndUser\TeamController@getListInvitationFromMaster');
            $router->get('/list-join-team-member', 'EndUser\TeamController@getListMemberJoins');
            $router->get('/get-myteams', 'EndUser\TeamController@getMyTeams');
            $router->get('/get-team/{idTeam}', 'EndUser\TeamController\@getTeam');
            $router->get('/get-all-teams', 'EndUser\TeamController@getAllTeams');
            $router->post('/create-scrim', 'EndUser\ScrimController@createScrim');
            $router->get('/get-scrims', 'EndUser\ScrimController@getAllScrims');
            $router->get('/get-myscrims', 'EndUser\ScrimController@getMyScrims');
            $router->get('/get-myscrim/{idScrim}', 'EndUser\ScrimController@getMyScrimId');
            $router->post('/add-scrim-team/{idScrim}/{idTeam}', 'EndUser\ScrimController@addTeam');
            $router->post('/join-scrim/{idScrim}', 'EndUser\ScrimController@joinScrim');
            $router->post('/accept-scrim/{idScrim}', 'EndUser\ScrimController@acceptInvitation');
            $router->post('/reject-scrim/{idScrim}', 'EndUser\ScrimController@rejectInvitation');
            $router->post('/registration-eo', 'EndUser\EoTournamentController@registrationEo');
            $router->get('/get-my-eo', 'EndUser\EoTournamentController@getMyEo');
            $router->get('/get-eo-tournament', 'EndUser\EoTournamentController@getEoTournament');
            $router->post('/logout', 'EndUser\Auth@logout');
        });
        //Auth
        $router->get('/all-ranks', 'RankController@getAllRanks');
        $router->get('/games-data', 'GameController@getGameData');
        //post
        //Put
        // $router->post('/update-profile', 'Client\Put\UpdateProfileController@update');
        // $router->post('/update-profile-image', 'Client\Put\UpdateImageProfileController@update');
        //Del
        $router->delete('/delete/{id}', 'Customers\CustomerController@destroy');
    });
});
