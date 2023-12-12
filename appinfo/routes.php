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

		// enable CORS for api calls (API version 1)
		['name' => 'weightdata_api#preflighted_cors', 'url' => '/api/1/{path}',
			'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],

		// main
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'persons#data', 'url' => '/person/{personId}/data', 'verb' => 'GET'],
		['name' => 'persons_api#data', 'url' => '/api/1/person/{personId}/data', 'verb' => 'GET'],

		['name' => 'persons#addAcl', 'url' => '/persons/{personId}/acl', 'verb' => 'POST'],
		['name' => 'persons#updateAcl', 'url' => '/persons/{personId}/acl/{aclId}', 'verb' => 'PUT'],
		['name' => 'persons#deleteAcl', 'url' => '/persons/{personId}/acl/{aclId}', 'verb' => 'DELETE'],
		['name' => 'persons_api#addAcl', 'url' => '/api/1/persons/{personId}/acl', 'verb' => 'POST'],
		['name' => 'persons_api#updateAcl', 'url' => '/api/1/persons/{personId}/acl/{aclId}', 'verb' => 'PUT'],
		['name' => 'persons_api#deleteAcl', 'url' => '/api/1/persons/{personId}/acl/{aclId}', 'verb' => 'DELETE'],

		// weight data
		['name' => 'weightdata#findByPerson',	'url' => '/weight/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'weightdata#create',	'url' => '/weight/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'weightdata#delete',	'url' => '/weight/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'weightdata#update',	'url' => '/weight/dataset/{id}',	'verb' => 'PUT'],

		['name' => 'weightdata_api#findByPerson',	'url' => '/api/1/weight/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'weightdata_api#create',	'url' => '/api/1/weight/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'weightdata_api#delete',	'url' => '/api/1/weight/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'weightdata_api#update',	'url' => '/api/1/weight/dataset/{id}',	'verb' => 'PUT'],

		// measurement data
		['name' => 'measurementdata#findByPerson',	'url' => '/measurement/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'measurementdata#create',	'url' => '/measurement/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'measurementdata#delete',	'url' => '/measurement/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'measurementdata#update',	'url' => '/measurement/dataset/{id}',	'verb' => 'PUT'],

		['name' => 'measurementdata_api#findByPerson',	'url' => '/api/1/measurement/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'measurementdata_api#create',	'url' => '/api/1/measurement/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'measurementdata_api#delete',	'url' => '/api/1/measurement/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'measurementdata_api#update',	'url' => '/api/1/measurement/dataset/{id}',	'verb' => 'PUT'],

		// feeling data
		['name' => 'feelingdata#findByPerson',	'url' => '/feeling/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'feelingdata#create',	'url' => '/feeling/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'feelingdata#delete',	'url' => '/feeling/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'feelingdata#update',	'url' => '/feeling/dataset/{id}',	'verb' => 'PUT'],

		['name' => 'feelingdata_api#findByPerson',	'url' => '/api/1/feeling/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'feelingdata_api#create',	'url' => '/api/1/feeling/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'feelingdata_api#delete',	'url' => '/api/1/feeling/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'feelingdata_api#update',	'url' => '/api/1/feeling/dataset/{id}',	'verb' => 'PUT'],

		// sleep data
		['name' => 'sleepdata#findByPerson',	'url' => '/sleep/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'sleepdata#create',	'url' => '/sleep/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'sleepdata#delete',	'url' => '/sleep/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'sleepdata#update',	'url' => '/sleep/dataset/{id}',	'verb' => 'PUT'],

		['name' => 'sleepdata_api#findByPerson',	'url' => '/api/1/sleep/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'sleepdata_api#create',	'url' => '/api/1/sleep/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'sleepdata_api#delete',	'url' => '/api/1/sleep/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'sleepdata_api#update',	'url' => '/api/1/sleep/dataset/{id}',	'verb' => 'PUT'],

		// smoking data
		['name' => 'smokingdata#findByPerson',	'url' => '/smoking/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'smokingdata#create',	'url' => '/smoking/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'smokingdata#delete',	'url' => '/smoking/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'smokingdata#update',	'url' => '/smoking/dataset/{id}',	'verb' => 'PUT'],

		['name' => 'smokingdata_api#findByPerson',	'url' => '/api/1/smoking/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'smokingdata_api#create',	'url' => '/api/1/smoking/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'smokingdata_api#delete',	'url' => '/api/1/smoking/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'smokingdata_api#update',	'url' => '/api/1/smoking/dataset/{id}',	'verb' => 'PUT'],

		// activities data
		['name' => 'activitiesdata#findByPerson',	'url' => '/activities/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'activitiesdata#create',	'url' => '/activities/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'activitiesdata#delete',	'url' => '/activities/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'activitiesdata#update',	'url' => '/activities/dataset/{id}',	'verb' => 'PUT'],

		['name' => 'activitiesdata_api#findByPerson',	'url' => 'api/1/activities/dataset/person/{personId}',	'verb' => 'GET'],
		['name' => 'activitiesdata_api#create',	'url' => 'api/1/activities/dataset/person/{personId}',	'verb' => 'POST'],
		['name' => 'activitiesdata_api#delete',	'url' => 'api/1/activities/dataset/{id}',	'verb' => 'DELETE'],
		['name' => 'activitiesdata_api#update',	'url' => 'api/1/activities/dataset/{id}',	'verb' => 'PUT'],

		// medication data
		['name' => 'medicationdata#findPlanByPerson',	'url' => '/medication/plans/person/{personId}',	'verb' => 'GET'],
		['name' => 'medicationdata#createPlan',	'url' => '/medication/plans/person/{personId}',	'verb' => 'POST'],
		['name' => 'medicationdata#deletePlan',	'url' => '/medication/plans/{id}',	'verb' => 'DELETE'],
		['name' => 'medicationdata#updatePlan',	'url' => '/medication/plans/{id}',	'verb' => 'PUT'],
		['name' => 'medicationdata#findMedicationByPlan',	'url' => '/medication/medication/plan/{planId}',	'verb' => 'GET'],
		['name' => 'medicationdata#createMedication',	'url' => '/medication/medication/plan/{planId}',	'verb' => 'POST'],
		['name' => 'medicationdata#deleteMedication',	'url' => '/medication/medication/{id}',	'verb' => 'DELETE'],
		['name' => 'medicationdata#updateMedication',	'url' => '/medication/medication/{id}',	'verb' => 'PUT'],

		['name' => 'medicationdata_api#findPlanByPerson',	'url' => 'api/1/medication/plans/person/{personId}',	'verb' => 'GET'],
		['name' => 'medicationdata_api#createPlan',	'url' => 'api/1/medication/plans/person/{personId}',	'verb' => 'POST'],
		['name' => 'medicationdata_api#deletePlan',	'url' => 'api/1/medication/plans/{id}',	'verb' => 'DELETE'],
		['name' => 'medicationdata_api#updatePlan',	'url' => 'api/1/medication/plans/{id}',	'verb' => 'PUT'],
		['name' => 'medicationdata_api#findMedicationByPlan',	'url' => 'api/1/medication/medication/plan/{planId}',	'verb' => 'GET'],
		['name' => 'medicationdata_api#createMedication',	'url' => 'api/1/medication/medication/plan/{planId}',	'verb' => 'POST'],
		['name' => 'medicationdata_api#deleteMedication',	'url' => 'api/1/medication/medication/{id}',	'verb' => 'DELETE'],
		['name' => 'medicationdata_api#updateMedication',	'url' => 'api/1/medication/medication/{id}',	'verb' => 'PUT'],
	]
];
