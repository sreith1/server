<!--
  - @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
  -
  - @author Gary Kim <gary@garykim.dev>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<Fragment>
		<td class="files-list__row-checkbox">
			<NcCheckboxRadioSwitch :aria-label="t('files', 'Select the row for {displayName}', { displayName })"
				:checked.sync="selectedFiles"
				:value="fileid.toString()"
				name="selectedFiles" />
		</td>
		<!-- Link to file and -->
		<td class="files-list__row-name">
			<router-link :to="to">
				{{ displayName }}
			</router-link>
		</td>
	</Fragment>
</template>

<script lang="ts">
import { Folder, File } from '@nextcloud/files'
import { Fragment } from 'vue-fragment'
import { translate } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import logger from '../logger'
import { join } from 'path'

export default {
	name: 'FileEntry',

	components: {
		Fragment,
		NcCheckboxRadioSwitch,
	},

	props: {
		index: {
			type: Number,
			required: true,
		},
		source: {
			type: [File, Folder],
			required: true,
		},
	},

	computed: {
		dir() {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
		},

		fileid() {
			return this.source.attributes.fileid
		},
		displayName() {
			return this.source.attributes.displayName
				|| this.source.basename
		},

		to() {
			if (this.source.type === 'folder') {
				return { ...this.$route, query: { dir: join(this.dir, this.source.basename) } }
			}
			return this.source.source
		},

		selectedFiles: {
			get() {
				return this.$store.state.selection.selected
			},
			set(selection) {
				logger.debug('Added node to selection', { selection })
				this.$store.commit('selection/set', selection)
			},
		},
	},

	methods: {
		/**
		 * Get a cached note from the store
		 *
		 * @param {number} fileId the file id to get
		 * @return {Folder|File}
		 */
		 getNode(fileId) {
			return this.$store.getters['files/getNode'](fileId)
		},

		t: translate,
	},
}
</script>

<style scoped lang="scss">
td {
	height: var(--row-height);
	vertical-align: middle;
	padding: 0px;
	border: none;
}

// Row select checkbox
.files-list__row-checkbox {
	width: var(--row-height);
	&::v-deep .checkbox-radio-switch {
		display: flex;
		justify-content: center;
		label.checkbox-radio-switch__label {
			margin: 0;
			height: 44px;
			width: 44px;
			padding: calc((44px - var(--icon-size)) / 2)
		}
		.checkbox-radio-switch__icon {
			margin: 0 !important;
		}
	}
}
</style>
