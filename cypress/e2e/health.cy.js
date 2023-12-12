let localUser

describe('The Home Page', () => {

	before(function() {
		cy.createRandomUser().then(user => {
			localUser = user
		})
	})

	beforeEach(function() {
		cy.login(localUser)
		cy.visit('apps/health')
	})

	it('successfully loads', () => {
		cy.get('h2').contains('Welcome to your health center').should('be.visible')
	})
})
