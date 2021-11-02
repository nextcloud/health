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

		// gadgetbridge settings
		['name' => 'gadgetbridgeSettings#find', 'url' => '/gadgetbridge/settings/person/{personId}', 'verb' => 'GET'],
		['name' => 'gadgetbridgeSettings#setSourcePath', 'url' => '/gadgetbridge/settings/sourcepath', 'verb' => 'POST'],
		['name' => 'gadgetbridgeSettings#setBackgroundImport', 'url' => '/gadgetbridge/settings/backgroundimport', 'verb' => 'POST'],
		['name' => 'gadgetbridgeSettings#triggerImport', 'url' => '/gadgetbridge/settings/import/trigger/person/{personId}', 'verb' => 'GET'],

		// weight data
		['name' => 'weightdata#findByPerson',	'url' => '/weight/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'weightdata#create',	'url' => '/weight/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'weightdata#delete',	'url' => '/weight/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'weightdata#update',	'url' => '/weight/dataset/{id}',	'verb' => 'PUT'],

		// measurement data
		['name' => 'measurementdata#findByPerson',	'url' => '/measurement/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'measurementdata#create',	'url' => '/measurement/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'measurementdata#delete',	'url' => '/measurement/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'measurementdata#update',	'url' => '/measurement/dataset/{id}',	'verb' => 'PUT'],

		// feeling data
		['name' => 'feelingdata#findByPerson',	'url' => '/feeling/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'feelingdata#create',	'url' => '/feeling/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'feelingdata#delete',	'url' => '/feeling/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'feelingdata#update',	'url' => '/feeling/dataset/{id}',	'verb' => 'PUT'],

		// sleep data
		['name' => 'sleepdata#findByPerson',	'url' => '/sleep/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'sleepdata#create',	'url' => '/sleep/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'sleepdata#delete',	'url' => '/sleep/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'sleepdata#update',	'url' => '/sleep/dataset/{id}',	'verb' => 'PUT'],

		// smoking data
		['name' => 'smokingdata#findByPerson',	'url' => '/smoking/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'smokingdata#create',	'url' => '/smoking/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'smokingdata#delete',	'url' => '/smoking/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'smokingdata#update',	'url' => '/smoking/dataset/{id}',	'verb' => 'PUT'],

		// activities data
		['name' => 'activitiesdata#findByPerson',	'url' => '/activities/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'activitiesdata#create',	'url' => '/activities/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'activitiesdata#delete',	'url' => '/activities/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'activitiesdata#update',	'url' => '/activities/dataset/{id}',	'verb' => 'PUT'],
    ]
];
