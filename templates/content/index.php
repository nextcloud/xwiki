<?php script('xwiki', 'xwiki-main'); ?>
<section id="xwiki-app-content"><?php
	if ($_['integratedMode'] && !empty($_['currentPage']) && !empty($_['currentInstance'])) { ?>
		<div id="xwiki-top">
			<ul id="xwiki-breadcrumbs">
				<?php
					[$wiki, $page]  = explode(':', $_['currentPage'], 2);
					$homePage = $wiki . ':Main';
					$pageParts = \OCA\Xwiki\Instance::getPageParts($page);
					$breadcrumbsPage = $wiki . ':';
				?>
				<li>
					<a href="<?php
						p($_['urlGenerator']->linkToRoute('xwiki.page.index', [
							'i' => $_['currentInstance']->url,
							'p' => $homePage,
						]));
					?>">
						<span class="icon-xwiki"></span>
						<span><?php
							p($_['currentInstance']->getPrettyName());
						?></span>
					</a>
				</li><?php
					$first = true;
					foreach ($pageParts as $pagePart) {
						if ($first) {
							$first = false;
						} else {
							$breadcrumbsPage .= '.';
						}
						$breadcrumbsPage .= $pagePart;
						if ($breadcrumbsPage !== $homePage) {
							?><li><a href="<?php p(
								rawurlencode(
									p($_['urlGenerator']->linkToRoute('xwiki.page.index', [
										'i' => $_['currentInstance']->url,
										'p' => $breadcrumbsPage,
									]))
								)
							); ?>" title="<?php p($pagePart); ?>"><?php p(
								$pagePart
							); ?></a></li><?php
						}
					}
				?>
			</ul>
			<ul id="xwiki-actions">
				<li>
					<a href="<?php p($_['xwikiURL']); ?>" title="View at <?php p($_['xwikiInstance']); ?>">
						<span class="icon icon-external"></span>
						View
					</a>
				</li>
				<li>
					<a href="<?php p($_['xwikiEditURL']); ?>" title="Edit on <?php p($_['xwikiInstance']); ?>">
						<span class="icon icon-edit"></span>
						Edit
					</a>
				</li>
				<li>
					<a
						class="xwiki-save-pdf"
						href="<?php p($_['xwikiExportPDFURL']); ?>"
						data-instance="<?php p($_['currentInstance']->url); ?>"
						data-page="<?php p($_['currentPage']); ?>"
					>
						<span class="icon icon-file"></span>
						Save as PDF
					</a>
				</li>
			</ul>
		</div><?php
	} ?>
	<div id="xwiki-content"><?php
		if (!empty($_['title'])) { ?>
			<h2><?php p($_['title']); ?></h2>
		<?php }

		if (empty($_['content'])) { ?>
			<h2> XWiki </h2><?php
		} else { ?>
			<section>
				<?php print_unescaped($_['content']); ?>
			</section>
		<?php } ?>
		<?php
		if (empty($_['instances'])) {
			?><p><?php
			p($l->t('With the XWiki app, you will be able to search and display XWiki content.'));
			p($l->t('No wikis are registered yet. Please ask your administrator to add some.'));
			?></p>
			<p><?php
				print_unescaped($l->t(
					"If you are an administrator, you can add wikis in the <a href='%s'>XWiki administration section</a>.",
					[$_['urlGenerator']->linkToRoute('settings.AdminSettings.index', ['section' => 'xwiki'])]
				));
			?></p><?php
		} else if (empty($_['content'])) { ?>
			<p><?php
				p($l->t('Click on the search button at the top right of the screen and start typing to search XWiki content.'))
			?></p><p><?php
				p($l->t('You will be able to see results from the following wikis:'));
			?></p>
			<ul><?php
			$atLeastOneInstanceAsGuest = false;
			foreach ($_['instances'] as $instance) {?>
				<li>
					<strong>
						<span class="icon icon-xwiki"></span>
						<?php p($instance->getPrettyName()); ?>
					</strong>
					<?php
						if (empty($instance->token)) {
							p($l->t('(not signed in)'));
							$atLeastOneInstanceAsGuest = true;
						}
					?>
				</li><?php
			} ?>
			</ul>
			<p><?php
				if ($atLeastOneInstanceAsGuest) {
					p($l->t('Sign in to these wikis to be able to see personalized results.'));
					print_unescaped(" " . $l->t(
						"You can do so in the <a href='%s'>XWiki settings</a>.",
						[$_['urlGenerator']->linkToRoute('settings.PersonalSettings.index', ['section' => 'xwiki'])]
					));
				}
			?></p>
			<p><?php
				if ($_['integratedMode']) {
					p($l->t('You can start browsing XWiki content using the menu on the left.'));
					p(' ' . $l->t('Note that this feature is still experimental and some bugs can show up. Please report any issue you find. Feedback welcome.'));
				} else {
					p($l->t('To browse XWiki content from here, please enable the integrated mode.'));
				}
			?></p><p><?php
				p($l->t('Do you want to access another wiki from Nextcloud? Please ask your administrator to add it!'));
			?></p><?php
		} ?>
	</div>
</section>
