<?php

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/service/Elastic.php';
require_once __DIR__ . '/service/ImportElastic.php';
use ParagonIE\EasyDB\Factory;

$config = parse_ini_file("config.ini", TRUE);


$dbMysql = Factory::create(
    'mysql:host='.$config['mysql']['host'].';dbname='.$config['mysql']['dbname'],
    $config['mysql']['username'],
    $config['mysql']['password']
);
$dbElastic = new \Application\Elastic($config['elastic']);


$importer = new \Application\ImportElastic($dbMysql, $dbElastic);

if ($argv[1] == "insert_all"){
    $importer->insertAll();
}
else if ($argv[1] == "insert_latest"){
    $importer->insertLatest();
}
else if ($argv[1] == "update"){
    $video_ids = [];
    for ($i = 2; $i< count($argv); $i++) {
        $video_ids[] = $argv[$i];
    }
    
    $importer->updateRecords($video_ids);
    
}
elseif ($argv[1] == "search") {
    $query = $argv[2];
    $from = $argv[3];
    $size = $argv[4];
    
    $body = '
    {
       "query": {
          "multi_match": {
                "query": "'. $query .'",
                "fields": [
                   "actors^6","title^5","tags^4","categories^3","description^2"
                ]
          }
       },
       "size": '.$size.',
       "from": '.$from.'
    }
    ';

    $data = $dbElastic->search('application','video',$body);
    var_dump($data["hits"]["hits"]);
    
}
