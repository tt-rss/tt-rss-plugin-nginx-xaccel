<?php
class Nginx_Xaccel extends Plugin {

	function about() {
		return [
			1.1,
			'Sends static files via nginx X-Accel-Redirect header',
			'fox',
			true,
			'https://github.com/tt-rss/tt-rss-plugin-nginx-xaccel',
		];
	}

	function init($host) {
		// In the official Docker setup this corresponds to the web-nginx container's APP_BASE.
		Config::add('NGINX_XACCEL_PREFIX', '/tt-rss');

		$host->add_hook($host::HOOK_SEND_LOCAL_FILE, $this);
	}

	/**
	 * @param string $filename The full path of the file to send.
	 * @return bool true if the plugin handled the request, false otherwise
	 */
	function hook_send_local_file($filename) {
		// Strip the base/self directory from the full path to get something like '/cache/x/y'.
		// For the sake of simplicity we'll assume the default of 'cache' is used for CACHE_DIR.
		$cache_path = mb_substr($filename, mb_strlen(Config::get_self_dir()));

		if (Config::get('NGINX_XACCEL_PREFIX') && str_starts_with($cache_path, '/cache/')) {
			$mimetype = mime_content_type($filename);

			// this is hardly ideal but 1) only media is cached in images/ and 2) seemingly only mp4
			// video files are detected as octet-stream by mime_content_type()
			if ($mimetype == 'application/octet-stream')
				$mimetype = 'video/mp4';

			header("Content-type: $mimetype");

			$stamp = gmdate('D, d M Y H:i:s', filemtime($filename)) . ' GMT';
			header("Last-Modified: $stamp", true);

			header('X-Accel-Redirect: ' . Config::get('NGINX_XACCEL_PREFIX') . $cache_path);

			return true;
		}

		return false;
	}

	function api_version() {
		return 2;
	}
}
