<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Super simple proxy service I built for a very specific need that no one else besides me will probably ever use">
  <title>GitHub Proxy</title>
  <script src="https://cdn.tailwindcss.com/"></script>
</head>
<body class="flex flex-col justify-center min-h-screen bg-gray-50">
  <div class="text-center px-4">
    <h1 class="text-5xl font-bold mb-4">GitHub Proxy</h1>
    <p class="text-xl text-gray-600 mb-8">This is a proxy for the GitHub's archive files.<br>It is intended to be used as resource urls for <a href="https://wordpress.org/playground/">WordPress Playground</a>.</p>
    <div class="max-w-md mx-auto">
      <label class="block text-gray-700 text-lg font-bold mb-2" for="url">
        Sample URL Format
      </label>
      <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="url" type="text" value="https://github-proxy.com/archive/{repo}[/{branch}]" readonly>
      <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="url" type="text" value="https://github-proxy.com/partial/{repo}/{directory}" readonly>
    </div>
  </div>
  <footer class="fixed inset-x-0 bottom-0 py-4 bg-gray-800 text-center text-xs text-gray-300 grid grid-cols-3 gap-4">
    <div class="flex justify-center">
      <span>stoph</span>
    </div>
    <div class="flex justify-center">
      <span></span>
    </div>
    <div class="flex justify-center">
      <span>Made in Charlotte</span>
    </div>
  </footer>
</body>
</html>