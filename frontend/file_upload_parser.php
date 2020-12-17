<?php
ini_set("upload_max_filesize", -1);
/*
require_once __DIR__ . '/vendor/autoload.php';

use Nats\Connection as NatsClient;

try {
    $connectionOptions = new \Nats\ConnectionOptions();
    $connectionOptions->setHost('nats')->setPort(4222);
    $c = new NatsClient($connectionOptions);
    $c->connect();

    // Simple Publisher.
    $c->publish('channel', 'Sending message here');
    // Wait for 1 message.
    $c->wait(1);
    $c->close();
} catch (Exception $e) {
    // Exception handling
    echo "<pre>";
    print_r($e);
    exit;
}
*/
$fileName = $_FILES["file1"]["name"]; // The file name
$fileData = $_FILES["file1"]["tmp_name"]; // File in the PHP tmp folder
$fileType = $_FILES["file1"]["type"]; // The type of file it is
$fileSize = $_FILES["file1"]["size"]; // File size in bytes
$fileErrorMsg = $_FILES["file1"]["error"]; // 0 for false... and 1 for true
if (!$fileData) { // if file not chosen
    echo "ERROR: There has something wrong with file upload size limit.";
    exit();
}

$url = 'http://localhost:3000/upload';
if ($fileData != '') {
    $headers = array("Content-Type:multipart/form-data"); // cURL headers for file uploading
    $fields = array("filedata" => "@$fileData", "filename" => $fileName);
    $ch = curl_init();
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => true,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_INFILESIZE => $fileSize,
        CURLOPT_RETURNTRANSFER => true
    ); // cURL options
    curl_setopt_array($ch, $options);
    curl_exec($ch);
    if (!curl_errno($ch)) {
        $info = curl_getinfo($ch);
        if ($info['http_code'] == 200)
            $errmsg = "File uploaded successfully";
    } else {
        $errmsg = curl_error($ch);
    }
    echo $errmsg;
    curl_close($ch);
}
