<?php
ini_set("upload_max_filesize", -1);
require_once __DIR__ . '/vendor/autoload.php';

use Nats\Connection as NatsClient;

try {
    $connectionOptions = new \Nats\ConnectionOptions();
    $connectionOptions->setHost('nats')->setPort(4222);
    global $c;
    $c = new NatsClient($connectionOptions);
    $c->connect();
    // Simple Subscriber.
    $c->subscribe(
        'channel',
        function ($message) {
            printf("Data: %s\r\n", $message->getBody());
        }
    );
    $c->close();
} catch (Exception $e) {
    // Exception handling
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Video Stream App</title>
    <link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

    <script>
        function _(el) {
            return document.getElementById(el);
        }

        function uploadFile() {
            var file = _("file1").files[0];
            var formData = new FormData();
            formData.append("file1", file);
            var ajax = new XMLHttpRequest();
            ajax.upload.addEventListener("progress", progressHandler, false);
            ajax.addEventListener("load", completeHandler, false);
            ajax.addEventListener("error", errorHandler, false);
            ajax.addEventListener("abort", abortHandler, false);
            ajax.open("POST", "file_upload_parser.php");
            ajax.send(formData);
        }

        function progressHandler(event) {
            // _("loaded_n_total").innerHTML = "Uploaded " + event.loaded + " bytes of " + event.total;
            var percent = (event.loaded / event.total) * 100;
            _("progressBar").value = Math.round(percent);
            _("status").innerHTML = Math.round(percent) + "% uploaded... please wait";
        }

        function completeHandler(event) {
            _("status").innerHTML = event.target.responseText;
            _("progressBar").value = 0;
        }

        function errorHandler(event) {
            _("status").innerHTML = "Upload Failed";
        }

        function abortHandler(event) {
            _("status").innerHTML = "Upload Aborted";
        }
    </script>
</head>

<body>
    <div class="app">
        <header>
            <nav class="navbar navbar-dark bg-primary">
                <h1 class="navbar-brand mb-0">UniqCast</h1>
            </nav>
        </header>
        <div class="container">
            <div class="row">
                <div class="col-md-8 m-auto pt-4">
                    <h1 class="text-center mb-5">UniqCast Video Upload</h1>
                    <form id="upload_form" name="upload_form" enctype="multipart/form-data" method="post">
                        <div class="form-group">
                            <label for="exampleFormControlInput1">File</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="inputGroupFileAddon01">Upload</span>
                                </div>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="file1" name="file1" aria-describedby="inputGroupFileAddon01">
                                    <label class="custom-file-label" for="file1">Choose file</label>
                                </div>
                            </div>
                        </div>


                        <div class="input-group">
                            <br />
                            <p id="status"></p> <br />
                            <progress id="progressBar" value="0" max="100" style="width:300px;display:none;"></progress>
                            <!-- <p id="loaded_n_total"></p> -->
                        </div>
                        <div class="input-group float-right">
                            <input type="button" value="Upload File" onclick="uploadFile()">
                        </div>
                    </form>

                    <!-- <video controls width="100%">
                        <source src="http://localhost:3001/video" type="video/mp4">
                        Sorry, your browser doesn't support embedded videos.
                    </video> -->
                </div>
            </div>
        </div>
    </div>
</body>

</html>