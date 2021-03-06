<?php

namespace SnooPHP\Instagram;

use SnooPHP\Curl\Get;

/**
 * Perform raw api requests or use dedicated methods
 * 
 * Requests can be saved in a dedicated cache
 * 
 * @author Sneppy
 */
class Api
{
	/**
	 * @var string $clientId application client id
	 */
	protected $clientId;

	/**
	 * @var string $secretId application secret id
	 */
	protected $secretId;

	/**
	 * @var string $token user access token, used for api requests
	 * 
	 * Access token can either be generated by the auth flow, or injected manually
	 */
	protected $token;

	/**
	 * @var string $lastResult last request result (raw)
	 */
	protected $lastResult;

	/**
	 * @var string $version api version (default: v1)
	 */
	protected $version = "v1";

	/**
	 * @var string $cacheClass cache class
	 */
	protected $cacheClass;

	/**
	 * @var string $defaultCacheClass
	 */
	protected static $defaultCacheClass = "SnooPHP\Cache\NullCache";

	/**
	 * @const ENDPOINT instagram api endpoint
	 */
	const ENDPOINT = "https://api.instagram.com";

	/**
	 * Create a new instance
	 */
	public function __construct()
	{
		// Set cache class
		$this->cacheClass = static::$defaultCacheClass;
	}

	/**
	 * Perform a generic query
	 * 
	 * @param string $query query string (with parameters)
	 * 
	 * @return object|bool false if fails
	 */
	public function query($query)
	{
		// If no access token, abort
		if (empty($this->token))
			return false;
		else
			$token = $this->token;

		// Build uri
		$uri	= preg_match("/^https?:\/\//", $query) ? $query : static::ENDPOINT."/{$this->version}/{$query}";
		$uri	.= (strpos($query, '?') !== false ? '&' : '?')."access_token=$token";

		// Check if cached result exists
		if ($record = $this->cacheClass::fetch("$uri|$token")) return $record;

		// Make api request
		$curl = new Get(static::ENDPOINT."/{$this->version}/{$query}".(strpos($query, '?') !== false ? '&' : '?')."access_token={$this->token}");
		if ($curl && $curl->success())
		{
			// Save record in cache and return it
			$this->lastResult = $curl->content();
			return $this->cacheClass::store("$uri|$token", $this->lastResult);
		}
		else
		{
			$this->lastResult = false;
			return false;
		}
	}

	/**
	 * Create a new instance from client id and secret
	 * 
	 * @param string	$clientId		client id
	 * @param string	$clientSecret	client secret
	 * 
	 * @return Api
	 */
	public static function withClient($clientId, $clientSecret)
	{
		$api = new static();
		$api->clientId		= $clientId;
		$api->clientSecret	= $clientSecret;
		return $api;
	}

	/**
	 * Create a new instance from existing access token
	 * 
	 * @param string $token provided access token
	 * 
	 * @return Api
	 */
	public static function withToken($token)
	{
		$api = new static();
		$api->token = $token;
		return $api;
	}

	/**
	 * Set or get default cache class for this session
	 * 
	 * @param string|null	$defaultCacheClass	cache full classname
	 * 
	 * @return string
	 */
	public static function defaultCacheClass($defaultCacheClass = null)
	{
		if ($defaultCacheClass) static::$defaultCacheClass = $defaultCacheClass;
		return static::$defaultCacheClass;
	}
}