<?php

namespace OCA\Xwiki\AppInfo;

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#savePDF', 'url' => '/savePDF', 'verb' => 'POST'],
		['name' => 'settings#deleteToken', 'url' => 'settings/deleteToken', 'verb' => 'POST'],
		['name' => 'settings#addToken', 'url' => 'settings/addToken', 'verb' => 'POST'],
		['name' => 'settings#requestToken', 'url' => 'settings/requestToken', 'verb' => 'GET'],
		['name' => 'settings#oidcRedirect', 'url' => 'settings/oidcRedirect', 'verb' => 'GET'],
		['name' => 'settings#addInstance', 'url' => 'settings/addInstance', 'verb' => 'POST'],
		['name' => 'settings#setUserValue', 'url' => 'settings/setUserValue', 'verb' => 'POST'],
		['name' => 'settings#replaceInstance', 'url' => 'settings/replaceInstance', 'verb' => 'POST'],
		['name' => 'settings#deleteInstance', 'url' => 'settings/deleteInstance', 'verb' => 'POST'],
		['name' => 'settings#pingInstance', 'url' => 'settings/pingInstance', 'verb' => 'GET'],
	]
];
