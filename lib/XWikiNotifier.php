<?php

declare(strict_types=1);
namespace OCA\Xwiki;

use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class XWikiNotifier implements INotifier {
	private IL10N $l10n;
	private IURLGenerator $urlGenerator;

	public function __construct(IL10N $l10n, IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
	}

	public function getID(): string {
		return 'xwiki';
	}

	public function getName(): string {
		return $this->l10n->t('XWiki');
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getSubject() === 'new_wiki_added') {
			$notification->setParsedSubject($this->l10n->t('New wiki added!'));
			$notification->setParsedMessage($this->l10n->t('You can now search and browse pages from %s. If you have an account on this wiki, make sure to log in so you can search pages you have access to!', $notification->getSubjectParameters()));
			$notification->setIcon($this->urlGenerator->imagePath('xwiki', 'app-dark.svg'));
			$notification->setLink($this->urlGenerator->linkToRoute(
				'settings.PersonalSettings.index',
				['section' => 'xwiki']
			));
			return $notification;
		}
		throw new \InvalidArgumentException('Invalid subject');
	}
}
