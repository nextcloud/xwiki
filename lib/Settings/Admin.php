<?php
namespace OCA\Xwiki\Settings;

use OCA\Xwiki\SettingsManager;
use OCA\Xwiki\Instance;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	private IL10N $l;
	public SettingsManager $settings;

	public function __construct(SettingsManager $settings, IL10N $l) {
		$this->settings = $settings;
		$this->l = $l;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		return new TemplateResponse(
			'xwiki',
			'admin', [
				'instances' => $this->settings->getInstances(),
				'redirectUri' => $this->settings->getRedirectURI()
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
