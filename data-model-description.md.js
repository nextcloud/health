persons: [
	{
		id: 1, // id from backend
		name: 'Me, Florian', // spoken name, not NC user
		notifications: [
			{
				type: 'green', // {green, orange, red}
				message: 'some text', // text for notification
			},
		],
		age: 30,
		sex: 'male',
		size: 170,
		enabledModules: [
			'weight',
		],
		weight: [
			unit: 'kg',
			target: '70',
			data: [
				{
					date: '',
					weight: 0,
					armleft: 0,
					armright: 0,
					chest: 0,
					waist: 0,
					hips: 0,
					thighleft: 0,
					thighricht: 0,
					bodyfat: 10,
				}
			],
		],
	},
	...
],