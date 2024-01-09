<?php

$action = $_GET['action'] ?? null;
$repo   = $_GET['repo'] ?? null;
$branch = $_GET['branch'] ?? 'main';

if ($action !== 'archive') {
  header('Location: /');
  exit;
}

if (!$repo) {
  http_response_code(400);
  die('Repo required');
}

$url = "https://github.com/$repo/archive/$branch.zip";

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $branch . '.zip"');
header('Pragma: no-cache');
readfile($url);