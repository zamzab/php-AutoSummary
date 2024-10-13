<?php

header("Content-type: text/html; charset=utf-8");
include_once 'PHPExcel/PHPExcel.php';
include_once 'function.php';

// config.json呼び出し
$jsonpath = 'config.json';
$config = JsonParse($jsonpath);
define('SUBSCRIPTION_TABLE_NAME', $config['取得tsv']['subscription']['tablename']);
define('CONTENTS_TABLE_NAME', $config['取得tsv']['contents']['tablename']);
define('CUSTOMER_TABLE_NAME', $config['取得tsv']['customer']['tablename']);
define('TARGET_YEARMONTH',$config['取得年月']);

// DB削除
@unlink('database/database.db');
// insetするtsv
$fileset = $config['取得tsv'];
// pdo初期化
$db=new DB();
$dbh=$db->construct('sqlite:database/database.db');
$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
foreach ($fileset as $key => $insertfile) {
	echo $insertfile['tablename']." insert...\n";
	ob_flush();
	flush();
	sleep(1);
	$dbh->beginTransaction();
	$fp = tmpfile();
	$fp = fopen($insertfile['filepath'],'r');
	$insert_count=0;
	$insert_error_count=0;
	$line_count=0;
	while ($data = fgetcsv_reg($fp,null,$insertfile['delimita'],$insertfile['wrap'],$insertfile['eol'])) {
		// 1行目で見出し作成
		if($line_count == 0){
			if($insertfile['tablename'] == CUSTOMER_TABLE_NAME){
				$sql = 'CREATE TABLE `'.$insertfile['tablename'].'` (会員ID text unique,性別 text,生年月日 text,最終ログイン日 text,本登録日 text)';
				$dbh->query($sql);
			} else {
				$sql = midasi_create($data,$insertfile['tablename'],$insertfile['unique_column']);
				$dbh->query($sql);
				++$line_count;
				continue;
			}
		}
		// 不要なカラム削除＆献本ID除外
		if($insertfile['tablename'] == CUSTOMER_TABLE_NAME){
			$check = ExceptKenponID($config,$data);
			if(!$check){
				++$line_count;
				continue;
			}
			unset($data[1]); //メールアドレス
			unset($data[2]); //氏名
			unset($data[5]); //住所
			unset($data[8]);
			unset($data[9]);
			unset($data[10]);
			unset($data[11]);
			unset($data[12]);
		}
		// 献本データは無視
		if($insertfile['tablename'] == SUBSCRIPTION_TABLE_NAME){
			if(preg_match('#^CC#',$data[1])){
				++$line_count;
				continue;
			}
		}
		if($insertfile['tablename'] == CONTENTS_TABLE_NAME){
			// コンテンツ説明ロングなど、不要なカラムをnull
			$data[14]='';$data[15]='';$data[16]='';$data[17]='';$data[18]='';$data[19]='';$data[37]='';
			$data[27]='';$data[63]='';$data[64]='';
			// コンテンツタイプ1は無視。
			if($data[3]=='1'){
				++$line_count;
				continue;
			}
			// 価格ゼロ円は排除
			if(preg_match('#@0$#', $data[67])){
				++$line_count;
				continue;
			}
			// 削除フラグ1は無視。
			if($data[83]=='1'){
				++$line_count;
				continue;
			}
			// 未来配信分は除外 【表示期間開始日時分】が指定月より未来の場合。
			$tempDate = date('Ymt',strtotime(TARGET_YEARMONTH.'01'));
			if(strtotime(substr($data[22],0,8)) > strtotime($tempDate)){
				++$line_count;
				continue;
			}
		}
		// insert data作成
		$sql_temp = array();
		foreach ($data as $value) {
			$sql_temp[] = "'".$value."'";
		}
		$upload_data = implode(',', $sql_temp);
		try{
			$sql = 'INSERT OR IGNORE INTO `'.$insertfile['tablename'].'` VALUES('.$upload_data.');';
			$flag = $dbh->query($sql);
		} catch (PDOException $e){
			print('Error:'.$e->getMessage());
			die();
		}
		++$line_count;
	}
	$dbh->commit();
	fclose($fp);
}

// 見出し付きのtsvは、最初の行をDBのカラム名にする
function midasi_create($data,$tablename,$unique_column){
	$sql='';
	$sql_temp='';
	$column_count = 0;
	foreach ($data as $value) {
		$option = '';
		if($unique_column === $column_count){
			$option = ' UNIQUE';
		}
		$sql_temp[] = "'".$value."' TEXT".$option;
		++$column_count;
	}
	$temp = implode(',', $sql_temp);
	$sql = "CREATE TABLE '".$tablename."' (".$temp.");";
	return $sql;
}

// 献本ID除外
function ExceptKenponID($config,$customerdata){
	// 除外用献本ID読み込み
	$kenponid = file_get_contents($config['除外する献本ID']);
	$kenponid = explode("\r\n", $kenponid);
	$kenponid = array_filter($kenponid, "strlen");
	$kenponid = array_values($kenponid);
	foreach ($kenponid as $key => $value) {
		if($customerdata[0] === $value){
			return FALSE;
		}
	}
	return TRUE;
}
