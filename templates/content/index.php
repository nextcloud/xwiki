<?php script('xwiki', 'xwiki-main'); ?>
<section id="xwiki-app-content"<?php if (empty($_['content'])) { ?> class="onboard"<?php } ?>><?php
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
						<?php p($l->t('Save as PDF')); ?>
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
		if (empty($_['instances'])) { ?>
			<section>
				<p><?php
				p($l->t('With the XWiki app, you will be able to search and display XWiki content.') . ' ');
				p($l->t('No wikis are registered yet.') . ' ' . $l->t('Please ask your administrator to add some.'));
				?></p>
				<p><?php
					print_unescaped($l->t(
						"If you are an administrator, you can add wikis in the <a href='%s'>XWiki administration section</a>.",
						[$_['urlGenerator']->linkToRoute('settings.AdminSettings.index', ['section' => 'xwiki'])]
					));
				?></p>
			</section><?php
		} else if (empty($_['content'])) { ?>
			<section>
				<h3 id="xwiki-onboarding-search">
					<!-- material-design-icon magnify-icon -->
					<svg fill="var(--color-main-text)" width="22" height="22" viewBox="0 0 24 24">
						<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/>
					</svg><!--
					--><span><?php p($l->t('Find XWiki Content')); ?></span>
				</h3>
				<p><?php
					p($l->t('Click on the search button at the top right of the screen and start typing to search XWiki content.'))
				?></p>
			</section>
			<section>
				<h3>
					<svg id="xwiki-onboard-icon" width="22" height="22" viewBox="-57 -57 114 114">
						<g transform="rotate(-45) translate(20 -20)" class="north"><path d="M 3 -3 L 14 -14"/></g>
						<g transform="rotate(45) translate(20 -20)" class="east"><path d="M 3 -3 L 14 -14"/></g>
						<g transform="rotate(135) translate(20 -20)" class="south"><path d="M 3 -3 L 14 -14"/></g>
						<g transform="rotate(-135) translate(20 -20)" class="west"><path d="M 3 -3 L 14 -14"/></g>
						<line class="X" x1="-35" y1="-40" x2="35" y2="40"/>
						<line class="X" x1="-35" y1="40" x2="35" y2="-40"/>
					</svg><!--
					--><span><?php p($l->t('Active Wikis'));?></span>
				</h3>
				<p><?php
					p($l->t('You will be able to see results from the following wikis:'));
				?></p><?php
				$atLeastOneInstanceEnabled = false;
				$atLeastOneInstanceAsGuest = false;
				foreach ($_['instances'] as $instance) {
					if (!$instance->disabled) {
						if (!$atLeastOneInstanceEnabled) {
							?><ul><?php
							$atLeastOneInstanceEnabled = true;
						} ?>
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
					}
				}

				if ($atLeastOneInstanceEnabled) {
					?></ul><?php
				} else { ?>
					<p>
						<?php p($l->t('You have no active wikis. You can enable some to find XWiki content.')); ?>
						<?php print_unescaped(" " . $l->t(
							"You can do so in the <a href='%s'>XWiki settings</a>.",
							[$_['urlGenerator']->linkToRoute('settings.PersonalSettings.index', ['section' => 'xwiki'])]
						)); ?>
					</p>
				<?php } ?>
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
					p($l->t('Do you want to access another wiki from Nextcloud? Please ask your administrator to add it!'));
				?></p>
			</section>
			<section>
				<h3> Mode intégré </h3>
				<p><?php
					if ($_['integratedMode']) {
						p($l->t('You can start browsing XWiki content using the menu on the left.'));
						p(' ' . $l->t('Note that this feature is still experimental and some bugs can show up. Please report any issue you find. Feedback welcome.'));
					} else {
						print_unescaped($l->t(
							"To browse XWiki content from here, you can enable integrated mode in the <a href='%s'>XWiki settings</a>.",
							[$_['urlGenerator']->linkToRoute('settings.PersonalSettings.index', ['section' => 'xwiki'])]
						));
					}
				?></p>
			</section><?php
		} ?>
	</div>
</section>
