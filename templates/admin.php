<?php
script('xwiki', 'xwiki-settings');
style('xwiki', 'style');
// script('files', 'jquery.fileupload');

use OCA\Xwiki\Instance;
use OCP\IL10N;

function showInstance(IL10N $l, Instance $instance, bool $hidden) {?>
	<tr<?php if ($hidden) { p(' hidden="hidden"'); }?>>
		<td>
			<input
				type="text"
				placeholder="https://xwiki.example.com/xwiki"
				name="instance-url"
				autocomplete="on"
				autocapitalize="none"
				autocorrect="off"
				value="<?php p($instance->url, ENT_QUOTES); ?>"
			/>
		</td>
		<td>
			<button class="xwiki-admin-ping-instance-btn"><?php p($l->t('Check')); ?></button>
			<button class="xwiki-admin-save-instance-btn">
				<?php p($l->t('Save')); ?>
			</button>
			<button class="xwiki-admin-remove-instance-btn" ><?php p($l->t('Remove')); ?></button>
		</td>
		<td>
			<span class="ping-result"></span>
			<span class="integration-result"></span>
		</td>
	</tr><?php
}

/** @var array $_ */
$instances = $_['instances'];

if (!is_array($instances)) {
	p($instances);
	$instances = [];
}

?>
<div class="settings-section">
	<div class="section">
		<h2 class="settings-section__title"><?php p($l->t('XWiki Instances')); ?></h2>
		<p><?php p($l->t('You can add XWiki instances that users will be able to access from Nextcloud.')); ?></p>
		<p>
			<label>
				<?php p($l->t('URL:')); ?>
				<input
					id="new-instance-url"
					placeholder="https://xwiki.example.com/xwiki"
					type="text"
					autocomplete="on"
					autocapitalize="none"
					autocorrect="off"
				/>
			</label>
			<button id="xwiki-add-instance-btn">
				<?php p($l->t('Add')); ?>
			</button>
		</p>
		<?php if (empty($instances)) { ?>
			<p id="no-wikis-registered-p"><?php p($l->t('No wikis are registered yet.')); ?></p>
		<?php } ?>
		<table id="xwiki-admin-instance-list" data-redirect-uri="<?php p($_['redirectUri']);?>">
			<tr>
				<th><?php p($l->t('URL')); ?></th>
				<th><?php p($l->t('Actions')); ?></th>
				<th id="instances-notes-th"><?php p($l->t('Notes')); ?></th>
			</tr><?php
			foreach ($instances as $i) {
				showInstance($l, $i, false);
			}
			showInstance($l, Instance::$DUMMY, true);
		?></table>
		<section class="onboarding" id="add-instance-onboarding" hidden="hidden">
			<h3><?php p($l->t('You are adding a wiki!')); ?></h3>
			<p id="install-nextcloud-app-advice">
				<?php print_unescaped(
					str_replace(
						'<a>',
						'<a id="nextcloud-app-link" href="https://extensions.xwiki.org/xwiki/bin/view/Extension/Application%20Nextcloud%20-%20UI/" target="_blank" rel="noopener">',
						$l->t('Make sure the <a>Nextcloud application</a> is installed on this wiki (this link will take you to your extension manager).')
					)
				); ?>
			</p>
			<p><?php
				p($l->t('You can skip this step.') . ' ');
				p($l->t('Users might only be able to search content guests can access.'));
			?></p>
			<button id="close-onboarding" class="primary"><?php p($l->t("Got it")); ?></button>
		</section>
	</div>
</div>
