<?php


$base_url = 'http://192.168.33.10';


$data = [
    "title" => "arraer",
    "url" => "",
    "body" => "fafda",
    "forum" => 1
];

$header = [
    'x-experimental-api: 1',
    'Content-Type: application/json',
];

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $base_url.'/api/submissions');
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST'); // post
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data)); // jsonデータを送信
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

$response = curl_exec($curl);

var_dump($response);

curl_close($curl);
