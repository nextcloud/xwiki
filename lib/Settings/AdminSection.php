<?php
namespace OCA\Xwiki\Settings;

use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	private IURLGenerator $urlGenerator;

	public function __construct(IURLGenerator $urlGenerator) {
		$this->urlGenerator = $urlGenerator;
	}

	public function getID() {
		return 'xwiki';
	}


	public function getName() {
		return 'XWiki';
	}

	public function getPriority() {
		return 80;
	}

	/**
	 * @return The relative path to a an icon describing the section
	 */
	public function getIcon() {
//  		return 'apps/xwiki/img/app-dark.svg';
		return $this->urlGenerator->imagePath('xwiki', 'app-dark.svg');
	}
}
