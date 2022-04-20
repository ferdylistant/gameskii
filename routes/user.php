<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    //Auth
    $router->post('/auth/login-with-google', 'SocialAuth\SocialAccountController@requestIdToken');
    $router->post('/register', 'EndUser\Auth@register');
    $router->post('/login', 'EndUser\Auth@login');

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
            $router->get('/get-team/{idTeam}', 'EndUser\TeamController@getTeam');
            $router->get('/get-all-teams', 'EndUser\TeamController@getAllTeams');
            $router->post('/create-scrim', 'EndUser\ScrimController@createScrim');
            $router->get('/get-scrims', 'EndUser\ScrimController@getAllScrims');
            $router->get('/get-myscrims', 'EndUser\ScrimController@getMyScrims');
            $router->get('/get-myscrim/{idScrim}', 'EndUser\ScrimController@getMyScrimId');
            $router->get('/get-scrim-i-follow', 'EndUser\ScrimFollowController@getScrimFollowed');
            $router->post('/follow-scrim/{idScrim}', 'EndUser\ScrimFollowController@followScrim');
            $router->post('/unfollow-scrim/{idScrim}', 'EndUser\ScrimFollowController@unfollowScrim');
            $router->post('/join-scrim-room/{idScrim}', 'EndUser\ScrimMatchController@joinRoom');
            $router->get('/get-request-scrim-room/{idScrim}', 'EndUser\ScrimMatchController@getRequestTeamMatch');
            $router->get('/get-team-scrim-room/{idScrim}', 'EndUser\ScrimMatchController@getTeamMatchScrim');
            $router->post('/accept-scrim-room/{idScrim}/{idMatch}', 'EndUser\ScrimMatchController@acceptRequestTeamMatch');
            $router->post('/reject-scrim-room/{idScrim}/{idMatch}', 'EndUser\ScrimMatchController@rejectRequestTeamMatch');
            $router->post('/lock-scrim-room/{idScrim}', 'EndUser\ScrimMatchController@lockMatchScrim');
            $router->post('/unlock-scrim-room/{idScrim}', 'EndUser\ScrimMatchController@unlockMatchScrim');
            $router->post('/start-scrim-room/{idScrim}', 'EndUser\ScrimMatchController@startMatchScrim');
            $router->post('/match-scrim-ready/{idScrim}', 'EndUser\ScrimMatchController@readyToPlay');
            $router->post('/match-scrim-not-ready/{idScrim}', 'EndUser\ScrimMatchController@notReadyToPlay');
            $router->post('/match-scrim-progress/{idScrim}', 'EndUser\ScrimProgressController@uploadResultMatch');
            $router->post('/registration-eo', 'EndUser\EoTournamentController@registrationEo');
            $router->get('/get-my-eo', 'EndUser\EoTournamentController@getMyEo');
            $router->get('/get-eo-tournament', 'EndUser\EoTournamentController@getEoTournament');
            $router->post('/create-tournament', 'EndUser\TournamentController@createTournament');
            $router->get('/get-all-tournament', 'EndUser\TournamentController@getTournaments');
            $router->get('/get-my-tournament', 'EndUser\TournamentController@getMyTournaments');
            $router->post('/follow-tournament/{idTournament}', 'EndUser\TournamentFollowController@followTournament');
            $router->post('/unfollow-tournament/{idTournament}', 'EndUser\TournamentFollowController@unfollowTournament');
            $router->get('/get-tournament-followed', 'EndUser\TournamentFollowController@getTournamentFollowed');
            $router->post('/join-tournament-room/{idTournament}', 'EndUser\TournamentMatchController@joinRoom');
            $router->get('/get-request-tournament-room/{idTournament}', 'EndUser\TournamentMatchController@getRequestTeamMatch');
            $router->get('/get-team-tournament-room/{idTournament}', 'EndUser\TournamentMatchController@getTeamMatchTournament');
            $router->post('/accept-tournament-room/{idTournament}/{idMatch}', 'EndUser\TournamentMatchController@acceptRequestTeamMatch');
            $router->post('/reject-tournament-room/{idTournament}/{idMatch}', 'EndUser\TournamentMatchController@rejectRequestTeamMatch');
            $router->post('/lock-tournament-room/{idTournament}', 'EndUser\TournamentMatchController@lockMatchTournament');
            $router->post('/unlock-tournament-room/{idTournament}', 'EndUser\TournamentMatchController@unlockMatchTournament');
            $router->post('/match-tournament-ready/{idTournament}', 'EndUser\TournamentMatchController@readyToPlay');
            $router->post('/match-tournament-not-ready/{idTournament}', 'EndUser\TournamentMatchController@notReadyToPlay');
            $router->post('/start-tournament-room/{idTournament}', 'EndUser\TournamentMatchController@startMatchTournament');
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
