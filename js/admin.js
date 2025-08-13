/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: sql_elements */
/** global: sql_settings */


$(document).ready(function () {


	/**
	 * @constructs SQLAdmin
	 */
	var SQLAdmin = function () {
		$.extend(SQLAdmin.prototype, sql_elements);
		$.extend(SQLAdmin.prototype, sql_settings);

		sql_elements.init();
		sql_settings.refreshSettingPage();
	};

	OCA.FullTextSearchAdmin.sql = SQLAdmin;
	OCA.FullTextSearchAdmin.sql.settings = new SQLAdmin();

});