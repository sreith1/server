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
	<NcAppContent v-show="!currentView?.legacy"
		:class="{'app-content--hidden': currentView?.legacy}"
		data-cy-files-content>
		<!-- Current folder breadcrumbs -->
		<BreadCrumbs :path="dir" />

		<!-- Empty content placeholder -->
		<NcEmptyContent v-if="isEmptyDir"
			:title="t('files', 'No files in here')"
			:description="t('files', 'No files or folders have been deleted yet')"
			data-cy-files-content-empty>
			<template #icon>
				<TrashCan />
			</template>
		</NcEmptyContent>
	
		<!-- File list -->
		<FilesListVirtual v-else :nodes="dirContents" />
	</NcAppContent>
</template>

<script lang="ts">
import { Folder } from '@nextcloud/files'
import { translate } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import TrashCan from 'vue-material-design-icons/TrashCan.vue'

import BreadCrumbs from '../components/BreadCrumbs.vue'
import logger from '../logger.js'
import Navigation from '../services/Navigation'
import FilesListVirtual from '../components/FilesListVirtual.vue'

export default {
	name: 'FilesList',

	components: {
		BreadCrumbs,
		FilesListVirtual,
		NcAppContent,
		NcEmptyContent,
		TrashCan,
	},

	props: {
		// eslint-disable-next-line vue/prop-name-casing
		Navigation: {
			type: Navigation,
			required: true,
		},
	},

	data() {
		return {
			loading: false,
			promise: null,
		}
	},

	computed: {
		currentViewId() {
			return this.$route.params.view || 'files'
		},

		/** @return {Navigation} */
		currentView() {
			return this.views.find(view => view.id === this.currentViewId)
		},

		/** @return {Navigation[]} */
		views() {
			return this.Navigation.views
		},

		dir() {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
		},

		currentFolder() {
			return this.$store.getters['paths/getPath'](this.currentViewId, this.dir)
		},

		dirContents() {
			return (this.currentFolder?.children || []).map(this.getNode)
		},

		isEmptyDir() {
			return this.loading === false && this.dirContents.length === 0
		},
	},

	watch: {
		currentView(newView, oldView) {
			logger.debug('View changed', { newView, oldView })
			this.$store.dispatch('selection/reset')
			this.fetchContent()
		},

		dir(newDir, oldDir) {
			logger.debug('Directory changed', { newDir, oldDir })
			// TODO: preserve selection on browsing?
			this.$store.dispatch('selection/reset')
			this.fetchContent()
		},

		paths(paths) {
			logger.debug('Paths changed', { paths })
		},

		currentFolder(currentFolder) {
			logger.debug('currentFolder changed', { currentFolder })
		},
	},

	methods: {
		async fetchContent() {
			if (this.currentView?.legacy) {
				return
			}

			this.loading = true
			const dir = this.dir
			const currentView = this.currentView

			// If we have a cancellable promise ongoing, cancel it
			if (typeof this.promise?.cancel === 'function') {
				this.promise.cancel()
				logger.debug('Cancelled previous ongoing fetch')
			}

			// Fetch the current dir contents
			this.promise = currentView.getContents(dir)
			try {
				const { root, contents } = await this.promise
				logger.debug('Fetched contents', { dir, root, contents })

				// Update store
				this.$store.dispatch('files/addNodes', contents)

				// If we're in the root dir, define the root
				if (dir === '/') {
					console.debug('files', 'Setting root', { service: currentView.id, root })
					this.$store.dispatch('files/setRoot', { service: currentView.id, root })
				}

				// Define current directory children
				root.children = contents.map(node => node.attributes.fileid)
				this.$store.dispatch('paths/addPath', { service: currentView.id, path: this.dir, node: root })

				// Update paths store
				const folders = contents.filter(node => node.type === 'folder')
				folders.forEach(node => {
					// Automatically determine the path from the current folder
					const path = node.source.replace(root.source, '')
					this.$store.dispatch('paths/addPath', { service: currentView.id, node, path })
				})
			} catch (error) {
				logger.error('Error while fetching content', { error })
			}

		},

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
.app-content {
	// Virtual list needs to be full height and is scrollable
	display: flex;
	overflow: hidden;
	flex-direction: column;
	max-height: 100%;

	// TODO: remove after all legacy views are migrated
	// Hides the legacy app-content if shown view is not legacy
	&:not(&--hidden)::v-deep + #app-content {
		display: none;
	}
}

.files-list-entry {
	height: 55px;
}

</style>
