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
	<NcAppContent v-show="!currentView?.legacy">
		<BreadCrumbs :path="dir" />
	</NcAppContent>
</template>

<script>
import { emit, subscribe } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import { translate } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import BreadCrumbs from '../components/BreadCrumbs.vue'

import logger from '../logger.js'
import Navigation from '../services/Navigation.ts'

export default {
	name: 'FilesList',

	components: {
		NcAppContent,
		BreadCrumbs,
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

	methods: {
		t: translate,
	},
}
</script>

<style scoped lang="scss">
</style>
