<?php
namespace OCA\Xwiki\Settings;

use OCA\Xwiki\SettingsManager;
use OCA\Xwiki\Instance;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class User implements ISettings {
	public SettingsManager $settings;
	private IClientService $clientService;
	private IL10N $l;
	private IRequest $request;
	private IURLGenerator $urlGenerator;

	public function __construct(
		IClientService $clientService,
		IL10N $l,
		IRequest $request,
		IURLGenerator $urlGenerator,
		SettingsManager $settings
	) {
		$this->clientService = $clientService;
		$this->l = $l;
		$this->request = $request;
		$this->settings = $settings;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$userIsLogged = null;
		$ping = null;
		$i = null;
		if ($this->request->getParam('checkUserLogin') === '1') {
			$i = $this->settings->getInstance($this->request->getParam('i'));
			$client = $this->clientService->newClient();
			$userIsLogged = $i->isUserLogged($client);
			if ($userIsLogged === false || $userIsLogged === null) {
				$ping = $i->ping($client, $this->l);
			}
		}
		return new TemplateResponse(
			'xwiki',
			'usersettings', [
				'instances' => $this->settings->getInstances(),
				'i' => $i,
				'urlGenerator' => $this->urlGenerator,
				'integratedMode' => $this->settings->getFromUserJSON('integratedMode', 'false'),
				'instanceUrl' => $this->request->getParam('i'),
				'error' => $this->request->getParam('error'),
				'ping' => $ping,
				'userIsLogged' => $userIsLogged
			],
			'blank'
		);
	}

	public function getSection() {
		return 'xwiki';
	}

	public function getPriority() {
		return 50;
	}
}
