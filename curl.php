<?php
$url = array(
	"http://localhost/sokuyomi_kpi/cps_download/index.php",
	"http://localhost/sokuyomi_kpi/tsv_download/download_customer.php",
	"http://localhost/sokuyomi_kpi/tsv_download/download_subscription.php",
	"http://localhost/sokuyomi_kpi/insert.php",
	"http://localhost/sokuyomi_kpi/summary.php",
	"http://localhost/sokuyomi_kpi/download.php"
);
$curl = curl_init();
foreach ($url as $key => $value) {
	curl_setopt($curl, CURLOPT_URL, $value);
	$result = curl_exec($curl);
}
curl_close($curl);
