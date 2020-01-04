<?
header('Content-type: image/svg+xml');
echo preg_replace('/FFFFFF/', $_GET['h'], file_get_contents("oliver_logo_thicker.svg"));
