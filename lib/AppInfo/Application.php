<?php

declare(strict_types=1);

namespace OCA\Xwiki\AppInfo;

use OCA\Xwiki\Search\Provider;
use OCA\Xwiki\XWikiNotifier;
use OCP\AppFramework\App;
use OCP\Notification\IManager;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'xwiki';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(Provider::class);
		$context->registerNotifierService(XWikiNotifier::class);
	}

	public function boot(IBootContext $context): void {}
}
