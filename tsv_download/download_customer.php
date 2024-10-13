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

echo "m_customer.tsv download...\n";
ob_flush();
flush();
sleep(1);
// basic認証とログインは実質セット。
$options = $Tenkawa -> BasicCheck('xxxxx','xxxxxx');
$cobj -> execute($curl,$options);
$options = $Tenkawa -> Login('xxxxx','xxxxx');
$cobj -> execute($curl,$options);
// ダウンロード サーバキャッシュが足りないので2回に分ける。
$options = $Tenkawa -> Download_Customer('200901','201601');
$data = $cobj -> execute($curl,$options);
if(!$FileController -> SaveFile($data,'./tsv/m_customer1.tsv','w+')){
	die();
}
$options = $Tenkawa -> Download_Customer('201602',$config['取得年月']);
$data = $cobj -> execute($curl,$options);
if(!$FileController -> SaveFile($data,'./tsv/m_customer2.tsv','w+')){
	die();
}
// 連結
$temp_a = file_get_contents('./tsv/m_customer1.tsv');
$temp_b = file_get_contents('./tsv/m_customer2.tsv');
$temp = $temp_a . $temp_b;
if(!$FileController -> SaveFile($temp,'./tsv/m_customer.tsv','w+')){
	die();
}
$cobj -> close($curl);

