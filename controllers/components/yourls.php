<?php
App::import('Core', array('Xml', 'HttpSocket'));
Configure::load('yourls');
class YourlsComponent extends Object
{

	/**
	 * Admin username
	 *
	 * @var string
	 */
	var $username;

	/**
	 * Admin password
	 *
	 * @var string
	 */
	var $password;

	/**
	 * A secret signature token is unique, associated to one account,
	 * and can be used only for API requests. You will find it in the
	 * Tools page of your YOURLS install.
	 *
	 * @var string Your secret signature token
	 */
	var $signature;

	/**
	 * Response format
	 * 
	 * @var string
	 */
	var $fotmat;
	
	/**
	 * Response filter for stats
	 * 
	 * @var string
	 */
	var $filter;
	
	/**
	 * Yourls address
	 * 
	 * @var string
	 */
	var $url;

	var $requestMethod;
	
	var $__http;

	var $__defaultFormat = 'xml';

	var $__defaultFilter = 'top';
	
	var $__defaultRequestMethod = 'get';

	var $__formats = array('json', 'xml', 'simple');

	var $__filters = array('top', 'bottom', 'rand', 'last');
	
	var $__requestMethods = array('get', 'post');

	/**
	 * Startup component
	 *
	 * @param object $controller Instantiating controller
	 * @access public
	 */
	function startup(&$controller)
	{
		$this->__http =& new HttpSocket();
		$this->url = Configure::read('Yourls.url');
		if (Configure::read('Yourls.signature'))
		{
			$this->signature = Configure::read('Yourls.signature');
		}
		elseif (Configure::read('Yourls.username') && Configure::read('Yourls.password'))
		{
			$this->username = Configure::read('Yourls.username');
			$this->password = Configure::read('Yourls.password');
		}
	}
	
	function initialize(&$controller, $settings = array())
	{
		if (isset($settings['requestMethod']) && in_array(strtolower($settings['requestMethod']), $this->__requestMethods))
		{
			$this->requestMethod = strtolower($settings['requestMethod']);
		}
		else
		{
			$this->requestMethod = $this->__defaultRequestMethod;
		}
		if (isset($settings['format']) && in_array(strtolower($settings['format']), $this->__formats))
		{
			$this->fotmat = strtolower($settings['format']);
		}
		else
		{
			$this->fotmat = $this->__defaultFormat;
		}

		if (isset($settings['filter']) && in_array(strtolower($settings['filter']), $this->__filters))
		{
			$this->filter = strtolower($settings['filter']);
		}
		else
		{
			$this->filter = $this->__defaultFilter;
		}
	}

	/**
	 * Get short URL for a link
	 *
	 * @param string $url to shorten
	 * @param string $keyword [optional] for custom short URLs
	 * @param string $format [optional] either "json" or "xml"
	 */
	function shorturl($url, $keyword = NULL, $format = NULL)
	{
		if (empty($format))
		{
			$format = $this->fotmat;
		}
		$query = array(
			'action' => 'shorturl',
			'url' => $url,
			'fotmat' => $format
		);
		if (!empty($keyword))
		{
			$query = array_merge($query, array('keyword' => $keyword));
		}
		return $this->__process($this->__request($query));
	}

	/**
	 * Get long URL of a shorturl
	 *
	 * @param string $shorturl to expand (can be either 'abc' or 'http://site/abc')
	 * @param string $format [optional] either "json" or "xml"
	 */
	function expand($shorturl, $format = NULL)
	{
		if (empty($format))
		{
			$format = $this->fotmat;
		}
		$query = array(
			'action' => 'expand',
			'shorturl' => $shorturl,
			'fotmat' => $format
		);
		return $this->__process($this->__request($query));
	}

	/**
	 * Get stats about one short URL
	 *
	 * @param string $shorturl for which to get stats (can be either 'abc' or 'http://site/abc')
	 * @param string $format [optional] either "json" or "xml"
	 */
	function url_stats($shorturl, $format = NULL)
	{
		if (empty($format))
		{
			$format = $this->fotmat;
		}
		$query = array(
			'action' => 'url-stats',
			'shorturl' => $shorturl,
			'fotmat' => $format
		);
		return $this->__process($this->__request($query));
	}

	/**
	 * Get stats about your links
	 *
	 * @param string $filter [optional] either "top", "bottom" , "rand" or "last"
	 * @param int $limit maximum number of links to return
	 * @param string $format [optional] either "json" or "xml"
	 */
	function stats($filter = NULL, $limit = NULL, $format = NULL)
	{
		if (empty($format))
		{
			$format = $this->fotmat;
		}
		if (empty($filter))
		{
			$filter = $this->filter;
		}
		$query = array(
			'action' => 'stats',
			'filter' => $filter,
			'fotmat' => fotmat
		);
		if (!empty($limit))
		{
			$query = array_merge($query, array('limit' => $limit));
		}
		return $this->__process($this->__request($query));
	}

	/**
	 * Calls HttpSocket request method using auth options
	 * 
	 * @param array $query
	 * @param array $request
	 */
	function __request($query = array(), $request = array())
	{
		$url = "{$this->url}/yourls-api.php";
		
		if (!empty($this->signature))
		{
			$query = array_merge($query, array('signature' => $this->signature));
		}
		elseif (!empty($this->username) && !empty($this->password))
		{
			$request = array_merge($request, $this->__getAuthHeader());
		}
		if ($this->requestMethod === 'get')
		{
			return $this->__http->get($url, $query, $request);
		} elseif ($this->requestMethod === 'post')
		{
			return $this->__http->post($url, $query, $request);
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Convert response into array
	 *
	 * @param string $response HttpSocket response.
	 */
	function __process($response)
	{
		$array = array();
		if (!empty($response))
		{
			if ($this->fotmat === 'xml')
			{
				$xml = new XML($response);
				$temp = $xml->toArray();
				$array = array(
					'Yourls' => $temp['Result']
				);
				$xml->__destruct();
				$xml = null;
				unset($xml);
			}
		}
		return $array;
	}

	/**
	 * Credentials array for method with mandatory auth
	 *
	 *
	 */
	function __getAuthHeader() {
		return array(
			'auth' => array(
				'method' => 'Basic',
				'user' => $this->username,
				'pass' => $this->password
			)
		);
	}

}
