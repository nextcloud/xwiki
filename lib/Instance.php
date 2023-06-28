<?php

namespace OCA\Xwiki;

use OCP\Http\Client\IClient;
use DOMDocument;

class Instance {
	public static Instance $DUMMY;

	public string $url;
	public string $token;
	public string $disabled;

	public function __construct(string $url, string $token = '', bool $disabled = false) {
		$this->url = Instance::fixURL($url);
		$this->token = $token;
		$this->disabled = $disabled;
	}

	public static function fromArray(array $instance, string $token = '', bool $disabled = false): Instance {
		return new Instance($instance['url'], $token, $disabled);
	}


	public static function fixURL(string $url): string {
		if (empty($url)) {
			return '';
		}

		$url = strtolower(trim($url));
		if (!str_starts_with($url, 'https:') && !str_starts_with($url, "http:")) {
			$url = (
				filter_var(parse_url('http://' . $url, PHP_URL_HOST), FILTER_VALIDATE_IP) === false
					? 'https://'
					: 'http://'
			) . $url;
		}

		return rtrim($url, '/');
	}

	private static function _getVersion($q): ?string {
		$matches = null;
		preg_match('#<version(?:[\s][^>]*)?>([^<]+)</version>#', $q, $matches);
		$version = $matches[1];
		return $version;
	}

	public static function pingURL(IClient $client, $l10n, string $url): array {
		$restURL = $url . '/rest';
		try {
			$q = $client->get($restURL)->getBody();
		} catch (\Exception) {
			$q = '';
		}

		if (empty($q)) {
			$workedWithXWikiAtTheEnd = false;
			if (!str_ends_with($url, '/xwiki')) {
				$url .= '/xwiki';
				$restURL = $url . '/rest';
				try {
					$q = $client->get($restURL)->getBody();
				} catch (\Exception) {
					$q = '';
				}
				if (!empty($q)) {
					$workedWithXWikiAtTheEnd = true;
				}
			}

			if (!$workedWithXWikiAtTheEnd) {
				return [
					'ok' => false,
					'error' => $l10n->t('We did not get a successful reply from the instance (URL: %s)', [$restURL])
				];
			}
		}

		$version = Instance::_getVersion($q);
		if (empty($version)) {
			$workedWithXWikiAtTheEnd = false;
			if (!str_ends_with($url, '/xwiki')) {
				$url .= '/xwiki';
				try {
					$q = $client->get($url . '/rest')->getBody();
				} catch (\Exception) {
					$q = '';
				}
				if (!empty($q)) {
					$version = Instance::_getVersion($q);
					if (!empty($version)) {
						$workedWithXWikiAtTheEnd = true;
					}
				}
			}

			if (!$workedWithXWikiAtTheEnd) {
				return [
					'ok' => false,
					'error' => $l10n->t('We did not understand the instance’s version') . ' ' . $q
				];
			}
		}

		return [
			'ok' => true,
			'version' => $version,
			'url' => $url,
			'hasNextcloudApplication' => Instance::hasNextcloudApplication($url, $client)
		];
	}

	public function ping(IClient $client, $l10n): array {
		return Instance::pingURL($client, $l10n, $this->url);
	}

	public function setURL($url) {
		$this->url = Instance::fixURL($url);
	}

	public function isUserLogged(IClient $client): ?bool {
		$opts = $this->getHTTPClientOpts();
		try {
			return !empty($client->get("$this->url/rest", $opts)->getHeader('XWiki-User'));
		} catch (\Exception) {
			return null;
		}
	}

	private function getRestURL(string $wiki, string $pageOrSpace, string $lastPart): string {
		$restURL = "$this->url/rest/wikis/$wiki";
		$pageParts = $this->getPageParts($pageOrSpace);

		$lenMinusOne = count($pageParts) - 1;

		for ($i = 0; $i < $lenMinusOne; $i++) {
			$restURL .= '/spaces/' . $pageParts[$i];
		}

		return $restURL . '/' . $lastPart . '/' . $pageParts[$lenMinusOne];
	}

	public static function getPageParts(string $pageOrSpace) {
		return explode('/', Instance::dotsToSlashes($pageOrSpace));
	}

	public function getPageRestURL(string $wiki, string $page): string {
		return $this->getRestURL($wiki, $page, 'pages');
	}

	public function getPageListRestURL(string $wiki, string $space): string {
		return $this->getRestURL($wiki, $space, 'spaces') . '/pages';
	}

	public static function dotsToSlashes(string $page): string {
		$pageRelativeURL = '';
		for ($i = 0, $len = strlen($page); $i < $len; $i++) {
			if ($page[$i] === '.') {
				$pageRelativeURL .= '/';
			} else {
				if ($page[$i] === '\\' && $i + 1 < $len) {
					$i++;
				}
				$pageRelativeURL .= $page[$i];
			}
		}
		return $pageRelativeURL;
	}

	public function getLinkFromId(string $id, string $action = 'view'): string {
		[$wiki, $page]  = explode(':', $id, 2);
		$baseLink = (
			$this->url .
			'/' . (
				$wiki === 'xwiki'
					? 'bin'
					: ('wiki/' . $wiki)
			) . '/' . $action . '/'
		);

		return $baseLink . Instance::dotsToSlashes($page);
	}

	public static function getIdFromLink(string $link, string $instanceURL, bool $forceView): string {
		$instanceURLAfterDomain =  preg_replace('|^https?://[^/]+/+|i', '', $instanceURL);

		if (str_starts_with(strtolower($link), 'http') && !str_starts_with($link, $instanceURL)) {
			return '';
		}

		$link = preg_replace('|^https?://[^/]+/+|i', '', $link);
		if (!str_starts_with($link, $instanceURLAfterDomain)) {
			return '';
		}

		$link = substr($link, strlen($instanceURLAfterDomain));
		$link = preg_replace('|/{2,}|', '/', $link);
		$link = preg_replace('|^/+|', '', $link);
		$link = preg_replace('|/+$|', '/WebHome', $link);
		$link = preg_replace('|\\?.*//$|', '', $link);
		$link = preg_replace('|\\#.*//$|', '', $link); // FIXME keep the anchor

		$parts = explode('/', $link);
		if (!count($parts) ) {
			return 'xwiki:Main';
		}

		$wiki = 'xwiki';
		if ($parts[0] === 'wiki') {
			$wiki = $parts[1];
			if ($forceView && $parts[2] !== 'view') {
				return '';
			}
			$pageParts = array_slice($parts, 3);
		} else if ($parts[0] === 'bin') {
			if ($forceView && $parts[1] !== 'view') {
				return '';
			}
			$pageParts = array_slice($parts, 2);
		} else {
			$pageParts = $parts;
		}

		$len = count($pageParts);
		for ($i = 0; $i < $len; $i++) {
			$pageParts[$i] = str_replace('.', '\\.', $pageParts[$i]);
		}

		$page = join('.', $pageParts);
		if (empty($page)) {
			$page = 'Main';
		}

		return $wiki . ':' . urldecode($page);
	}

	public function getHomeURL(string $action = 'view'): string {
		return $this->getLinkFromId('xwiki:WebHome', $action);
	}

	public function getPrettyName(): string {
		$host = parse_url($this->url, PHP_URL_HOST);

		if (empty($host)) {
			$host = $this->url;
		}

		if (str_starts_with($host, 'http://')) {
			$host = substr($host, 7);
		} else if (str_starts_with($host, 'https://')) {
			$host = substr($host, 8);
		}

		if (str_starts_with($host, 'www.')) {
			$host = substr($host, 4);
		}

		if (str_ends_with($host, '/xwiki')) {
			$host = substr($host, 0, strlen($host) - 6);
		}

		return $host;
	}

	private function getHTTPClientOpts() {
		$opts = [];
		if (!empty($this->token)) {
			$opts['headers'] = ['Authorization' => 'Bearer ' . $this->token];
		}
		return $opts;
	}

	public function getFile(string $url, IClient $client): ?string {
		if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
			if ($url[0] !== '/') {
				$url = '/' . $url;
			}
			$url = $this->url . $url;
		}

		try {
			return $client->get($url, $this->getHTTPClientOpts())->getBody();
		} catch (\Exception) {
			return null;
		}
	}

	// returns
	//  - true if the instance has nextcloud integration
	//  - false if it does not have it
	//  - null if we don't know
	public static function hasNextcloudApplication($url, IClient $client): ?bool {
		$matches = [];
		try {
			$status = $client->head(
				$url . '/rest/wikis/xwiki/spaces/Nextcloud/pages/WebHome',
			)->getStatusCode();
		} catch (\Exception) {
			return false;
		}

		if ($status === 200) {
			return true;
		}

		if ($status === 404) {
			return false;
		}

		// Could be that the instance requires authentication, or some other error
		return null;
	}

	public function loadXML(string $url, IClient $client) {
		$doc = new DOMDocument();
		try {
			$f = $this->getFile($url, $client);
			if (empty($f)) {
				return null;
			}
			$doc->loadXML($f);
		} catch (\Exception) {
			return null;
		}
		return $doc;
	}

	public function loadHTML(string $url, IClient $client) {
		try {
			$file = $this->getFile($url, $client);
			if (empty($file)) {
				return null;
			}
			$doc = new DOMDocument();
			$doc->loadHTML($file);
		} catch (\Exception) {
			return null;
		}

		return $doc;
	}
}

Instance::$DUMMY = new Instance('');
