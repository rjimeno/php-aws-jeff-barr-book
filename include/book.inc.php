<?php

define('BOOK_BUCKET', 'rjimeno-sitepoint-aws-cloud-book');
define('THUMB_BUCKET_SUFFIX', '-thumbs');
define('THUMB_SIZE', 200);
define('S3_ACL_PUBLIC', 'public-read');
require_once('chapter04.inc.php'); // $s3clientArgs
require_once('chapter06.inc.php'); // $sqsClientOptions

if (isset($argc) && $argc>1) {
    $bucketArgs = array(
        'Bucket'    => ($argv[1]=='-') ? BOOK_BUCKET : $argv[1],
    );
}

function getBucketObjects($s3, $bucket) {
    $objects = array();

    try {
        $items = $s3->getIterator('ListObjects', array(
            'Bucket' => $bucket
        ));
    } catch (AwsException $e) {
        die($e->getMessage());
    }

    foreach ($items as $object) {
        $objects[] = $object;
    }

    return $objects;
}

function guessType($file) {
    $info = pathinfo($file, PATHINFO_EXTENSION);
    switch (strtolower($info)) {
        case "jpg":
        case "jpeg":
            return "image/jpg";

        case "png":
            return "image/png";

        case "gif":
            return "image/gif";

        case "htm":
        case "html":
            return "text/html";

        case "txt":
            return "text/plain";

        default:
            return "text/plain";
    }
}

function thumbnailImage($imageBitsIn, $contentType){
    // $imageIn = ImageCreateFromString(($imageBitsIn));
    $imageIn = imagecreatefromstring(($imageBitsIn));
    if ($imageIn === false) {
        die("Unable to create image from string '${imageBitsIn}'.\n");
    }
    $inX = ImageSx($imageIn);
    $inY = ImageSy($imageIn);

    if ($inX > $inY) {
        $outX = THUMB_SIZE;
        $outY = (int) (THUMB_SIZE * ((float) $inY / $inX));
    } else {
        $outX = (int) (THUMB_SIZE * ((float) $inX / $inY));
        $outY = THUMB_SIZE;
    }

    $imageOut = @ImageCreateTrueColor($outX, $outY) or die('Cannot Initialize new GD image stream');
    ImageFill($imageOut, 0, 0, ImageColorAllocate($imageOut, 255,255,255));
    ImageCopyResized($imageOut, $imageIn, 0, 0, 0, 0, $outX, $outY, $inX, $inY);
    $fileOut = tempnam("/tmp", "aws") . ".aws";

    switch ($contentType) {
        case "image/jpg":
            $ret = ImageJPEG($imageOut, $fileOut, 100);
            break;

        case "image/png":
            $ret = ImagePNG($imageOut, $fileOut, 0);
            break;

        case "image/gif":
            $ret = ImageGIF($imageOut, $fileOut);
            break;

        default:
            unlink($fileOut);
            return false;
    }

    if (!$ret) {
        unlink($fileOut);
        return false;
    }

    $imageBitsOut = file_get_contents($fileOut);
    unlink($fileOut);
    return $imageBitsOut;
}


function list_distributions($cf){
    try {
        $res = $cf->listDistributions([]);
    } catch (AwsException $e) {
        echo "Could not retrieve list of CloudFront distributions\n";
        exit($e->getMessage());
    }

    $distributionList = $res['DistributionList'];
    $items = $distributionList['Items'];
    $r=[];
    foreach ($items as $distribution) {
        $id = $distribution['Id'];
        $domainName = $distribution['DomainName'];
        $origins = $distribution['Origins'];
        $originItems = $origins['Items'];
        $DNs = [];
        foreach ($originItems as $oi) {
            array_push($DNs, $oi['DomainName']);
        }
        $origin = implode(', ', $DNs);
        array_push($r, [
            'Id' => $id,
            'DomainName' => $domainName,
            'Origins' => $origin
        ]);
    }
    return $r;
}

function findDistributionForBucket($cf, $bucket) {
    try {
        //$res = $cf->listDistributions([]);
        $res = list_distributions($cf);
    } catch (\Aws\Exception\AwsException $e) {
        die($e->getMessage());
    }

    $needle = $bucket . ".";
    $needle = $bucket;
    // $distributions = $res->body->DistributionSummary;

    foreach ($res as $distribution) {
        if (substr($distribution['Origins'], 0, strlen($needle)) == $needle) {
            return $distribution;
        }
    }
    return null;
}

function createVolume($ec2, $az, $size) {
    try {
        $res = $ec2->createVolume([
            'AvailabilityZone' => $az,
            'Size' => $size,
        ]);
    } catch (Exception $e) {
        echo "Unable to create volume: ", $e->getMessage(), "\n";
        exit($e->getCode());
    }
    return $res;
}

?>

