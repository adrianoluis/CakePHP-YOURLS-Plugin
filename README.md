# CakePHP YOURLS Plugin
* Author:  Adriano Luís Rocha (adriano.luis.rocha@gmail.com)
* Version: 0.7
* license: MIT
# Install and Setup
First clone the repository into your `APP/Plugin/Yourls` directory

	git clone git@github.com:driflash/CakePHP-YOURLS-Plugin.git APP/Plugin/Yourls

Once installed you'll need to create a file `/Yourls/Config/auth.php`. You can find an example of what you'll need and how it is laid out in `/Yourls/Config/auth.php.example`.

	//app/config/yourls.php
	$config = array(
		'Yourls' => array(
			'url' => 'YOURLS_URL',
			'username' => 'YOURLS_USERNAME',
			'password' => 'YOURLS_PASSWORD',
		)
	);

# Usage
You can call the component from any action in a controller or automate url shortening just using the follow code in your `/APP/Controller/AppController.php`:

		function beforeRender() {
			$this->pageTitle = 'your url title goes here'
		}

PS: is necessary to provide a title for shorter method otherwise it'll go in a infinite loop trying to resolve URL's title using YOURLS internal libs.

Than from yout view, access the shorted url using:

	<?php echo $shorturl['url']; ?>

To get statistics from all your links you must change the default plugin format to xml (son is nor supported yet). This new setup changes the return from shorturl method.

	var $components = array(
		'Yourls.Yourls' => array(
			'format' => 'xml'
		)
	);
