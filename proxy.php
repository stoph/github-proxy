<?php
$start_time = microtime(true);

$action     = $_GET['action'] ?? null;
$repo       = $_GET['repo'] ?? null;
$branch     = $_GET['branch'] ?? null;
$pr         = $_GET['pr'] ?? null; // numeric
$commit     = $_GET['commit'] ?? null; // alphanumeric
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

// Hide output from exec calls in logs when not in debug mode
$exec_redirection = $debug ? '' : ' > /dev/null 2>&1';

if ($debug) { $debug_log .= "Repo: $repo\n";}
$repo_name = explode('/', $repo);
$repo_name = end($repo_name);

if ($directory) {
  $action = 'partial';
  $path   = "$repo/$directory";
  if ($debug) { $debug_log .= "Directory: $directory\n";}
} else {
  $action = 'archive';
  $path   = $repo;
}
if ($debug) { $debug_log .= "Action: $action\n";}
$path = str_replace('/', '-', $path);

// Determine reference type for the checkout PR, Commit, or Branch based
if ($pr) {
  $reference = 'pr';
  $repo_filename = "$repo_name-pr_$pr.zip";
  if ($debug) { $debug_log .= "Reference > PR: $pr\n";}
} elseif ($commit) {
  $reference = 'commit';
  $repo_filename = "$repo_name-commit_$commit.zip";
  if ($debug) { $debug_log .= "Reference > Commit: $commit\n";}
} else {
  $reference = 'branch';
  if (!$branch) {
    $branch = getDefaultBranch($repo);
    if (!$branch) {
      http_response_code(400);
      die('Invalid default branch');
    }
  }
  $repo_filename = $repo_name . '-branch_' . str_replace('/', '-', $branch) . '.zip';
  if ($debug) { $debug_log .= "Reference > Branch: $branch\n";}
}

if ($debug) { $debug_log .= "Repo filename: $repo_filename\n";}

$unique_id = uniqid( str_replace('/', '-', $repo). '_');

// echo "<xmp>$debug_log</xmp>";
// die();

$github_repo_url = "https://github.com/$repo.git";
if ($debug) { $debug_log .= "Github repo url: $github_repo_url\n";}

$temp_checkout_dir = $checkouts_dir . '/' . $unique_id;
if ($debug) { $debug_log .= "Temp checkout directory: $temp_checkout_dir\n";}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $repo_filename . '"');
header('Pragma: no-cache');

switch ($action) {
  case 'archive':
    // What type of reference are we using to get the full archive
    switch ($reference) {
      case 'pr':
        // Create and change to a temp directory for checkouts
        mkdir($temp_checkout_dir, 0755, true);
        chdir($temp_checkout_dir);

        $command = "git clone --depth 1 --filter=blob:none --no-checkout " . escapeshellarg($github_repo_url);
        exec($command . $exec_redirection);
        if ($debug) { $debug_log .= "[Clone] $command\n"; }

        chdir($repo_name);
        if ($debug) { $debug_log .= "[Change directory] cd $repo_name\n"; }

        $command = "git fetch origin " . escapeshellarg("pull/$pr/head");
        exec($command . $exec_redirection);
        if ($debug) { $debug_log .= "[Fetch] $command\n"; }

        $command = "git checkout FETCH_HEAD";
        exec($command . $exec_redirection);
        if ($debug) { $debug_log .= "[Checkout] $command\n"; }
        
        zipAndServe(".", $checkouts_dir, $temp_checkout_dir);
        break;
      case 'commit':
        $url = "https://github.com/$repo/archive/$commit.zip";
        readfile($url);
        break;
      case 'branch':
        $url = "https://github.com/$repo/archive/$branch.zip";
        readfile($url);
        break;
    }
    break;

  case 'partial':
    // Create and change to a temp directory for checkouts
    mkdir($temp_checkout_dir, 0755, true);
    chdir($temp_checkout_dir);

    switch ($reference) {
      case 'pr':
        $command = "git clone --depth 1 --filter=blob:none --sparse --no-checkout " . escapeshellarg($github_repo_url);
        break;
      case 'commit':
        // We have to get the full history to have access to all commits (this is slow)
        $command = "git clone --filter=blob:none --sparse --no-checkout " . escapeshellarg($github_repo_url);
        break;
      case 'branch':
        // Only get the specific branch with a depth of 1
        $command = "git clone --depth 1 --filter=blob:none --sparse --no-checkout --branch " . escapeshellarg($branch) . " " . escapeshellarg ($github_repo_url);
        break;
    }
    
    exec($command . $exec_redirection);
    if ($debug) { $debug_log .= "[Clone] $command\n"; }

    chdir($repo_name);
    if ($debug) { $debug_log .= "cd $repo_name\n"; }

    $command = "git config core.sparseCheckout true";
    exec($command . $exec_redirection);
    if ($debug) { $debug_log .= "[Setting sparseCheckout] $command\n"; }

    // Set only the directory we need
    $command = "git sparse-checkout set " . escapeshellarg($directory) . " --no-cone";
    exec($command . $exec_redirection);
    if ($debug) { $debug_log .= "[Setting sparse directories] $command\n"; }
    
    // Get specific PR or commit
    switch ($reference) {
      case 'pr':
        // Fetch specific PR (in a detached HEAD state)
        $command = "git fetch origin " . escapeshellarg("pull/$pr/head");
        exec($command . $exec_redirection);
        if ($debug) { $debug_log .= "[Fetch PR] $command\n"; }
        // Checkout from temp reference (FETCH_HEAD) for last fetched commit
        $command = "git checkout FETCH_HEAD";
        break;
      case 'commit':
        // Checkout specific commit
        $command = "git checkout " . escapeshellarg($commit);
        break;
      case 'branch':
        // Checkout specific branch (defined in clone above) at HEAD
        $command = "git read-tree -mu HEAD";
        break;
    }

    exec($command . $exec_redirection);
    if ($debug) { $debug_log .= "[Checkout] $command\n"; }

    zipAndServe($directory, $checkouts_dir, $temp_checkout_dir);
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

function zipAndServe($directory, $checkouts_dir, $temp_checkout_dir) {
  global $debug, $debug_log;
  
  $command = "zip -rq - " . escapeshellarg($directory);
  if ($debug) { $debug_log .= "[Zipping files] $command\n";}
  passthru($command);

  // Remove this temporary checkout directory
  chdir($checkouts_dir);
  if (!$debug) {
    exec("rm -rf $temp_checkout_dir");
  }
}

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
