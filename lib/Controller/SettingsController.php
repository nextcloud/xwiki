<?php

namespace OCA\Xwiki\Controller;

use OCA\Xwiki\Instance;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUser;
use OCP\Notification\IManager;
use OCP\PreConditionNotMetException;
use OCP\Util;

class SettingsController extends Controller {
	private $userId;
	private IL10N $l10n;
	private IConfig $config;
    private IClientService $clientService;
	private IManager $manager;
	private IURLGenerator $urlGenerator;
	private IUserManager $userManager;

	public function __construct(
		$appName,
		$UserId,
		IConfig $config,
		IClientService $clientService,
		IL10N $l10n,
		IManager $manager,
		IRequest $request,
		IURLGenerator $urlGenerator,
		IUserManager $userManager
	) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->config = $config;
		$this->clientService = $clientService;
		$this->request = $request;
		$this->urlGenerator = $urlGenerator;
		$this->userId = $UserId;
		$this->userManager = $userManager;
		$this->manager = $manager;
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

	/**
	 * @NoCSRFRequired
	*/
	public function addToken() {
		$instanceUrl = $this->request->getParam('i');
		$token = $this->request->getParam('token');
		$this->setUserToken($instanceUrl, $token);
		return new RedirectResponse(
			$this->urlGenerator->linkToRoute(
				'settings.PersonalSettings.index',
				['section' => 'xwiki']
			)
		);
	}

	public function requestToken() {
		$instanceUrl = $this->request->getParam('i');
		$instance = $this->getInstance($instanceUrl);
		if ($instance !== null) {
			$state = bin2hex(random_bytes(20));
			$states = $this->getFromAppJSON('oidc_states', []);
			$states[$state] = [
				'user' => $this->userId,
				'instance' => $instance->url,
				'date' => $_SERVER['REQUEST_TIME']
			];
			$this->saveAsAppJSON('oidc_states', $states);
			// FIXME check instance
			return new RedirectResponse(
				$instanceUrl
				. '/bin/view/Nextcloud/Tokens?' . http_build_query([
					'response_type' => 'code',
					'client_id' => $instance->clientId,
					'state' => $state,
					'redirect_uri' => $this->getRedirectURL()
				])
			);
		} // else todo
	}

	public function getRedirectURL() {
		return (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') .
			$_SERVER['SERVER_NAME'] .
			$this->urlGenerator->linkToRoute('xwiki.settings.oidcRedirect');
	}

	/**
	 * @NoCSRFRequired
	*/
	public function oidcRedirect() {
		$state = $this->request->getParam('state');
		$code = $this->request->getParam('code');
		$states = $this->getFromAppJSON('oidc_states', []);

		if (empty($states[$state])) {
			return new RedirectResponse(
				$this->urlGenerator->linkToRoute(
					'settings.PersonalSettings.index',
					['section' => 'xwiki', 'diagnostic' => 'state not found']
				)
			);
		}

		['instance' => $instanceURL, 'user' => $user] = $states[$state];
		unset($states[$state]);
		$this->saveAsAppJSON('oidc_states', $states);
		$instance = $this->getInstance($instanceURL);
		$error = $this->request->getParam('error');
		if (!empty($error)) {
			return new RedirectResponse(
				$this->urlGenerator->linkToRoute(
					'settings.PersonalSettings.index', [
						'section' => 'xwiki',
						'error' => $error,
						// 'error_description' => $this->request->getParam('error_description'),
						'i' => $instance->url
					]
				)
			);
		}

		if ($user === $this->userId) {
			$client = $this->clientService->newClient();
			try {
				$response = $client->post(
					$instanceURL . '/bin/view/Nextcloud/Tokens/Create', [
						'headers'  => [
							'Content-type: application/x-www-form-urlencoded',
						],
						'body' => http_build_query([
							'grant_type' => 'authorization_code',
							'code' => $code,
							'redirect_uri' => $this->getRedirectURL()
						])
					]
				);
			} catch (\Exception) {
				$response = '';
			}

			$t = json_decode($response, true);

			if (empty($t) || empty($t['access_token'])) {
				return new RedirectResponse(
					$this->urlGenerator->linkToRoute(
						'settings.PersonalSettings.index', [
							'section' => 'xwiki',
							'error' => 'missing_access_token',
							'i' => $instance->url
						]
					)
				);
			}

			$this->setUserToken($instanceURL, $t['access_token']);
		}

		return new RedirectResponse(
			$this->urlGenerator->linkToRoute(
				'settings.PersonalSettings.index',
				['section' => 'xwiki']
			)
		);
	}

	public function deleteToken() {
		$instanceUrl = $this->request->getParam('i');
		$this->setUserToken($instanceUrl, '');
		return new RedirectResponse(
			$this->urlGenerator->linkToRoute(
				'settings.PersonalSettings.index',
				['section' => 'xwiki']
			)
		);
	}

	public function setDisabled() {
		$instanceUrl = $this->request->getParam('i');
		$value = $this->request->getParam('v') === 'true';
		$this->setUserDisabled($instanceUrl, $value);
		return new JSONResponse(['ok' => true]);
	}

	public function setUserValue() {
		$key = $this->request->getParam('k');
		$value = $this->request->getParam('v');

		if (empty($key)) {
			return new JSONResponse([
				'error' => 'Mandatory parameter:' .
					' k. Use v to give the value. ' .
					'If unset, the setting will be removed'
			], Http::STATUS_BAD_REQUEST);
		}

		if ($key !== 'integratedMode') {
			return new JSONResponse(
				['error' => 'Unknown key ' . $key],
				Http::STATUS_BAD_REQUEST
			);
		}

		if (empty($value)) {
			$this->config->deleteUserValue($this->userId, 'xwiki', $key);
		}

		$this->saveAsUserString($key, $value);
		return new JSONResponse(['ok' => true]);
	}

	public function getInstance($url): Instance | null {
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
		if (empty($disabledInstances)) {
			unset($disabledInstances[$url]);
		} else {
			$disabledInstances[$url] = $disabled;
		}
		$this->saveAsUserJSON('disabledInstances', $disabledInstances);
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

	public function addInstance(): JSONResponse {
		$url = Instance::fixURL($this->request->getParam('url'));
		$clientId = trim($this->request->getParam('clientId'));

		if (empty($url)) {
			return new JSONResponse(
				['error' => 'Mandatory parameter: url'],
				Http::STATUS_BAD_REQUEST
			);
		}

		$instances = $this->getFromAppJSON('instances', '[]');

		foreach ($instances as $instance) {
			if ($instance['url'] === $url) {
				return new JSONResponse(
					['error' => $this->l10n->t('An instance with this URL already exists.')],
					Http::STATUS_BAD_REQUEST
				);
			}
		}

		$instances[] = ['url' => $url, 'clientId' => $clientId];

		$this->saveAsAppJSON('instances', $instances);

		$this->maybeNotify($url);

		return new JSONResponse(['ok' => true, 'url' => $url]);
	}

	private function maybeNotify(string $url) {
		if ($this->request->getParam('notifyUsers') === 'true') {
			$instance = new Instance($url);
			$prettyName = $instance->getPrettyName();

			$notification = $this->manager->createNotification();
			$notification->setApp('xwiki')
				->setDateTime(new \DateTime())
				->setObject('new_wiki_added', $instance->url)
				->setSubject('new_wiki_added', [$prettyName]);

			$this->userManager->callForAllUsers(function (IUser $user) use (&$notification) {
				$notification->setUser($user->getUID());
				$this->manager->notify($notification);
			});
		}
	}

	public function pingInstance(): JSONResponse {
		return $this->_pingInstance($this->request->getParam('url'));
	}

	private function _getVersion($q): ?string {
		$matches = null;
		preg_match('#<version(?:[\s][^>]*)?>([^<]+)</version>#', $q, $matches);
		$version = $matches[1];
		return $version;
	}

	private function _pingInstance($url): JSONResponse {
		$url = Instance::fixURL($url);
		if (empty($url)) {
			return new JSONResponse(
				['error' => 'Mandatory parameter: url'],
				Http::STATUS_BAD_REQUEST
			);
		}

		$client = $this->clientService->newClient();

		try {
			$q = $client->get($url . '/rest')->getBody();
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
				return new JSONResponse(
					['error' => $this->l10n->t('We did not get a successful reply from the instance (URL: %s)', [$restURL])],
					Http::STATUS_BAD_REQUEST
				);
			}
		}

		$version = $this->_getVersion($q);
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
					$version = $this->_getVersion($q);
					if (!empty($version)) {
						$workedWithXWikiAtTheEnd = true;
					}
				}
			}

			if (!$workedWithXWikiAtTheEnd) {
				return new JSONResponse(
					['error' => $this->l10n->t('We did not understand the instance’s version') . ' ' . $q],
					Http::STATUS_BAD_REQUEST
				);
			}
		}

		return new JSONResponse([
			'ok' => true,
			'version' => $version,
			'url' => $url,
			'hasNextcloudApplication' => Instance::hasNextcloudApplication($url)
		]);
	}

	public function replaceInstance(): JSONResponse {
		$oldUrl = $this->request->getParam('oldUrl');
		$newUrl = $this->request->getParam('newUrl');
		$clientId = trim($this->request->getParam('clientId'));

		if (empty($oldUrl) || empty($newUrl)) {
			return new JSONResponse(
				['error' => 'Mandatory parameters: oldUrl and newUrl'],
				Http::STATUS_BAD_REQUEST
			);
		}

		$found = null;
		$instances = $this->getFromAppJSON('instances', '[]');
		foreach ($instances as &$instance) {
			if ($instance['url'] === $oldUrl) {
				$instance['url'] = Instance::fixURL($newUrl);
				$instance['clientId'] = $clientId;
				$found = $instance;
			} else if ($instance['url'] === $newUrl) {
				return new JSONResponse(
					['error' => $this->l10n->t('An instance with this URL already exists')],
					Http::STATUS_BAD_REQUEST
				);
			}
		}

		if (!$found) {
			return new JSONResponse(['error' => $this->l10n->t('Could not find the instance you are trying to replace. This should not happen, please report a bug.')]);
		}

		$this->saveAsAppJSON('instances', $instances);
		$this->maybeNotify($newUrl);
		return new JSONResponse(['ok' => true, 'url' => $found['url']]);
	}

	private function saveAsAppJSON($key, $value): void {
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

	private function saveAsUserString(string $key, string $value): void {
		$this->config->setUserValue($this->userId, 'xwiki', $key, $value);
	}

	public function getFromAppJSON($key, $defaultJSONString): mixed {
		return $this->getFromJSON(
			$this->config->getAppValue('xwiki', $key, $defaultJSONString),
			$defaultJSONString
		);
	}

	public function getFromUserJSON($key, $defaultJSONString): mixed {
		return $this->getFromJSON(
			$this->config->getUserValue($this->userId, 'xwiki', $key, $defaultJSONString),
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

	public function deleteInstance(): JSONResponse {
		$url = $this->request->getParam('url');

		$this->saveAsAppJSON('instances', array_filter(
			$this->getFromAppJSON('instances', '[]'),
			function ($instance) use ($url) {
				return !empty($instance['url']) && $instance['url'] !== $url;
			}
		));

		// TODO: cleanup token from all user's config

		return new JSONResponse(['ok' => true]);
	}
}
