<?php
class Nginx_Xaccel extends Plugin {
	private const DEFAULT_PREFIX = '/tt-rss';

	function about() {
		return [
			null,
			'Sends static files via nginx X-Accel-Redirect header',
			'fox',
			true,
			'https://github.com/tt-rss/tt-rss-plugin-nginx-xaccel',
		];
	}

	function init($host) {
		Config::add('NGINX_XACCEL_PREFIX', self::DEFAULT_PREFIX);

		$host->add_hook($host::HOOK_SEND_LOCAL_FILE, $this);
	}

	/**
	 * @param string $filename The full path of the file to send.
	 * @return bool true if the plugin handled the request, false otherwise
	 */
	function hook_send_local_file($filename) {
		$app_base = getenv('APP_BASE');
		$cfg_nginx_xaccel_prefix = Config::get('NGINX_XACCEL_PREFIX');

		$nginx_xaccel_prefix = match(true) {
			// Use APP_BASE if set and NGINX_XACCEL_PREFIX is the default (as APP_BASE is more likely to be accurate).
			$app_base !== false && $cfg_nginx_xaccel_prefix === self::DEFAULT_PREFIX => $app_base,
			// Otherwise use NGINX_XACCEL_PREFIX if it exists and isn't an empty string, which people might be using
			// as an indicator to disable this behavior (per old forum discussions).  Use '/' for the value if needed.
			$cfg_nginx_xaccel_prefix !== '' => $cfg_nginx_xaccel_prefix,
			// Otherwise do nothing and let Cache_Local handle things.
			default => null,
		};

		if ($nginx_xaccel_prefix === null)
			return false;

		// $cache_path will have a leading '/'
		$nginx_xaccel_prefix = rtrim($nginx_xaccel_prefix, '/');

		// Strip the base/self directory from the full path to get something like '/cache/x/y'.
		// For the sake of simplicity we'll assume the default of 'cache' is used for CACHE_DIR.
		$cache_path = mb_substr($filename, mb_strlen(Config::get_self_dir()));

		if (str_starts_with($cache_path, '/cache/')) {
			$mimetype = mime_content_type($filename);

			// this is hardly ideal but 1) only media is cached in images/ and 2) seemingly only mp4
			// video files are detected as octet-stream by mime_content_type()
			if ($mimetype == 'application/octet-stream')
				$mimetype = 'video/mp4';

			header("Content-type: $mimetype");

			$stamp = gmdate('D, d M Y H:i:s', filemtime($filename)) . ' GMT';
			header("Last-Modified: $stamp", true);

			header('X-Accel-Redirect: ' . $nginx_xaccel_prefix . $cache_path);

			return true;
		}

		return false;
	}

	function api_version() {
		return 2;
	}
}
