/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type Node from '@nextcloud/files/dist/files/node'
import { Store } from 'vuex'

type FileStore {
	[id: number]: Node
}

// Create a new store instance.
export default new Store({
	state: {
		files: {} as FileStore,
	},

	getters: {
		/**
		 * Get a file or folder by id
		 */
		getNode: (state)  => (id: number): Node|undefined => state.files[id],

		/**
		 * Get a list of files or folders by their IDs
		 * Does not return undefined values
		 */
		getNodes: (state) => (ids: number[]): Node[] => ids
				.map(id => state.files[id])
				.filter(Boolean)
	},
	
	mutations: {
		updateNodes: (state, nodes: Node[]) => {
			nodes.forEach(node => {
				state.files[node.attributes.fileid] = node
			})
		}
	},

	actions: {
		addNode: (context, node: Node) => {
			context.commit('updateNodes', [node])
		},
	},
})
