<?php
namespace OCA\Xwiki\Controller;

use OCP\IRequest;
use OCA\Xwiki\Controller\SettingsController;
use OCA\Xwiki\Instance;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;

// Thanks https://www.php.net/manual/fr/class.domelement.php#101243
function get_inner_html($node) {
    $innerHTML =  '';
    foreach ($node->childNodes as $child) {
        $innerHTML .= $child->ownerDocument->saveXML($child);
    }
    return $innerHTML;
}

class PageController extends Controller {
	private $userId;
	private SettingsController $settings;
	private IL10N $l10n;
	private IRootFolder $rootFolder;
	private IURLGenerator $urlGenerator;

	public function __construct($AppName, IRequest $request, $UserId, SettingsController $settings, IL10N $l10n, IURLGenerator $urlGenerator, IRootFolder $rootFolder) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->settings = $settings;
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->rootFolder = $rootFolder;
	}

	public function savePDF(): JSONResponse {
		$instance = null;
		$i = $this->request->getParam('i');
		if ($i) {
			$instance = $this->settings->getInstance($i);
		}

		if (empty($instance)) {
			return new JSONResponse(
				['error' => $this->l10n->t('This XWiki instance was not found in the admin settings. Please contact your administrator.')],
				Http::STATUS_BAD_REQUEST
			);
		}

		$page = $this->request->getParam('p');
		if (empty($page)) {
			return new JSONResponse(
				['error' => $this->l10n->t('Please provide a page name in the p parameter.')],
				Http::STATUS_BAD_REQUEST
			);
		}

		$path = $this->request->getParam('save_path');
		if (empty($path)) {
			return new JSONResponse(
				['error' => $this->l10n->t('Please provide a path in the save_path parameter.')],
				Http::STATUS_BAD_REQUEST
			);
		}

		$userFolder = $this->rootFolder->getUserFolder($this->userId);

		$xwikiExportPDFURL = $instance->getLinkFromId($page, 'export') . '?format=pdf';
		try {
			$f = $userFolder->get($path);
		} catch (\OCP\Files\NotFoundException $e) {}

		$page = strtr(explode(':', $page, 2)[1], ':', '_');
		$suffix = '';
		$originalPath = $path;
		$i = 0;
		if ($f->getType() === FileInfo::TYPE_FOLDER) {
			do {
				$path = $originalPath . '/' . $page . $suffix . '.pdf';
				$i++;
				$suffix = '.' . $i;
			} while ($userFolder->nodeExists($path));
		}

		$content = file_get_contents($xwikiExportPDFURL);
		if (empty($content)) {
			return new JSONResponse(
				['error' => $this->l10n->t('Could not get the content of this page.')],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		try {
			if ($userFolder->newFile($path, $content)) {
				return new JSONResponse(['ok' => true, 'path' => $path]);
			}
		} catch (\OCP\Files\NotPermittedException $e) {
			return new JSONResponse(
				['error' => $this->l10n->t('You are not permitted to create this file.'), 'path' => $path],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse(
			['error' => $this->l10n->t('Could not create the file.')],
			Http::STATUS_INTERNAL_SERVER_ERROR
		);
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$xwikiURL = '';
		$xwikiEditURL = '';
		$title = '';
		$instanceURL = '';
		$instance = null;
		$pages = [];
		$page = '';
		$xwikiExportPDFURL = '';
		$integratedMode = $this->settings->getFromUserJSON('integratedMode', 'false');

		$i = $this->request->getParam('i');
		if ($i) {
			if (!$integratedMode) {
				return new RedirectResponse(
					$this->urlGenerator->linkToRoute('xwiki.page.index')
				);
			}

			$instance = $this->settings->getInstance($i);
			if ($instance) {
				$instanceURL = $instance->getPrettyName();
				$page = $this->request->getParam('p');
				$xwikiURL = $instance->getLinkFromId($page);
				$xwikiEditURL = $instance->getLinkFromId($page, 'edit');
				$xwikiExportPDFURL = $instance->getLinkFromId($page, 'export') . '?format=pdf';
				if ($integratedMode) {
					$doc = $instance->loadHTML($xwikiURL);
					$xwikiContent = '';

					if ($doc !== null && $doc->documentElement !== null) {
						$ref = $doc->documentElement->getAttribute('data-xwiki-reference');
						if (!empty($ref)) {
							$page = $ref;
							$xwikiURL = $instance->getLinkFromId($page);
							$xwikiEditURL = $instance->getLinkFromId($page, 'edit');
							$xwikiExportPDFURL = $instance->getLinkFromId($page, 'export') . '?format=pdf';
						}

						$xwikiContent = $doc->getElementById('xwikicontent');

						$titleTag = $doc->getElementById('document-title');
						if ($titleTag) {
							$title = $titleTag->textContent;
						}
					}

					if (!empty($xwikiContent)) {
						$dirnameXWikiURL = dirname($xwikiURL);
						$instanceBaseURL = preg_replace('|^(https?://[^/]+).*|i', '$1', $instance->url);

						foreach ($xwikiContent->getElementsByTagName('*') as $tag) {
							foreach (['src', 'href'] as $attrName) {
								$attr = $tag->getAttribute($attrName);
								if (!empty($attr)) {
									if (!preg_match('#^https?://#i', $attr)) {
										if ($attr[0] === '/') {
											$attr = $instanceBaseURL . $attr;
										} else {
											$attr = $dirnameXWikiURL . $attr;
										}
									}

									if ($attrName === 'href' && strtolower($tag->nodeName) === 'a') {
										$id = \OCA\Xwiki\Instance::getIdFromLink($attr, $instance->url, true);
										if (!empty($id)) {
											$attr = $this->urlGenerator->linkToRoute('xwiki.page.index', [
												'i' => $instance->url,
												'p' => $id,
											]);
										}
									}

									$tag->setAttribute($attrName, $attr);
									$tag->setAttribute("data-nextcloud-xwiki-dbg", \OCA\Xwiki\Instance::getIdFromLink($attr, $instance->url, true));
								}
							}
						}
						$content = get_inner_html($xwikiContent);
					} else {
						$content = $this->l10n->t('Could not find the content of this page.');
					}
					[$wiki, $unqualifiedPage] = explode(':', $page, 2);
					if (str_ends_with($unqualifiedPage, '.WebHome')) {
						$unqualifiedPage = substr($unqualifiedPage, 0, -8);
					}

					$pagesURL = ($instance->getPageListRestURL($wiki, $unqualifiedPage));
					if (!empty($pagesURL)) {
						$doc = $instance->loadXML($pagesURL);
						if (!empty($doc)) {
							foreach ($doc->getElementsByTagName('pageSummary') as $pageSummary) {
								$pages[] = [
									'url' => $this->urlGenerator->linkToRoute('xwiki.page.index', [
										'i' => $instance->url,
										'p' => $pageSummary->getElementsByTagName('id')[0]->textContent
									]),
									'title' => (
										$pageSummary->getElementsByTagName('title')[0]->textContent
										?: $pageSummary->getElementsByTagName('name')[0]->textContent
										?: $pageSummary->getElementsByTagName('fullName')[0]->textContent
									)
								];
							}
						}
					}
				}
			} else {
				$content = $this->l10n->t('This XWiki instance was not found in the admin settings. Please contact your administrator.');
			}
		}

		$response = new TemplateResponse('xwiki', 'index', [
			'content' => $content,
			'title' => $title,
			'xwikiInstance' => $instanceURL,
			'instances' => $this->settings->getInstances(),
			'xwikiURL' => $xwikiURL,
			'xwikiEditURL' => $xwikiEditURL,
			'xwikiExportPDFURL' => $xwikiExportPDFURL,
			'urlGenerator' => $this->urlGenerator,
			'currentInstance' => $instance,
			'pages' => $pages,
			'currentPage' => $page,
			'integratedMode' => $integratedMode
		]);  // templates/index.php

		if (!empty($instance)) {
			$cspURL = $instance->url . '/';
			$csp = $response->getContentSecurityPolicy();
			$csp->addAllowedImageDomain($cspURL);
			$csp->addAllowedMediaDomain($cspURL);
			$response->setContentSecurityPolicy($csp);
		}

		return $response;
	}
}
