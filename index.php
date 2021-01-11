<?php
?>

<html lang="en">
<head>
    <title>Dbase to Mysql - Download or save to server !</title>
    <link href="css/bootstrap.css?<?= time(); ?>"  type="text/css" rel="stylesheet">
    <link href="css/sweetalert2.min.css?<?= time(); ?>"  type="text/css" rel="stylesheet">
    <link href="css/ingenieria.css?<?= time(); ?>"  type="text/css" rel="stylesheet">
    <link href="css/dropzone.css?<?= time(); ?>"  type="text/css" rel="stylesheet">
    <link href="fontawesome-free/css/all.css?<?= time(); ?>"  type="text/css" rel="stylesheet">
    <script src="js/jquery.min.js?<?= time(); ?>" type="text/javascript"></script>
    <script src="js/bootstrap.min.js?<?= time(); ?>" type="text/javascript"></script>
    <script src="js/sweetalert2.all.min.js?<?= time(); ?>" type="text/javascript"></script>
    <script src="js/dropzone.js?<?= time(); ?>" type="text/javascript"></script>
    <script type="text/javascript">
        var tmrProgress;
        Dropzone.autoDiscover = false;

        async function credentials() {
            var {value: values} = await Swal.fire({
                title: 'Input server info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                html:
                    '<label>Info will not be stored, credentials will be deleted after execution.</label>' +
                    '<input id="swal-input1" class="swal2-input" value="" placeholder="Server">' +
                    '<input id="swal-input2" class="swal2-input" value="" placeholder="Database">' +
                    '<input id="swal-input3" class="swal2-input" value="" placeholder="User">' +
                    '<input type="password" id="swal-input4" class="swal2-input" value="" placeholder="Password">',
                preConfirm: () => {
                    return [
                        _('swal-input1').value,
                        _('swal-input2').value,
                        _('swal-input3').value,
                        _('swal-input4').value
                    ]
                }
            });
            if (values) {
                if ((values[0] === "") || (values[1] === "") || (values[2] === "") || (values[3] === "")){ credentials(); }
                _('database').value = values[1];
                _('server').value = values[0];
                _('user').value = values[2];
                _('pass').value = values[3];
                const conn = await mysqlConn();
                if (!conn) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Failed',
                        text: 'Bad credentials !',
                        showConfirmButton: true
                    }).then(() => {
                        credentials();
                    });
                }

                var drop = new Dropzone('div#dropzone', {
                    url: "upload.php",
                    uploadMultiple: true,
                    previewsContainer: '.dz-preview',
                    parallelUploads: 10,
                    timeout: 240000,
                    maxFilesize: 1024,
                    maxFiles: 1000,
                    paramName: 'dbfiles',
                    thumbnailWidth: 100,
                    thumbnailHeight: 100,
                    dictRemoveFile: 'Remove file',
                    addRemoveLinks: true,
                    acceptedFiles: '.zip,.tar,.csv,.dbf,.txt',
                    init: function(){
                        var th = this;
                        this.on('queuecomplete', function(){
                            var total = this.getAcceptedFiles().length
                            Swal.fire({
                                title: 'Processing files',
                                html: 'This could take a while ... <h3>DO NOT REFRESH YOUR BROWSER !</h3><br><label></label><br><b></b>',
                                onBeforeOpen: () => {
                                    Swal.showLoading();
                                    tmrProgress = setInterval(getProgress,500,total);
                                    setTimeout(function(){
                                    $.ajax({
                                        type: "POST",
                                        url: 'Dbf2Mysql.php',
                                        data: {
                                            db: _('database').value,
                                            server: _('server').value,
                                            pass: _('pass').value,
                                            user: _('user').value
                                        },
                                        success: function (data) {
                                            clearInterval(tmrProgress);
                                            setTimeout(function(){
                                                th.removeAllFiles();
                                            },500);
                                            swal.close();
                                            Swal.fire({
                                                position: 'top-end',
                                                icon: 'success',
                                                title: 'Files, uploaded',
                                                text: 'Database ready, enjoy !.',
                                                showConfirmButton: false,
                                                timer: 1500
                                            });
                                        }
                                    });
                                    },2000);
                                }, allowOutsideClick: () => !Swal.isLoading()
                            });
                        });



                    }
                });

            } else {
                credentials();
            }
        }

        async function testConn(){
            var conn;
            const result = await $.ajax({
                type: 'POST',
                url: 'mysqlConn.php',
                data: {
                    db: _('database').value,
                    server: _('server').value,
                    pass: _('pass').value,
                    user: _('user').value
                },
                success: function (data) {
                    if (Number(data.split(";")[0]) === 1) {
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: 'Connection Established',
                            text: 'Drag and drop files !.',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        conn = true;
                    } else {
                        conn = false;
                    }
                }
            });
            return conn;
        }

        async function mysqlConn(){
            // test mysql connection
            const conn = await testConn();
            console.log(conn);
            return conn;
        }

        function getProgress(total){
            $.ajax({
                url: 'processed.php',
                success: function (data){
                    const content = Swal.getContent()
                    if (content) {
                        const l = content.querySelector('label')
                        if (l) {
                            switch (Number(data.split(";")[0])){
                                case 1:
                                    l.textContent = 'Creating sql queries to files.';
                                    break;
                                case 2:
                                    l.textContent = 'Processing sql files from DBF.';
                                    break;

                                case 3:
                                    l.textContent = 'Processing TXT files.';
                                    break;
                                case 4:
                                    l.textContent = 'Processing CSV files.';
                                    break;
                            }
                        }
                        const b = content.querySelector('b')
                        if (b) {
                            if (Number(data.split(";")[1]) > 0) {
                                b.textContent = '' + Math.round(((Number(data.split(";")[1]) * 100) / total) * 100) / 100 + ' %';
                            } else {
                                b.textContent = '0 %';
                            }
                        }
                    }
                },
                error: function () {
                    clearInterval(tmrProgress);
                }

            })
        }

        function _(el) { return document.getElementById(el); }

        $(document).ready(function(){
            credentials();
        });

    </script>
</head>
<body>
<div class="wrapper">
    <div id="container"><img src="logo.png">
        <div class="sub_container">
    <input type="hidden" id="server">
    <input type="hidden" id="database">
    <input type="hidden" id="user">
    <input type="hidden" id="pass">

            <div class="dropzone" id="dropzone">
                <div class="fallback">
                    <input name="file" type="file" multiple />
                </div>
                <div class="dz-message needsclick">
                    <h1 style="color: #ffffff">Drag and drop files</h1><label style="color: #ffffff">Valid formats:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </label><label style="font-size: large; color: #6cd463">zip, tar, csv, txt, dbf</label>
                </div>

            <div class="dz-preview dz-file-preview">

            </div></div>
        </div>
    </div>
</div>
<div id="footer">
    <div class="container">
        <div class="pull-right hidden-xs">
            <a href="https://github.com/saulgonzalez76/dbf2mysql.git"><i class="fab fa-github-square"></i> Github repo</a>
            Version <b><?= date("ymd",filectime(__FILE__)); ?></b>
        </div>
        <strong>Copyright &copy; <?= date("Y");?> <a href="https://saulgonzalez.dev">Saul Gonzalez</a>.</strong> MIT License
    </div>
    <!-- /.container -->
</div>
</body>
</html>
