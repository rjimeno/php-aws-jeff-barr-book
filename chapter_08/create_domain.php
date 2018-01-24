#!/usr/bin/env php

<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/22/18
 * Time: 9:52 PM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); # $sdbClientArguments

use Aws\SimpleDb\SimpleDbClient;

$sdb = new SimpleDbClient($sdbClientArguments);

foreach ([BOOK_FILE_DOMAIN, BOOK_FEED_DOMAIN, BOOK_FEED_ITEM_DOMAIN] as $domain) {
    try {
        $res = $sdb->createDomain([ 'DomainName' => $domain]);
    } Catch (Exception $e) {
        print("Unable to create domain: " . $e->getMessage() . "\n");
        exit($e->getCode());
    }
    print("Domain '${domain}' created.\n");
}
exit(0); // Success!

?>