<template>
	<NcBreadcrumbs data-cy-files-content-breadcrumbs>
		<!-- Current path sections -->
		<NcBreadcrumb v-for="section in sections"
			:key="section.dir"
			:aria-label="t('files', `Go to the '{dir}' directory`, section)"
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
		 * Push the new directory route to the router.
		 * The only way to get an exact match and query change.
		 *
		 * @param {object} section the section object
		 * @param {string} section.dir the dir path
		 */
		onBreadClick({ dir }) {
			const name = this.$route.name
			const params = this.$route.params
			console.debug({ name, params, query: { dir } })
			this.$router.push({ name, params, query: { dir } })
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
