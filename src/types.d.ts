/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IAdminSettingsConfig {
	encodings: string
	excerpt_context: number | string
}

/**
 * Detail broadcast by the main fulltextsearch app whenever its admin settings change.
 */
export interface ISettingsUpdatedEventDetail {
	platform: string
	providers: string[]
}
