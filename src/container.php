<?php

declare(strict_types=1);

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
    DatabaseConnection::class => function () {
        $dbPath = getenv('DB_PATH') ?: 'database/crowdmap.sqlite';
        // Make path absolute if relative
        if ($dbPath[0] !== '/') {
            $dbPath = __DIR__ . '/../' . $dbPath;
        }
        return new SqliteConnection($dbPath);
    },

    UserRepository::class => function ($container) {
        return new SqliteUserRepository($container->get(DatabaseConnection::class));
    },

    MindmapRepository::class => function ($container) {
        return new SqliteMindmapRepository($container->get(DatabaseConnection::class));
    },

    StatementRepository::class => function ($container) {
        return new SqliteStatementRepository($container->get(DatabaseConnection::class));
    },

    VoteRepository::class => function ($container) {
        return new SqliteVoteRepository($container->get(DatabaseConnection::class));
    },
];
