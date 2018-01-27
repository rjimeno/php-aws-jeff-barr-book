#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/23/18
 * Time: 4:30 PM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); # $sdbClientArguments

use Aws\SimpleDb\SimpleDbClient;

$sdb = new SimpleDbClient($sdbClientArguments);

$query = "select * from " . BOOK_FILE_DOMAIN;

// The code below was copied verbatim from query_domain.php:
try {
    $res1 = $sdb->select(['SelectExpression' => $query]);
} catch (Exception $e) {
    print("Unable to query with 'select': " . $e->getMessage() . "\n");
    exit($e->getCode());
}

if (count($res1) <= 1) die("No results from query.");
// At this point the result ($res) has at least one actual result.
foreach ($res1['Items'] as $item) {
    $itemName = $item['Name'];
    $file = $item['Attributes'][2]['Value'];
    // The line immediately above does not feel correct: The 2 there is
    // arbitrary and brittle. How about '$file = $itemName;' instead?

    try {
        $modTime = filemtime($file);
    } catch (Exception $e) {
        die("Unable to obtain the modification time of file '${file}'.\n");
    }

    $attrs = [
        [
            'Name' => 'ModTime',
            'Value' => sprintf("%010s", $modTime),
            'Replace' => false
        ]
    ];

    try {
        $res2 = $sdb->putAttributes([
            'DomainName' => BOOK_FILE_DOMAIN,
            'ItemName'   => $file,
            'Attributes' => $attrs
        ]);
    } catch (Exception $e) {
        print("Unable to update record for '${file}: " . $e->getMessage() . "\n");
        exit($e->getCode());
    }
    print("Updated item '$itemName'.\n");
}
exit(0);
?>