<?php
require_once __DIR__ . '/vendor/autoload.php';

use Nats\Connection as NatsClient;
use Nats\ConnectionOptions as ConnectionOptions;

$fileName = $_FILES["file1"]["name"]; // The file name
$fileData = $_FILES["file1"]["tmp_name"]; // File in the PHP tmp folder
$fileType = $_FILES["file1"]["type"]; // The type of file it is
$fileSize = $_FILES["file1"]["size"]; // File size in bytes
$fileErrorMsg = $_FILES["file1"]["error"]; // 0 for false... and 1 for true
if (!$fileData) { // if file not chosen
    echo "ERROR: There has something wrong with file upload size limit.";
    exit();
}
$fileNewName = "video-" . time() . ".mp4";
if (move_uploaded_file($fileData, "uploads/" . $fileNewName)) {
    $url = "public/" . $fileNewName;
    try {
        $options = new ConnectionOptions();
        $options->setHost('nats')->setPort(4222);

        $client = new NatsClient($options);
        $client->connect();
        $client->subscribe(
            'video',
            function ($message) {
                printf("File is stored in mounted directory and passed over the NATS Channel. <br />");
                printf("\n \n <b> URL: </b> %s\r\n", $message->getBody());
                printf("<br /> <br /> ");
            }
        );
        // Simple Publisher.
        $client->publish('video', $url);

        // Wait for 1 message.
        $client->wait(1);

        // Responding to requests.
        $sid = $client->subscribe(
            'record',
            function ($message) {
                $message->reply('Micro-service process this file and return the respond is, 
            ' . $message->getBody() . '.');
            }
        );

        $client->request(
            'record',
            'File Segment is generated and new files is saved in the mounted directory.',
            function ($message) {
                echo $message->getBody();
            }
        );

        // Wait for 1 message.
        $client->wait(1);
    } catch (Exception $e) {
        echo "<pre>";
        print_r($e);
        exit;
    }
} else {
    echo "ERROR: file uploading failed.";
}


/*
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
*/