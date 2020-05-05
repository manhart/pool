<?php
/**
 * Created by PhpStorm.
 * User: manhart
 * Date: 30.05.2016
 * Time: 11:16
 */

function httpPost($url, $data)
{
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}