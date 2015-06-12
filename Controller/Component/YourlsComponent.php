<?php
/**
 * Yourls.Yourls
 * Uses remote calls to short the url.
 *
 * @author Adriano Luís Rocha <adriano [dot] luis [dot] rocha [at] gmail [dot] com>
 * @since 1.0
 * @license MIT
 */
App::uses('Xml', 'Utility');
App::uses('HttpSocket', 'Network/Http');

Configure::load('yourls');

class YourlsComponent extends Component
{

	/**
	 * Admin username
	 *
	 * @var string
	 */
	private $__username;

	/**
	 * Admin password
	 *
	 * @var string
	 */
	private $__password;

	/**
	 * A secret signature token is unique, associated to one account,
	 * and can be used only for API requests. You will find it in the
	 * Tools page of your YOURLS install.
	 *
	 * @var string Your secret signature token
	 */
	private $__signature;

	/**
	 * YOURLS installation URL, no trailing slash
	 *
	 * @var string
	 */
	private $__url;

	/**
	 * Handle HttpSocket instance from CakePHP Core
	 *
	 * @var HttpSocket
	 */
	private $__httpSocket;

	/**
	 * Available file formats to comunicate with Yourls API.
	 *
	 * @var string
	 */
	private $__formats = array('json', 'xml', 'simple');

	/**
	 * Available filters for statistics.
	 *
	 * @var string
	 */
	private $__filters = array('top', 'bottom', 'rand', 'last');

	/**
	 * Available communication methods
	 *
	 * @var string
	 */
	private $__requestMethods = array('get', 'post');

	/**
	 * Response format
	 *
	 * @var string
	 */
	public $format = 'simple';

	/**
	 * Response filter for stats
	 *
	 * @var string
	 */
	public $filter = 'top';

	/**
	 * Request method
	 *
	 * @var string
	 */
	public $requestMethod = 'get';

	/**
	 * Convert response into array
	 *
	 * @param string $response from remote call to YOURLS api
	 * @return array|string
	 */
	private function process(HttpSocketResponse $response)
	{
		$result = array();
		if (!empty($response)) {
			$body = $response->body();
			switch ($this->format) {
				case 'xml':
					$xml = Xml::build($body);
					$temp = Xml::toArray($xml);
					$result = array('Yourls' => $temp['result']);
					break;
				case 'json':
					$result = array('Yourls' => json_decode($body, true));
					break;
				default:
					$result = $body;
					break;
			}
		}
		return $result;
	}

	/**
	 * Calls HttpSocket request method using auth options
	 *
	 * @param array $query array with request parameters
	 */
	private function request($query)
	{
		$url = "{$this->__url}/yourls-api.php";

		if (!empty($this->__signature)) {
			$query = array_merge($query, array('signature' => $this->__signature));
		} elseif (!empty($this->__username) && !empty($this->__password)) {
			$query = array_merge($query, array('username' => $this->__username, 'password' => $this->__password));
		}
		return $this->__httpSocket->{$this->requestMethod}($url, $query);
	}

	/*
	 * @see Component::beforeRender($controller)
	 */
	public function beforeRender(Controller $controller)
	{
		if (isset($controller->shortIt) && $controller->shortIt === true) {
			if (isset($controller->pageTitle)) {
				$controller->set('shorturl', $this->shorturl("http://{$_SERVER['SERVER_NAME']}{$controller->request->here}", $controller->pageTitle));
			} else {
				trigger_error(__('No page title provided. Impossible to short URL.'), E_USER_ERROR);
			}
		}
	}

	/*
	 * @see Component::startup($controller)
	 */
	public function startup(Controller $controller)
	{
		$this->__httpSocket =& new HttpSocket();
		$this->__url = Configure::read('Yourls.url');
		if (Configure::read('Yourls.signature')) {
			$this->__signature = Configure::read('Yourls.signature');
		} elseif (Configure::read('Yourls.username') && Configure::read('Yourls.password')) {
			$this->__username = Configure::read('Yourls.username');
			$this->__password = Configure::read('Yourls.password');
		} else {
			trigger_error(__('No authentication provided!'), E_USER_ERROR);
		}
	}

	/*
	 * @see Component::__construct($collection, $settings)
	 */
	public function __construct(ComponentCollection $collection, $settings = array())
	{
		if (isset($settings['format']) && !in_array($settings['format'], $this->__formats)) {
			trigger_error(__('Invalid value for \'format\' setting.'), E_USER_WARNING);
			unset($settings['format']);
		}
		if (isset($settings['requestMethods']) && !in_array($settings['requestMethods'], $this->__requestMethods)) {
			trigger_error(__('Invalid value for \'requestMethods\' setting.'), E_USER_WARNING);
			unset($settings['requestMethods']);
		}
		if (isset($settings['filter']) && !in_array($settings['filter'], $this->__filters)) {
			trigger_error(__('Invalid value for \'filter\' setting.'), E_USER_WARNING);
			unset($settings['filter']);
		}
		parent::__construct($collection, $settings);
	}

	/**
	 * Get short URL for a link
	 *
	 * @param string $url to shorten
	 * @param string $title title for url
	 * @param string $keyword [optional] for custom short URLs
	 * @param string $format [optional] either "json" or "xml"
	 * @return array|string
	 */
	public function shorturl($url, $title, $keyword = null, $format = null)
	{
		if (empty($format)) {
			$format = $this->format;
		}
		$query = array(
			'action' => 'shorturl',
			'url' => $url,
			'title' => $title,
			'format' => $format
		);
		if (!empty($keyword)) {
			$query = array_merge($query, array('keyword' => $keyword));
		}
		$result = $this->process($this->request($query));
		if ($this->format !== 'simple') {
			$result = $result['Yourls']['shorturl'];
		}
		return $result;
	}

	/**
	 * Get long URL of a shorturl
	 *
	 * @param string $shorturl to expand (can be either 'abc' or 'http://site/abc')
	 * @param string $format [optional] either "json" or "xml"
	 * @return array|string
	 */
	public function expand($shorturl, $format = null)
	{
		if (empty($format)) {
			$format = $this->format;
		}
		$query = array(
			'action' => 'expand',
			'shorturl' => $shorturl,
			'format' => $format
		);
		return $this->process($this->request($query));
	}

	/**
	 * Get stats about one short URL
	 *
	 * @param string $shorturl for which to get stats (can be either 'abc' or 'http://site/abc')
	 * @param string $format [optional] either "json" or "xml"
	 * @return array|string
	 */
	public function urlStats($shorturl, $format = null)
	{
		if (empty($format)) {
			$format = $this->format;
		}
		$query = array(
			'action' => 'url-stats',
			'shorturl' => $shorturl,
			'format' => $format
		);
		return $this->process($this->request($query));
	}

	/**
	 * Get stats about your links
	 *
	 * @param string $filter [optional] either "top", "bottom" , "rand" or "last"
	 * @param int [optional] $limit maximum number of links to return
	 * @param string $format [optional] either "json" or "xml"
	 * @return array|string
	 */
	public function stats($filter = null, $limit = null, $format = null)
	{
		if (empty($format)) {
			$format = $this->format;
		}
		if (empty($filter)) {
			$filter = $this->filter;
		}
		$query = array(
			'action' => 'stats',
			'filter' => $filter,
			'format' => $format
		);
		if (!empty($limit)) {
			$query = array_merge($query, array('limit' => $limit));
		}
		return $this->process($this->request($query));
	}

	/**
	 * Get database stats
	 *
	 * @param string $format [optional] either "json" or "xml"
	 * @return array|string
	 */
	public function dbStats($format = null)
	{
		if (empty($format)) {
			$format = $this->format;
		}
		$query = array(
			'action' => 'db-stats',
			'format' => $format
		);
		return $this->process($this->request($query));
	}

}
