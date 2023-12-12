// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
import { addCommands } from '@nextcloud/cypress'
require('cypress-downloadfile/lib/downloadFileCommand')

const url = Cypress.config('baseUrl').replace(/\/index.php\/?$/g, '')
Cypress.env('baseUrl', url)

addCommands()

Cypress.Commands.add('uploadFile', (fileName, mimeType, target) => {
	return cy.fixture(fileName, 'binary')
		.then(Cypress.Blob.binaryStringToBlob)
		.then(blob => {
			if (typeof target !== 'undefined') {
				fileName = target
			}
			cy.request('/csrftoken')
				.then(({ body }) => {
					return cy.wrap(body.token)
				})
				.then(async (requesttoken) => {
					return cy.request({
						url: `${url}/remote.php/webdav/${fileName}`,
						method: 'put',
						body: blob.size > 0 ? blob : '',
						// auth,
						headers: {
							requesttoken,
							'Content-Type': mimeType,
						},
					})
				}).then(response => {
					const fileId = Number(
						response.headers['oc-fileid']?.split('oc')?.[0],
					)
					cy.log(`Uploaded ${fileName}`,
						response.status,
						{ fileId },
					)
					return cy.wrap(fileId)
				})
		})
})

Cypress.Commands.add('ocsRequest', (user, options) => {
	const auth = { user: user.userId, password: user.password }
	return cy.request({
		form: true,
		auth,
		headers: {
			'OCS-ApiRequest': 'true',
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		...options,
	})
})
Cypress.Commands.add('createGroup', (groupName) => {
	cy.ocsRequest({ userId: 'admin', password: 'admin' }, {
		method: 'POST',
		url: `${url}/ocs/v2.php/cloud/groups`,
		body: {
			groupid: groupName,
			displayname: groupName,
		},
	}).then(response => {
		cy.log(`Group ${groupName} created.`, response.status)
	})
})

Cypress.Commands.add('addUserToGroup', (userId, groupId) => {
	cy.ocsRequest({ userId: 'admin', password: 'admin' }, {
		method: 'POST',
		url: `${url}/ocs/v2.php/cloud/users/${userId}/groups`,
		body: {
			groupid: groupId,
		},
	}).then(response => {
		cy.log(`User ${userId} added to group ${groupId}.`, response.status)
	})
})
