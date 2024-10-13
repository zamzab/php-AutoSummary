<?php

header("Content-type: text/html; charset=utf-8");
setlocale(LC_ALL, 'ja_JP.UTF-8');

class TenkawaHacks{
	function BasicCheck($USERID,$PASS){
		/**
		* 天河BASIC認証
		* @param string $USERID   まんま
		* @param string $PASS     まんま
		* @return array $options  curlオブジェクトに投げるパラメータ設定
		*/
		$options = array(
			$URL = "xxxxx",
			$USERNAME = $USERID,
			$PASSWORD = $PASS,
			CURLOPT_URL => $URL,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_USERPWD => $USERNAME . ":" . $PASSWORD,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
		);
		return $options;
	}
	function Login($admin_id,$admin_password){
		/**
		* 天河ログイン
		* @param string $admin_id        まんま
		* @param string $admin_password  まんま
		* @return array $options         curlオブジェクトに投げるパラメータ設定
		*/
		$options = array(
			$URL = 'xxxxx',
			$POST_DATA = array(
				'admin_id' => $admin_id,
				'admin_password' => $admin_password
			),
			CURLOPT_URL => $URL,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => http_build_query($POST_DATA),
			// CURLOPT_POSTFIELDS => $POST_DATA,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_SSL_VERIFYHOST => FALSE,
			CURLOPT_COOKIESESSION => TRUE,
			CURLOPT_COOKIEJAR      => 'cookie',
			CURLOPT_COOKIEFILE     => 'tmp',
			// CURLOPT_REFERER        => 'http://admin.shogakukan.co.jp/admin',
			// CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36'
		);
		return $options;
	}
	function Download_Subscription($StartDate='',$EndDate='',$add_header = '1',$sum_type = '0',$title = '',$start_customer = '',$end_customer = ''){
		/**
		* subscription.tsv書き出し
		* @param string $StartDate       書出条件指定　ダウンロード対象 開始日時 初期値前月1日
		* @param string $EndDate         書出条件指定　ダウンロード対象 終了日時　初期値前月31日
		* @param string $add_header      ヘッダ必要可否　1:ヘッダ付き
		* @param string $sum_type        集計期間条件　0:期間集計しない
		* @param string $title           書出条件指定　特定作品の集計 作品名・商品名
		* @param string $start_customer  書出条件指定　会員番号範囲の指定　開始会員番号 
		* @param string $end_customer    書出条件指定　会員番号範囲の指定　終了会員番号
		* @return array $options         curlオブジェクトに投げるパラメータ設定
		*/
		if(!$StartDate){
			$StartDate = date('Ym01',strtotime(date('Ym01') . '-1 month'));
		}
		if(!$EndDate){
			$EndDate = date('Ym31',strtotime(date('Ym01') . '-1 month'));
			// 天河はその日がなければ出ない仕様なので31日でよい。
		}
		$start_year = substr($StartDate,0,4);
		$start_month = substr($StartDate,4,2);
		$start_day = substr($StartDate,6,2);
		$end_year = substr($EndDate,0,4);
		$end_month = substr($EndDate,4,2);
		$end_day = substr($EndDate,6,2);
		$options = array(
			$URL = 'xxxxx',
			$POST_DATA = array(
				'sum_type' => $sum_type,
				'title' => $title,
				'start_customer' => $start_customer,
				'end_customer' => $end_customer,
				'start_year' => $start_year,
				'start_month' => $start_month,
				'start_day' => $start_day,
				'end_year' => $end_year,
				'end_month' => $end_month,
				'end_day' => $end_day,
				'add_header' => $add_header,
				'output' => 'ダウンロード'
			),
			CURLOPT_URL => $URL,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => http_build_query($POST_DATA),
			// CURLOPT_POSTFIELDS => $POST_DATA,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_SSL_VERIFYHOST => FALSE,
			CURLOPT_COOKIESESSION => TRUE,
			CURLOPT_COOKIEJAR      => 'cookie',
			CURLOPT_COOKIEFILE     => 'tmp',
			// CURLOPT_REFERER        => 'https://admin.shogakukan.co.jp',
			// CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36'
		);
		return $options;
	}

	function Download_Customer($StartDate='',$EndDate=''){
		/**
		* m_customer.tsv書き出し
		* @param string $StartDate       書出条件指定　ダウンロード対象 開始日時 2009年1月
		* @param string $EndDate         書出条件指定　ダウンロード対象 終了日時　初期値前月
		* @return array $options         curlオブジェクトに投げるパラメータ設定
		*/
		if(!$StartDate){
			$StartDate = date('200901');
		}
		if(!$EndDate){
			$EndDate = date('Ym',strtotime(date('Ym01') . '-1 month'));
		}
		$start_year = substr($StartDate,0,4);
		$start_month = substr($StartDate,4,2);
		$end_year = substr($EndDate,0,4);
		$end_month = substr($EndDate,4,2);
		$options = array(
			$URL = 'xxxxx',
			$POST_DATA = array(
				'start_year' => $start_year,
				'start_month' => $start_month,
				'end_year' => $end_year,
				'end_month' => $end_month,
				'output' => 'ダウンロード'
			),
			CURLOPT_URL => $URL,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => http_build_query($POST_DATA),
			// CURLOPT_POSTFIELDS => $POST_DATA,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_SSL_VERIFYHOST => FALSE,
			CURLOPT_COOKIESESSION => TRUE,
			CURLOPT_COOKIEJAR      => 'cookie',
			CURLOPT_COOKIEFILE     => 'tmp',
			// CURLOPT_REFERER        => 'https://admin.shogakukan.co.jp',
			// CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36'
		);
		return $options;
	}
  function Download_Groupwork($digipub_no = 'NONE',$publisher_no = 'NONE',$title = '',$author = '',$from = '',$to = '',$add_header = '1'){
    /**
    * group_work書き出し
    * @param  string $digipub_no   提供会社絞り込み
    * @param  string $publisher_no 出版社絞り込み
    * @param  string $title        タイトル絞り込み
    * @param  string $author       著者名絞り込み
    * @param  string $from         作品番号範囲from
    * @param  string $to           作品番号範囲to
    * @param  string $add_header   見出し付き=1
    * @return array  $options      curlオブジェクトに投げるパラメータ設定
    */
    $options = array(
      $URL = 'xxxxxxxx',
      $POST_DATA = array(
        'digipub_no' => $digipub_no,
        'publisher_no' => $publisher_no,
        'title' => $title,
        'author' => $author,
        'from' => $from,
        'to' => $to,
        'add_header' => $add_header
      ),
      CURLOPT_URL => $URL,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => http_build_query($POST_DATA),
      // CURLOPT_POSTFIELDS => $POST_DATA,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
      CURLOPT_COOKIESESSION => TRUE,
      CURLOPT_COOKIEJAR      => 'cookie',
      CURLOPT_COOKIEFILE     => 'tmp',
      // CURLOPT_REFERER        => 'http://admin.shogakukan.co.jp/admin',
      // CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36'
    );
    return $options;
  }
	function Download_MasterData($master_name){
		/**
		* m_publisherほか、マスタ書き出し
		* @param string $master_name   天河マスタデータのname属性（postするデータ名）
		* @return array $options       curlオブジェクトに投げるパラメータ設定
		*/
		$options = array(
			$URL = 'xxxxxxx',
			$POST_DATA = array(
				'targetTable' => $master_name
			),
			CURLOPT_URL => $URL,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => http_build_query($POST_DATA),
			// CURLOPT_POSTFIELDS => $POST_DATA,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_SSL_VERIFYHOST => FALSE,
			CURLOPT_COOKIESESSION => TRUE,
			CURLOPT_COOKIEJAR      => 'cookie',
			CURLOPT_COOKIEFILE     => 'tmp',
			// CURLOPT_REFERER        => 'http://admin.shogakukan.co.jp/admin',
			// CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36'
		);
		return $options;
	}
}

class curl_Client{
	function init() {
		// curl初期化
		$curl = curl_init();
		return $curl;
	}
	function execute($curl,$options){
		/**
		* curl実行
		* @param object $curl          curlオブジェクト
		* @param string $options      curlパラメータオプション
		* @return string 実行結果のデータ
		*/
		curl_setopt_array($curl, $options);
		$result = curl_exec($curl);
		// エラー取得
		if(curl_errno($curl))
		{
			echo 'Curl error: ' . curl_error($curl);
			die();
		}
		return $result;
	}
	function close($curl) {
		// まんま
		curl_close($curl);
	}
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
		if(!is_file($zip_path) || !is_readable($extract_path))
		{
			die("読み込みに失敗しました。");
		}
		if(!is_dir($extract_path) || !is_writable($extract_path))
		{
			die("書き込みに失敗しました。");
		}
		if(file_exists(is_dir($zip_path))){
			die("ZIPファイルが見つかりません");
		}
		// 拡張子取得
		$kk = substr($zip_path, strrpos($zip_path, '.') + 1);
		switch ($kk) {
			case 'gz':
				$gzip = file_get_contents($zip_path);
				$rest = substr($gzip, -4); 
				$zipsize = end(unpack("V", $rest));
				$zd = gzopen($zip_path, 'r');
				$contents = gzread($zd,$zipsize);
				gzclose($zd);
				//書き込む
				$outputfilename = $extract_path.substr(basename($zip_path),0,-3);
				if(!$file = fopen($outputfilename,"w+")){
					echo $extract_path.substr(basename($zip_path),0,-3).'を作成できませんでした。';
					return false;
				}
				fputs($file,$contents);
				fclose($file);
				break;

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
		* @param array $addfiles_path   追加するファイルパス
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

