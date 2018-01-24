#!/usr/bin/env php

<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/23/18
 * Time: 11:36 AM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); # $sdbClientArguments

use Aws\SimpleDb\SimpleDbClient;

function WriteBatch($sdb, &$items) {
    $data = [
        'DomainName' => BOOK_FILE_DOMAIN,
        'Items' => $items
    ];

    try {
        $res = $sdb->batchPutAttributes($data);
    } catch (Exception $e) {
        print("Unable to batch-insert items: " . $e->getMessage() . "\n");
        exit($e->getCode());
    }
    print("Inserted " . count($items) . " items.\n");
}

$sdb = new SimpleDbClient($sdbClientArguments);

$items = [];

$dir = opendir(".");
while (false !== ($file = readdir($dir))) {
    if (preg_match("/^[a-zA-Z0-9_-]*\.php$/", $file)) {
        $data = file_get_contents($file);
        $hash = md5($data);
        $size = filesize($file);

        $attrs = [
            ['Name' => 'Name', 'Value' => $file, 'Replace' => true],
            ['Name' => 'Hash', 'Value' => $hash, 'Replace' => true],
            ['Name' => 'Size', 'Value' => $size, 'Replace' => true]
        ];
        // For the sake of simplicity in this code itemName() is also stored as Name.
        $items[] = [
            'Name' => $file,
            'Attributes' =>$attrs,
        ];

        if (NUMBER_OF_ITEMS_IN_BATCH_INSERT == count($items)) {
            WriteBatch($sdb, $items);
            $items = [];
        }

    }
}
closedir($dir);

if (0 < count($items)) {
    WriteBatch($sdb, $items);
}

?>