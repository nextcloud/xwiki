<?php
script('xwiki', 'xwiki-settings');
style('xwiki', 'style');
// script('files', 'jquery.fileupload');

use OCA\Xwiki\Instance;
use OCP\IL10N;

function showInstance(IL10N $l, Instance $instance, bool $hidden) {?>
	<li<?php if ($hidden) { p(' hidden="hidden"'); }?>>
		<p>
			<label>
				<?php p($l->t('URL:')); ?>
				<input
					type="text"
					placeholder="https://xwiki.example.com"
					name="instance-url"
					autocomplete="on"
					autocapitalize="none"
					autocorrect="off"
					value="<?php p($instance->url, ENT_QUOTES); ?>"
				/>
			</label>
			<button
				class="xwiki-admin-remove-instance-btn icon icon-delete"
				aria-label="<?php p($l->t('Remove')); ?>"
				title="<?php p($l->t('Remove')); ?>"
			>
			</button>
		</p>
		<p>
			<label><?php p($l->t('Client ID:')); ?> <input type="text" value="<?php p($instance->clientId); ?>" name="instance-clientid" /></label>
			<button class="xwiki-admin-generate-clientid-btn">
				<?php p($l->t('Generate')); ?>
			</button>
		</p>
		<p>
			<button class="xwiki-admin-ping-instance-btn"><?php p($l->t('Check')); ?></button>
			<button class="xwiki-admin-save-instance-btn">
				<span class="icon icon-checkmark"></span>
				<?php p($l->t('Save')); ?>
			</button>
			<span class="ping-result"></span>
			<span class="integration-result"></span>
		</p>
	</li><?php
}

/** @var array $_ */
$instances = $_['instances'];

if (!is_array($instances)) {
	p($instances);
	$instances = [];
}

if (!count($instances)) {
	$instances[] = Instance::$DUMMY;
}

?>
<div class="settings-section">
	<div class="section">
		<h2 class="settings-section__title"><?php p($l->t('XWiki Instances')); ?></h2>
		<p><?php p($l->t('You can add XWiki instances that users will be able to access from Nextcloud.')); ?></p>
		<ul id="xwiki-admin-instance-list" data-redirect-uri="<?php p($_['redirectUri']);?>"><?php
			foreach ($instances as $i) {
				showInstance($l, $i, false);
			}
			showInstance($l, Instance::$DUMMY, true);
		?></ul>
		<button id="xwiki-add-instance-btn">
			<span class="icon icon-add"></span>
			<?php p($l->t('Add')); ?>
		</button>
		<p>
			<?php p($l->t('Note: client IDs will allow Nextcloud users to log in to XWiki instances and have personalized search result. Administrators of XWiki instance can generate a Client ID with the XWiki Nextcloud application which can be installed using the extension manager.')); ?>
		</p>
	</div>
</div>
