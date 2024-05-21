<?php
if ( !isset($_GET['url']) ) {
  include 'homepage.html';
  die();
}

$gh_url = 'https://github.com/' . $_GET['url'];

// Only download zip files.
$ch = curl_init($gh_url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

$curl_info = curl_getinfo($ch);
curl_close($ch);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/zip');
// header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="github.zip"');
header('Pragma: no-cache');

$error_array = [];
if ($curl_info['content_type'] != 'application/octet-stream') {
  array_push($error_array, '• Unexpected content type. Expected <i>application/octet-stream</i>, got <i>' . $curl_info['content_type'] . '</i>');
}
if ($curl_info['http_code'] != 200) {
  array_push($error_array, '• Unexpected HTTP code. Expected <i>200</i>, got <i>' . $curl_info['http_code'] . '</i>');
}
// Not sure if this is a consistent/reliable header yet.
// if ($curl_info['download_content_length'] <= 0) {
//   array_push($error_array, '• Empty file');
// }

if (empty($error_array)) {
  header('Content-Length: ' . $curl_info['download_content_length'] );
  readfile($gh_url);
} else {
  // Return a plugin that displays the error message in the admin :)
  $message = '';
  foreach($error_array as $error) {
    $message .= $error . '<br>';
  }

  $plugin = <<<PLUGIN
<?php
    /*
    Plugin Name: Error Message Plugin
    Description: Temporary error message from Github Proxy for WP Playground
    Author: Github Proxy
    */
    function displayProxyErrorMessage() {
      echo '<div class="notice notice-error"><p>The proxied file generated an error from Github.<br>';
      echo '$message';
      echo '</p></div>';
    }
    add_action('admin_notices', 'displayProxyErrorMessage');
    ?>
PLUGIN;
  
  $zip = new ZipArchive();
  $zip_file = 'checkouts/error_plugin.zip';
  $ret = $zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
  $zip->addFromString('error_plugin.php', $plugin);
  $zip->close();
  //header('Content-Length: ' . filesize($zip_file));
  readfile($zip_file);
  unlink($zip_file);
}
