<?php
class Nginx_Xaccel extends Plugin {

	function about() {
		return array(1.0,
			"Sends static files via nginx X-Accel-Redirect header",
			"fox",
			true,
			"https://git.tt-rss.org/fox/ttrss-nginx-xaccel");
	}

	function init($host) {
		Config::add("NGINX_XACCEL_PREFIX", "/tt-rss");

		$host->add_hook($host::HOOK_SEND_LOCAL_FILE, $this);
	}

	function hook_send_local_file($filename) {

		if (Config::get("NGINX_XACCEL_PREFIX") && mb_strpos($filename, "cache/") === 0) {

			$mimetype = mime_content_type($filename);

			// this is hardly ideal but 1) only media is cached in images/ and 2) seemingly only mp4
			// video files are detected as octet-stream by mime_content_type()

			if ($mimetype == "application/octet-stream")
				$mimetype = "video/mp4";

			header("Content-type: $mimetype");

			$stamp = gmdate("D, d M Y H:i:s", filemtime($filename)) . " GMT";
			header("Last-Modified: $stamp", true);

			header("X-Accel-Redirect: " . Config::get("NGINX_XACCEL_PREFIX") . "/" . $filename);

			return true;
		}

		return false;
	}

	function api_version() {
		return 2;
	}

}
?>
