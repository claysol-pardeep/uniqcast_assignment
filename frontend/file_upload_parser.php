<?php
$fileName = $_FILES["file1"]["name"]; // The file name
$fileData = $_FILES["file1"]["tmp_name"]; // File in the PHP tmp folder
$fileType = $_FILES["file1"]["type"]; // The type of file it is
$fileSize = $_FILES["file1"]["size"]; // File size in bytes
$fileErrorMsg = $_FILES["file1"]["error"]; // 0 for false... and 1 for true
if (!$fileData) { // if file not chosen
    echo "ERROR: Please browse for a file before clicking the upload button.";
    exit();
}

$url = 'http://0.0.0.0:3002/upload';
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
    curl_close($ch);
}
