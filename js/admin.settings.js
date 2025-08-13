/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OC */
/** global: sql_elements */
/** global: fts_admin_settings */




var sql_settings = {

	config: null,

	refreshSettingPage: function () {

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/fulltextsearch_sql/admin/settings')
		}).done(function (res) {
			sql_settings.updateSettingPage(res);
		});

	},

	/** @namespace result.encodings */
	/** @namespace result.excerpt_context */
	updateSettingPage: function (result) {

		sql_elements.encodings.val(result.encodings);
		sql_elements.excerpt_context.val(result.excerpt_context);

		fts_admin_settings.tagSettingsAsSaved(sql_elements.sql_div);
	},


	saveSettings: function () {

		var data = {
			encodings: sql_elements.encodings.val(),
			excerpt_context: sql_elements.excerpt_context.val(),
		};

		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/fulltextsearch_sql/admin/settings'),
			data: {
				data: data
			}
		}).done(function (res) {
			sql_settings.updateSettingPage(res);
		});

	}


};