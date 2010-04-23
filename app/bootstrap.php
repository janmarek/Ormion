<?php

use Nette\Debug;
use Nette\Environment;
use Nette\Application\Route;
use Ormion\Ormion;

require LIBS_DIR . '/Nette/loader.php';

Debug::enable();

Environment::loadConfig();

$application = Environment::getApplication();

Ormion::connect(Environment::getConfig("database"));

$router = $application->getRouter();

$router[] = new Route('<presenter>/<action>/<id>', array(
	'presenter' => 'Homepage',
	'action' => 'default',
	'id' => NULL,
));

if (Environment::getName() !== "console") {
	$application->run();
}
