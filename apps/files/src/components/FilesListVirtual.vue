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
	<VirtualList class="files-list"
		:data-component="FileEntry"
		:data-key="getFileId"
		:data-sources="nodes"
		:estimate-size="55"
		:table-mode="true"
		item-class="files-list__row"
		wrap-class="files-list__body">
		<caption class="files-list__caption">
			{{ summary }}
		</caption>
	</VirtualList>
</template>

<script lang="ts">
import { Folder, File } from '@nextcloud/files'
import { translate, translatePlural } from '@nextcloud/l10n'
import VirtualList from 'vue-virtual-scroll-list'

import FileEntry from './FileEntry.vue'

export default {
	name: 'FilesListVirtual',

	components: {
		VirtualList,
	},

	props: {
		nodes: {
			type: [File, Folder],
			required: true,
		},
	},

	data() {
		return {
			FileEntry,
		}
	},

	computed: {
		files() {
			return this.nodes.filter(node => node.type === 'file')
		},

		summaryFile() {
			const count = this.files.length
			return translatePlural('files', '{count} file', '{count} files', count, { count })
		},
		summaryFolder() {
			const count = this.nodes.length - this.files.length
			return translatePlural('files', '{count} folder', '{count} folders', count, { count })
		},
		summary() {
			return translate('files', '{summaryFile} and {summaryFolder}', this)
		},
	},

	methods: {
		getFileId(node) {
			return node.attributes.fileid
		},

		t: translate,
	},
}
</script>

<style scoped lang="scss">
.files-list {
	display: block;
	overflow: auto;
	height: 100%;

	&::v-deep {
		.files-list__body {
			display: flex;
			flex-direction: column;
			width: 100%;
		}

		.files-list__row {
			--row-height: 55px;
			border-bottom: 1px solid var(--color-border);
		}
	}
}
</style>
