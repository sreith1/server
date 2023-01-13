<template>
	<NcBreadcrumbs>
		<!-- Current path sections -->
		<NcBreadcrumb v-for="section in sections"
			:key="section.dir"
			v-bind="section"
			@click.prevent.stop="onBreadClick(section)" />
	</NcBreadcrumbs>
</template>

<script>
import NcBreadcrumbs from '@nextcloud/vue/dist/Components/NcBreadcrumbs.js'
import NcBreadcrumb from '@nextcloud/vue/dist/Components/NcBreadcrumb.js'
import { basename } from 'path'

export default {
	name: 'BreadCrumbs',

	components: {
		NcBreadcrumbs,
		NcBreadcrumb,
	},

	props: {
		path: {
			type: String,
			default: '/',
		},
	},

	computed: {
		dirs() {
			const cumulativePath = (acc) => (value) => (acc += `${value}/`)
			return ['/', ...this.path.split('/').filter(Boolean).map(cumulativePath('/'))]
		},

		sections() {
			return this.dirs.map(dir => {
				const to = this.$router.resolve({ ...this.$route, query: { dir } })
				return {
					dir,
					href: to.href,
					title: basename(dir),
				}
			})
		},
	},

	methods: {
		/**
		 * The only way to get an exact match and query change
		 *
		 * @param {object} section
		 * @param {string} section.dir the dir path
		 */
		onBreadClick({ dir }) {
			this.$router.replace({ query: { dir } })
		},
	},
}
</script>

<style lang="scss" scoped>
.breadcrumb {
	// Put next to the navigation toggle icon
	margin: 4px 4px 4px 50px;
}
</style>
