<?php

header("Content-type: text/html; charset=utf-8");
include_once 'function.php';

// PDO初期化
class DB{

	function construct($db_path='sqlite:database/contents.db'){
		try{
			$dbh = new PDO($db_path);
			$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}catch (PDOException $e){
			print('Connection failed:'.$e->getMessage());
			die();
		}
		return $dbh;
	}

	function dc1_construct(){
		$dsn = 'mysql:dbname=xxxxxx;host=xxxxxx';
		$user = 'xxxxxxx';
		$password = 'xxxxxxx';
		try{
			$dbh = new PDO($dsn, $user, $password);
		}catch (PDOException $e){
			print('Error:'.$e->getMessage());
			die();
		}
		return $dbh;
	}
}

// テーブルの存在確認
function table_exist($dbh,$tablename){
	$sql="SELECT count(*) FROM sqlite_master WHERE type='table' AND name='".$tablename."'";
	$sth = $dbh->query($sql);
	$count = $sth->fetchAll(PDO::FETCH_COLUMN);
	if($count==0){
		return true;
	}
	return false;
}

function table_delete($dbh,$table){
	try{
		$sql="DELETE FROM '".$table."'";
		// $sth = $dbh->prepare($sql);
		// $sth -> bindValue(':id',$table, PDO::PARAM_STR);
		$dbh->query($sql);
		$sql = "VACUUM;";
		$dbh->query($sql);
	}catch(PDOException $e){
		print('テーブル【'.$table.'】を削除できませんでした。'.$e->getMessage());
		die();
	}
}

// excelアルファベット順に移動　A,B…AA,AB…
function excelColumnRange($lower, $upper) {
	++$upper;
	for ($i = $lower; $i != $upper; ++$i) {
		yield $i;
	}
}

// 自動ダウンロード
function download_file($path_file)
{
	// mb_convert_encoding ($path_file,'SJIS','UTF-8');
	// ファイルの存在確認 
	if(!file_exists($path_file)) {
		die("File(".$path_file.") がありません！");
	}
	// オープンできるか確認 
	if(!($fp = fopen($path_file, "r"))) {
		die("file(".$path_file.")が開けません！");
	}
	fclose($fp);
	// ファイルサイズの確認 
	if(($content_length = filesize($path_file)) == 0) {
		die("ファイルサイズが 0.(".$path_file.")です。でかすぎます。");
	}
	// ダウンロード用のHTTPヘッダ送信 
	header("Content-Disposition: inline; filename=\"".basename(mb_convert_encoding ($path_file,'SJIS','UTF-8'))."\"");
	header("Content-Length: ".$content_length);
	header("Content-Type: application/octet-stream");
	ob_end_clean();
	// ファイルを読んで出力 
	if(!readfile($path_file)) {
		die("Cannot read the file(".$path_file.")");
	}
}

// ファイル更新日を取得
function showlastfiletime($filename,$ymd)
{
	if(file_exists($filename)){
		$filedate = date($ymd, filemtime($filename));
	}else{
		$filedate='ファイルが存在しません';
	}
	return $filedate;
}

function publisher_pickup($dbh,$pub_id,$SheetName){
	try{
		// 出版社IDから出版社を取得
		$sql="SELECT * FROM 'publisher' WHERE `出版社ID`=:id";
		$sth = $dbh->prepare($sql);
		$sth -> bindValue(':id',$pub_id, PDO::PARAM_STR);
		$sth->execute();
		$data = $sth->fetchAll(PDO::FETCH_COLUMN,1);
		return $data;
	}catch(PDOException $e){
		print('【'.$SheetName.'】の【'.$pub_id.'】のデータにアクセスできません:'.$e->getMessage());
		die();
	}
}

function magazine_pickup($dbh,$magazine_id,$SheetName){
	try{
		// 雑誌IDから雑誌名を取得
		$sql="SELECT * FROM 'magazine' WHERE `雑誌ID`=:id";
		$sth = $dbh->prepare($sql);
		$sth -> bindValue(':id',$magazine_id, PDO::PARAM_STR);
		$sth->execute();
		$data = $sth->fetchAll(PDO::FETCH_COLUMN,1);
		return $data;
	}catch(PDOException $e){
		print('【'.$SheetName.'】の【'.$magazine_id.'】のデータにアクセスできません:'.$e->getMessage());
		die();
	}
}

function auther_pickup($dbh,$auther,$SheetName){
	// 著者IDから関連人物を取得
	try{
		$pattern='#[a-z]+\_[0-9]{3}#';
		preg_match_all($pattern, $auther,$temp,PREG_SET_ORDER);
		// 著者名取得
		for ($i=0; $i < count($temp); ++$i) {
			if(isset($temp)){
				$data[$i]= $temp[$i][0];
				$sql="SELECT * FROM 'person' WHERE `著者ID`=:id";
				$sth = $dbh->prepare($sql);
				$sth -> bindValue(':id',$data[$i], PDO::PARAM_STR);
				// $sth -> bindValue(':id','%'.addcslashes($id,'\_%').'%', PDO::PARAM_STR);
				$sth->execute();
				$temp_a[$i] = $sth->fetchAll(PDO::FETCH_ASSOC);
			}
		}
		// 連想配列になっていると扱いづらいので配列入れ直す。
		for ($i=0; $i < count($temp_a); ++$i) {
			if(isset($temp_a)){
				$data[$i] = $temp_a[$i][0];
			}
		}
		return $data;
	}catch(PDOException $e){
		print('【'.$SheetName.'】の【'.$auther.'】のデータにアクセスできません:'.$e->getMessage());
		die();
	}
}

function contentstype_pickup($dbh,$id,$SheetName){
	// 作品IDからコンテンツタイプ取得
	try{
		// コンテンツタイプ取得
		$sql="SELECT `コンテンツタイプ` FROM 'contentstype' WHERE `作品ID`=:id";
		$sth = $dbh->prepare($sql);
		$sth -> bindValue(':id',$id, PDO::PARAM_STR);
		// $sth -> bindValue(':id','%'.addcslashes($id,'\_%').'%', PDO::PARAM_STR);
		$sth->execute();
		$data = $sth->fetchAll(PDO::FETCH_COLUMN);
		return $data[0];
	}catch(PDOException $e){
		print('【'.$SheetName.'】の【'.$id.'】のコンテンツタイプにアクセスできません:'.$e->getMessage());
		die();
	}
}

function searchContentsData($id,$kan,$SheetName){
// 作品IDからDBをさらって必要なデータをかき集める。
	try{
		$db=new DB();
		$dbh=$db->construct('sqlite:database/contents.db');
		$sql="SELECT `コンテンツタイトル`,`出版社ID`,`関連人物`,`代表者ID`,`コンテンツ説明（ロング）`,`カテゴリーID`,`ジャンル`,`雑誌ID`,`シリーズID`,`コピーライト` FROM `contents` WHERE `コンテンツID`='$id'";
		$sth = $dbh->prepare($sql);
		$sth->execute();
		$data = $sth->fetchAll(PDO::FETCH_ASSOC);
		if(!$data){
			echo '作品IDがDBにないか、IDが不正です！:'.$id."<br />";
			echo '無料フラグが立っている作品IDはDBに読ませていないので、いったん有料作品のIDでページを出力して、無料の作品IDに置換してください。<br />';
			die();
		}
		foreach ($data as $data) {
			$title=$data['コンテンツタイトル'];
			$pub_id=$data['出版社ID'];
			$auther=$data['関連人物'];
			$main_auther=$data['代表者ID'];
			$comment=$data['コンテンツ説明（ロング）'];
			$data['カテゴリーID']=strtolower($data['カテゴリーID']); //小文字に
			$category=$data['カテゴリーID'];
			$genre=$data['ジャンル'];
			$magazine_id=$data['雑誌ID'];
			$copyright=$data['コピーライト'];

			// 巻数
			if(!isset($kan)){
				$kan=1;
			}

			// コンテンツタイプを取得
			$type=substr($id, 0,2);
			// 書誌の著者データから著者IDと著者名を配列で取得。
			$auther=auther_pickup($dbh,$auther,$SheetName);
			// 出版社idから出版社名を取得
			$pub_name=publisher_pickup($dbh,$pub_id,$SheetName);
			// 雑誌idから雑誌名を取得 idがなかっらた両方空にする。
			if(isset($magazine_id)){
				$temp=magazine_pickup($dbh,$magazine_id,$SheetName);
				$magazine_name=@$temp[0];
			}else{
				$magazine_id='';
				$magazine_name='';
			}
			// カテゴリーIDからカテゴリ名を取得
			if($category=='boy'){$category_name='少年';}
			if($category=='girl'){$category_name='少女';}
			if($category=='man'){$category_name='青年';}
			if($category=='lady'){$category_name='女性';}

			$data=array('id'=>$id,'title'=>$title,'kan'=>$kan,'pub_id'=>$pub_id,'pub_name'=>$pub_name[0],'auther'=>$auther,'main_auther'=>$main_auther,'comment'=>$comment,'category'=>$category,'category_name'=>$category_name,'genre'=>$genre,'magazine_id'=>$magazine_id,'magazine_name'=>$magazine_name,'type'=>$type,'copyright'=>$copyright);
		}

		// 文字列置換
		$data['title']=StringEscape($data['title']);

		return $data;
	}catch(PDOException $e){
		print($id.'の作品データにアクセスできません:'.$e->getMessage());
		die();
	}
}

// --------------------------------------------------------------------------------
// GAとの繋ぎ
// --------------------------------------------------------------------------------
function set_phpgoogle(){
	//google-analytics-api.php
	require_once __DIR__ . '/phpgoogle/Google_Client.php';
	require_once __DIR__ . '/phpgoogle/contrib/Google_AnalyticsService.php';
	//クライアントID
	if(!defined('CLIENT_ID')){
		define('CLIENT_ID', 'xxxxxxx');
	}
	//メールアドレス
	if(!defined('SERVICE_ACCOUNT_NAME')){
		define('SERVICE_ACCOUNT_NAME', 'xxxxx');
	}
	//シークレットキー
	if(!defined('KEY_FILE')){
		define('KEY_FILE', __DIR__ . 'xxxxx');
	}
	//ビューID
	if(!defined('PROFILE_ID')){
		define('PROFILE_ID', 'xxxxx');
	}
	$client = new Google_Client();
	$client->setApplicationName("xxxxx");
	$client->setClientId(CLIENT_ID);
	$client->setAssertionCredentials(new Google_AssertionCredentials(
		SERVICE_ACCOUNT_NAME,
		array('https://www.googleapis.com/auth/analytics.readonly'),
		file_get_contents(KEY_FILE)
	));
	return $client;
}
// --------------------------------------------------------------------------------
// 書誌変換関連　DB読み込み系　jsonパースなど
// --------------------------------------------------------------------------------
// csv・tsvをSQLiteに取り込む際の正規化。
function fgetcsv_reg (&$handle, $length = null, $d = "\t", $e = '"',$rn = "\n") {
	$_line = "";
	$_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
	$_line = preg_replace('#^"#', '', $_line);
	$_line = preg_replace('#"'.$rn.'$#', '', $_line);
	$_csv_data = explode($e.$d.$e ,$_line);
	// SQLite3::escapeStringはバイナリセーフじゃないが、動的にSQL文を生成するため利用する。
	$_csv_data = array_map('SQLite3::escapeString',$_csv_data);
	return empty($_line) ? false : $_csv_data;
}

function StringEscape($string){
	$string=str_replace('"', '”', $string);
	$string=str_replace("\n", '', $string);
	$string=str_replace('\'', '’', $string);
	$string=str_replace("\t", '', $string);
	$string=str_replace('&', '＆', $string);
	$string=str_replace(',', '，', $string);
	$string=str_replace(';', '；', $string);
	return $string;
}

// CSV、TSVからDBに読み込み テーブル、見出しを作成
function TextToDB($option){
	$filename=$option['InputTxt'];
	$dbh=$option['SqliteDatabaseObj'];
	$SourceTable=$option['TableName'];
	$Delim=$option['Delimita'];
	$Kakomi=$option['Kakomi'];
	$key_row=$option['KeyRow'];

	$fp = tmpfile();
	fwrite($fp, mb_convert_encoding(file_get_contents('xxxxxx'), 'UTF-8', 'sjis-win'));
	rewind($fp);
	$insert_count=0;
	$insert_error_count=0;
	$line_count=0;
	while ($data=fgetcsv_reg($fp,null,$Delim,$Kakomi)) {
		// 文字列エスケープ
		$data = array_map("StringEscape", $data);
		// 見出し列をkeyに
		if($line_count==$key_row){
			$sql_midasi='';
			$key_array=array();
			$key_array=$data;
			// $key_array=implode($Delim,$data);
			foreach ($key_array as $key => $value) {
				$sql_midasi=$sql_midasi.'['.$value.'] text,';
			}
			$sql_midasi=substr($sql_midasi, 0,-1);
			$sql = 'CREATE TABLE ['.$SourceTable.'] ('.$sql_midasi.')';
			$dbh->query($sql);
			++$line_count;
			continue;
		}
		$value_array=array();
		// 書誌連結の前処理として、MBJ仕様のため一部カラムにだけ使われている囲み文字のダブルクォートがあったら、
		// カンマを消して、次のカラムと連結する。
		for ($i=0; $i < count($data); $i++) { 
			if(preg_match('#^"#', $data[$i])){
				$data[$i] = ltrim($data[$i],'"').'，'.rtrim($data[$i + 1],'"');
			}
		}
		for ($i=0; $i < count($data); $i++) { 
			if(preg_match('#"$#', $data[$i])){
				unset($data[$i]);
			}
		}
		$data = array_merge($data);
		// 書誌を配列に入れる。
		$value_array = implode($Delim,$data);
		$upload_data = "'".$value_array."'";
		$upload_data = preg_replace("#$Delim#","','",$upload_data);
		$sql = 'INSERT INTO '.$SourceTable.' VALUES('.$upload_data.');';
		try{
			$dbh->beginTransaction();
			$sth = $dbh->prepare($sql);
			$flag=$sth->execute();
			$dbh->commit();
		}catch (PDOException $e) { 
			$dbh->rollBack();
			echo "PDO Error: ".$e->getMessage();
			die();
		}catch (Exception $ex) {
			$dbh->rollBack();
			echo "Error: ".$ex->getMessage();
			die();
		}
	}
	fclose($fp);
}


// エクセルからDBに読み込み テーブル、見出しを作成
function ExcelToDB($option) {
	$ExcelobjSheet=$option['ExcelObjSheet'];
	$dbh=$option['SqliteDatabaseObj'];
	$SourceTable=$option['TableName'];
	$key_row=$option['KeyRow'];
	$value_row=$option['ValueRow'];
	$key_column=$option['KeyColumn'];

	$dbh->beginTransaction();
	try{
		$c=0;
		$sql_midasi='';
		$midasi_array=array();
		// 見出しデータ
		while($midasi=$ExcelobjSheet->getCellByColumnAndRow($c,$key_row)->getValue()){
			if(!$midasi){break;}
			$midasi_array[]=StringEscape($midasi);
			++$c;
		}
		foreach ($midasi_array as $key => $value) {
			$sql_midasi=$sql_midasi.'['.$value.'] text,';
		}
		$sql_midasi=substr($sql_midasi, 0,-1);
		$sql = 'CREATE TABLE ['.$SourceTable.'] ('.$sql_midasi.')';
		$dbh->query($sql);
		// 書誌データ
		// キーカラムに空データがでるまでまわす。
		while($ExcelobjSheet->getCellByColumnAndRow($key_column,$value_row)->getValue()){
			// データを1行ずつ集めてDB挿入
			$data_array=array();
			for ($c=0; $c < count($midasi_array); $c++) { 
				$data=$ExcelobjSheet->getCellByColumnAndRow($c,$value_row)->getValue();
				$data=StringEscape($data);
				$data_array[]=$data;
			}
			$sql_keys=implode('\',\'', $midasi_array);
			$sql_values=implode('\',\'', $data_array);
			$sql='INSERT INTO '.$SourceTable.'(\''.$sql_keys.'\') VALUES(\''.$sql_values.'\');';
			$dbh->query($sql);
			++$value_row;
		}
	}catch (PDOException $e) { 
		$dbh->rollBack();
		echo "PDO Error: ".$e->getMessage();
		die();
	}catch (Exception $ex) {
		$dbh->rollBack();
		echo "Error: ".$ex->getMessage();
		die();
	}
	$dbh->commit();
}

// テーブルの存在を確認してあればdrop
function ExistTableDrop($option){
	$dbh=$option['SqliteDatabaseObj'];
	$SourceTable=$option['TableName'];
	try{
		$sql='SELECT count(*) FROM sqlite_master WHERE type=\'table\' AND name=\''.$SourceTable.'\'';
		$sth = $dbh->prepare($sql);
		$sth->execute();
		$tableflag = $sth->fetchAll(PDO::FETCH_COLUMN);
		if($tableflag[0]!='0'){
			$sql='DROP TABLE '.$SourceTable;
			$dbh->query($sql);
			$sql='VACUUM';
			$dbh->query($sql);
		}
	}catch (PDOException $e) { 
		$dbh->rollBack();
		echo "PDO Error: ".$e->getMessage();
		die();
	}catch (Exception $ex) {
		$dbh->rollBack();
		echo "Error: ".$ex->getMessage();
		die();
	}
}

function JsonParse($path){
	$json=file_get_contents($path);
	// $json=mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
	$string_array=json_decode($json,true);
	if ($string_array === NULL) {
		echo $path.'のパースに失敗しました！';
		die();
	}
	return $string_array;
}

//　yyyymmddをソク読み書誌の日付フォーマットに変換
function DateSokuyomi($datestring){
	$date=array();
	$date[]=substr($datestring,0,4);
	$date[]=(int)substr($datestring,4,2);
	$date[]=(int)substr($datestring,-2,2);
	$temp=implode('/', $date);
	return $temp;
}

// フォルダがなかったら作成
function noExistsMakeFolder($folderpath){
	if(!file_exists($folderpath)){
		mkdir($folderpath, 0755);
	}
}
