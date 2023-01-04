import {
	FilePicker,
	FilePickerType,
	showError,
	showSuccess,
} from '@nextcloud/dialogs'

import { request } from './common';

window.addEventListener('DOMContentLoaded', function () {
	'use strict';

	async function savePDF(e) {
		e.preventDefault();
		const targetPath = await new FilePicker(
			t('xwiki', 'Where do you want to save the PDF?'),
			false,
			['httpd/unix-directory', 'application/pdf'],
			true,
			FilePickerType.Choose,
			true
		).pick();

		const body = new FormData();
		body.append('save_path', targetPath || '/');

		const {instance, page} = e.target.dataset;
		const response = await request('POST', 'savePDF?i=' + encodeURIComponent(instance) + '&p=' + encodeURIComponent(page), {body});
		if (response?.ok) {
			showSuccess(t('xwiki', 'The page has been successfully saved!'));
		} else {
			showError(t('xwiki', 'An error occurred while saving the page:') + ' ' + response?.error);
		}

	}

	for (const link of document.querySelectorAll('.xwiki-save-pdf')) {
		link.addEventListener('click', savePDF);
	}
});
