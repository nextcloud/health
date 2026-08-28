<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\Health\AppInfo\Application::APP_ID, OCA\Health\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\Health\AppInfo\Application::APP_ID, OCA\Health\AppInfo\Application::APP_ID . '-main');

?>

<div id="health"></div>
