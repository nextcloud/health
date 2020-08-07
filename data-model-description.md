persons: [
	{
		id: 1, // id from backend
		name: 'Me, Florian', // spoken name, not NC user
		notifications: [
			{
				client: 'F', //notifications F = frontend, B = backend, FB = both
				type: 'green', // {green, orange, red}
				message: 'some text', // text for notification
			},
		],
	},
	{
		id: 2,
		name: 'Madita',
	},
],