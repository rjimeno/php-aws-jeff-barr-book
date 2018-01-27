#!/usr/bin/env php

<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/24/18
 * Time: 4:09 PM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); # $sdbClientArguments

use Aws\SimpleDb\SimpleDbClient;

$sdb = new SimpleDbClient($sdbClientArguments);

$attrs = [
    [
        'Name' => 'ModTime',
        'Value' => '1516774335' // Erroneously required!!!
    ]
];

$query = "select * from " . BOOK_FILE_DOMAIN;
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

    try {
        $res2 = $sdb->deleteAttributes([
            'DomainName' => BOOK_FILE_DOMAIN,
            'ItemName'   => $itemName,
            'Attributes' => $attrs
        ]);
    } catch (Exception $e) {
        print("Unable to delete attribute '${itemName}: " . $e->getMessage() . "\n");
        exit($e->getCode());
    }
    print("Updated item '${itemName}'.\n");
}
exit(0);
?>

