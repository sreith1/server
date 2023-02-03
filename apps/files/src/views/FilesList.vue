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
		<NcEmptyContent v-if="true"
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
import { ContentsWithRoot } from '../services/Navigation'
import { join } from 'path'

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
			if (this.dir === '/') {
				return this.$store.getters['files/getRoot'](this.currentViewId)
			}
			const fileId = this.$store.getters['paths/getPath'](this.currentViewId, this.dir)
			return this.$store.getters['files/getNode'](fileId)
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
			if (newView?.id === oldView?.id) {
				return
			}

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
			/** @type {Promise<ContentsWithRoot>} */
			this.promise = currentView.getContents(dir)
			try {
				const { folder, contents } = await this.promise
				logger.debug('Fetched contents', { dir, folder, contents })

				// Update store
				this.$store.dispatch('files/addNodes', contents)

				// Define current directory children
				folder.children = contents.map(node => node.attributes.fileid)

				// If we're in the root dir, define the root
				if (dir === '/') {
					console.debug('files', 'Setting root', { service: currentView.id, folder })
					this.$store.dispatch('files/setRoot', { service: currentView.id, root: folder })
				} else
				// Otherwise, add the folder to the store
				if (folder.attributes.fileid) {
					this.$store.dispatch('files/addNodes', [folder])
					this.$store.dispatch('paths/addPath', { service: currentView.id, fileid: folder.attributes.fileid, path: dir })
				} else {
					// If we're here, the view API messed up
					logger.error('Invalid root folder returned', { dir, folder, currentView })
				}

				// Update paths store
				const folders = contents.filter(node => node.type === 'folder')
				folders.forEach(node => {
					this.$store.dispatch('paths/addPath', { service: currentView.id, fileid: node.attributes.fileid, path: join(dir, node.basename) })
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
