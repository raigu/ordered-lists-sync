<?php

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Demonstration how some data from internet can be downloaded and kept in sync.
 *
 * Demo downloads country codes and their ISO-3166-2 codes.
 * The source is a wiki page https://en.wikipedia.org/wiki/ISO_3166-2
 * It contains the list of countries already sorted by code.
 */

$pdo = new PDO('sqlite:demo.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->query('CREATE TABLE IF NOT EXISTS countries (code TEXT NOT NULL, name TEXT NOT NULL)');

/**
 * Generator returns country names from internet.
 * Countries are ordered by code and then by name.
 * The returned string is in format '<code><name>' ie the two first characters are the country code.
 */
function source(): Generator
{
    $ch = curl_init();
    try {

        curl_setopt($ch, CURLOPT_URL, 'https://en.wikipedia.org/wiki/ISO_3166-2');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $content = curl_exec($ch);
        if ($content === false) {
            throw new Exception('Could not download country names and codes from wiki page. ' . curl_error($ch));
        }
    } finally {
        curl_close($ch);
    }


    $dom = new DOMDocument();

    $use_errors = libxml_use_internal_errors(true);
    try {
        if ($dom->loadHTML($content, LIBXML_NOWARNING) === false) {
            throw new Exception('Source data loading failed. Could not parse the wiki page. ' . libxml_get_last_error());
        }
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($use_errors);
    }

    $xpath = new DOMXPath($dom);

    // we know the first one is the table we are interested in it.
    $tables = $xpath->query('//table');
    $table = $tables->item(0);
    $rows = $xpath->query("./tbody/tr/td/a[starts-with(@href, '/wiki/ISO_3166-2:')]/parent::td/parent::tr", $table);

    if ($rows === false or $rows->length === 0) { // something went wrong. Terminate in order to prevent deleting current target.
        throw new \Exception('Source data loading failed. Could not find companies data in wiki page content.');
    }

    foreach ($rows as $row) {
        $columns = $row->getElementsByTagName('td');
        $code = trim($columns[0]->nodeValue);
        $name = trim($columns[1]->nodeValue);

        yield $code . $name;
    }
}

/**
 * Generator returning country names from target database.
 * Countries are ordered by code and then by name.
 * The returned string is in format '<code><name>' ie the two first characters are the country code.
 */
function target($pdo): Generator
{
    $sql = 'SELECT code, name FROM countries ORDER BY code ASC, name ASC';
    foreach ($pdo->query($sql) as $row) {
        yield $row['code'] . $row['name'];
    }
}

$sthAdd = $pdo->prepare('INSERT INTO countries (code, name) VALUES (?, ?)');
$sthRemove = $pdo->prepare('DELETE FROM countries WHERE code=?');

$counts = [
    'added' => 0,
    'removed' => 0,
];

$synchronization = new \Raigu\OrderedListsSynchronization\Synchronization();
$synchronization(
    source(),
    target($pdo),
    function ($element) use ($sthAdd, &$counts) {
        $code = substr($element, 0, 2);
        $name = substr($element, 2);
        echo "+ {$code}" . PHP_EOL;
        $counts['added'] += 1;
        $sthAdd->execute([$code, $name]);
    },
    function ($element) use ($sthRemove, &$counts) {
        $code = substr($element, 0, 2);
        echo "- {$code}" . PHP_EOL;
        $counts['removed'] += 1;
        $sthRemove->execute([$code]);
    }
);

echo "Added: {$counts['added']}" . PHP_EOL;
echo "Removed: {$counts['removed']}" . PHP_EOL;
