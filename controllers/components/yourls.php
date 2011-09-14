<?php
/**
 * Yourls.Yourls
 * Uses remote calls to short the url.
 *
 * @author Adriano Lu’s Rocha <driflash [at] gmail [dot] com>
 * @since 0.6
 * @license MIT
 */
App::import('Core', array('Xml', 'HttpSocket'));
Configure::load('yourls');
class YourlsComponent extends Object
{

	/**
	 * Admin username
	 *
	 * @var string
	 */
	var $__username;

	/**
	 * Admin password
	 *
	 * @var string
	 */
	var $__password;

	/**
	 * A secret signature token is unique, associated to one account,
	 * and can be used only for API requests. You will find it in the
	 * Tools page of your YOURLS install.
	 *
	 * @var string Your secret signature token
	 */
	var $__signature;

	/**
	 * YOURLS installation URL, no trailing slash
	 *
	 * @var string
	 */
	var $__url;
	
	/**
	* Handle HttpSocket instance from CakePHP Core
	*
	* @var HttpSocket
	*/
	var $__httpSocket;

	/**
	* Available file formats to comunicate with Yourls API.
	*
	* @var string
	*/
	var $__formats = array('json', 'xml', 'simple');
	
	/**
	 * Available filters for statistics.
	 *
	 * @var string
	 */
	var $__filters = array('top', 'bottom', 'rand', 'last');
	
	/**
	 * Available communication methods
	 *
	 * @var string
	
	/**
	 * Response format
	 *
	 * @var string
	 */
	var $format = 'simple';

	/**
	 * Response filter for stats
	 *
	 * @var string
	 */
	var $filter = 'top';

	/**
	 * Request method
	 *
	 * @var string
	 */
	var $requestMethod = 'get';

	/**
	 * Startup component
	 *
	 * @param object $controller Instantiating controller
	 */
	function startup(&$controller)
	{
		$this->__httpSocket =& new HttpSocket();
		$this->__url = Configure::read('Yourls.url');
		if (Configure::read('Yourls.signature'))
		{
			$this->__signature = Configure::read('Yourls.signature');
		}
		elseif (Configure::read('Yourls.username') && Configure::read('Yourls.password'))
		{
			$this->__username = Configure::read('Yourls.username');
			$this->__password = Configure::read('Yourls.password');
		}
		else
		{
			trigger_error(__('No authentication provided!', TRUE), E_USER_NOTICE);
		}
	}

	function initialize(&$controller, $settings = array())
	{
		if (isset($settings['format']) && !in_array($settings['format'], $this->__formats))
		{
			trigger_error(__('Invalid value for \'format\' setting.', TRUE), E_USER_NOTICE);
			unset($settings['format']);
		}
		if (isset($settings['filter']) && !in_array($settings['filter'], $this->__filters))
		{
			trigger_error(__('Invalid value for \'filter\' setting.', TRUE), E_USER_NOTICE);
			unset($settings['filter']);
		}
		$this->_set($settings);
	}

	/**
	 * Get short URL for a link
	 *
	 * @param string $url to shorten
	 * @param string $title title for url
	 * @param string $keyword [optional] for custom short URLs
	 * @param string $format [optional] either "json" or "xml"
	 */
	function shorturl($url, $title, $keyword = NULL, $format = NULL)
	{
		if (empty($format))
		{
			$format = $this->format;
		}
		$query = array(
			'action' => 'shorturl',
			'url' => $url,
			'title' => $title,
			'format' => $format
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
			$format = $this->format;
		}
		$query = array(
			'action' => 'expand',
			'shorturl' => $shorturl,
			'format' => $format
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
			$format = $this->format;
		}
		$query = array(
			'action' => 'url-stats',
			'shorturl' => $shorturl,
			'format' => $format
		);
		return $this->__process($this->__request($query));
	}

	/**
	 * Get stats about your links
	 *
	 * @param string $filter [optional] either "top", "bottom" , "rand" or "last"
	 * @param int [optional] $limit maximum number of links to return
	 * @param string $format [optional] either "json" or "xml"
	 */
	function stats($filter = NULL, $limit = NULL, $format = NULL)
	{
		if (empty($format))
		{
			$format = $this->format;
		}
		if (empty($filter))
		{
			$filter = $this->filter;
		}
		$query = array(
			'action' => 'stats',
			'filter' => $filter,
			'format' => $format
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
	 * @param array $query array with request parameters
	 */
	function __request($query)
	{
		$url = "{$this->__url}/yourls-api.php";

		if (!empty($this->__signature))
		{
			$query = array_merge($query, array('signature' => $this->__signature));
		}
		elseif (!empty($this->__username) && !empty($this->__password))
		{
			$query = array_merge($query, array('username' => $this->__username, 'password' => $this->__password));
		}
		if ($this->requestMethod === 'get')
		{
			return $this->__httpSocket->get($url, $query);
		}
		elseif ($this->requestMethod === 'post')
		{
			return $this->__httpSocket->post($url, $query);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Convert response into array
	 *
	 * @param string $response from remote call to YOURLS api
	 */
	function __process($response)
	{
		$array = array();
		if (!empty($response))
		{
			if ($this->format === 'xml')
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
			elseif ($this->format === 'json')
			{
				// TODO json parse
			}
			elseif ($this->format === 'simple')
			{
				$array = array(
					'url' => $response
				);
			}
		}
		return $array;
	}

}
