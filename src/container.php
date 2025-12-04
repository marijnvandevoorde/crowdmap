<?php

declare(strict_types=1);

use Crowdmap\Application\Command\Statement\DeleteStatementsCommand;
use Crowdmap\Application\Command\Statement\UpdateStatementCommand;
use Crowdmap\Application\CommandBus\CommandBus;
use Crowdmap\Application\CommandBus\SimpleCommandBus;
use Crowdmap\Application\CommandHandler\Statement\DeleteStatementsHandler;
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
    UpdateStatementHandler::class => fn($c) => new UpdateStatementHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),
    DeleteStatementsHandler::class => fn($c) => new DeleteStatementsHandler(
        $c->get(MindmapRepository::class),
        $c->get(StatementRepository::class),
    ),

    // Command Bus
    CommandBus::class => function ($container) {
        $bus = new SimpleCommandBus($container);

        // Register command -> handler mappings
        $bus->register(UpdateStatementCommand::class, UpdateStatementHandler::class);
        $bus->register(DeleteStatementsCommand::class, DeleteStatementsHandler::class);

        return $bus;
    },
];
