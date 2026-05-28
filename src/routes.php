<?php

declare(strict_types=1);

use Crowdmap\Http\Action\AddStatementAction;
use Crowdmap\Http\Action\CreateMindmapAction;
use Crowdmap\Http\Action\DeleteStatementsAction;
use Crowdmap\Http\Action\GetMindmapAction;
use Crowdmap\Http\Action\GetMindmapModeratorAction;
use Crowdmap\Http\Action\ListMindmapsAction;
use Crowdmap\Http\Action\ConfirmMfaAction;
use Crowdmap\Http\Action\DisableMfaAction;
use Crowdmap\Http\Action\LoginAction;
use Crowdmap\Http\Action\LogoutAction;
use Crowdmap\Http\Action\MeAction;
use Crowdmap\Http\Action\MergeStatementsAction;
use Crowdmap\Http\Action\RegisterAction;
use Crowdmap\Http\Action\SetFocusedStatementAction;
use Crowdmap\Http\Action\SetupMfaAction;
use Crowdmap\Http\Action\ToggleVoteAction;
use Crowdmap\Http\Action\UpdateStatementAction;
use Crowdmap\Http\Action\VerifyMfaLoginAction;
use Slim\App;

return function (App $app) {
    // Auth
    $app->post('/api/login', LoginAction::class);
    $app->post('/api/login/mfa', VerifyMfaLoginAction::class);
    $app->post('/api/register', RegisterAction::class);
    $app->get('/api/me', MeAction::class);
    $app->post('/api/logout', LogoutAction::class);

    // MFA enrollment (authenticated)
    $app->post('/api/mfa/setup', SetupMfaAction::class);
    $app->post('/api/mfa/confirm', ConfirmMfaAction::class);
    $app->post('/api/mfa/disable', DisableMfaAction::class);

    // Mindmaps (authenticated - moderator)
    $app->get('/api/mindmaps', ListMindmapsAction::class);
    $app->post('/api/mindmaps', CreateMindmapAction::class);
    $app->get('/api/mindmaps/{uuid}/moderate', GetMindmapModeratorAction::class);
    $app->post('/api/mindmaps/{uuid}/focus', SetFocusedStatementAction::class);
    $app->post('/api/mindmaps/{uuid}/merge', MergeStatementsAction::class);
    $app->post('/api/mindmaps/{uuid}/delete', DeleteStatementsAction::class);
    $app->put('/api/mindmaps/{uuid}/statements/{statementId}', UpdateStatementAction::class);

    // Mindmaps (public - contributor)
    $app->get('/api/mindmaps/{uuid}', GetMindmapAction::class);
    $app->post('/api/mindmaps/{uuid}/statements', AddStatementAction::class);

    // Votes
    $app->post('/api/statements/{id}/vote', ToggleVoteAction::class);
};
