<?php

include_once 'function.php';

// config.json呼び出し
$jsonpath = 'config.json';
$config = JsonParse($jsonpath);

// ダウンロード
$exportfilepath = './output/'.$config['取得年月'].'_KPI.xlsx';
download_file($exportfilepath);
