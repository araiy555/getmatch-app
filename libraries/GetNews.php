<?php

function action()
{
    return 'raf';
}

function postJson($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-experimental-api: 1', 'Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, $url);

    $result=curl_exec($ch);

    curl_close($ch);
    return $result;
}


