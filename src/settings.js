/* global t */
import { request } from './common';

(function () {
	'use strict';

	function getParentLi(element) {
		while (element && element.nodeName.toLowerCase() !== 'li') {
			element = element.parentNode;
		}

		return element;
	}

	function handleInputChange({ currentTarget }) {
		const li = getParentLi(currentTarget);
		const urlInput = getUrlInput(li);
		const clientIdInput = getClientIdInput(li);
		const v = urlInput.value;

		if (!v || (v === urlInput.dataset.initialValue && clientIdInput.value === clientIdInput.dataset.initialValue)) {
			li.classList.remove('xwiki-admin-changed');
		} else {
			li.classList.add('xwiki-admin-changed');
		}

		if (currentTarget === urlInput) {
			const pingResult = li.querySelector('.ping-result');
			if (pingResult) {
				pingResult.textContent = '';
			}

			const integrationResult = li.querySelector('.integration-result');
			if (integrationResult) {
				integrationResult.textContent = '';
			}
		}
	}

	function handleAddInstanceClick() {
		const list = document.getElementById('xwiki-admin-instance-list');

		// Prevent the user from adding spare inputs, this would expose
		// limitations of the way the settings are saved (order cannot be
		// guarenteed)
		const penultimateLi = list.children[list.children.length - 2];
		if (penultimateLi) {
			const penultimateUrlInput = getUrlInput(penultimateLi);
			if (!penultimateUrlInput.dataset.initialValue) {
				penultimateUrlInput.focus();
				return;
			}
		}

		const lastLi = list.children[list.children.length - 1];
		const newLi = lastLi.cloneNode(true);
		newLi.hidden = false;
		bindEventsToLI(newLi);
		list.insertBefore(newLi, lastLi);
		getUrlInput(newLi).focus();
	}

	function removeLi(li) {
		li.remove();
		if (!getLIs().length) {
			handleAddInstanceClick();
		}
	}

	async function handleRemoveInstanceClick({ currentTarget }) {
		const li = getParentLi(currentTarget);
		const url = getUrlInput(li).dataset.initialValue;
		if (!url) {
			removeLi(li);
			return;
		}

		if (!confirm(t('xwiki', 'Are you sure you want to remove this instance?'))) {
			return;
		}

		currentTarget.disabled = true;
		currentTarget.textContent = '';
		currentTarget.classList.remove('icon')
		currentTarget.classList.remove('icon-delete');
		currentTarget.append(
			document.createElement('span'),
			' ' + t('xwiki', 'Removing…')
		);
		currentTarget.firstChild.className = 'xwiki-span-icon icon icon-loading-small';

		const body = new FormData();
		body.append('url', url);

		const result = await request('POST', 'settings/deleteInstance', { body });

		if (result?.ok) {
			li.remove();
		} else {
			alert(
				t('xwiki', 'An error occured while removing the instance.') + (
					result?.error
						? ('\n' + (result.error || ''))
						: (' ' + t('xwiki', 'Please try again later.'))
				)
			);
		}
	}

	async function pingInstance(li) {
		const pingResult = li.querySelector('.ping-result');
		const integrationResult = li.querySelector('.integration-result');

		pingResult.textContent = '';
		pingResult.appendChild(document.createElement('span'));
		pingResult.firstChild.className = 'icon icon-loading-small';
		integrationResult.textContent = '';

		const encodedURL = encodeURIComponent(getUrlInput(li).value);
		const result = await request('GET', 'settings/pingInstance?url=' + encodedURL);
		if (result?.ok) {
			pingResult.textContent = '✓ (XWiki v' + result.version + ')';
			pingResult.classList.remove('ping-failure');
			pingResult.classList.add('ping-successful');
			switch (result.hasNextcloudApplication) {
				case true:
					integrationResult.textContent = t('xwiki', 'This wiki has the Nextcloud application :-)');
					break;
				case false:
					integrationResult.textContent = t('xwiki', 'This wiki does not have the Nextcloud application. Please install it!');
					break;
				case null:
					integrationResult.textContent = t('xwiki', 'Please make sure the Nextcloud application is installed on this wiki.');
					break;
			}

			return result.hasNextcloudApplication;
		}

		pingResult.textContent = '⚠ ' + t('xwiki', 'Could not reach this instance');
		pingResult.classList.remove('ping-successful');
		pingResult.classList.add('ping-failure');

		return null;
	}

	async function handleSaveInstanceClick({ currentTarget }) {
		const li = getParentLi(currentTarget);
		const urlInput = getUrlInput(li);
		const clientIdInput = getClientIdInput(li);

		const initialURL = urlInput.dataset.initialValue;
		let newURL = urlInput.value;

		if (!newURL) {
			return;
		}

		if (initialURL === newURL && clientIdInput.dataset.initialValue === clientIdInput.value) {
			pingInstance(li);
			return;
		}

		if (!newURL.endsWith('/')) {
			newURL += '/';
		}

		if (!newURL.endsWith('/xwiki/') && !confirm(t('xwiki', 'The URL does not end with /xwiki/. It is uncommon, and this should probably be added. But some XWiki installations don’t have /xwiki/. Continue?'))) {
			return;
		}

		const savingSpan = document.createElement('span');
		savingSpan.append(document.createElement('span'), t('xwiki', 'Saving…'));
		savingSpan.firstChild.className = 'xwiki-span-icon icon icon-loading-small';
		savingSpan.style.minWidth = currentTarget.clientWidth + 'px';
		savingSpan.style.display = 'inline-block';

		currentTarget.parentNode.replaceChild(savingSpan, currentTarget);


		const clientId = clientIdInput.value.trim();
		const body = new FormData();
		body.append('clientId', clientId);
		body.append('notifyUsers', (clientId !== '' && confirm(t('xwiki', 'Do you want to notify users about this new instance?'))).toString());

		let result;

		if (initialURL) {
			body.append('oldUrl', initialURL);
			body.append('newUrl', newURL);
			result = await request('POST', 'settings/replaceInstance', { body });
		} else {
			body.append('url', newURL);
			result = await request('POST', 'settings/addInstance', { body });
		}

		if (result?.ok) {
			urlInput.dataset.initialValue = urlInput.value = result.url;
			clientIdInput.dataset.initialValue = clientIdInput.value = clientId;
			handleInputChange({currentTarget: urlInput});
			pingInstance(li);
		} else {
			alert(
				t('xwiki', 'An error occured while saving the instance.') + (
					result?.error
						? ('\n' + (result.error || ''))
						: (' ' + t('xwiki', 'Please try again later.'))
				)
			);
		}

		savingSpan.parentNode.replaceChild(currentTarget, savingSpan);
	}

	function getUrlInput(li) {
		return li.querySelector('[name="instance-url"]');
	}

	function getClientIdInput(li) {
		return li.querySelector('[name="instance-clientid"]');
	}

	function getLIs() {
		const lis = document.getElementById('xwiki-admin-instance-list')?.children || [];

		// We don't take the last <li> which is the hidden one used to create a
		// new element and turn into a proper array that can be used in a
		// for..of loop
		return [].slice.call(lis, 0, lis.length - 1);
	}

	function handlePingInstanceClick({ currentTarget }) {
		const li = getParentLi(currentTarget);
		pingInstance(li);
	}

	async function handleGenerateClientIdClick({ currentTarget }) {
		const li = getParentLi(currentTarget);
		const result = await pingInstance(li);
		switch (result) {
			case null: {
				if (!confirm(t('xwiki', 'We were unable to determine if the Nextcloud application is installed on this wiki. If it is not the case, you will reach a non-existing page. Continue?'))) {
					break;
				}
				// the absence of break is intentional
			}

			case true: {
				const urlInput = getUrlInput(li);
				location.href = urlInput.dataset.initialValue + '/bin/view/Nextcloud/Admin/?redirect_uri=' + document.getElementById('xwiki-admin-instance-list').dataset.redirectUri;
				break;
			}

			case false: {
				alert(t('xwiki', 'The Nextcloud application is missing on this wiki. Please install it before you can generate the client ID.'));
				break;
			}

			default: console.error('should not happen');
		}
	}

	function bindEventsToLI(li) {
		const urlInput = getUrlInput(li);
		const clientIdInput = getClientIdInput(li);
		urlInput.dataset.initialValue = urlInput.value;
		clientIdInput.dataset.initialValue = clientIdInput.value;
		urlInput.onchange = urlInput.oninput = clientIdInput.onchange = clientIdInput.oninput = handleInputChange;

		const removeBtn = li.querySelector('.xwiki-admin-remove-instance-btn');
		removeBtn.onclick = handleRemoveInstanceClick;

		const saveBtn = li.querySelector('.xwiki-admin-save-instance-btn');
		saveBtn.onclick = handleSaveInstanceClick;

		const pingBtn = li.querySelector('.xwiki-admin-ping-instance-btn');
		pingBtn.onclick = handlePingInstanceClick;

		const generateClientIdBtn = li.querySelector('.xwiki-admin-generate-clientid-btn');
		generateClientIdBtn.onclick = handleGenerateClientIdClick;

		urlInput.onkeydown = function (e) {
			if (e.key === 'Enter') {
				saveBtn.click();
			}
		};
		clientIdInput.onkeydown = function (e) {
			if (e.key === 'Enter') {
				saveBtn.click();
			}
		};
	}

	const addInstanceButton = document.getElementById('xwiki-add-instance-btn');
	if (addInstanceButton)  {
		addInstanceButton.onclick = handleAddInstanceClick;
		for (const li of getLIs()) {
			bindEventsToLI(li);
		}
	}

	const integratedMode = document.getElementById('integrated-mode');
	if (integratedMode) {
		integratedMode.addEventListener('click', async function () {
			const result = await request('POST', 'settings/setUserValue?k=integratedMode&v=' + integratedMode.checked);
			if (!result?.ok) {
				integratedMode.checked = !integratedMode.checked;
				alert('Sorry, could not save the settings');
			}
		});
	}

	for (const getTokenBtn of document.getElementsByClassName('get-token')) {
		getTokenBtn.addEventListener('click', async function (e) {
			e.preventDefault();

			const spin = document.createElement('span');
			spin.className = 'xwiki-span-icon icon icon-loading-small';
			e.target.parentNode.appendChild(spin);

			const result = await request('GET', 'settings/pingInstance?url=' + new URL(e.target.href).searchParams.get('i'));
			if (result?.ok) {
				spin.remove();
				switch (result.hasNextcloudApplication) {
					case true:
						location.href = e.target.href;
						return;

					case false:
						alert(t('xwiki', 'This wiki does not have the Nextcloud application. This is needed to get access to it from Nextcloud. Please ask its administrator to install this extension.'));
						return;

					default:
						break;
				}
			}

			// fallback
			alert(t('xwiki', 'We could not determine if this wiki has the Nextcloud application. This is needed to get access to it from Nextcloud. If you land on a non existing page, it means it is not installed, in which case please ask the administrator of the wiki to install this extension.'));
			location.href = e.target.href;
		});
	}
})();
