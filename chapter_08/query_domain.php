#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/23/18
 * Time: 1:57 PM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); # $sdbClientArguments

use Aws\SimpleDb\SimpleDbClient;

$sdb = new SimpleDbClient($sdbClientArguments);


$query = "select * from " . BOOK_FILE_DOMAIN . " where itemName() like '%items%'";

try {
    $res = $sdb->select(['SelectExpression' => $query]);
} catch (Exception $e) {
    print("Unable to query with 'select': " . $e->getMessage() . "\n");
    exit($e->getCode());
}

if (count($res) <= 1) die("No results from query.");
// At this point the result ($res) has at least one actual result.
foreach ($res['Items'] as $item) {
    foreach ($item['Attributes'] as $attribute) {
        print ($attribute['Name'] . ": " . $attribute['Value'] . ", ");
    }
    print("\n");
}
exit(0);
?>