<?php
/**
 * @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
 *
 * @author Florian Steffens <flost-dev@mailbox.org>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

return [
	'resources' => [
        'persons' => ['url' => '/persons'],
    ],
    'routes' => [
    	// main
	   	['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	   	['name' => 'persons#data', 'url' => '/person/{personId}/data', 'verb' => 'GET'],

		// weight data
		['name' => 'weightdata#index', 'url' => '/weightdata/person/{personId}', 'verb' => 'GET'],
		['name' => 'weightdata#create', 'url' => '/weightdata/person/{personId}/create', 'verb' => 'POST'],
		['name' => 'weightdata#destroy', 'url' => '/weightdata/delete/{id}', 'verb' => 'DELETE'],
		['name' => 'weightdata#update', 'url' => '/weightdata/update/{id}', 'verb' => 'PUT'],

		// ces service
		['name' => 'ces#index', 'url' => '/ces', 'verb' => 'POST'],
		['name' => 'ces#contexts', 'url' => '/ces/contexts', 'verb' => 'POST'],
		['name' => 'ces#contexts', 'url' => '/ces/contexts', 'verb' => 'GET'],
    ]
];
