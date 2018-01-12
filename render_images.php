#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/10/18
 * Time: 2:17 PM
 */

error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once('include/book.inc.php'); // $s3clientArgs & $sqsClientOptions.

define('DEBUG', false); // Make it true to show debug messages.
define('BORDER_LEFT', 12);
define('BORDER_RIGHT', 12);
define('BORDER_TOP', 12);
define('BORDER_BOTTOM', 12);
define('IMAGES_ACROSS', 4);
define('IMAGES_DOWN', 4);
define('GAP_SIZE', 6);

use Aws\Sqs\SqsClient;
use Aws\S3\S3Client;

$sqs = new SqsClient($sqsClientOptions);
$s3 = new s3Client($s3clientArgs);

$queueRenderer = findQueueURL($sqs, RENDER_QUEUE);

while (true) {
    $message = pullMessage($sqs, $queueRenderer);

    if ($message != null) {
        $messageDetail = $message['MessageDetail'];
        $receiptHandle = $message['ReceiptHandle'];
        $imageKeys = $messageDetail['Data'];
        $pageTitle = $messageDetail['PageTitle'];

        print("Processing message with " . count($imageKeys) . " images:\n");

        $outX = BORDER_LEFT + BORDER_RIGHT + (IMAGES_ACROSS * THUMB_SIZE) + ((IMAGES_ACROSS - 1) * GAP_SIZE);
        $outY = BORDER_TOP + BORDER_BOTTOM + (IMAGES_DOWN * THUMB_SIZE) + ((IMAGES_DOWN - 1) * GAP_SIZE);

        $imageOut = ImageCreateTrueColor($outX, $outY);

        ImageFill($imageOut, 0, 0, ImageColorAllocate($imageOut,255, 255, 255));
        ImageRectangle($imageOut, 0, 0, $outX -1, $outY -1,
            ImageColorAllocate($imageOut,0, 0, 0));

        $nextX = BORDER_LEFT;
        $nextY = BORDER_TOP;

        foreach ($imageKeys as $imageKey) {
            print(" Fetch image '${imageKey}'.\n");
            $image = $s3->getObject([ 'Bucket' => BOOK_BUCKET, 'Key' => $imageKey]);

            DEBUG && print("   BODY:\n");
            DEBUG && print_r($image['Body']->getContents());
            DEBUG && print("   BODY.\n");
            try {
                $imageBits = ImageCreateFromString($image['Body']->getContents());
            } catch (Exception $e) {
                print("Unable to create image from '" . $image['Body'] . "': " . $e->getMessage() . "\n");
                exit($e->getCode());
            }

            print(" Render image at ${nextX}, ${nextY},");
            ImageCopy($imageOut, $imageBits, $nextX, $nextY, 0, 0, ImageSx($imageBits), ImageSy($imageBits));

            ImageRectangle($imageOut, $nextX, $nextY,
                $nextX + ImageSx($imageBits), $nextY + ImageSy($imageBits),
                ImageColorAllocate($imageOut, 0, 0, 0));

            $nextX += THUMB_SIZE + GAP_SIZE;
            if (($nextX + THUMB_SIZE) > $outX) {
                $nextX = BORDER_LEFT;
                $nextY += THUMB_SIZE + GAP_SIZE;
            }
        }

        $imageFileOut = tempnam('/tmp', 'aws') . '.png';
        ImagePNG($imageOut, $imageFileOut, 0);
        $imageBitsOut = file_get_contents($imageFileOut);
        unlink($imageFileOut);

        $key = 'page_image_' . md5($pageTitle) . '.png';

        if (uploadObject($s3, BOOK_BUCKET, $key, $imageBitsOut, S3_ACL_PUBLIC)) {
            print(" Stored final image in S3 using key '${key}'.\n");
            print_r($messageDetail['History']);

            try {
                $res = $sqs->deleteMessage([
                    'QueueUrl' => $queueRenderer,
                    'ReceiptHandle' => $receiptHandle
                ]);
            } catch (Exception $e) {
                echo "Unable to delete message with handle '${receiptHandle}' from queue '${queueRenderer}': ", $e->getMessage(), "\n";
                exit($e->getCode());
            }
            print(" Delete message from render queue.\n");
        }
        print("\n");
    }
}

?>