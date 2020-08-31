<?php
declare(strict_types=1);
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

namespace OCA\Health\Services;


class PersonsService {

	public function getAllPersons($full=true) {
		return [
			[
				'id' 		=> 1,
				'name' 		=> 'First remote person',
				'notifications'	=> [],
				'age'		=> 18,
				'sex'		=> 'female',
				'size'		=> 155,
				'enabledModules'	=> [
					'weight'	=> true,
				],
				'weight'	=> [
					'unit'			=> 'kg',
					'weightTarget'	=> 70,
					'weightTargetInitialWeight'	=> 85,
					'data'	=> [
						[
							'date' => '2020-08-01',
							'weight' => 82,
							'measurement' => 30,
							'bodyfat' => 10,
						],
						[
							'date' => '2020-08-02',
							'weight' => 83,
							'measurement' => 30,
							'bodyfat' => 20,
						],
						[
							'date' => '2020-08-03',
							'weight' => 88,
							'measurement' => 30,
							'bodyfat' => 30,
						],
					],
					'measurementName'	=> 'my measure',
				],
			],
			[
				'id' 		=> 8,
				'name' 		=> 'Second remote person',
				'notifications'	=> [],
				'age'		=> 18,
				'sex'		=> 'male',
				'size'		=> 155,
				'enabledModules'	=> [
					'weight'	=> true,
				],
				'weight'	=> [
					'unit'			=> 'kg',
					'weightTarget'	=> 70,
					'weightTargetInitialWeight'	=> 85,
					'data'	=> [],
					'measurementName'	=> 'my measure',
				],
			],
		];
	}
}
