<?php

declare(strict_types=1);

namespace OCA\XWiki\Reference;

use OCA\Xwiki\SettingsManager;
use OCP\Http\Client\IClientService;
use OC\Collaboration\Reference\ReferenceManager;
use OCA\XWiki\AppInfo\Application;
// use OCA\Pexels\Service\PexelsService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class XWikiReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_page';

	private ?string $userId;
	private IConfig $config;
	private ReferenceManager $referenceManager;
	private IL10N $l10n;
	private IURLGenerator $urlGenerator;
    public SettingsManager $settings;
    private IClientService $clientService;

	public function __construct(SettingsManager $settings,
                                IClientService $clientService, 
                                IConfig $config,
								IL10N $l10n,
								IURLGenerator $urlGenerator,
								ReferenceManager $referenceManager,
								?string $userId) {
		$this->userId = $userId;
		$this->config = $config;
		$this->referenceManager = $referenceManager;
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
        $this->settings = $settings;
        $this->clientService = $clientService;
	}

	public function getId(): string	{
		return 'xwiki-page';
	}

	public function getTitle(): string {
		return $this->l10n->t('XWiki Page');
	}

	public function getOrder(): int	{
		return 10;
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')
		);
	}

	public function getSupportedSearchProviderIds(): array {
		return ['xwiki'];

	}

    public function matchReference(string $referenceText): bool {
        $instances = $this->settings->getInstances();
        $matching = false;
        foreach ($instances as $instance) {
            if ($instance->disabled) {
				continue;
			}
            if (str_starts_with($referenceText, $instance->url)) {
                $matching = true;
            }
        }

        return $matching;
    }
    

    public function resolveReference(string $referenceText): ?IReference {
        if ($this->matchReference($referenceText)) {
            $title = '';
            $description='';
            $imageUrl=$this->urlGenerator->imagePath('xwiki', 'app-dark.svg');
            $instances = $this->settings->getInstances();
            $client = $this->clientService->newClient();
            foreach ($instances as $instance) {
                if ($instance->disabled) {
                    continue;
                }

                if (str_starts_with($referenceText, $instance->url)) {
                    $content = $instance->loadHTML($referenceText,$client);
                    if (!$content) {
                        continue;
                    }
                    $title=$content->getElementsByTagName('title')->item(0)->nodeValue;
                    $description=$content->getElementsByTagName('description')->item(0)->nodeValue;
                }
                
            }

            $reference = new Reference($referenceText);
            $reference->setTitle($title);
            $reference->setDescription($description);
            $reference->setImageUrl($imageUrl);
            return $reference;
        }
        return null;
    }

    public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	public function getCacheKey(string $referenceId): ?string {
		return $referenceId;
	}
}



