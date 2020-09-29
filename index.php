<?php
//
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

date_default_timezone_set('Asia/Kathmandu');

//phpQuery::$debug = true;
require_once('curlFunctions.php');
require_once('OTScrapper.php');

//Starts
echo "Started at : " ,date("Y-m-d H:i:s") , "\n";
logInfo("Started at : ". date("Y-m-d H:i:s"));

//Scraps
$filename = __DIR__ . '/downloads/OT_' . date('Y_m_d_His') . '.csv';

if (file_exists($filename)) {
    unlink($filename);
}
$ot_scrapper = new OTScrapper($filename);
$status = $ot_scrapper->scrap();

echo ($status) ? "Successfully scrapped \n" :"Failed scrapped \n";

//Ends
echo "End at : " ,date("Y-m-d H:i:s") , "\n";
logInfo("End at : " . date("Y-m-d H:i:s"));
