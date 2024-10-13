<?php

require_once 'libclass.php';
require_once "../function.php";

// header("Content-type: text/html; charset=utf-8");
// setlocale(LC_ALL, 'ja_JP.UTF-8');

// config.json呼び出し
$jsonpath = '../config.json';
$config = JsonParse($jsonpath);

$cobj = new curl_Client();
$curl = $cobj -> init();
$Tenkawa = new TenkawaHacks();
$FileController = new FileHacks();

// basic認証とログインは実質セット。
$options = $Tenkawa -> BasicCheck('xxxxx','xxxxxx');
$cobj -> execute($curl,$options);
$options = $Tenkawa -> Login('xxxxxx','xxxxxx');
$cobj -> execute($curl,$options);
// 出版社tsvダウンロード
// $options = $Tenkawa -> Download_MasterData('publisher');
// $data = $cobj -> execute($curl,$options);
// if(!$FileController -> SaveFile($data,'./m_publisher.tsv','w+')){
// 	die();
// }

// subscriptionダウンロード
echo "subscription.tsv download...\n";
ob_flush();
flush();
sleep(1);
$options = $Tenkawa -> Download_Subscription($config['取得年月'].'01',$config['取得年月'].'31'); //ひと月分
$data = $cobj -> execute($curl,$options);
if(!$FileController -> SaveFile($data,'./tsv/subscription.tsv.gz','w+')){
	die();
}
if(!$FileController -> ZipExtract('./tsv/subscription.tsv.gz','./tsv/')){
	die();
}
unlink('./tsv/subscription.tsv.gz');

// 前月subscriptionダウンロード（【顧客リピート率】抽出のため）
echo "ante_subscription.tsv download...\n";
ob_flush();
flush();
sleep(1);
// $StartDate = date('Ym01',strtotime(date('Ym01') . '-2 month'));
// $EndDate = date('Ym31',strtotime(date('Ym01') . '-2 month'));
$StartDate = date('Ym01',strtotime($config['取得年月'].'01 -1 month'));
$EndDate = date('Ym31',strtotime($config['取得年月'].'01 -1 month'));
$options = $Tenkawa -> Download_Subscription($StartDate,$EndDate);
$data = $cobj -> execute($curl,$options);
if(!$FileController -> SaveFile($data,'./tsv/ante_subscription.tsv.gz','w+')){
	die();
}
if(!$FileController -> ZipExtract('./tsv/ante_subscription.tsv.gz','./tsv/')){
	die();
}
$cobj -> close($curl);
unlink('./tsv/ante_subscription.tsv.gz');

