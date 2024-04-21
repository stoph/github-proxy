<?php
$start_time = microtime(true);

$action     = $_GET['action'] ?? null;
$repo       = $_GET['repo'] ?? null;
$branch     = $_GET['branch'] ?? null;
$directory  = $_GET['directory'] ?? null;
$debug      = $_GET['debug'] ?? false;

$debug_log      = '';
$checkouts_dir  = __DIR__ . '/checkouts';
$logs_dir       = __DIR__ . '/logs';
if (!file_exists($logs_dir)) {
  mkdir($logs_dir, 0755, true);
}

if (!$repo) {
  http_response_code(400);
  die('Repo required');
}

$repo_name = end(explode('/', $repo));

if (!$branch) {
  $branch = getDefaultBranch($repo);
  if (!$branch) {
    http_response_code(400);
    die('Invalid default branch');
  }
}
if ($debug) { $debug_log .= "Branch: $branch\n";}

$repo_filename = "$repo_name-$branch";
if ($debug) { $debug_log .= "Repo name: $repo_name\n";}
if ($debug) { $debug_log .= "Repo filename: $repo_filename\n";}


$unique_id = uniqid($action . '_' . str_replace('/', '-', $repo) . '_' . $branch . '_' . str_replace('/', '-', $directory) . '_');
if ($debug) { $debug_log .= "Unique id: $unique_id\n";}

switch ($action) {
  case 'archive':
    $url = "https://github.com/$repo/archive/$branch.zip";
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $repo_filename . '.zip"');
    header('Pragma: no-cache');
    readfile($url);
    break;

  case 'partial':
    if (!$directory) {
      http_response_code(400);
      die('Directory required for partial action');
    }

    if (!file_exists($checkouts_dir)) {
      mkdir($checkouts_dir, 0755, true);
    }
    
    $temp_checkout_dir = $checkouts_dir . '/' . $unique_id;
    if ($debug) { $debug_log .= "Temp dir: $temp_checkout_dir\n";}

    mkdir($temp_checkout_dir);
    chdir($temp_checkout_dir);

    $github_repo_url = "https://github.com/$repo.git";
    if ($debug) { $debug_log .= "Github repo url: $github_repo_url\n";}

    $command = "git clone --depth 1 --filter=blob:none --sparse --no-checkout --branch $branch $github_repo_url";
    exec($command);
    if ($debug) { $debug_log .= "$command\n"; }

    chdir($repo_name);
    if ($debug) { $debug_log .= "cd $repo_name\n"; }

    $command = "git config core.sparseCheckout true";
    exec($command);
    if ($debug) { $debug_log .= "$command\n"; }

    // Set only the directory we need
    $command = "git sparse-checkout set $directory --no-cone";
    exec($command);
    if ($debug) { $debug_log .= "$command\n"; }
    
    $command = "git read-tree -mu HEAD";
    exec($command);
    if ($debug) { $debug_log .= "$command\n"; }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $repo_filename . '.zip"');
    header('Pragma: no-cache');

    $command = "zip -r - $directory";
    if ($debug) { $debug_log .= "Zipping file: $command\n";}
    passthru($command);

    chdir($checkouts_dir);
    if (!$debug) {
      exec("rm -rf $temp_checkout_dir");
    }
    break;

  default:
    header('Location: /');
    exit;
}
$elapsed_time = (microtime(true) - $start_time) * 1000;

if ($debug) { 
  $debug_log .= "Total time: $elapsed_time ms\n";
  file_put_contents("$logs_dir/$unique_id.log", $debug_log);
}

$usage = date('Y/m/d g:i:s a') . " [$elapsed_time ms] " . print_r($_GET, true) . "\n";
file_put_contents("$logs_dir/usage.log", $usage, FILE_APPEND);

function getDefaultBranch($repo) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/$repo");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

  $response = curl_exec($ch);
  curl_close($ch);

  if ($response) {
    $data = json_decode($response, true);
    $default_branch = $data['default_branch'] ?? 'unknown';

    return $default_branch;
  } else {
    return false;
  }
}
