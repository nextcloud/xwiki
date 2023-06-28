<?php

namespace OCA\Xwiki;

use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;

class SettingsManager {
	private $userId;
	private ISecureRandom $secureRandom;
	private IConfig $config;
	private IURLGenerator $urlGenerator;
	// https://github.com/nextcloud/server/blob/c6ae53096c36e6a475467eaeb6df00ac8d38e4b2/apps/oauth2/lib/Controller/SettingsController.php#L78-L79
	public const validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	public function __construct(
		$UserId,
		IConfig $config,
		ISecureRandom $secureRandom,
		IURLGenerator $urlGenerator
	) {
		$this->config = $config;
		$this->secureRandom = $secureRandom;
		$this->userId = $UserId;
		$this->urlGenerator = $urlGenerator;
	}

	public function getFromUserJSON($key, $defaultJSONString): mixed {
		return $this->getFromJSON(
			$this->config->getUserValue($this->userId, 'xwiki', $key, $defaultJSONString),
			$defaultJSONString
		);
	}

	public function getFromAppJSON($key, $defaultJSONString): mixed {
		return $this->getFromJSON(
			$this->config->getAppValue('xwiki', $key, $defaultJSONString),
			$defaultJSONString
		);
	}

	private function getFromJSON($configVal, $defaultJSONString): mixed {
		$configVal = empty($configVal) ? '' : json_decode($configVal, true);

		if (($defaultJSONString === '[]' || is_array($defaultJSONString)) && !is_array($configVal)) {
			return [];
		}

		return $configVal;
	}

	public function getInstance(string $url): Instance | null {
		foreach ($this->getFromAppJSON('instances', '[]') as $instance) {
			if ($instance['url'] === $url) {
				return Instance::fromArray(
					$instance,
					$this->getUserToken($url),
					$this->getUserDisabled($url)
				);
			}
		}
		return null;
	}

	public function getInstances(): array {
		$res = [];
		foreach ($this->getFromAppJSON('instances', '[]') as $instance) {
			$url = $instance['url'];
			if (!empty($url)) {
				$res[] =  Instance::fromArray(
					$instance,
					$this->getUserToken($url),
					$this->getUserDisabled($url)
				);
			}
		}
		return $res;
	}


	public function getUserToken(string $url) {
		$tokens = $this->getFromUserJSON('tokens', '{}');
		if (!empty($tokens[$url])) {
			return $tokens[$url];
		}

		return '';
	}

	public function getUserDisabled(string $url) {
		$disabledInstances = $this->getFromUserJSON('disabledInstances', '{}');
		if (!empty($disabledInstances[$url])) {
			return $disabledInstances[$url];
		}

		return false;
	}

	public function setUserDisabled(string $url, bool $disabled) {
		$disabledInstances = $this->getFromUserJSON('disabledInstances', '{}');
		$disabledInstances[$url] = $disabled;
		$this->saveAsUserJSON('disabledInstances', $disabledInstances);
	}

	public function deleteUserValue($userId, $key) {
		$this->config->deleteUserValue($this->userId, 'xwiki', $key);
	}

	public function saveAsAppJSON($key, $value) {
		$this->config->setAppValue(
			'xwiki',
			$key,
			json_encode(
				$value,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK
			)
		);
	}

	private function saveAsUserJSON(string $key, mixed $value): void {
		$this->saveAsUserString(
			$key,
			json_encode(
				$value,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK
			)
		);
	}

	public function setUserToken(string $url, string $token) {
		$tokens = $this->getFromUserJSON('tokens', '{}');
		if (empty($token)) {
			unset($tokens[$url]);
		} else {
			$tokens[$url] = $token;
		}
		$this->saveAsUserJSON('tokens', $tokens);
	}


	public function saveAsUserString(string $key, string $value): void {
		$this->config->setUserValue($this->userId, 'xwiki', $key, $value);
	}

	public function getRedirectURI() {
		return $this->urlGenerator->linkToRouteAbsolute('xwiki.settings.oidcRedirect');
	}

	public function getClientId(): string {
		// see https://help.nextcloud.com/t/instanceid-a-suitable-client-id-value-for-openid-connect/154552
		$clientId = $this->config->getAppValue(
			'xwiki',
			'clientId',
			''
		);

		if (empty($clientId)) {
			$clientId = $this->secureRandom->generate(64, self::validChars);
		}

		$this->config->setAppValue(
			'xwiki',
			'clientId',
			$clientId
		);

		return $clientId;
	}
}
