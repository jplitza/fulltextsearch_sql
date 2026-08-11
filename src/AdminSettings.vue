<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		v-show="visible"
		:name="t('fulltextsearch_sql', 'SQL')">
		<NcFormBox>
			<NcTextField
				v-model="config.encodings"
				:label="t('fulltextsearch_sql', 'Encodings')"
				:helperText="t('fulltextsearch_sql', 'Comma-separated list of encodings to detect while indexing. Adding encodings other than UTF-8 can cause binary files to be indexed unintentionally.')"
				placeholder="UTF-8, ISO-8859-1"
				@blur="saveSettings" />

			<NcTextField
				v-model="config.excerpt_context"
				type="number"
				min="0"
				:label="t('fulltextsearch_sql', 'Excerpt context')"
				:helperText="t('fulltextsearch_sql', 'Approximate number of characters to include before and after a matching phrase in search excerpts.')"
				placeholder="30"
				@blur="saveSettings" />
		</NcFormBox>
	</NcSettingsSection>
</template>

<script setup lang="ts">
import type { IAdminSettingsConfig, ISettingsUpdatedEventDetail } from './types.d.ts'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { SETTINGS_UPDATED_EVENT, SQL_PLATFORM_ID } from './constants.ts'
import { logger } from './logger.ts'

const config = ref(loadState<IAdminSettingsConfig>('fulltextsearch_sql', 'adminConfig'))
const currentSettings = window.OCA?.FullTextSearch?.settings

// The legacy settings frontend controls the #sql mount element directly. If the modern event
// contract is absent, keep the component itself visible to retain Nextcloud 31-33 compatibility.
const visible = ref(currentSettings === undefined || currentSettings.platform === SQL_PLATFORM_ID)

/**
 * Show or hide this section based on the platform selected in the main Full Text Search settings.
 *
 * @param detail The settings event payload.
 */
function onSettingsUpdated(detail: ISettingsUpdatedEventDetail): void {
	visible.value = detail.platform === SQL_PLATFORM_ID
}

/**
 * @param event The fulltextsearch:settings-admin-updated CustomEvent.
 */
function handleSettingsUpdatedEvent(event: Event): void {
	onSettingsUpdated((event as CustomEvent<ISettingsUpdatedEventDetail>).detail)
}

onMounted(() => {
	window.addEventListener(SETTINGS_UPDATED_EVENT, handleSettingsUpdatedEvent)
})

onBeforeUnmount(() => {
	window.removeEventListener(SETTINGS_UPDATED_EVENT, handleSettingsUpdatedEvent)
})

/** Persist the settings and refresh local state from the server response. */
async function saveSettings(): Promise<void> {
	try {
		const { data } = await axios.post<IAdminSettingsConfig>(generateUrl('/apps/fulltextsearch_sql/admin/settings'), {
			data: {
				encodings: config.value.encodings,
				excerpt_context: Number.parseInt(String(config.value.excerpt_context), 10),
			},
		})
		config.value = data
	} catch (error) {
		logger.error('Failed to save Full Text Search SQL settings', { error })
	}
}
</script>
