<?php

/**
 * My Application bootstrap file.
 *
 * @copyright  Copyright (c) 2009 John Doe
 * @package    MyApplication
 */



// Step 1: Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR . '/Nette/loader.php';

// Step 2: Configure environment
// 2a) enable Nette\Debug for better exception and error visualisation
Debug::enable();

// 2b) load configuration from config.ini file
Environment::loadConfig();



// Step 3: Configure application
// 3a) get and setup a front controller
$application = Environment::getApplication();
$application->errorPresenter = 'Error';
//$application->catchExceptions = TRUE;

OrmionMapper::addConnection(Environment::getConfig("database"));



// Step 4: Setup application router
$router = $application->getRouter();

$router[] = new Route('index.php', array(
	'presenter' => 'Page',
	'action' => 'default',
), Route::ONE_WAY);

$router[] = new Route('<presenter>/<action>/<id>', array(
	'presenter' => 'Page',
	'action' => 'default',
	'id' => NULL,
));


// Step 5: Run the application!
if (Environment::getName() !== "console") {
	$application->run();
}
