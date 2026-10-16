<?php

declare(strict_types=1);

use Crowdmap\Application\Command\Auth\ConfirmMfaCommand;
use Crowdmap\Application\Command\Auth\DisableMfaCommand;
use Crowdmap\Application\Command\Auth\GetCurrentUserCommand;
use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Application\Command\Auth\LogoutCommand;
use Crowdmap\Application\Command\Auth\RegisterCommand;
use Crowdmap\Application\Command\Auth\SetupMfaCommand;
use Crowdmap\Application\Command\Auth\VerifyMfaLoginCommand;
use Crowdmap\Application\Command\Mindmap\CreateMindmapCommand;
use Crowdmap\Application\Command\Mindmap\GetMindmapCommand;
use Crowdmap\Application\Command\Mindmap\GetMindmapForModerationCommand;
use Crowdmap\Application\Command\Mindmap\ListMindmapsCommand;
use Crowdmap\Application\Command\Mindmap\SetFocusedStatementCommand;
use Crowdmap\Application\Command\Statement\AddStatementCommand;
use Crowdmap\Application\Command\Statement\DeleteStatementsCommand;
use Crowdmap\Application\Command\Statement\MergeStatementsCommand;
use Crowdmap\Application\Command\Statement\UpdateStatementCommand;
use Crowdmap\Application\Command\Vote\SetVoteCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Application\CommandBus\SimpleCommandBus;
use Crowdmap\Application\CommandHandler\Auth\ConfirmMfaHandler;
use Crowdmap\Application\CommandHandler\Auth\DisableMfaHandler;
use Crowdmap\Application\CommandHandler\Auth\GetCurrentUserHandler;
use Crowdmap\Application\CommandHandler\Auth\LoginHandler;
use Crowdmap\Application\CommandHandler\Auth\LogoutHandler;
use Crowdmap\Application\CommandHandler\Auth\RegisterHandler;
use Crowdmap\Application\CommandHandler\Auth\SetupMfaHandler;
use Crowdmap\Application\CommandHandler\Auth\VerifyMfaLoginHandler;
use Crowdmap\Application\CommandHandler\Mindmap\CreateMindmapHandler;
use Crowdmap\Application\CommandHandler\Mindmap\GetMindmapForModerationHandler;
use Crowdmap\Application\CommandHandler\Mindmap\GetMindmapHandler;
use Crowdmap\Application\CommandHandler\Mindmap\ListMindmapsHandler;
use Crowdmap\Application\CommandHandler\Mindmap\SetFocusedStatementHandler;
use Crowdmap\Application\CommandHandler\Statement\AddStatementHandler;
use Crowdmap\Application\CommandHandler\Statement\DeleteStatementsHandler;
use Crowdmap\Application\CommandHandler\Statement\MergeStatementsHandler;
use Crowdmap\Application\CommandHandler\Statement\UpdateStatementHandler;
use Crowdmap\Application\CommandHandler\Vote\SetVoteHandler;
use Crowdmap\Database\ConnectionFactory;
use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Domain\Service\TotpService;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\MysqlMindmapRepository;
use Crowdmap\Repository\MysqlStatementRepository;
use Crowdmap\Repository\MysqlUserRepository;
use Crowdmap\Repository\MysqlVoteRepository;
use Crowdmap\Repository\SqliteMindmapRepository;
use Crowdmap\Repository\SqliteStatementRepository;
use Crowdmap\Repository\SqliteUserRepository;
use Crowdmap\Repository\SqliteVoteRepository;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\UserRepository;
use Crowdmap\Repository\VoteRepository;

return [
    // Infrastructure - Database
    DatabaseConnection::class => fn() => ConnectionFactory::fromEnvironment(),

    // Domain services
    TotpService::class => fn() => new TotpService(),

    // Repositories
    UserRepository::class => function ($c) {
        return ConnectionFactory::driver() === 'mysql'
            ? new MysqlUserRepository($c->get(DatabaseConnection::class))
            : new SqliteUserRepository($c->get(DatabaseConnection::class));
    },
    MindmapRepository::class => function ($c) {
        return ConnectionFactory::driver() === 'mysql'
            ? new MysqlMindmapRepository($c->get(DatabaseConnection::class))
            : new SqliteMindmapRepository($c->get(DatabaseConnection::class));
    },
    StatementRepository::class => function ($c) {
        return ConnectionFactory::driver() === 'mysql'
            ? new MysqlStatementRepository($c->get(DatabaseConnection::class))
            : new SqliteStatementRepository($c->get(DatabaseConnection::class));
    },
    VoteRepository::class => function ($c) {
        return ConnectionFactory::driver() === 'mysql'
            ? new MysqlVoteRepository($c->get(DatabaseConnection::class))
            : new SqliteVoteRepository($c->get(DatabaseConnection::class));
    },

    // Command Handlers
    AddStatementHandler::class => fn($c) => new AddStatementHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
        $c->get(VoteRepository::class),
    ),
    UpdateStatementHandler::class => fn($c) => new UpdateStatementHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    DeleteStatementsHandler::class => fn($c) => new DeleteStatementsHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    MergeStatementsHandler::class => fn($c) => new MergeStatementsHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    SetFocusedStatementHandler::class => fn($c) => new SetFocusedStatementHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    CreateMindmapHandler::class => fn($c) => new CreateMindmapHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    GetMindmapHandler::class => fn($c) => new GetMindmapHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    GetMindmapForModerationHandler::class => fn($c) => new GetMindmapForModerationHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    ListMindmapsHandler::class => fn($c) => new ListMindmapsHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    LoginHandler::class => fn($c) => new LoginHandler(
        $c->get(UserRepository::class),
    ),
    RegisterHandler::class => fn($c) => new RegisterHandler(
        $c->get(UserRepository::class),
    ),
    SetupMfaHandler::class => fn($c) => new SetupMfaHandler(
        $c->get(UserRepository::class),
        $c->get(TotpService::class),
    ),
    ConfirmMfaHandler::class => fn($c) => new ConfirmMfaHandler(
        $c->get(UserRepository::class),
        $c->get(TotpService::class),
    ),
    DisableMfaHandler::class => fn($c) => new DisableMfaHandler(
        $c->get(UserRepository::class),
    ),
    VerifyMfaLoginHandler::class => fn($c) => new VerifyMfaLoginHandler(
        $c->get(UserRepository::class),
        $c->get(TotpService::class),
    ),
    LogoutHandler::class => fn() => new LogoutHandler(),
    GetCurrentUserHandler::class => fn($c) => new GetCurrentUserHandler(
        $c->get(UserRepository::class),
    ),
    SetVoteHandler::class => fn($c) => new SetVoteHandler(
        $c->get(StatementRepository::class),
        $c->get(VoteRepository::class),
    ),

    // Command Bus
    CommandBus::class => function ($container) {
        $bus = new SimpleCommandBus($container);

        // Register command -> handler mappings
        $bus->register(AddStatementCommand::class, AddStatementHandler::class);
        $bus->register(UpdateStatementCommand::class, UpdateStatementHandler::class);
        $bus->register(DeleteStatementsCommand::class, DeleteStatementsHandler::class);
        $bus->register(MergeStatementsCommand::class, MergeStatementsHandler::class);
        $bus->register(SetFocusedStatementCommand::class, SetFocusedStatementHandler::class);
        $bus->register(CreateMindmapCommand::class, CreateMindmapHandler::class);
        $bus->register(GetMindmapCommand::class, GetMindmapHandler::class);
        $bus->register(GetMindmapForModerationCommand::class, GetMindmapForModerationHandler::class);
        $bus->register(ListMindmapsCommand::class, ListMindmapsHandler::class);
        $bus->register(LoginCommand::class, LoginHandler::class);
        $bus->register(RegisterCommand::class, RegisterHandler::class);
        $bus->register(SetupMfaCommand::class, SetupMfaHandler::class);
        $bus->register(ConfirmMfaCommand::class, ConfirmMfaHandler::class);
        $bus->register(DisableMfaCommand::class, DisableMfaHandler::class);
        $bus->register(VerifyMfaLoginCommand::class, VerifyMfaLoginHandler::class);
        $bus->register(LogoutCommand::class, LogoutHandler::class);
        $bus->register(GetCurrentUserCommand::class, GetCurrentUserHandler::class);
        $bus->register(SetVoteCommand::class, SetVoteHandler::class);

        return $bus;
    },
];
