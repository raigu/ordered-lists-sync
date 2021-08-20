<?php

require_once __DIR__ . '/../vendor/autoload.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->query('CREATE TABLE source (value TEXT NOT NULL)');
$pdo->query('CREATE TABLE target (value TEXT NOT NULL)');

// Seed sample data. Half to source and half to target.
$sths = $pdo->prepare('INSERT INTO source (value) VALUES (?)');
$sthi = $pdo->prepare('INSERT INTO target (value) VALUES (?)');
for ($i = 0; $i < 6; $i++) {
    if ($i === 0) {
        $sths->execute([chr(ord('A') + $i)]);
        $sthi->execute([chr(ord('A') + $i)]);
    } else if ($i % 2 == 0) {
        $sths->execute([chr(ord('A') + $i)]);
    } else {
        $sthi->execute([chr(ord('A') + $i)]);
    }
}

$source = function () use ($pdo) {
    foreach ($pdo->query('SELECT value FROM source ORDER BY value') as $row) {
        yield $row[0];
    }
};

$target = function () use ($pdo) {
    foreach ($pdo->query('SELECT value FROM target ORDER BY value') as $row) {
        yield $row[0];
    }
};

echo "Database state:" . PHP_EOL;
echo "Source: " . implode(',', iterator_to_array($source())) . PHP_EOL;
echo "Target: " . implode(',', iterator_to_array($target())) . PHP_EOL;

$add = function ($element) use ($sthi) {
    echo "ADD: {$element}\n" ;
    $sthi->execute([$element]);
};

$sthd = $pdo->prepare('DELETE FROM target WHERE value=?');
$remove = function ($element) use ($sthd) {
    echo "REMOVE: {$element}\n";
    $sthd->execute([$element]);
};

echo PHP_EOL;
echo "Executing synchronization..." . PHP_EOL;
$synchronization = new \Raigu\OrderedDataSynchronization\Synchronization();
$synchronization($source(), $target(), $add, $remove);

echo PHP_EOL;
echo "Database state again:" . PHP_EOL;
echo "Source: " . implode(',', iterator_to_array($source())) . PHP_EOL;
echo "Target: " . implode(',', iterator_to_array($target())) . PHP_EOL;
echo "Source and target are now identical.\n";