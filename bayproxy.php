<?php

/**
 * www.devvblog.com
 */
session_start();

define("_THEPIRATEBAY_", "thepiratebay.se");

class BayProxy {
	private $content_headers = array (
		"jpg" => "image/jpg",
		"png" => "image/png",
		"gif" => "image/gif",
		"css" => "text/css",
		"xml" => "application/opensearchdescription+xml",
		"js" => "text/javascript",
		"torrent" => "application/x-bittorrent"
	);
	private $fqdn;

	function __construct() {
	}

	function run() {
		if (isset ($_GET["p"])) {
			if (!$this->isFile($_GET["p"])) {
				$this->fqdn = $this->getFQDN($_GET["p"]);
				$_SESSION["fqdn"] = $this->fqdn;
				print ($this->getParsedHTML($this->getData($_GET["p"])));
			} else {
				$this->getFile($_GET["p"]);
			}
		} else {
			if (isset ($_GET["q"]) && isset ($_SESSION["fqdn"])) {
				$this->fqdn = $_SESSION["fqdn"];
				print ($this->getParsedHTML($this->getData($this->fqdn . "/search/" . $_GET["q"])));
			} else {
				header("Location: " . $_SERVER["PHP_SELF"] . "?p=" . _THEPIRATEBAY_);
			}
		}
	}

	private function getScheme($url) {
		$parsed = parse_url($url);
		if (!array_key_exists("scheme", $parsed)) {
			return "http://";
		} else {
			return "";
		}
	}

	private function getFile($url) {
		preg_match("/\.(" . $this->getExtensions() . ")$/i", $url, $matches);
		if (count($matches) >= 1) {
			$fp = fopen($this->getScheme($url) . $url, "rb");
			header("Content-Type: " . $this->content_headers[$matches[1]]);
			fpassthru($fp);
			exit;
		}
	}

	private function isHost($host) {
		return count(explode(".", $host)) > 1 && !preg_match("/php|html|htm|asp|aspx/i", $host);
	}

	private function getParsedHTMLCallback($matches) {
		$match = $matches[3];
		do {
			$match = trim($match, "/");
		} while (preg_match("/^\/\/$/i", $match));
		$host = $this->getHost($match);
		if ($this->isHost($host)) {
			if (preg_match("/" . $this->getHost($this->fqdn) . "/i", $host)) {
				return $matches[1] . '="' . $_SERVER["PHP_SELF"] . '?p=' . $match . '"';
			} else {
				return $matches[1] . '="' . $match . '"';
			}
		} else {
			return $matches[1] . '="' . $_SERVER["PHP_SELF"] . '?p=' . $this->fqdn . '/' . $match . '"';
		}
	}

	private function getParsedHTML($data) {
		return preg_replace_callback('/(href|src|action)=("|\')((http:\/\/|https:\/\/)?([a-z0-9\.-]+)?(:[0-9]+)?[a-z0-9\._\/\-\?\=%&\(\)\[\]\+\!]+)("|\')/i', array (
			$this,
			"getParsedHTMLCallback"
		), $data);
	}

	private function getData($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Opera/9.23 (Windows NT 5.1; U; en)");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	private function getFQDN($url) {
		preg_match("/^(http:\/\/|https:\/\/)?([a-z0-9\._-]+(:[0-9]+)?)\/?/i", $url, $matches);
		if (count($matches) >= 2) {
			return $matches[2];
		}
		die("Whoops, something went wrong. Please try again.");
	}

	private function getHost($url) {
		$host = "";
		preg_match("/^(http:\/|https:\/)?(\/+)?([a-z0-9\.-]+)?(:[0-9]+)?\/?/i", $url, $matches);
		if (count($matches) >= 3) {
			$host = $matches[3];
			if (count(explode(".", $host)) > 2) {
				preg_match("/([a-z0-9-]+)\.([a-z]{2,}|\.[a-z]{2,}\.[a-z]{2,})$/i", $host, $matches);
				if (count($matches) >= 1) {
					$host = $matches[0];
				}
			} else {
				$host = $matches[3];
			}
		}
		return $host;
	}

	private function getExtensionsCallback($value) {
		return $value . "|";
	}

	private function getExtensions() {
		return trim(implode(array_map(array (
			$this,
			"getExtensionsCallback"
		), array_keys($this->content_headers))), "|");
	}

	private function isFile($url) {
		return preg_match("/^(http:\/\/|https:\/\/)?[a-z0-9\.-]+(:[0-9]+)?[a-z0-9\._\/\-\?\=%&\(\)\[\]\+\!]+\.(" . $this->getExtensions() . ")$/i", $url);
	}
}
?>
<?php

$proxy = new BayProxy();
$proxy->run();
?>