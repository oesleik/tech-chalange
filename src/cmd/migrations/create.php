<?php

declare(strict_types=1);

use App\Core\Config\AppConfig;
use App\Core\Config\MigrationsConfig;

require_once __DIR__ . '/../../bootstrap.php';

echo "Give the new migration a short name: ";
$input = fgets(STDIN);

$input = strtolower(str_replace([" ", "-"], "_", trim($input)));
$input = preg_replace("/[^a-z0-9_]/", "", $input);

if (empty($input)) {
    fwrite(STDERR, "No name provided, creation aborted!" . PHP_EOL);
    exit(1);
}

$appConfig = new AppConfig();
$rootFolder = $appConfig->getProjectRootFolder();

$migrationsConfig = new MigrationsConfig();
$migrationsFolder = $migrationsConfig->getMigrationsFolder();

$fileName = date("ymdHis") . "_" . $input . ".sql";
$filePath = $migrationsFolder . $fileName;

if (file_exists($fileName)) {
    fwrite(STDERR, "A migration with this name already exists, creation aborted!" . PHP_EOL);
    exit(1);
}

if (strlen($fileName) - 4 > 255) {
    fwrite(STDERR, "The name given is too big, creation aborted!" . PHP_EOL);
    exit(1);
}

touch($filePath);

$relativeFilePath = str_replace($rootFolder, "", $filePath);
echo "File created at " . $relativeFilePath . PHP_EOL;
