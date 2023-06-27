/* global t */
import { request } from './common';
import { getRootUrl } from '@nextcloud/router';
import { showError, showInfo } from '@nextcloud/dialogs';

(function () {
	'use strict';

	function getParentElement(element) {
		while (element && element.nodeName.toLowerCase() !== 'tr') {
			element = element.parentNode;
		}

		return element;
	}

	function handleInputChange({ currentTarget }) {
		const element = getParentElement(currentTarget);
		const urlInput = getUrlInput(element);
		const clientIdInput = getClientIdInput(element);
		const v = urlInput.value;

		if (!v || (v === urlInput.dataset.initialValue && clientIdInput.value === clientIdInput.dataset.initialValue)) {
			element.classList.remove('xwiki-admin-changed');
		} else {
			element.classList.add('xwiki-admin-changed');
		}

		if (currentTarget === urlInput && currentTarget.value !== currentTarget.dataset.lastPing) {
			const pingResult = element.querySelector('.ping-result');
			if (pingResult) {
				pingResult.textContent = '';
			}

			const integrationResult = element.querySelector('.integration-result');
			if (integrationResult) {
				integrationResult.textContent = '';
			}
		}
	}

	async function handleAddInstanceClick() {
		const newInstanceUrlInput = document.getElementById('new-instance-url');
		if (!newInstanceUrlInput.value.trim()) {
			return;
		}

		const noWikiP = document.getElementById("no-wikis-registered-p");
		if (noWikiP) { noWikiP.hidden = true; }

		const registeredInstances = getRegisteredInstanceElements();

		const list = document.querySelectorAll('#xwiki-admin-instance-list tr');

		// Prevent the user from adding spare inputs, this would expose
		// limitations of the way the settings are saved (order cannot be
		// guaranteed)
		const penultimateElement = list[list.length - 2];
		if (penultimateElement) {
			const penultimateUrlInput = getUrlInput(penultimateElement);
			if (penultimateUrlInput && !penultimateUrlInput.dataset.initialValue) {
				penultimateUrlInput.focus();
				return;
			}
		}

		const lastElement = list[list.length - 1];
		const newElement = lastElement.cloneNode(true);
		newElement.hidden = false;
		bindEventsToElement(newElement);
		lastElement.parentNode.insertBefore(newElement, lastElement);
		const urlInput = getUrlInput(newElement);
		urlInput.value = newInstanceUrlInput.value;
		newInstanceUrlInput.value = "";
		urlInput.focus();
		await pingInstance(newElement);
		hideOnboarding();
		const url = urlInput.value;
		for (const element of registeredInstances) {
			const registeredUrlInput = getUrlInput(element);
			if (registeredUrlInput.dataset.initialValue === url) {
				newElement.remove();
				registeredUrlInput.focus();
				showInfo(t("xwiki", "This wiki is already registered. We focused it for you."));
				return;
			}
		}
		document.getElementById("add-instance-onboarding").hidden = false;
	}

	function removeElement(element) {
		element.remove();
		if (!getRegisteredInstanceElements().length) {
			const noWikiP = document.getElementById("no-wikis-registered-p");
			if (noWikiP) { noWikiP.hidden = false; }
		}
	}

	async function handleRemoveInstanceClick({ currentTarget }) {
		const element = getParentElement(currentTarget);
		const url = getUrlInput(element).dataset.initialValue;
		if (!url) {
			removeElement(element);
			return;
		}

		if (!confirm(t('xwiki', 'Are you sure you want to remove this instance?'))) {
			return;
		}


		currentTarget.disabled = true;
		const text = currentTarget.textContent;
		currentTarget.textContent = '';
		currentTarget.append(
			document.createElement('span'),
			' ' + t('xwiki', 'Removing…')
		);
		currentTarget.firstChild.className = 'xwiki-span-icon icon icon-loading-small';

		const body = new FormData();
		body.append('url', url);

		const result = await request('POST', 'settings/deleteInstance', { body });

		if (result?.ok) {
			element.remove();
		} else {
			currentTarget.removeChild(currentTarget.lastChild);
			currentTarget.removeChild(currentTarget.lastChild);
			currentTarget.textContent = text;
			currentTarget.disabled = false;
			alert(
				t('xwiki', 'An error occured while removing the instance.') + (
					result?.error
						? ('\n' + (result.error || ''))
						: (' ' + t('xwiki', 'Please try again later.'))
				)
			);
		}
	}

	async function pingInstance(element) {
		const pingResult = element.querySelector('.ping-result');
		const integrationResult = element.querySelector('.integration-result');

		pingResult.textContent = '';
		pingResult.appendChild(document.createElement('span'));
		pingResult.firstChild.className = 'icon icon-loading-small';
		integrationResult.textContent = '';

		const urlInput = getUrlInput(element);
		const url = urlInput.value;
		const encodedURL = encodeURIComponent(url);
		const result = await request('GET', 'settings/pingInstance?url=' + encodedURL);
		if (result?.ok) {
			pingResult.textContent = '✓ (XWiki v' + result.version + ')';
			pingResult.classList.remove('ping-failure');
			pingResult.classList.add('ping-successful');
			if (result.url && result.url !== url) {
				urlInput.value = result.url;
				getParentElement(urlInput).classList.add('xwiki-admin-changed');
			}
			integrationResult.dataset.result = result.hasNextcloudApplication;
			switch (result.hasNextcloudApplication) {
				case true:
					integrationResult.textContent = t('xwiki', 'with the Nextcloud app');
					break;
				case false:
					integrationResult.textContent = t('xwiki', '(the Nextcloud app is missing)');
					break;
				case null:
					integrationResult.textContent = t('xwiki', '(make sure the Nextcloud application is installed)');
					break;
			}

			urlInput.dataset.lastPing = urlInput.value;

			return result.hasNextcloudApplication;
		}

		urlInput.dataset.lastPing = urlInput.value;
		integrationResult.dataset.result = null;
		pingResult.textContent = '⚠ ' + t('xwiki', 'Could not reach this instance');
		pingResult.classList.remove('ping-successful');
		pingResult.classList.add('ping-failure');

		return null;
	}

	async function getPingResult(element) {
		const d = element.querySelector('.integration-result').dataset;
		if ("result" in d) {
			const res = d.result;
			return res === "false" ? false : res === "true" ? true : null;
		}
		return await pingInstance(element);
	}


	async function handleSaveInstanceClick({ currentTarget }) {
		const element = getParentElement(currentTarget);
		const urlInput = getUrlInput(element);
		const clientIdInput = getClientIdInput(element);

		const initialURL = urlInput.dataset.initialValue;
		let newURL = urlInput.value;

		if (!newURL) {
			return;
		}

		if (initialURL === newURL && clientIdInput.dataset.initialValue === clientIdInput.value) {
			pingInstance(element);
			return;
		}

		if (!newURL.endsWith('/')) {
			newURL += '/';
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

	function getUrlInput(element) {
		return element.querySelector('[name="instance-url"]');
	}

	function getClientIdInput(element) {
		return element.querySelector('[name="instance-clientid"]');
	}

	function getRegisteredInstanceElements() {
		const elements = document.querySelectorAll('#xwiki-admin-instance-list tr');

		// We don't take the last element which is the hidden one used to create a
		// new element, nor the first which is the header, and turn into a proper
		// array that can be used in a for..of loop
		return [].slice.call(elements, 1, elements.length - 1);
	}

	function handlePingInstanceClick({ currentTarget }) {
		const element = getParentElement(currentTarget);
		pingInstance(element);
	}

	async function handleGenerateClientIdClick({ currentTarget }) {
		const element = getParentElement(currentTarget);
		const urlInput = getUrlInput(element);
		const result = await getPingResult(element);
		const onboarding = document.getElementById("generate-client-id-onboarding");
		onboarding.hidden = false;
		document.getElementById("install-nextcloud-app-advice").hidden = result === true;
		document.getElementById("nextcloud-app-link").href = urlInput.value + "/bin/admin/XWiki/XWikiPreferences?section=XWiki.Extensions&search=Nextcloud";
		const link = document.getElementById("generate-client-id-link");
		const redirectURI = encodeURIComponent(document.getElementById('xwiki-admin-instance-list').dataset.redirectUri);
		const instanceURI = location.protocol + '//' + location.host + getRootUrl();
		link.href = urlInput.value + `/bin/view/Nextcloud/Admin/?redirect_uri=${redirectURI}&instance_uri=${instanceURI}`;
		link.onclick = function () {
			const clientIdInput = getClientIdInput(element);
			clientIdInput.value = t('xwiki', 'Paste the client ID here');
			clientIdInput.focus();
			clientIdInput.select();
			onboarding.hidden = true;
		};
		location.href = "#generate-client-id-onboarding";
	}

	function handleUrlBlur({ currentTarget }) {
		const element = getParentElement(currentTarget);
		const urlInput = getUrlInput(element);
		if (urlInput.dataset.lastPing !== urlInput.value) {
			pingInstance(element);
		}
	}

	function bindEventsToElement(element) {
		const urlInput = getUrlInput(element);
		const clientIdInput = getClientIdInput(element);
		urlInput.dataset.initialValue = urlInput.value;
		clientIdInput.dataset.initialValue = clientIdInput.value;
		urlInput.onchange = urlInput.oninput = clientIdInput.onchange = clientIdInput.oninput = handleInputChange;
		urlInput.onblur = handleUrlBlur;

		const removeBtn = element.querySelector('.xwiki-admin-remove-instance-btn');
		removeBtn.onclick = handleRemoveInstanceClick;

		const saveBtn = element.querySelector('.xwiki-admin-save-instance-btn');
		saveBtn.onclick = handleSaveInstanceClick;

		const pingBtn = element.querySelector('.xwiki-admin-ping-instance-btn');
		pingBtn.onclick = handlePingInstanceClick;

		const generateClientIdBtn = element.querySelector('.xwiki-admin-generate-clientid-btn');
		generateClientIdBtn.onclick = handleGenerateClientIdClick;

		urlInput.onkeydown = clientIdInput.onkeydown = function (e) {
			if (e.key === 'Enter') {
				saveBtn.click();
			}
		};
	}

	function getNewestInstance() {
		return document.querySelector("#xwiki-admin-instance-list tr:last-child").previousSibling;
	}

	function hideOnboarding() {
		for (const onboarding of document.getElementsByClassName("onboarding")) {
			onboarding.hidden = true;
		}
	}

	const addInstanceButton = document.getElementById('xwiki-add-instance-btn');
	if (addInstanceButton)  {
		addInstanceButton.onclick = handleAddInstanceClick;
		document.getElementById("new-instance-url").onkeydown = function (e) {
			if (e.key === 'Enter') {
				addInstanceButton.click();
			}
		};

		for (const element of getRegisteredInstanceElements()) {
			bindEventsToElement(element);
		}
	}

	const integratedMode = document.getElementById('integrated-mode');
	if (integratedMode) {
		integratedMode.addEventListener('click', async function () {
			const result = await request('POST', 'settings/setUserValue?k=integratedMode&v=' + integratedMode.checked);
			if (!result?.ok) {
				integratedMode.checked = !integratedMode.checked;
				showError(t('xwiki', 'Sorry, could not save the settings'));
			}
		});
	}

	for (const check of document.getElementsByClassName('use-instance-checkbox')) {
		check.addEventListener('click', async function () {
			const disabled = !check.checked;
			const url = check.closest('tr').querySelector('a').href;
			const result = await request('POST', `settings/setDisabled?i=${url}&v=${disabled}`);
			if (!result?.ok) {
				check.checked = disabled;
				showError(t('xwiki', 'Sorry, could not save the settings'));
			}
		});
	}

	for (const btn of document.getElementsByClassName("onboarding-skip-btn")) {
		btn.onclick = hideOnboarding;
	}

	const onboardingGenerateButton = document.getElementById("add-instance-onboarding-generate-client-id-btn");
	if (onboardingGenerateButton) {
		onboardingGenerateButton.onclick = function () {
			getNewestInstance().querySelector(".xwiki-admin-generate-clientid-btn").click();
			hideOnboarding();
		};
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
