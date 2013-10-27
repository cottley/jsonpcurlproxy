<?php
    session_start();
    header("Access-Control-Allow-Origin: *");
    $ckfile = '/tmp/jsonpcurlproxy-cookie-'.session_id();
    unlink($ckfile);
    session_unset();
    session_destroy();
    session_write_close();
    setcookie(session_name(),'',0,'/');
    session_regenerate_id(true);
?>