<?php
if ( !isset($_GET['url']) ) {
  include 'homepage.html';
  die();
}

$gh_url = 'https://github.com/' . $_GET['url'];

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="github.zip"');
header('Pragma: no-cache');
readfile($gh_url);

/*
// Only download zip files.
$ch = curl_init($gh_url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($content_type === 'application/octet-stream') {
  readfile($gh_url);
} else {
  // Return a predefined plugin that displays the error message in the admin :)
  die('The file is not an application/octet-stream.');
}
*/