<?php

declare(strict_types=1);

namespace OCA\Xwiki\Search;

use OCA\Xwiki\SettingsManager;
use OCP\Http\Client\IClientService;
use OCP\IUser;
use OCP\IURLGenerator;
use OCP\Search\SearchResult;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResultEntry;

if (!function_exists('str_starts_with')) {
	// TODO: remove when PHP < 8 is not used anymore
	// Thanks https://www.php.net/manual/en/function.str-starts-with.php#126531
	function str_starts_with(string $haystack, string $needle): bool {
	    return (@substr_compare($haystack, $needle, 0, strlen($needle)) === 0);
	}
}

class Provider implements IProvider {
	public SettingsManager $settings;
	private IClientService $clientService;

	public function __construct(SettingsManager $settings, IClientService $clientService, IURLGenerator $urlGenerator) {
		$this->settings = $settings;
		$this->clientService = $clientService;
        $this->urlGenerator = $urlGenerator;
	}

	public function getId(): string {
		return 'xwiki';
	}

	public function getName(): string {
		return 'XWiki';
	}

	public function getOrder(string $route, array $routeParameters): int {
		if (strpos($route, 'xwiki' . '.') === 0) {
			// Active app, prefer my results
			return -1;
		}

		return 55;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$results = [];

		$integratedMode = $this->settings->getFromUserJSON('integratedMode', 'false');
		$instances = $this->settings->getInstances();
		$instanceCount = count($instances);
		$client = $this->clientService->newClient();
		foreach ($instances as $instance) {
			if ($instance->disabled) {
				continue;
			}

			$tags = null;
			$content = $instance->getFile(
				'/rest/wikis/query?q=' . rawurlencode($query->getTerm()),
				$client
			);

			if (!$content) {
				continue;
			}

			$xmlparser = xml_parser_create();
			xml_parse_into_struct($xmlparser, $content, $tags);
			xml_parser_free($xmlparser);

			$res = null;
			foreach ($tags as $tag) {
				switch ($tag['type']) {
					case 'open':
						if ($tag['tag'] === 'SEARCHRESULT') {
							$fullName = '';
							$link = '';
							$title = '';
							$wiki = '';
							$type = '';
						}
						break;

					case 'complete':
						switch ($tag['tag']) {
							case 'PAGEFULLNAME':
								$fullName = $tag['value'];
								break;

							case 'TITLE':
								$title = $tag['value'];
								break;

							case 'TYPE':
								$type = $tag['value'];
								break;

							case 'WIKI':
								$wiki = $tag['value'];
								break;

							case 'ID':
								$id = $tag['value'];
								break;

							default: // ignore
						}
						break;

					case 'close':
						if ($tag['tag'] === 'SEARCHRESULT' && $type === 'page') {
							$trimmedId = (
								str_starts_with($id, 'xwiki:')
									? substr($id, 6)
									: $id
							);

							$url = (
								$instanceCount > 1
									? ($instance ->getPrettyName()  . ' - ')
									: ''
							);

							$subline = $url . $trimmedId;

							$results[] = new SearchResultEntry(
								'',
								$title,
								$subline,
								$integratedMode
									? $this->urlGenerator->linkToRoute(
										'xwiki.page.index',
										[
											'i' => $instance ->url,
											'p' => $id,
										]
									)
									: $instance->getLinkFromId($id),
							   $this->urlGenerator->imagePath('xwiki', 'app-dark.svg')
							);
						}
						break;

					default: // ignore
				}
			}
		}

		return SearchResult::complete('XWiki', $results);
	}
}
