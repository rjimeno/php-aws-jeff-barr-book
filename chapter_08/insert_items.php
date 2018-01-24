#!/usr/bin/env php

<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/23/18
 * Time: 10:34 AM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); # $sdbClientArguments

use Aws\SimpleDb\SimpleDbClient;

$sdb = new SimpleDbClient($sdbClientArguments);

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
        try {
            $res = $sdb->putAttributes([
                'DomainName' => BOOK_FILE_DOMAIN,
                'ItemName'   => $file,
                'Attributes' => $attrs
            ]);
        } catch (Exception $e) {
            print("Unable to insert item: " . $e->getMessage() . "\n");
            exit($e->getCode());
        }
        print("Inserted item '$file'.\n");
    }
}
closedir($dir);
exit(0); // All items were inserted successfully.
?>