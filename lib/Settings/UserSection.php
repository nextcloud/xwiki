<?php
namespace OCA\Xwiki\Settings;

use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class UserSection implements IIconSection {
	/** @var IURLGenerator */
	private $url;

	public function __construct(IURLGenerator $url) {
		$this->url = $url;
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
		return $this->url->imagePath('xwiki', 'app-dark.svg');
	}
}
