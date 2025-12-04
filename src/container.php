<?php

declare(strict_types=1);

use Crowdmap\Application\Command\Auth\GetCurrentUserCommand;
use Crowdmap\Application\Command\Auth\LoginCommand;
use Crowdmap\Application\Command\Auth\LogoutCommand;
use Crowdmap\Application\Command\Mindmap\CreateMindmapCommand;
use Crowdmap\Application\Command\Mindmap\SetFocusedStatementCommand;
use Crowdmap\Application\Command\Statement\AddStatementCommand;
use Crowdmap\Application\Command\Statement\DeleteStatementsCommand;
use Crowdmap\Application\Command\Statement\MergeStatementsCommand;
use Crowdmap\Application\Command\Statement\UpdateStatementCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Application\CommandBus\SimpleCommandBus;
use Crowdmap\Application\CommandHandler\Auth\GetCurrentUserHandler;
use Crowdmap\Application\CommandHandler\Auth\LoginHandler;
use Crowdmap\Application\CommandHandler\Auth\LogoutHandler;
use Crowdmap\Application\CommandHandler\Mindmap\CreateMindmapHandler;
use Crowdmap\Application\CommandHandler\Mindmap\SetFocusedStatementHandler;
use Crowdmap\Application\CommandHandler\Statement\AddStatementHandler;
use Crowdmap\Application\CommandHandler\Statement\DeleteStatementsHandler;
use Crowdmap\Application\CommandHandler\Statement\MergeStatementsHandler;
use Crowdmap\Application\CommandHandler\Statement\UpdateStatementHandler;
use Crowdmap\Database\DatabaseConnection;
use Crowdmap\Database\SqliteConnection;
use Crowdmap\Repository\MindmapRepository;
use Crowdmap\Repository\SqliteMindmapRepository;
use Crowdmap\Repository\SqliteStatementRepository;
use Crowdmap\Repository\SqliteUserRepository;
use Crowdmap\Repository\SqliteVoteRepository;
use Crowdmap\Repository\StatementRepository;
use Crowdmap\Repository\UserRepository;
use Crowdmap\Repository\VoteRepository;

return [
    // Infrastructure - Database
    DatabaseConnection::class => function () {
        $dbPath = getenv('DB_PATH') ?: 'database/crowdmap.sqlite';
        if ($dbPath[0] !== '/') {
            $dbPath = __DIR__ . '/../' . $dbPath;
        }
        return new SqliteConnection($dbPath);
    },

    // Repositories
    UserRepository::class => fn($c) => new SqliteUserRepository($c->get(DatabaseConnection::class)),
    MindmapRepository::class => fn($c) => new SqliteMindmapRepository($c->get(DatabaseConnection::class)),
    StatementRepository::class => fn($c) => new SqliteStatementRepository($c->get(DatabaseConnection::class)),
    VoteRepository::class => fn($c) => new SqliteVoteRepository($c->get(DatabaseConnection::class)),

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
    LoginHandler::class => fn($c) => new LoginHandler(
        $c->get(UserRepository::class),
    ),
    LogoutHandler::class => fn() => new LogoutHandler(),
    GetCurrentUserHandler::class => fn($c) => new GetCurrentUserHandler(
        $c->get(UserRepository::class),
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
        $bus->register(LoginCommand::class, LoginHandler::class);
        $bus->register(LogoutCommand::class, LogoutHandler::class);
        $bus->register(GetCurrentUserCommand::class, GetCurrentUserHandler::class);

        return $bus;
    },
];
