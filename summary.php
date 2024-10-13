<?php

include_once 'function.php';
include_once 'PHPExcel/PHPExcel.php';

// config.json呼び出し
$jsonpath = 'config.json';
$config = JsonParse($jsonpath);

// DB初期化
echo "summary...\n";
ob_flush();
flush();
sleep(1);
$db = new DB();
$dbh = $db->construct('sqlite:database/database.db');
$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
// 今月の新規ユーザ抽出
$NewUserIDList = NewUserID_Get($dbh,$config);
// 今月の既存ユーザ抽出
$ExistingUserIDList = ExistingUserID_Get($dbh,$NewUserIDList);
// 今月の無課金ユーザ抽出
$PoorUserIDList = PoorUserID_Get($dbh,$NewUserIDList,$ExistingUserIDList);
// 各集計処理
$KPI = array();
$KPI['基本指標'] = Summary_kihon($dbh,$PoorUserIDList);
$KPI['新規会員の獲得'] = Summary_sinki($dbh,$NewUserIDList,$PoorUserIDList,$config);
$KPI['既存会員の維持'] = Summary_kizon($dbh,$NewUserIDList,$PoorUserIDList);
$Cohort = Summary_cohort($dbh,$config);
$exportfilepath = OutputExcel($KPI,$Cohort,$config);


/**
* KPI基本指標の集計。計算してKPI配列に入れてreturnする。
* @param  object $dbh            DBハンドル
* @param  array  $KPI            集計済みのKPIデータ
* @param  array  $PoorUserIDList 無課金者リスト
* @return array  $KPI            集計済みのKPIデータ
*/
function Summary_kihon($dbh,$PoorUserIDList){
  // 配信冊数
  $sql = 'SELECT COUNT(0) FROM `contents`;';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['配信冊数'] = (int)$count[0];
  // 累計会員数
  $sql = 'SELECT COUNT(0) FROM `customer`;';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['累計会員数'] = (int)$count[0];
  // 新規会員数
  // $lastmonth = date('Ym',strtotime(date('Y-m-01') . '-1 month'));
  // $sql = 'SELECT COUNT(0) FROM `customer` WHERE `本登録日` LIKE \''.$lastmonth.'%\';';
  // $result = $dbh->query($sql);
  // $count = $result->fetchAll(PDO::FETCH_COLUMN);
  // $KPI['新規会員数'] = (int)$count[0];
  // 退会者
  // 集計しない。
  // 課金額
  $sql = 'SELECT SUM(`販売額`) FROM `subscription`;';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['課金額'] = (int)$count[0];
  // ポイントチャージ課金額
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `注文ID` LIKE \'PT%\';';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['ポイントチャージ課金額'] = (int)$count[0];
  // 月額課金額
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `注文ID` LIKE \'PK%\';';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['月額課金額'] = (int)$count[0];
  // 購入冊数
  $sql = 'SELECT COUNT(0) FROM `subscription` WHERE `注文ID` LIKE \'SJ%\';';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['購入冊数'] = (int)$count[0];
  // 購入者数（０円購入のみ含む）
  $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription`;';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['購入者数（０円購入のみ含む）'] = (int)$count[0];
  // ポイントチャージ課金利用者数
  $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `注文ID` LIKE \'PT%\';';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['ポイントチャージ課金利用者数'] = (int)$count[0];
  // 月額課金利用者数
  $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `注文ID` LIKE \'PK%\';';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['月額課金利用者数'] = (int)$count[0];
  // 無課金購入者数
  $KPI['無課金購入者数'] = count($PoorUserIDList['新規会員 無課金者数']) + count($PoorUserIDList['既存会員 無課金者数']);
  // 平均購入商品単価
  $sql = "SELECT SUM(`利用ポイント`) FROM `cps_subscription`;";
  $result = $dbh->query($sql);
  $temp = $result->fetchAll(PDO::FETCH_COLUMN);
  $usepoint = (int)$temp[0];
  $kakin = $KPI['課金額'] - ($KPI['ポイントチャージ課金額'] + $KPI['月額課金額']);
  $KPI['平均購入商品単価'] = round(($usepoint + $kakin) / $KPI['配信冊数'],2);
  // 平均購入者単価
  // $KPI['平均購入者単価'] = round($KPI['購入金額'] / $KPI['購入者数'],1);
  return $KPI;
}

/**
* KPI【新規顧客の獲得】指標の集計。計算してKPI配列に入れてreturnする。
* @param  object $dbh            DBハンドル
* @param  array  $KPI            集計済みのKPIデータ
* @param  array  $PoorUserIDList 無課金者リスト
* @return array  $KPI            集計済みのKPIデータ
*/
function Summary_sinki($dbh,$NewUserIDList,$PoorUserIDList,$config){
  // ga取得の日付
  $StartDate = date('Y-m-01',strtotime($config['取得年月'] . '01'));
  $EndDate = date('Y-m-t',strtotime($config['取得年月'] . '01'));
  // $StartDate = date('Y-m-01',strtotime(date('Y-m-01') . '-1 month'));
  // $EndDate = date('Y-m-t',strtotime(date('Y-m-01') . '-1 month'));
  // 新規セッション数 新規ユーザ数
  $param = array(
    'metrics'    => 'ga:sessions,ga:visitors',
    'segment'    => 'gaid::-2', //新規のみ
    'dimensions' => 'ga:userType,ga:month'
    );
  $temp = GA_Get($StartDate,$EndDate,$param);
  $KPI['新規セッション数'] = (int)$temp['totalsForAllResults']['ga:sessions'];
  $KPI['新規訪問者数（MUU)'] = (int)$temp['totalsForAllResults']['ga:visitors'];
  // 新規会員/新規ユーザ数
  // $KPI['新規会員/新規ユーザ数'] = round(($KPI['新規会員数'] / $KPI['新規セッション数'])*100,1);
  // 新規入会購入者数（0円購入のみ含む）
  $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['新規入会購入者数（0円購入のみ含む）'] = (int)$count[0];
  // ポイントチャージ課金利用者数
  $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `注文ID` LIKE \'PT%\' AND `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['新規会員 ポイントチャージ課金利用者数'] = (int)$count[0];
  // 月額課金利用者数
  $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `注文ID` LIKE \'PK%\' AND `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['新規会員 月額課金利用者数'] = (int)$count[0];
  //新規入会者課金額
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['新規入会者課金額'] = (int)$count[0];
  // ポイントチャージ課金額
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `注文ID` LIKE \'PT%\' AND `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['新規会員 ポイントチャージ課金額'] = (int)$count[0];
  // 月額課金額
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `注文ID` LIKE \'PK%\' AND `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['新規会員 月額課金額'] = (int)$count[0];
  // 新規入会　無課金購入者数
  $KPI['新規入会　無課金購入者数'] = count($PoorUserIDList['新規会員 無課金者数']);
  // 新規入会ポイントチャージ利用者 販売額合計
  // （新規入会かつポイントチャージ利用者の全課金を集計）
  $sql = 'SELECT DISTINCT `会員ID` FROM `subscription` WHERE `注文ID` LIKE \'PT%\' AND `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\') ORDER BY `会員ID` ASC;';
  $result = $dbh->query($sql);
  $new_pt_list = $result->fetchAll(PDO::FETCH_COLUMN);
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$new_pt_list) . '\');';
  $result = $dbh->query($sql);
  $new_pt_sum = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['新規入会ポイントチャージ利用者 販売額合計'] = (int)$new_pt_sum[0];
  // 新規入会月額課金利用者 販売額合計
  // （新規入会かつ月額課金利用者の全課金を集計）
  $sql = 'SELECT DISTINCT `会員ID` FROM `subscription` WHERE `注文ID` LIKE \'PK%\' AND `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\') ORDER BY `会員ID` ASC;';
  $result = $dbh->query($sql);
  $new_pk_list = $result->fetchAll(PDO::FETCH_COLUMN);
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$new_pk_list) . '\');';
  $result = $dbh->query($sql);
  $new_pk_sum = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['新規入会月額課金利用者 販売額合計'] = (int)$new_pk_sum[0];
  return $KPI;
}

/**
* KPI【既存会員の維持】指標の集計。計算してKPI配列に入れてreturnする。
* @param  object $dbh            DBハンドル
* @param  array  $KPI            集計済みのKPIデータ
* @param  array  $PoorUserIDList 無課金者リスト
* @return array  $KPI            集計済みのKPIデータ
*/
function Summary_kizon($dbh,$NewUserIDList,$PoorUserIDList){
  // 既存会員購入者数
  // $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `会員ID` NOT IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  // $result = $dbh->query($sql);
  // $count = $result->fetchAll(PDO::FETCH_COLUMN);
  // $KPI['既存会員購入者数'] = (int)$count[0];
  // ポイントチャージ購入者数
  // $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `注文ID` LIKE \'PT%\' AND `会員ID` NOT IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  // $result = $dbh->query($sql);
  // $count = $result->fetchAll(PDO::FETCH_COLUMN);
  // $KPI['既存会員 ポイントチャージ課金利用者数'] = (int)$count[0];
  // 月額購入者数
  // $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `注文ID` LIKE \'PK%\' AND `会員ID` NOT IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  // $result = $dbh->query($sql);
  // $count = $result->fetchAll(PDO::FETCH_COLUMN);
  // $KPI['既存会員 月額課金利用者数'] = (int)$count[0];
  // 既存会員 無課金購入者数
  $KPI['既存会員 無課金購入者数'] = count($PoorUserIDList['既存会員 無課金者数']);
  // 翌月リピート率
    // 前月分購入者の会員ID取得
  $sql = 'SELECT DISTINCT `会員ID` FROM `ante_subscription`;';
  $result = $dbh->query($sql);
  $antedata = $result->fetchAll(PDO::FETCH_COLUMN);
    // 前月分購入者数取得
  $anteusercount = count($antedata);
    // 前月の会員が今月何名購入しているか
  $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$antedata) . '\');';
  $result = $dbh->query($sql);
  $count = $result->fetchAll(PDO::FETCH_COLUMN);
  $couhukuIDcount = (int)$count[0];
  $KPI['翌月リピート率'] = round((int)$couhukuIDcount / (int)$anteusercount,2);
  // 既存会員ポイントチャージ利用者 販売額合計
  // （既存会員かつポイントチャージ利用者の全課金を集計）
  $sql = 'SELECT DISTINCT `会員ID` FROM `subscription` WHERE `注文ID` LIKE \'PT%\' AND `会員ID` NOT IN(\'' . implode('\',\'',$NewUserIDList) . '\') ORDER BY `会員ID` ASC;';
  $result = $dbh->query($sql);
  $kizon_pt_list = $result->fetchAll(PDO::FETCH_COLUMN);
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$kizon_pt_list) . '\');';
  $result = $dbh->query($sql);
  $kizon_pt_sum = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['既存会員ポイントチャージ利用者 販売額合計'] = (int)$kizon_pt_sum[0];
  // 既存会員月額課金利用者 販売額合計
  // （既存会員かつ月額課金利用者の全課金を集計）
  $sql = 'SELECT DISTINCT `会員ID` FROM `subscription` WHERE `注文ID` LIKE \'PK%\' AND `会員ID` NOT IN(\'' . implode('\',\'',$NewUserIDList) . '\') ORDER BY `会員ID` ASC;';
  $result = $dbh->query($sql);
  $kizon_pk_list = $result->fetchAll(PDO::FETCH_COLUMN);
  $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$kizon_pk_list) . '\');';
  $result = $dbh->query($sql);
  $kizon_pk_sum = $result->fetchAll(PDO::FETCH_COLUMN);
  $KPI['既存会員月額課金利用者 販売額合計'] = (int)$kizon_pk_sum[0];
  return $KPI;
}

/**
* 当該月のsubscription.tsvに対して、m_customer.tsv内【本登録日】の月ごとに
* リピート購入者数、リピート購入金額を抽出する。
* @param  object $dbh    DBハンドル
* @return array  $Cohort 集計済みのコホートデータ
*/
function Summary_cohort($dbh,$config){
  // 該当月購入者の会員ID取得
  $checkdate = '201501'; //集計開始日付
  $presentdate = date('Ym',strtotime($config['取得年月'].'01 +1 month'));
  // $presentdate = date('Ym',strtotime(date('Y-m-01'))); //当月
  while($checkdate != $presentdate){
    $sql = 'SELECT `会員ID` FROM `customer` WHERE `本登録日` LIKE \''.$checkdate.'%\';';
    $result = $dbh->query($sql);
    $customerdate[$checkdate] = $result->fetchAll(PDO::FETCH_COLUMN);
    $checkdate = date('Ym',strtotime(date($checkdate.'01') . ' +1 month'));
  }
  // 残存会員数をチェック
  foreach ($customerdate as $date => $clist) {
    $sql = 'SELECT COUNT(DISTINCT 会員ID) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$clist) . '\');';
    $result = $dbh->query($sql);
    $count = $result->fetchAll(PDO::FETCH_COLUMN);
    $Cohort[$date]['購入者数'] = (int)$count[0];
  }
  // 残存会員購入金額をチェック
  foreach ($customerdate as $date => $clist) {
    $sql = 'SELECT SUM(`販売額`) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$clist) . '\');';
    $result = $dbh->query($sql);
    $pay = $result->fetchAll(PDO::FETCH_COLUMN);
    $Cohort[$date]['購入金額']= (int)$pay[0];
  }
  return $Cohort;
}

/**
* 当該月の新規会員データを取得
* @param  object $dbh           DBハンドル
* @return array  $NewUserIDList 該当会員ID
*/
function NewUserID_Get($dbh,$config){
  $lastmonth = $config['取得年月'];
  // $lastmonth = date('Ym',strtotime(date('Y-m-01') . '-1 month'));
  $sql = 'SELECT `会員ID` FROM `customer` WHERE `本登録日` LIKE \''.$lastmonth.'%\';';
  $result = $dbh->query($sql);
  $NewUserIDList = $result->fetchAll(PDO::FETCH_COLUMN);
  return $NewUserIDList;
}

/**
* 既存会員データを取得
* @param  object $dbh                DBハンドル
* @param  array  $NewUserIDList       新規会員リスト
* @return array  $ExistingUserIDList 該当会員ID
*/
function ExistingUserID_Get($dbh,$NewUserIDList){
  $sql = 'SELECT `会員ID` FROM `customer` WHERE `会員ID` NOT IN(\'' . implode('\',\'',$NewUserIDList) . '\');';
  $result = $dbh->query($sql);
  $ExistingUserIDList = $result->fetchAll(PDO::FETCH_COLUMN);
  return $ExistingUserIDList;
}

/**
* 無課金者データを取得
* @param  object $dbh                DBハンドル
* @param  array  $NewUserIDList      新規会員リスト
* @param  array  $ExistingUserIDList 既存会員リスト
* @return array  $PoorUserIDList     該当会員ID 新規と既存
*/
function PoorUserID_Get($dbh,$NewUserIDList,$ExistingUserIDList){
  // 新規の無課金者抽出
  $sql = 'SELECT `会員ID`,SUM(`販売額`) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$NewUserIDList) . '\') GROUP BY `会員ID`;';
  $result = $dbh->query($sql);
  $subscriptionList = $result->fetchAll(PDO::FETCH_ASSOC);
  foreach ($subscriptionList as $key => $value) {
    if($value['SUM(`販売額`)'] == 0){
      $temppoorList[] = $value['会員ID'];
    }
  }
  $PoorUserIDList['新規会員 無課金者数'] = $temppoorList;
  // 既存の無課金者抽出
  $sql = 'SELECT `会員ID`,SUM(`販売額`) FROM `subscription` WHERE `会員ID` IN(\'' . implode('\',\'',$ExistingUserIDList) . '\') GROUP BY `会員ID`;';
  $result = $dbh->query($sql);
  $subscriptionList = $result->fetchAll(PDO::FETCH_ASSOC);
  $temppoorList = array();
  foreach ($subscriptionList as $key => $value) {
    if($value['SUM(`販売額`)'] == 0){
      $temppoorList[] = $value['会員ID'];
    }
  }
  $PoorUserIDList['既存会員 無課金者数'] = $temppoorList;
  return $PoorUserIDList;
}


/**
* GAのデータを取得
* @param  string $StartDate 集計開始日
* @param  string $EndDate 集計最終日
* @return array  $param 取得パラメータ
*/
function GA_Get($StartDate,$EndDate,$param){
  // ga準備
  $client = set_phpgoogle();
  // 新規セッション
  $service = new Google_AnalyticsService($client);
  $result = $service->data_ga->get(
    'ga:' . PROFILE_ID,
    $StartDate,
    $EndDate,
    $param['metrics'],
    array(
      'segment'    => $param['segment'],
      'dimensions' => $param['dimensions']
    )
  );
  return $result;
}

/**
* エクセルファイルを作成してデータ出力。outputフォルダに保存。
* @param  object $KPI        KPI集計データ
* @param  object $Cohort     コホート集計データ
* @return string $exportpath 出力ファイルのパス
*/
function OutputExcel($KPI,$Cohort,$config){
  // 頓挫。phpexcelで条件付き書式を読んで吐き出すと、エラーになったり表示が崩れてしまうため
  // $getfiledate = date('Ym',strtotime(date('Y-m-01') . '-2 month'));
  // $kpidate = date('n',strtotime(date('Y-m-01') . '-1 month'));
  // $objPHPExcel = PHPExcel_IOFactory::load('./output/'.$getfiledate.'_KPI.xlsx');
  // $objPHPExcel->setActiveSheetIndex(0);
  // $objSheet = $objPHPExcel->getActiveSheet();
  // // 新しい月の列を作成して書式をコピー
  // $position = SearchCell($objSheet,3,2,'col','blank');
  // $x = $position['col'];
  // $y = $position['row'];
  // // 列挿入
  // $xalp = NumerictoAlphabets($x);
  // $objSheet->insertNewColumnBefore( $xalp, 1 );
  // $objSheet->getCellByColumnAndRow($x,$y)->setValueExplicit($kpidate.'月', PHPExcel_Cell_DataType::TYPE_STRING );
  // // 列書式コピー
  // $objSheet = CopyCells($objSheet,$x - 1,1,1,500,1,0);
  // // コホートに行挿入
  // $position = SearchCell($objSheet,$x - 1,41,'row','exist');
  // $y = $position['row'] + 1;
  // $objSheet->insertNewRowBefore($y,1);

  // コピペ前提で別ファイルに書き出し。
  $objPHPExcel = new PHPExcel();
  $objPHPExcel->setActiveSheetIndex(0);
  $objSheet = $objPHPExcel->getActiveSheet();
  // KPIをコピペできる形で書き込み
  $x = 0;
  $y = 1;
  $kpidate = date('n',strtotime(date($config['取得年月'].'01')));
  // $kpidate = date('n',strtotime(date('Y-m-01') . '-1 month'));
  $objSheet->getCellByColumnAndRow($x,$y)->setValue($kpidate.'月集計分');
  foreach ($KPI as $keya => $midasi) {
    $y += 2;
    $objSheet->getCellByColumnAndRow($x,$y)->setValue($keya);
    foreach ($midasi as $keyb => $valueb) {
      ++$y;
      $objSheet->getCellByColumnAndRow($x,$y)->setValue($keyb);
      $objSheet->getCellByColumnAndRow($x + 1,$y)->setValue($valueb);
    }
  }
  // コホート
  $y += 2;
  $objSheet->getCellByColumnAndRow($x,$y)->setValue('新規入会月ごとのリピート購入者数');
  foreach ($Cohort as $key => $value) {
    ++$x;
    $objSheet->getCellByColumnAndRow($x,$y)->setValue($value['購入者数']);
  }
  ++$y;
  $x = 0;
  $objSheet->getCellByColumnAndRow($x,$y)->setValue('新規入会月ごとのリピート購入金額');
  foreach ($Cohort as $key => $value) {
    ++$x;
    $objSheet->getCellByColumnAndRow($x,$y)->setValue($value['購入金額']);
  }
  // 出力ファイル名
  $filedate = $config['取得年月'];
  // $filedate = date('Ym',strtotime(date('Y-m-01') . '-1 month'));
  $exportpath = './output/'.$filedate.'_KPI.xlsx';
  // 保存
  $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
  $writer->save($exportpath);
  return $exportpath;
}

/**
* エクセル用 最初の空欄カラムを探す
* @param  integer $col       列番号
* @param  integer $row       行番号
* @param  string  $direction チェック方向 row or col
* @param  string  $param     blank or exist
* @return array   $result    colとrow
**/
// function SearchCell($objSheet,$col,$row,$direction,$param){
//   if($param == 'blank'){
//     while($value = $objSheet->getCellByColumnAndRow($col, $row)->getValue()){
//       if($direction == 'row'){++$row;}
//       if($direction == 'col'){++$col;}
//     }
//   } else {
//     while(!$value = $objSheet->getCellByColumnAndRow($col, $row)->getValue()){
//       if($direction == 'row'){++$row;}
//       if($direction == 'col'){++$col;}
//     }
//   }
//   $result = array('col' => $col,'row' => $row);
//   return $result;
// }

/**
* エクセル用数字カラムをアルファベットに変換。
* @param  integer $num    列番号
* @return string  $result 変換アルファベット
*/
// function NumerictoAlphabets($num){
//   for($i = 0; $i < 26; $i++){
//     $alphabet[] = strtoupper(chr(ord('A') + $i));
//   }
//   $one = fmod($num, 26);
//   $result = $alphabet[$one];
//   $carry = ($num - $one) / 26;
//   while($carry != 0) {
//     $one = fmod($carry - 1, 26);
//     $result = $alphabet[$one].$result;
//     $carry = ($carry - 1 - $one) / 26;
//   }
//   return $result;
// }

/**
* エクセル用特定範囲のセルスタイルをコピペ。
* @param  object  $objSheet      phpexcelのシートオブジェクト
* @param  integer $col_start = 0 開始列数
* @param  integer $row_start = 1 開始行数
* @param  integer $col_Maxcount  最大取得列数
* @param  integer $row_Maxcount  最大取得行数
* @param  integer $col_offset    列オフセット値
* @param  integer $row_offset    行オフセット値
* @return object  $objSheet      phpexcelのシートオブジェクト
*/
// function CopyCells($objSheet,$col_start = 0,$row_start = 1,$col_Maxcount,$row_Maxcount,$colpaste_offset = 0,$rowpaste_offset = 0){
//   for($col = $col_start;$col < $col_start + $col_Maxcount;$col++) {
//     for($row = $row_start;$row < $row_start + $row_Maxcount;$row++) {
//       // セルスタイルを取得
//       $style = $objSheet->getStyleByColumnAndRow($col, $row);
//       // 数値から列文字列に変換する (0,1) → A1
//       $offsetCell = PHPExcel_Cell::stringFromColumnIndex($col + $colpaste_offset) . (string)($row + $rowpaste_offset);
//       // スタイルをコピー
//       $objSheet->duplicateStyle($style, $offsetCell);
//     }
//   }
//   return $objSheet;
// }
