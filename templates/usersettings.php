<?php
style('xwiki', 'style');
script('xwiki', 'xwiki-settings');

/** @var array $_ */
$instances = $_['instances'];

if (!is_array($instances)) {
	p($instances);
	$instances = [];
}
?>

<div class="section">
	<h2 class="settings-section__title"><?php p($l->t('Preferences')); ?></h2>
	<p class="settings-section__desc"><?php
		p($l->t("By default, you are redirected to XWiki whenever you click on a search result. If you enable the integrated mode, XWiki results will be displayed within Nextcloud. This can lead to a more seamless experience, but this mode is experimental and there can be display issues."));
	?></p>

	<p class="p-input">
		<input
			type="checkbox"
			class="checkbox"
			name="integrated-mode"
			id="integrated-mode"
			<?php if ($_['integratedMode']) { p(' checked="checked"');} ?>
		/>
		<label for="integrated-mode"><?php p($l->t('Integrated mode (experimental)')); ?></label>
	</p>
</div>

<?php
use OCA\Xwiki\Instance;
use OCP\IL10N;

function showGetAccessButton(IL10N $l, Instance $instance, $_) { ?>
	<a
		class="get-token link-button"
		href="<?php rawurlencode(
			p($_['urlGenerator']->linkToRoute('xwiki.settings.requestToken', [
				'i' => $instance->url,
				'requesttoken' => $_['requesttoken']
			]))
		); ?>"
	>
		<?php p($l->t('Get access')); ?>
	</a><?php
}

function showInstance(IL10N $l, Instance $instance, $_) {
	?>
	<tr>
		<td>
			<a href="<?php p($instance->url, ENT_QUOTES); ?>" target="_blank" rel="noopener"><?php
				p($instance->getPrettyName());
			?></a>
		</td>
		<td>
			<input
				type="checkbox"
				class="use-instance-checkbox"
				aria-label="<?php p($l->t('Use this wiki (%s)', [$instance->getPrettyName()])); ?>"
				<?php if (!$instance->disabled) { ?> checked="checked" <?php } ?>
			/>
		</td>
		<td><?php
			if (empty($instance->token)) {
				if ($_['instanceUrl'] === $instance->url && !empty($_['error'])) {
					?><span class="xwiki-error"><?php
					p($l->t('An error happened while getting access to this wiki.'));
					if ($_['error'] === 'unauthorized_client') {
						p(' ' . $l->t('Please ask its administrator to register this Nextcloud instance.'));
					} else if ($_['error'] === 'access_denied') {
						p(' ' . $l->t('You cancelled the access.'));
					} else if ($_['error'] === 'missing_access_token') {
						p(' ' . $l->t('XWiki didn’t provide an access token.'));
					}
					?></span><?php
				} else {
					showGetAccessButton($l, $instance, $_);
				}
			} else { ?>
				<form action="<?php p(
					rawurlencode(
						$_['urlGenerator']->linkToRoute('xwiki.settings.deleteToken', [
							'i' => $instance->url,
							'requesttoken' => $_['requesttoken']
						])
					)
				); ?>" method="post">
					<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>" />
					<button><?php
						p($l->t('Log out'));
					?></button>
				</form>
				<a class="link-button" href="?checkUserLogin=1&amp;i=<?php p($instance->url); ?>"><?php
					p($l->t('Check'));
				?></a>
			<?php }?>
		</td>
		</td>
	</tr><?php
}

?>
<div class="section">
	<h2 class="settings-section__title"><?php
		p($l->t('Wikis and Access'));
	?></h2>
	<p class="settings-section__desc"><?php
		p($l->t('When you search something, Nextcloud will fetch results from the following wikis. By default, Nextcloud is not logged to these wikis, so results will be what guests see.'));
	?></p>
	<p class="settings-section__desc"><?php
		p($l->t('If you have an XWiki account, you can click on “Get access” so Nextcloud will be able to give you results for the things you can access.'));
	?></p>
	<p class="settings-section__desc"><?php
		p($l->t('You can disable wikis by unchecking their “Use” checkbox. Search results from these wikis will not be displayed.'));
	?></p>
	<?php if (count($instances)) { ?>
		<table id="xwiki-user-settings-instance-table">
			<tr>
				<th><?php p($l->t('URL')); ?></th>
				<th><?php p($l->t('Use')); ?></th>
				<th><?php p($l->t('Access')); ?></th>
			</tr>
			<?php
				foreach ($instances as $instance) {
					showInstance($l, $instance, $_);
				}
			?>
		</table>
		<?php if ($_['userIsLogged'] === false || ($_['userIsLogged'] === null && $_['ping'] !== null)) { ?>
			<div class="warning">
				<?php if ($_['ping'] === null || $_['ping']['ok']) { ?>
					<p><?php p($l->t(
						'We were unable to authenticate on your behalf on the wiki at %s. Try to get access with the button below. If it still does not work, please ask for help to its administrator. They need to set it up so Nextcloud can access it on your behalf.',
						[$_['i']->getPrettyName()]
					)); ?></p><?php
					showGetAccessButton($l, $i, $_);
				} else { ?>
					<p><?php p($l->t(
						'Could not contact the wiki at %s. Please try again later, or ask for help to its administrator. The error was: %s',
						[$_['i']->getPrettyName(), $_['ping']['error']]
					)); ?></p><?php
				} ?>
			</div><?php
		} else if ($_['userIsLogged'] === true) { ?>
			<div class="ok">
				<h3><?php p($l->t('All set!')); ?></h3>
				<p><?php p(
					$l->t('The wiki %s is ready to be used.', [$_['i']->getPrettyName()])
				); ?></p>
			</div><?php
		} ?>
		<div class="note">
			<p><?php p($l->t('If you reach a non-existing document after clicking on “Get access”, this means the wiki must be set up. Please ask its administrator to do it for you.')); ?></p>
		</div>

		<p>
			<?php p($l->t('Do you want to access another wiki from Nextcloud? Please ask your administrator to add it!')); ?>
		</p>
	<?php } else { ?>
		<p><?php
			p($l->t('No wikis are registered yet.') . ' ' . $l->t('Please ask your administrator to add some.'));
		?></p>
	<?php }?>
</div>
