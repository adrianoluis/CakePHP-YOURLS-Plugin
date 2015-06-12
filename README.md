# CakePHP YOURLS Plugin
* Author:  Adriano Luís Rocha (adriano.luis.rocha@gmail.com)
* Version: 1.0
* license: MIT

# Install and Setup
First add this repository as git submodule in your CakePHP project:

	git submodule add https://github.com/adrianoluis/CakePHP-YOURLS-Plugin.git APP/Plugin/Yourls
	git submodule update --init

Once installed you'll need to create a file `/APP/Config/yourls.php`. You can find an example of what you'll need and how it is laid out in `/Yourls/Config/yourls.php.example`.

	//app/Config/yourls.php
	$config = array(
		'Yourls' => array(
			'url' => 'YOURLS_URL',
			'username' => 'YOURLS_USERNAME',
			'password' => 'YOURLS_PASSWORD',
		)
	);

# Usage
You can call the component from any action in a controller or automate url shortening just using the follow code in your `/APP/Controller/AppController.php`:

	public function beforeRender() {
		$this->shortIt = true;
		$this->pageTitle = 'your url title goes here'
	}

PS: is necessary to provide a title for shorter method otherwise it will go in a infinite loop trying to resolve URL's title using YOURLS internal libs.

Than from yout view, access the shorted url using:

	<?php echo $shorturl['url']; ?>

To get statistics from all your links you need to choose between json or xml. This new setup changes the return from shorturl method.

	public $components = array(
		'Yourls.Yourls' => array(
			'format' => 'xml'
		)
	);
