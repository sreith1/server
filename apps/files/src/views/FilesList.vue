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
		<NcEmptyContent data-cy-files-content-empty
			:title="t('files', 'No files in here')"
			:description="t('files', 'No files or folders have been deleted yet')">
			<template #icon>
				<TrashCan />
			</template>
		</NcEmptyContent>
	</NcAppContent>
</template>

<script lang="ts">
import { translate } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import TrashCan from 'vue-material-design-icons/TrashCan.vue'

import BreadCrumbs from '../components/BreadCrumbs.vue'
import logger from '../logger.js'
import Navigation from '../services/Navigation'

export default {
	name: 'FilesList',

	components: {
		NcAppContent,
		NcEmptyContent,
		BreadCrumbs,
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
			return this.$route?.params?.view || 'files'
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
			return this.$route?.query?.dir || '/'
		},
	},

	watch: {
		currentView(newView, oldView) {
			logger.debug('View changed', { newView, oldView })
			this.fetchContent()
		},

		dir(newDir, oldDir) {
			logger.debug('Directory changed', { newDir, oldDir })
			this.fetchContent()
		},
	},

	methods: {
		async fetchContent() {
			this.loading = true

			// If we have a cancellable promise ongoing, cancel it
			if (typeof this.promise?.cancel === 'function') {
				this.promise.cancel()
				logger.debug('Cancelled previous ongoing fetch')
			}

			// Fetch the current dir content
			this.promise = this.currentView.getContent(this.dir)
			try {
				const content = await this.promise
				logger.debug('Fetched content', { content })
			} catch (error) {
				logger.error('Error while fetching content', { error })
			}
		},

		t: translate,
	},
}
</script>

<style scoped lang="scss">
.app-content {
	// TODO: remove after all legacy views are migrated
	// Hides the legacy app-content if shown view is not legacy
	&:not(&--hidden)::v-deep + #app-content {
		display: none;
	}
}
</style>
