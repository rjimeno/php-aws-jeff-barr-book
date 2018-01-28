#!/usr/bin/env php

<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/27/18
 * Time: 6:46 PM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); # $sdbClientArguments

use Aws\SimpleDb\SimpleDbClient;

$sdb = new SimpleDbClient($sdbClientArguments);

$totalBytes = 0;
foreach([
    BOOK_FILE_DOMAIN,
    BOOK_FEED_DOMAIN,
    BOOK_FEED_ITEM_DOMAIN
] as $domain) {
    try {
        $res = $sdb->domainMetadata([
            'DomainName' => $domain
        ]);
    } catch (Exception $e) {
        print("Unable to obtain domain metadata: " . $e->getMessage() . "\n");
        exit($e->getCode());
    }

    $sizeBytes = $res->get('ItemNamesSizeBytes') + $res->get('AttributeNamesSizeBytes') + $res->get('AttributeValuesSizeBytes');
    print("\nDomain '${domain}' uses ${sizeBytes} of storage and has the following metadata:\n");
    print_r($res);
    $totalBytes += $sizeBytes;
}
print("\nTOTAL storage used by all domains together: ${totalBytes}.\n");
print("\nNOTE: The amount of storage used by each the domain is ItemNamesSizeBytes + AttributeNamesSizeBytes + AttributeValuesSizeBytes'.\n");
exit(0);

?>