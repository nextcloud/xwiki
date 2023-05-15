<?php if ($_['integratedMode']) { ?>
<ul id="xwiki-nav"><?php
	foreach ($_['instances'] as $instance) {?>
		<li>
			<a href="<?php p($_['urlGenerator']->linkToRoute('xwiki.page.index', [
				'i' => $instance->url,
				'p' => 'xwiki:Main',
			]));?>">
				<span class="icon icon-xwiki"></span>
				<?php p($instance->getPrettyName());?>
			</a><?php
			if ($_['currentInstance']->url === $instance->url && !empty($_['pages'])) {?>
				<ul><?php
					foreach ($_['pages'] as $page) {?>
						<li>
							<a href="<?php p($page['url']); ?>">
								<span class="icon icon-xwiki-page"></span>
								<?php
									p($page['title']);
								?>
							</a>
						</li><?php
					}
				?>
				</ul><?php
			} ?>
		</li><?php
	} ?>
</ul>
<?php } ?>
