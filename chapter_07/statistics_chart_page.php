#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: U6033350
 * Date: 1/14/18
 * Time: 12:43 AM
 */

error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once('../include/book.inc.php'); // $cWclientArguments

use Aws\CloudWatch\CloudWatchClient;

$startDate_DT = new DateTime('now');
$endDate_DT   = new DateTime('now');
$startDate_DT->modify('-1 day');

$startDate = $startDate_DT->format('Y-m-d');
$endDate   = $endDate_DT->format('Y-m-d');

$period = IsSet($_GET['period']) ? $_GET['period'] : 15;
$start  = IsSet($_GET['start'])  ? $_GET['start']  : $startDate;
$end    = IsSet($_GET['end'])    ? $_GET['end']    : $endDate;
$period *= 60;

$charts = array(
    array('M' => 'NetworkIn',
        'U' => 'Bytes',
        'L' => 'Network In (Bytes)'),

    array('M' => 'NetworkOut',
        'U' => 'Bytes',
        'L' => 'Network Out (Bytes)'),

    array('M' => 'CPUUtilization',
        'U' => 'Percent',
        'L' => 'CPU Utilization (Percent)'),

    array('M' => 'DiskReadBytes',
        'U' => 'Bytes',
        'L' => 'Disk Read Bytes'),

    array('M' => 'DiskReadOps',
        'U' => 'Count',
        'L' => 'Disk Read Operations/Second'),

    array('M' => 'DiskWriteBytes',
        'U' => 'Bytes',
        'L' => 'Disk Write Bytes'),

    array('M' => 'DiskWriteOps',
        'U' => 'Count',
        'L' => 'Disk Write Operations/Second'),

);

$cW = new CloudWatchClient($cWclientArguments);

$opt = array('Namespace' => 'AWS/EC2', 'Period' => $period);
$statistics = array('Average','Minimum','Maximum','Sum');

$chartImages = array();
foreach ($charts as &$chart) {
    $measure = $chart['M'];
    $unit = $chart['U'];
    $label = $chart['L'];

    try {
        $res = $cW->getMetricStatistics([
            'MetricName' => $measure,
            'Statistics' => $statistics,
            'Unit' => $unit,
            'StartTime' => $start,
            'EndTime' => $end,
            'Namespace' => 'AWS/EC2',
            'Period' => $period
        ]);
    } catch (Exception $e) {
        print("Unable to get metric statistics: " . $e->getMessage() . "\n");
        exit($e->getCode());
    }

    print_r($res);

    $datapoints = $res['Datapoints'];
        //$res->body->GetMetricStatisticsResult->Datapoints->member;


    $dataRows = array();
    foreach ($datapoints as $datapoint) {
        $timestamp = (string)$datapoint->Timestamp;

        $dataRows[$timestamp] =
            array('Timestamp' => (string)$datapoint->Timestamp,
                'Units' => (string)$datapoint->Unit,
                'Samples' => (string)$datapoint->Samples,
                'Average' => (float)$datapoint->Average,
                'Minimum' => (float)$datapoint->Minimum,
                'Maximum' => (float)$datapoint->Maximum,
                'Sum' => (float)$datapoint->Sum);
    }
    ksort($dataRows);

    $averages = array();
    $minimums = array();
    $maximums = array();
    $sums = array();

    foreach ($dataRows as $dataRow) {
        $averages[] = $dataRow['Average'];
        $minimums[] = $dataRow['Minimum'];
        $maximums[] = $dataRow['Maximum'];
        $sums[] = $dataRow['Sum'];
    }

    if (empty($averages)) continue ; // getMetricStatistics() seem to be returning no useful data in this case.
    $chartMax = max(max($averages), max($minimums),
        max($maximums), max($sums));
    $scale = 100.0 / $chartMax;

    for ($i = 0; $i < count($averages); $i++) {
        $averages[$i] = (int)($averages[$i] * $scale);
        $minimums[$i] = (int)($minimums[$i] * $scale);
        $maximums[$i] = (int)($maximums[$i] * $scale);
        $sums[$i] = (int)($sums[$i] * $scale);
    }

    $average = implode(',', $averages);
    $minimum = implode(',', $minimums);
    $maximum = implode(',', $maximums);
    $sum = implode(',', $sums);

    // Combine arrays for use in chart
    $series = $average . '|' .
        $minimum . '|' .
        $maximum . '|' .
        $sum;

    $label = str_replace(' ', '+', $label);
    $colors = 'ff0000,00ff00,0000ff,800080';

    $chartURL = "http://chart.apis.google.com/chart";
    $chartURL .= '?chs=300x180';              // Chart size
    $chartURL .= '&cht=lc';                   // Line chart
    $chartURL .= '&chtt=' . $label;           // Label
    $chartURL .= '&chdlp=b';                  // Legend at bottom
    $chartURL .= '&chdl=Avg|Min|Max|Sum';     // Legend
    $chartURL .= '&chco=' . $colors;          // Colors
    $chartURL .= '&chd=t:' . $series;         // Data series

    $chartImages[] = $chartURL;

    $output_title = 'Chapter 7 Sample - Charts of CloudWatch ' .
        'Statistics';
    $output_message = "Charts of CloudWatch Statistics from ${start}" .
        " to ${end}";

    include '../include/statistics.html.php';
}
?>