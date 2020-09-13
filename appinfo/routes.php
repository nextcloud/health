<?php
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
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
        // 'weightdata' => ['url' => '/weightdata']
    ],
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	   ['name' => 'weightdata#create', 'url' => '/weightdata/create', 'verb' => 'POST'],
	   ['name' => 'weightdata#destroy', 'url' => '/weightdata/delete/{id}', 'verb' => 'DELETE'],
	   ['name' => 'weightdata#update', 'url' => '/weightdata/update/{id}', 'verb' => 'PUT'],
    ]
];
