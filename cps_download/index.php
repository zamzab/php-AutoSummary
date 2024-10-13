<?php

require_once "../function.php";

header("Content-type: text/html; charset=utf-8");
setlocale(LC_ALL, 'ja_JP.UTF-8');

// config.json呼び出し
$jsonpath = '../config.json';
$config = JsonParse($jsonpath);
define('TARGET_YEARMONTH',$config['取得年月']);

// 接続を確立する
$conn_id = ftp_connect('xx.xx.xx.xx');
$login_result = ftp_login($conn_id, 'xxxxxx', 'xxxxxx');
// pointダウンロード
echo "cps_point.csv download...\n";
ob_flush();
flush();
sleep(1);
$datecount = TARGET_YEARMONTH.'01';
// $datecount = date('Ym01',strtotime(date('Ym01') . '-1 month'));
while(@ftp_get($conn_id, './cps_point/'.(string)$datecount.'_point_history_log.csv.tar.gz', '/operation_data/history/point/'.(string)$datecount.'_point_history_log.csv.tar.gz', FTP_BINARY)){
	// echo (string)$datecount.'_point_history_log.csv.tar.gz<br />';
	(int)$datecount++;
}
// subscription_pointダウンロード
echo "cps_subscription.csv download...\n";
ob_flush();
flush();
sleep(1);
$datecount = TARGET_YEARMONTH.'01';
// $datecount = date('Ym01',strtotime(date('Ym01') . '-1 month'));
while(@ftp_get($conn_id, './cps_subscription/'.(string)$datecount.'_subscription_history_log.csv.tar.gz', '/operation_data/history/subscription/'.(string)$datecount.'_subscription_history_log.csv.tar.gz', FTP_BINARY)){
	// echo (string)$datecount.'_subscription_history_log.csv.tar.gz<br />';
	(int)$datecount++;
}
// 日数を取得
$getumatu = (int)$datecount;
// 接続を閉じる
ftp_close($conn_id);

// 解凍
echo "cps data extract...\n";
ob_flush();
flush();
sleep(1);
$FileCont = new FileHacks();
$datecount = TARGET_YEARMONTH.'01';
// $datecount = date('Ym01',strtotime(date('Ym01') . '-1 month'));
while((int)$datecount < $getumatu){
	$FileCont -> ZipExtract('./cps_subscription/'.(string)$datecount.'_subscription_history_log.csv.tar.gz','./cps_subscription/');
	$FileCont -> ZipExtract('./cps_point/'.(string)$datecount.'_point_history_log.csv.tar.gz','./cps_point/');
	(int)$datecount++;
}
// テキスト連結して保存
echo "cps data save...\n";
ob_flush();
flush();
sleep(1);
$datecount = TARGET_YEARMONTH.'01';
// $datecount = date('Ym01',strtotime(date('Ym01') . '-1 month'));
$point_csv='';
$subscription_csv='';
$tmp='';
while((int)$datecount < $getumatu){
	$tmp = file_get_contents('./cps_subscription/'.(string)$datecount.'_subscription_history_log.csv');
	// x月1日分以外は見出し行削除
	if(!preg_match('#01$#',(string)$datecount)){
		$line = explode("\n", $tmp);
		unset($line[0]);
		$tmp = implode("\n", $line);
	}
	$subscription_csv .= $tmp;
	$tmp = file_get_contents('./cps_point/'.(string)$datecount.'_point_history_log.csv');
	if(!preg_match('#01$#',(string)$datecount)){
		$line = explode("\n", $tmp);
		unset($line[0]);
		$tmp = implode("\n", $line);
	}
	$point_csv .= $tmp;
	(int)$datecount++;
}
if(!$FileCont->SaveFile($subscription_csv,'./cps_subscription/cps_subscription.csv')){
	echo 'cps_subscription.csvを作成できませんでした！';
}
if(!$FileCont->SaveFile($point_csv,'./cps_point/cps_point.csv')){
	echo 'cps_point.csvを作成できませんでした！';
}

// 元データ削除
$datecount = TARGET_YEARMONTH.'01';
// $datecount = date('Ym01',strtotime(date('Ym01') . '-1 month'));
while((int)$datecount < $getumatu){
	unlink('./cps_subscription/'.(string)$datecount.'_subscription_history_log.csv.tar.gz');
	unlink('./cps_subscription/'.(string)$datecount.'_subscription_history_log.csv');
	unlink('./cps_point/'.(string)$datecount.'_point_history_log.csv.tar.gz');
	unlink('./cps_point/'.(string)$datecount.'_point_history_log.csv');
	(int)$datecount++;
}


// ファイル操作
class FileHacks{
	function SaveFile($data,$SavePath,$fopen_option='w+'){
		/**
		* ファイル保存
		* @param string $data          保存データ
		* @param string $SavePath      保存先パス
		* @param string $fopen_option  まんま。
		* @return bool  処理の成否
		*/
		if(!$file = fopen($SavePath, $fopen_option)){
			echo $SavePath.'を作成できませんでした。';
			return false;
		}
		fputs($file, $data);
		fclose($file);
		return true;
	}
	function ZipExtract($zip_path,$extract_path = './'){
		/**
		* zip、gz解凍
		* @param string $zip_path       解凍するファイルパス
		* @param string $extract_path   保存先パス
		* @return bool  処理の成否
		*/
		// パスのチェック
		if(file_exists(is_dir($zip_path))){
			die("ZIPファイルが見つかりません");
		}
		if(!is_file($zip_path) || !is_readable($extract_path))
		{
			die("読み込みに失敗しました。");
		}
		if(!is_dir($extract_path) || !is_writable($extract_path))
		{
			die("書き込みに失敗しました。");
		}
		// 拡張子取得
		$kk = substr($zip_path, strrpos($zip_path, '.') + 1);
		switch ($kk) {
			case 'gz':
				// gz解凍
				$gzip = file_get_contents($zip_path);
				$rest = substr($gzip, -4); 
				$zipsize = end(unpack("V", $rest));
				$zd = gzopen($zip_path, 'r');
				$contents = gzread($zd,$zipsize);
				gzclose($zd);
				//書き込む
				$outputfilename = $extract_path.basename($zip_path);
				if(!$file = fopen($outputfilename,"w+")){
					echo $outputfilename.'を作成できませんでした。';
					return false;
				}
				fputs($file,$contents);
				fclose($file);
				// tar解除
				$p = new PharData($outputfilename);
				$p->extractTo($extract_path, null, TRUE);
			default:
				// ZIP解凍
				$zip = new ZipArchive();
				$res = $zip->open($zip_path,ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
				if ($res === true) {
					$zip->extractTo($extract_path);
					$zip->close();
				} else {
					echo $zip_path.'のファイル解凍に失敗しました！：'.$res;
					return false;
				}
				break;
		}
		return true;
	}
	function ZipCreate($addfiles_path,$zip_path){
		/**
		* zip圧縮
		* @param string $addfiles_path   追加するファイルパス
		* @param string $zip_path        圧縮ファイルパス
		* @return bool  処理の成否
		*/
		$zip = new ZipArchive();
		$res = $zip->open($zip_path, ZipArchive::CREATE);
		if ($res === true) {
			if(!$addfiles_path){
				echo '圧縮対象ファイルが指定されていません！';
				return false;
			}
			foreach ($addfiles_path as $key => $path) {
				$zip->addFile($path,basename($path));
			}
			$zip->close();
		} else {
			echo $zip_path.'のファイル圧縮に失敗しました！：'.$res;
			return false;
		}
		return true;
	}
	function ZipCreateFolders($dir, $inner_path, $create_empty_dir=false){
		/**
		* フォルダ構造ごとzip圧縮
		* @param string $dir              ディレクトリパス
		* @param string $inner_path       zipファイル中のディレクトリパス
		* @param bool   $create_empty_dir 空ディレクトリもディレクトリを作成するか
		* @return bool  処理の成否
		*/
		$items = \array_diff(\scandir($dir), ['.','..']);
		$item_count = \count($items);
		if($create_empty_dir || $item_count > 0){
			$this->addEmptyDir($inner_path);
		}
		// 追加するものがないならここで終了する
		if($item_count === 0) return true;
		foreach($items as $_item){ // forで行うなら$itemsは一旦array_values()を通したほうがいい
			$_path = $dir . DIRECTORY_SEPARATOR . $_item;
			$_item_inner_path = $inner_path . DIRECTORY_SEPARATOR . $_item;
			// ディレクトリの場合は再帰的に処理する
			if(\is_dir($_path)){
				$_r = \call_user_func( // "$this->addDir"より保守的に好ましい
					[$this, __FUNCTION__], $_path, $_item_inner_path);
				if(!$_r) return false;
			}
			// ファイルの場合でかつ処理に失敗したとき
			else if(!$this -> addFile($_path, $_item_inner_path) && !$this -> on_recursive_error($dir, $inner_path, $create_empty_dir)){
				return false;
			}
		}
		return true;
	}
	private function on_recursive_error($parent_dir, $parent_inner_path, $create_empty_dir){
		/**
		* 再帰的処理のときにエラーが生じた場合の処理
		* @param string $parent_dir              ディレクトリパス
		* @param string $parent_inner_path       zipファイル中のディレクトリパス
		* @param bool   $create_empty_dir        空ディレクトリもディレクトリを作成するか
		* @return bool Falseなら中断
		*/
		// 自由に定義してください
		return false;
	}
	function StreamDownload($Filename,$Filepath){
		/**
		* ファイルをストリームに出力
		* @param string $FileName     ファイル名
		* @param string $Filepath     ファイルパス
		*/
		header('Content-Type: application/zip; name="' . $FileName . '"');
		header('Content-Disposition: attachment; filename="' . $FileName . '"');
		header('Content-Length: '.filesize($Filepath));
		echo file_get_contents($Filepath);
	}

}
