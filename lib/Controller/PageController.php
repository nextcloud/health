<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;

/**
 * @psalm-suppress UnusedClass
 */
class PageController extends Controller {
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	#[FrontpageRoute(verb: 'GET', url: '/journal/{date}', postfix: 'journal')]
	#[FrontpageRoute(verb: 'GET', url: '/goals', postfix: 'goals')]
	#[FrontpageRoute(verb: 'GET', url: '/statistics', postfix: 'statistics')]
	#[FrontpageRoute(verb: 'GET', url: '/settings', postfix: 'settings')]
	public function index(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'index',
		);
	}
}
