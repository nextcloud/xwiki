<?php
namespace OCA\Xwiki\Settings;

use OCA\Xwiki\SettingsManager;
use OCA\Xwiki\Instance;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class User implements ISettings {
	public SettingsManager $settings;
	private IL10N $l;
	private IRequest $request;
	private IURLGenerator $urlGenerator;

	public function __construct(SettingsManager $settings, IL10N $l, IRequest $request, IURLGenerator $urlGenerator) {
		$this->settings = $settings;
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;
		$this->request = $request;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		return new TemplateResponse(
			'xwiki',
			'usersettings', [
				'instances' => $this->settings->getInstances(),
				'urlGenerator' => $this->urlGenerator,
				'integratedMode' => $this->settings->getFromUserJSON('integratedMode', 'false'),
				'instanceUrl' => $this->request->getParam('i'),
				'error' => $this->request->getParam('error')
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
