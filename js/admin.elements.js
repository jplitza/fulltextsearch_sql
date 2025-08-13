/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: fts_admin_settings */
/** global: sql_settings */


var sql_elements = {
	sql_div: null,
	encodings: null,
	excerpt_context: null,


	init: function () {
		sql_elements.sql_div = $('#sql');
		sql_elements.encodings = $('#encodings');
		sql_elements.excerpt_context = $('#excerpt_context');

		sql_elements.encodings.on('input', function () {
			fts_admin_settings.tagSettingsAsNotSaved($(this));
		}).blur(function () {
			sql_settings.saveSettings();
		});

		sql_elements.excerpt_context.on('input', function () {
			fts_admin_settings.tagSettingsAsNotSaved($(this));
		}).blur(function () {
			sql_settings.saveSettings();
		});
	}


};