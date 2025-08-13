<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCA\FullTextSearch_SQL\AppInfo\Application;
use OCP\Util;


Util::addScript(Application::APP_NAME, 'admin.elements');
Util::addScript(Application::APP_NAME, 'admin.settings');
Util::addScript(Application::APP_NAME, 'admin');

?>

<div id="sql" class="section" style="display: none;">
	<h2><?php p($l->t('SQL')) ?></h2>

	<div class="div-table">

		<div class="div-table-row">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol"><?php p($l->t('Encodings')); ?>:</span>
				<br/>
				<em><?php print_unescaped($l->t('Comma separated list of encodings from %1$sthis list%2$s that the app will try to detect during indexing of content.', ['<a href="https://www.php.net/manual/en/mbstring.supported-encodings.php">', '</a>'])); ?></em>
			</div>
			<div class="div-table-col">
				<input type="text" id="encodings" placeholder="UTF-8,ISO-8859-1"/>
			</div>
		</div>

		<div class="div-table-row">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol"><?php p($l->t('Excerpt Context')); ?>:</span>
				<br/>
				<em><?php p($l->t('Number of characters to include before and after matching phrase in excerpts returned to the user.')); ?></em>
			</div>
			<div class="div-table-col">
				<input type="text" id="excerpt_context" placeholder="30"/>
			</div>
		</div>


	</div>


</div>