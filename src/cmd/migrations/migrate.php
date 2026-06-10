<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Core\Config\MigrationsConfig;
use App\Core\Consts\SqlStateEnum;
use App\Core\ServiceContainerBuilder;

require __DIR__ . '/../../bootstrap.php';

$containerBuilder = new ServiceContainerBuilder();
$container = $containerBuilder->build();

$migrationsConfig = new MigrationsConfig();
$migrationsFolder = $migrationsConfig->getMigrationsFolder();

$migrations = glob($migrationsFolder . "*.sql");
sort($migrations);

$pdo = $container->get(AppDatabase::class);
$versionsExecuted = [];
$countExecuted = 0;

try {
    $result = $pdo->query("SELECT * FROM migrations", PDO::FETCH_ASSOC);

    foreach ($result as $row) {
        $versionsExecuted[] = $row["version"];
    }
} catch (PDOException $e) {
    if ($e->getCode() != SqlStateEnum::TABLE_NOT_FOUND->value) {
        throw $e;
    }
}

foreach ($migrations as $migration) {
    $version = basename($migration);

    if (in_array($version, $versionsExecuted, true)) {
        continue;
    }

    if (count($versionsExecuted) === 0 && !str_ends_with($version, "_migrations_table.sql")) {
        fwrite(STDERR, "Invalid first migration setup, migrations aborted!" . PHP_EOL);
        exit(1);
    }

    echo "Running $version" . PHP_EOL;

    if (strlen($version) > 255) {
        fwrite(STDERR, "The migration name is too big, migrations aborted!" . PHP_EOL);
        exit(1);
    }

    $sql = file_get_contents($migration);
    $pdo->beginTransaction();

    try {
        $pdo->exec($sql);
        $pdo->prepare('INSERT INTO migrations (version) VALUES (?)')->execute([$version]);

        // Some statements auto-commit and finishes the transaction
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

if ($countExecuted > 0) {
    echo "$countExecuted migrations executed, all done!" . PHP_EOL;
} else {
    echo "No new migrations to execute, all done!" . PHP_EOL;
}
