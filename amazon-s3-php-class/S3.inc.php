<?php
/**
* $Id$
*
* Copyright (c) 2013, Donovan Schönknecht.  All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* Amazon S3 is a trademark of Amazon.com, Inc. or its affiliates.
*/

/**
* Amazon S3 PHP class
*
* @link http://undesigned.org.za/2007/10/22/amazon-s3-php-class
* @version 0.5.1
*/
/*
2022-06-16 - Brian Blood
- Major change: I have removed all of the static constructions.

*/

class S3
{
	// ACL flags
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	const ACL_AUTHENTICATED_READ = 'authenticated-read';

	const STORAGE_CLASS_STANDARD = 'STANDARD';
	const STORAGE_CLASS_RRS = 'REDUCED_REDUNDANCY';
	const STORAGE_CLASS_STANDARD_IA = 'STANDARD_IA';

	const SSE_NONE = '';
	const SSE_AES256 = 'AES256';

	const DEFAULT_ENDPOINT_S3 = 's3.amazonaws.com';
	const DEFAULT_ENDPOINT_CF = 'cloudfront.amazonaws.com';

	/**
	 * The AWS Access key
	 *
	 * @var string
	 * @access private
	 */
	private $__accessKey = null;

	/**
	 * AWS Secret Key
	 *
	 * @var string
	 * @access private
	 */
	private $__secretKey = null;

	/**
	 * SSL Client key
	 *
	 * @var string
	 * @access private
	 */
	private $__sslKey = null;

	/**
	 * Default delimiter to be used, for example while getBucket().
	 * @var string
	 * @access public
	 * @static 
	 */
	public $defDelimiter = null;

	/**
	 * AWS URI
	 *
	 * @var string
	 * @acess public
	 */
	public $endpoint = self::DEFAULT_ENDPOINT_S3;

	/**
	 * Are buckets prefixed the host name 
	 *
	 * @var boolean
	 * @acess protected
	 */
	protected $hostBuckets = true;

	/**
	 * AWS Region
	 *
	 * @var string
	 * @acess public
	 */
	public $region = '';

	/**
	 * Proxy information
	 *
	 * @var null|array
	 * @access public
	 */
	public $proxy = null;

	/**
	 * Connect using SSL?
	 *
	 * @var bool
	 * @access public
	 */
	public $useSSL = false;

	/**
	 * Use SSL validation?
	 *
	 * @var bool
	 * @access public
	 */
	public $useSSLValidation = true;

	/**
	 * Use SSL version
	 *
	 * @var const
	 * @access public
	 */
	public $useSSLVersion = null;

	/**
	 * Use PHP exceptions?
	 *
	 * @var bool
	 * @access public
	 */
	public $useExceptions = false;

	/**
	 * Time offset applied to time()
	 * @access private
	 */
	private $__timeOffset = 0;

	/**
	 * SSL client key
	 *
	 * @var bool
	 * @access public
	 */
	public $sslKey = null;
	
	/**
	 * SSL client certfificate
	 *
	 * @var string
	 * @acess public
	 */
	public $sslCert = null;
	
	/**
	 * SSL CA cert (only required if you are having problems with your system CA cert)
	 *
	 * @var string
	 * @access public
	 */
	public $sslCACert = null;
	
	/**
	 * AWS Key Pair ID
	 *
	 * @var string
	 * @access private
	 */
	private $__signingKeyPairId = null;
	
	/**
	 * Key resource, freeSigningKey() must be called to clear it from memory
	 *
	 * @var bool
	 * @access private
	 * @static 
	 */
	private $__signingKeyResource = false;

	/**
	 * CURL progress function callback 
	 *
	 * @var function
	 * @access public
	 * @static 
	 */
	public $progressFunction = null;


	/**
	 * curl timeout
	 *
	 * @var int
	 * @access public
	 * @static
	 */
	public $curlTimeout = 60;


	/**
	 * curl timeout
	 *
	 * @var int
	 * @access public
	 * @static
	 */
	public $LastRequest = null;


## ---------------

	/**
	* Constructor
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @param boolean $useSSL Enable SSL
	* @param string $endpoint Amazon URI
	* @return void
	*/
	public function __construct($accessKey = null, $secretKey = null, $useSSL = false, $endpoint=false, $region = '')
	{
		if ($accessKey !== null && $secretKey !== null)
		{
			self::setAuth($accessKey, $secretKey);
		}
		$this->useSSL = $useSSL;
		$this->endpoint = (false === $endpoint) ? self::DEFAULT_ENDPOINT_S3 : $endpoint;
		$this->region = $region;
	}



	/**
	* Get whether buckets are prefixed to the hostname when constructing URI
	*
	* @return boolean
	*/
	public function getHostBuckets()
	{
		return $this->hostBuckets;
	}


	/**
	* Set whether buckets are prefixed to the hostname when constructing URI
	*
	* @param string $host Hostname
	* @return void
	*/
	public function setHostBuckets($useHostBuckets)
	{
		$this->hostBuckets = boolval($useHostBuckets);
		return $this;
	}

	/**
	* Set the service region
	*
	* @param string $region
	* @return void
	*/
	public function setRegion($region)
	{
		$this->region = $region;
		return $this;
	}


	/**
	* Get the service region
	*
	* @return string $region
	* @static
	*/
	public function getRegion()
	{
		$region = $this->region;

		// parse region from endpoint if not specific
		if (empty($region)) 
		{
			if (preg_match("/s3[.-](?:website-|dualstack\.)?(.+)\.amazonaws\.com/i", $this->endpoint, $match) !== 0 
			&& strtolower($match[1]) !== "external-1") 
			{
				$region = $match[1];
			}		
		}

		return empty($region) ? 'us-east-1' : $region;
	}


	/**
	* Set AWS access key and secret key
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @return void
	*/
	public function setAuth($accessKey, $secretKey)
	{
		$this->__accessKey = $accessKey;
		$this->__secretKey = $secretKey;
		return $this;
	}


	/**
	* Check if AWS keys have been set
	*
	* @return boolean
	*/
	public function hasAuth() {
		return ($this->__accessKey !== null && $this->__secretKey !== null);
	}


	/**
	* Set SSL on or off
	*
	* @param boolean $enabled SSL enabled
	* @param boolean $validate SSL certificate validation
	* @return void
	*/
	public function setSSL($enabled, $validate = true)
	{
		$this->useSSL = $enabled;
		$this->useSSLValidation = $validate;
		return $this;
	}


	/**
	* Set SSL client certificates (experimental)
	*
	* @param string $sslCert SSL client certificate
	* @param string $sslKey SSL client key
	* @param string $sslCACert SSL CA cert (only required if you are having problems with your system CA cert)
	* @return void
	*/
	public function setSSLAuth($sslCert = null, $sslKey = null, $sslCACert = null)
	{
		$this->sslCert = $sslCert;
		$this->sslKey = $sslKey;
		$this->sslCACert = $sslCACert;
		return $this;
	}


	/**
	* Set proxy information
	*
	* @param string $host Proxy hostname and port (localhost:1234)
	* @param string $user Proxy username
	* @param string $pass Proxy password
	* @param constant $type CURL proxy type
	* @return void
	*/
	public function setProxy($host, $user = null, $pass = null, $type = CURLPROXY_SOCKS5)
	{
		$this->proxy = array('host' => $host, 'type' => $type, 'user' => $user, 'pass' => $pass);
		return $this;
	}


	/**
	* Set the error mode to exceptions
	*
	* @param boolean $enabled Enable exceptions
	* @return void
	*/
	public function setExceptions($enabled = true)
	{
		$this->useExceptions = $enabled;
		return $this;
	}


	/**
	* Set AWS time correction offset (use carefully)
	*
	* This can be used when an inaccurate system time is generating
	* invalid request signatures.  It should only be used as a last
	* resort when the system time cannot be changed.
	*
	* @param string $offset Time offset (set to zero to use AWS server time)
	* @return void
	*/
	public function setTimeCorrectionOffset($offset = 0)
	{
		if ($offset == 0)
		{
			$this->LastRequest = $rest = new S3Request($this, 'HEAD');
			$rest = $rest->getResponse();
			$awstime = $rest->headers['date'];
			$systime = time();			
			$offset = $systime > $awstime ? -($systime - $awstime) : ($awstime - $systime);
		}
		$this->__timeOffset = $offset;
		return $this;
	}


	/**
	* Set signing key
	*
	* @param string $keyPairId AWS Key Pair ID
	* @param string $signingKey Private Key
	* @param boolean $isFile Load private key from file, set to false to load string
	* @return boolean
	*/
	public function setSigningKey($keyPairId, $signingKey, $isFile = true)
	{
		$this->__signingKeyPairId = $keyPairId;
		$skData = $isFile ?	file_get_contents($signingKey) : $signingKey;
		$this->__signingKeyResource = openssl_pkey_get_private($skData);
		if ( false !== $this->__signingKeyResource )			 return true;

		self::__triggerError(__METHOD__.'(): Unable to open load private key: '.$signingKey, __FILE__, __LINE__);
		return false;
	}



	/**
	* Free signing key from memory, MUST be called if you are using setSigningKey()
	*
	* @return void
	*/
	public function freeSigningKey()
	{
		if ($this->__signingKeyResource !== false)
			openssl_free_key($this->__signingKeyResource);
	}

	/**
	* Set progress function
	*
	* @param function $func Progress function 
	* @return void
	*/
	public function setProgressFunction($func = null)
	{
		if (!is_callable($func))		return false;
		$this->progressFunction = $func;
		return $this;
	}

/**
	 * Sets the curl timeout from the default of 60 seconds (use 0 for no timeout)
	 *
	 * @return void
	 */
	public static function setTimeout($timeout)
	{
		$this->curlTimeout = abs($timeout+0);
		return $this;
	}


## ------------------------------------------------------------------------


	/**
	* Internal error handler
	*
	* @internal Internal error handler
	* @param string $message Error message
	* @param string $file Filename
	* @param integer $line Line number
	* @param integer $code Error code
	* @return void
	*/
	private function __triggerError($message, $file, $line, $code = 0)
	{
		if ($this->useExceptions)
			throw new S3Exception($message, $file, $line, $code, $this->LastRequest);
		else
			trigger_error($message, E_USER_WARNING);
	}


	/**
	* Get a list of buckets
	*
	* @param boolean $detailed Returns detailed bucket list when true
	* @return array | false
	*/
	public function listBuckets($detailed = false)
	{
		$this->LastRequest = $rest = new S3Request($this, 'GET', '', '');
		$rest = $rest->getResponse();

		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s", $rest->error['code'],
			$rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		$results = array();
		if (!isset($rest->body->Buckets)) return $results;

		if ($detailed)
		{
			if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
			$results['owner'] = array(
				'id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->DisplayName
			);
			$results['buckets'] = array();
			foreach ($rest->body->Buckets->Bucket as $b)
				$results['buckets'][] = array(
					'name' => (string)$b->Name, 'time' => strtotime((string)$b->CreationDate)
				);
		} else
			foreach ($rest->body->Buckets->Bucket as $b) $results[] = (string)$b->Name;

		return $results;
	}


	/**
	* Get contents for a bucket
	*
	* If maxKeys is null this method will loop through truncated result sets
	*
	* @param string $bucket Bucket name
	* @param string $prefix Prefix
	* @param string $marker Marker (last file listed)
	* @param string $maxKeys Max keys (maximum number of keys to return)
	* @param string $delimiter Delimiter
	* @param boolean $returnCommonPrefixes Set to true to return CommonPrefixes
	* @return array | false
	*/
	public function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false)
	{
		$this->LastRequest = $rest = new S3Request($this, 'GET', $bucket, '');
		if ($maxKeys == 0) $maxKeys = null;
		if ($prefix !== null && $prefix !== '') $rest->setParameter('prefix', $prefix);
		if ($marker !== null && $marker !== '') $rest->setParameter('marker', $marker);
		if ($maxKeys !== null && $maxKeys !== '') $rest->setParameter('max-keys', $maxKeys);
		if ($delimiter !== null && $delimiter !== '') $rest->setParameter('delimiter', $delimiter);
		else if (!empty($this->defDelimiter)) $rest->setParameter('delimiter', $this->defDelimiter);
		$response = $rest->getResponse();
		if ($response->error === false && $response->code !== 200)
			$response->error = array('code' => $response->code, 'message' => 'Unexpected HTTP status');
		if ($response->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
			$response->error['code'], $response->error['message']), __FILE__, __LINE__);
			return false;
		}

		$results = array();

		$nextMarker = null;
		if (isset($response->body, $response->body->Contents))
		foreach ($response->body->Contents as $c)
		{
			$results[(string)$c->Key] = array(
				'name' => (string)$c->Key,
				'time' => strtotime((string)$c->LastModified),
				'size' => (int)$c->Size,
				'hash' => substr((string)$c->ETag, 1, -1)
			);
			$nextMarker = (string)$c->Key;
		}

		if ($returnCommonPrefixes && isset($response->body, $response->body->CommonPrefixes))
			foreach ($response->body->CommonPrefixes as $c)
				$results[(string)$c->Prefix] = array('prefix' => (string)$c->Prefix);

		if (isset($response->body, $response->body->IsTruncated) &&
		(string)$response->body->IsTruncated == 'false') return $results;

		if (isset($response->body, $response->body->NextMarker))
			$nextMarker = (string)$response->body->NextMarker;

		// Loop through truncated results if maxKeys isn't specified
		if ($maxKeys == null && $nextMarker !== null && (string)$response->body->IsTruncated == 'true')
		do
		{
			$this->LastRequest = $rest = new S3Request($this, 'GET', $bucket, '');
			if ($prefix !== null && $prefix !== '') $rest->setParameter('prefix', $prefix);
			$rest->setParameter('marker', $nextMarker);
			if ($delimiter !== null && $delimiter !== '') $rest->setParameter('delimiter', $delimiter);

			if (($response = $rest->getResponse()) == false || $response->code !== 200) break;

			if (isset($response->body, $response->body->Contents))
			foreach ($response->body->Contents as $c)
			{
				$results[(string)$c->Key] = array(
					'name' => (string)$c->Key,
					'time' => strtotime((string)$c->LastModified),
					'size' => (int)$c->Size,
					'hash' => substr((string)$c->ETag, 1, -1)
				);
				$nextMarker = (string)$c->Key;
			}

			if ($returnCommonPrefixes && isset($response->body, $response->body->CommonPrefixes))
				foreach ($response->body->CommonPrefixes as $c)
					$results[(string)$c->Prefix] = array('prefix' => (string)$c->Prefix);

			if (isset($response->body, $response->body->NextMarker))
				$nextMarker = (string)$response->body->NextMarker;

		} while ($response !== false && (string)$response->body->IsTruncated == 'true');

		return $results;
	}


	/**
	* Put a bucket
	*
	* @param string $bucket Bucket name
	* @param constant $acl ACL flag
	* @param string $location Set as "EU" to create buckets hosted in Europe
	* @return boolean
	*/
	public function putBucket($bucket, $acl = self::ACL_PRIVATE, $location = false)
	{
		$this->LastRequest = $rest = new S3Request($this, 'PUT', $bucket, '');
		$rest->setAmzHeader('x-amz-acl', $acl);

		if ($location === false) $location = self::getRegion();

		if ($location !== false && $location !== "us-east-1")
		{
			$dom = new DOMDocument;
			$createBucketConfiguration = $dom->createElement('CreateBucketConfiguration');
			$locationConstraint = $dom->createElement('LocationConstraint', $location);
			$createBucketConfiguration->appendChild($locationConstraint);
			$dom->appendChild($createBucketConfiguration);
			$rest->data = $dom->saveXML();
			$rest->size = strlen($rest->data);
			$rest->setHeader('Content-Type', 'application/xml');
		}
		$rest = $rest->getResponse();

		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, {$acl}, {$location}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}


	/**
	* Delete an empty bucket
	*
	* @param string $bucket Bucket name
	* @return boolean
	*/
	public function deleteBucket($bucket)
	{
		$this->LastRequest = $rest = new S3Request($this, 'DELETE', $bucket, '');
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 204)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}


	/**
	* Create input info array for putObject()
	*
	* @param string $file Input file
	* @param mixed $md5sum Use MD5 hash (supply a string if you want to use your own)
	* @return array | false
	*/
	public function inputFile($file, $md5sum = true)
	{
		if (!file_exists($file) || !is_file($file) || !is_readable($file))
		{
			self::__triggerError(__METHOD__.'(): Unable to open input file: '.$file, __FILE__, __LINE__);
			return false;
		}
		clearstatcache(false, $file);
		return array('file' => $file, 'size' => filesize($file), 'md5sum' => $md5sum !== false ?
		(is_string($md5sum) ? $md5sum : base64_encode(md5_file($file, true))) : '', 'sha256sum' => hash_file('sha256', $file));
	}


	/**
	* Create input array info for putObject() with a resource
	*
	* @param string $resource Input resource to read from
	* @param integer $bufferSize Input byte size
	* @param string $md5sum MD5 hash to send (optional)
	* @return array | false
	*/
	public function inputResource(&$resource, $bufferSize = false, $md5sum = '')
	{
		if (!is_resource($resource) || (int)$bufferSize < 0)
		{
			self::__triggerError(__METHOD__.'(): Invalid resource or buffer size', __FILE__, __LINE__);
			return false;
		}

		// Try to figure out the bytesize
		if ($bufferSize === false)
		{
			if (fseek($resource, 0, SEEK_END) < 0 || ($bufferSize = ftell($resource)) === false)
			{
				self::__triggerError(__METHOD__.'(): Unable to obtain resource size', __FILE__, __LINE__);
				return false;
			}
			fseek($resource, 0);
		}

		$input = array('size' => $bufferSize, 'md5sum' => $md5sum);
		$input['fp'] =& $resource;
		return $input;
	}


	/**
	* Put an object
	*
	* @param mixed $input Input data
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param array $requestHeaders Array of request headers or content type as a string
	* @param constant $storageClass Storage class constant
	* @param constant $serverSideEncryption Server-side encryption
	* @param boolean $checkExistence Whether to check if the object was already there
	* @return boolean
	*/
	public function putObject($input, $bucket, $uri, $acl=self::ACL_PRIVATE, $metaHeaders=array(), $requestHeaders=array(), $storageClass=false, $serverSideEncryption=false, $checkExistence=false)
	{
		if ($input === false)				return false;

		if (false === $storageClass)					$storageClass = self::STORAGE_CLASS_STANDARD;
		if (false === $serverSideEncryption)			$serverSideEncryption = self::SSE_NONE;


		if (!is_array($input))		$input = array(
					'data' => $input, 'size' => strlen($input),
					'md5sum' => base64_encode(md5($input, true)),
					'sha256sum' => hash('sha256', $input),
																						);

		if ($checkExistence && isset($input['md5sum'])) {
			$response = $this->getObjectInfo($bucket, $uri);
			if (isset($response['hash']) && bin2hex(base64_decode($input['md5sum'])) == $response['hash']) 				return true;
		}

		$this->LastRequest = $rest = new S3Request($this, 'PUT', $bucket, $uri);


		// Data
		if (isset($input['fp']))
			$rest->fp =& $input['fp'];
		elseif (isset($input['file']))
			$rest->fp = @fopen($input['file'], 'rb');
		elseif (isset($input['data']))
			$rest->data = $input['data'];

		// Content-Length (required)
		if (isset($input['size']) && $input['size'] >= 0)
			$rest->size = $input['size'];
		else {
			if (isset($input['file'])) {
				clearstatcache(false, $input['file']);
				$rest->size = filesize($input['file']);
			}
			elseif (isset($input['data']))
				$rest->size = strlen($input['data']);
		}

		// Custom request headers (Content-Type, Content-Disposition, Content-Encoding)
		if (is_array($requestHeaders))
			foreach ($requestHeaders as $h => $v)
				strpos($h, 'x-amz-') === 0 ? $rest->setAmzHeader($h, $v) : $rest->setHeader($h, $v);
		elseif (is_string($requestHeaders)) // Support for legacy contentType parameter
			$input['type'] = $requestHeaders;

		// Content-Type
		if (!isset($input['type']))
		{
			if (isset($requestHeaders['Content-Type']))
				$input['type'] =& $requestHeaders['Content-Type'];
			elseif (isset($input['file']))
				$input['type'] = self::__getMIMEType($input['file']);
			else
				$input['type'] = 'application/octet-stream';
		}

		if ($storageClass !== self::STORAGE_CLASS_STANDARD) // Storage class
			$rest->setAmzHeader('x-amz-storage-class', $storageClass);

		if ($serverSideEncryption !== self::SSE_NONE) // Server-side encryption
			$rest->setAmzHeader('x-amz-server-side-encryption', $serverSideEncryption);

		// We need to post with Content-Length and Content-Type, MD5 is optional
		if ($rest->size >= 0 && ($rest->fp !== false || $rest->data !== false))
		{
			$rest->setHeader('Content-Type', $input['type']);
			if (isset($input['md5sum']))				$rest->setHeader('Content-MD5', $input['md5sum']);

			if (isset($input['sha256sum']))			$rest->setAmzHeader('x-amz-content-sha256', $input['sha256sum']);

			$rest->setAmzHeader('x-amz-acl', $acl);
			foreach ($metaHeaders as $h => $v)		$rest->setAmzHeader('x-amz-meta-'.$h, $v);
			$rest->getResponse();
		}
		else
				$rest->response->error = array('code' => 0, 'message' => 'Missing input parameters');

		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
			$rest->response->error['code'], $rest->response->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}


	/**
	* Put an object from a file (legacy function)
	*
	* @param string $file Input file path
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param string $contentType Content type
	* @return boolean
	*/
	public function putObjectFile($file, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = null, $storageClass=false, $serverSideEncryption=false, $checkExistence=false)
	{
		return self::putObject(self::inputFile($file), $bucket, $uri, $acl, $metaHeaders, $contentType, $storageClass, $serverSideEncryption, $checkExistence);
	}


	/**
	* Put an object from a string (legacy function)
	*
	* @param string $string Input data
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param string $contentType Content type
	* @return boolean
	*/
	public function putObjectString($string, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = 'text/plain', $storageClass=false, $serverSideEncryption=false, $checkExistence=false)
	{
		return self::putObject($string, $bucket, $uri, $acl, $metaHeaders, $contentType, $storageClass, $serverSideEncryption, $checkExistence);
	}


	/**
	* Get an object
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param mixed $saveTo Filename or resource to write to
	* @return mixed
	*/
	public function getObject($bucket, $uri, $saveTo = false)
	{
		$this->LastRequest = $rest = new S3Request($this, 'GET', $bucket, $uri);
		if ($saveTo !== false)
		{
			if (is_resource($saveTo))
				$rest->fp =& $saveTo;
			else
				if (($rest->fp = @fopen($saveTo, 'wb')) !== false)
					$rest->file = realpath($saveTo);
				else
					$rest->response->error = array('code' => 0, 'message' => 'Unable to open save file for writing: '.$saveTo);
		}
		if ($rest->response->error === false) $rest->getResponse();

		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, {$uri}): [%s] %s",
			$rest->response->error['code'], $rest->response->error['message']), __FILE__, __LINE__);
			return false;
		}
		return $rest->response;
	}


	/**
	* Get object information
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param boolean $returnInfo Return response information
	* @return mixed | false
	*/
	public function getObjectInfo($bucket, $uri, $returnInfo=true)
	{
		$this->LastRequest = $rest = new S3Request($this, 'HEAD', $bucket, $uri);
		$rest = $rest->getResponse();
		if ( $rest->error === false && ($rest->code !== 200 && $rest->code !== 404) )
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, {$uri}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		if ($rest->code == 200)			return $returnInfo;
		return !empty($rest->headers);
	}


	/**
	* Copy an object
	*
	* @param string $srcBucket Source bucket name
	* @param string $srcUri Source object URI
	* @param string $bucket Destination bucket name
	* @param string $uri Destination object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Optional array of x-amz-meta-* headers
	* @param array $requestHeaders Optional array of request headers (content type, disposition, etc.)
	* @param constant $storageClass Storage class constant
	* @return mixed | false
	*/
	public function copyObject($srcBucket, $srcUri, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $requestHeaders = array(), $storageClass = self::STORAGE_CLASS_STANDARD)
	{
		$this->LastRequest = $rest = new S3Request($this, 'PUT', $bucket, $uri);
		$rest->setHeader('Content-Length', 0);
		foreach ($requestHeaders as $h => $v)
				strpos($h, 'x-amz-') === 0 ? $rest->setAmzHeader($h, $v) : $rest->setHeader($h, $v);
		foreach ($metaHeaders as $h => $v) $rest->setAmzHeader('x-amz-meta-'.$h, $v);
		if ($storageClass !== self::STORAGE_CLASS_STANDARD) // Storage class
				$rest->setAmzHeader('x-amz-storage-class', $storageClass);
		$rest->setAmzHeader('x-amz-acl', $acl);
		$rest->setAmzHeader('x-amz-copy-source', sprintf('/%s/%s', $srcBucket, rawurlencode($srcUri)));
		if (sizeof($requestHeaders) > 0 || sizeof($metaHeaders) > 0)
				$rest->setAmzHeader('x-amz-metadata-directive', 'REPLACE');

		$rest = $rest->getResponse();
		if ($rest->error === false && ($rest->code !== 200 && $rest->code !== 403 && $rest->code !== 404) )
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$srcBucket}, {$srcUri}, {$bucket}, {$uri}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return isset($rest->body->LastModified, $rest->body->ETag) ? array(
			'time' => strtotime((string)$rest->body->LastModified),
			'hash' => substr((string)$rest->body->ETag, 1, -1)
		) : false;
	}


	/**
	* Set up a bucket redirection
	*
	* @param string $bucket Bucket name
	* @param string $location Target host name
	* @return boolean
	*/
	public function setBucketRedirect($bucket = NULL, $location = NULL)
	{
		$this->LastRequest = $rest = new S3Request($this, 'PUT', $bucket, '');

		if( empty($bucket) || empty($location) ) {
			self::__triggerError(__METHOD__."({$bucket}, {$location}): Empty parameter.", __FILE__, __LINE__);
			return false;
		}

		$dom = new DOMDocument;
		$websiteConfiguration = $dom->createElement('WebsiteConfiguration');
		$redirectAllRequestsTo = $dom->createElement('RedirectAllRequestsTo');
		$hostName = $dom->createElement('HostName', $location);
		$redirectAllRequestsTo->appendChild($hostName);
		$websiteConfiguration->appendChild($redirectAllRequestsTo);
		$dom->appendChild($websiteConfiguration);
		$rest->setParameter('website', null);
		$rest->data = $dom->saveXML();
		$rest->size = strlen($rest->data);
		$rest->setHeader('Content-Type', 'application/xml');
		$rest = $rest->getResponse();

		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, {$location}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}


	/**
	* Set logging for a bucket
	*
	* @param string $bucket Bucket name
	* @param string $targetBucket Target bucket (where logs are stored)
	* @param string $targetPrefix Log prefix (e,g; domain.com-)
	* @return boolean
	*		https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketLogging.html
	*/
	public function setBucketLogging($bucket, $targetBucket, $targetPrefix = null)
	{
		// The S3 log delivery group has to be added to the target bucket's ACP
		if ($targetBucket !== null && ($acp = self::getAccessControlPolicy($targetBucket, '')) !== false)
		{
			// Only add permissions to the target bucket when they do not exist
			$aclWriteSet = false;
			$aclReadSet = false;
			foreach ($acp['acl'] as $acl)
			if ($acl['type'] == 'Group' && $acl['uri'] == 'http://acs.amazonaws.com/groups/s3/LogDelivery')
			{
				if ($acl['permission'] == 'WRITE') $aclWriteSet = true;
				elseif ($acl['permission'] == 'READ_ACP') $aclReadSet = true;
			}
			if (!$aclWriteSet) $acp['acl'][] = array(
				'type' => 'Group', 'uri' => 'http://acs.amazonaws.com/groups/s3/LogDelivery', 'permission' => 'WRITE'
			);
			if (!$aclReadSet) $acp['acl'][] = array(
				'type' => 'Group', 'uri' => 'http://acs.amazonaws.com/groups/s3/LogDelivery', 'permission' => 'READ_ACP'
			);
			if (!$aclReadSet || !$aclWriteSet) self::setAccessControlPolicy($targetBucket, '', $acp);
		}

		$dom = new DOMDocument;
		$bucketLoggingStatus = $dom->createElement('BucketLoggingStatus');
		$bucketLoggingStatus->setAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');
		if ($targetBucket !== null)
		{
			if ($targetPrefix == null) $targetPrefix = $bucket . '-';
			$loggingEnabled = $dom->createElement('LoggingEnabled');
			$loggingEnabled->appendChild($dom->createElement('TargetBucket', $targetBucket));
			$loggingEnabled->appendChild($dom->createElement('TargetPrefix', $targetPrefix));
			// TODO: Add TargetGrants?
			$bucketLoggingStatus->appendChild($loggingEnabled);
		}
		$dom->appendChild($bucketLoggingStatus);

		$this->LastRequest = $rest = new S3Request($this, 'PUT', $bucket);
		$rest->setParameter('logging', null);
		$rest->data = $dom->saveXML();
		$rest->size = strlen($rest->data);
		$rest->setHeader('Content-Type', 'application/xml');
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, {$targetBucket}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}



	/**
	* Get logging status for a bucket
	*
	* This will return false if logging is not enabled.
	* Note: To enable logging, you also need to grant write access to the log group
	*
	* @param string $bucket Bucket name
	* @return array | false
	*/
	public function getBucketLogging($bucket)
	{
		$this->LastRequest = $rest = new S3Request($this, 'GET', $bucket, '');
		$rest->setParameter('logging', null);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		if (!isset($rest->body->LoggingEnabled)) return false; // No logging
		return array(
			'targetBucket' => (string)$rest->body->LoggingEnabled->TargetBucket,
			'targetPrefix' => (string)$rest->body->LoggingEnabled->TargetPrefix,
		);
	}


	/**
	* Disable bucket logging
	*
	* @param string $bucket Bucket name
	* @return boolean
	*/
	public function disableBucketLogging($bucket)
	{
		return self::setBucketLogging($bucket, null);
	}


	/**
	* Get a bucket's location
	*
	* @param string $bucket Bucket name
	* @return string | false
	*/
	public function getBucketLocation($bucket)
	{
		$this->LastRequest = $rest = new S3Request($this, 'GET', $bucket, '');
		$rest->setParameter('location', null);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return (isset($rest->body[0]) && (string)$rest->body[0] !== '') ? (string)$rest->body[0] : 'US';
	}


	/**
	* Set object or bucket Access Control Policy
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param array $acp Access Control Policy Data (same as the data returned from getAccessControlPolicy)
	* @return boolean
	*/
	public function setAccessControlPolicy($bucket, $uri = '', $acp = array())
	{
		$dom = new DOMDocument;
		$dom->formatOutput = true;
		$accessControlPolicy = $dom->createElement('AccessControlPolicy');
		$accessControlList = $dom->createElement('AccessControlList');

		// It seems the owner has to be passed along too
		$owner = $dom->createElement('Owner');
		$owner->appendChild($dom->createElement('ID', $acp['owner']['id']));
		$owner->appendChild($dom->createElement('DisplayName', $acp['owner']['name']));
		$accessControlPolicy->appendChild($owner);

		foreach ($acp['acl'] as $g)
		{
			$grant = $dom->createElement('Grant');
			$grantee = $dom->createElement('Grantee');
			$grantee->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			if (isset($g['id']))
			{ // CanonicalUser (DisplayName is omitted)
				$grantee->setAttribute('xsi:type', 'CanonicalUser');
				$grantee->appendChild($dom->createElement('ID', $g['id']));
			}
			elseif (isset($g['email']))
			{ // AmazonCustomerByEmail
				$grantee->setAttribute('xsi:type', 'AmazonCustomerByEmail');
				$grantee->appendChild($dom->createElement('EmailAddress', $g['email']));
			}
			elseif ($g['type'] == 'Group')
			{ // Group
				$grantee->setAttribute('xsi:type', 'Group');
				$grantee->appendChild($dom->createElement('URI', $g['uri']));
			}
			$grant->appendChild($grantee);
			$grant->appendChild($dom->createElement('Permission', $g['permission']));
			$accessControlList->appendChild($grant);
		}

		$accessControlPolicy->appendChild($accessControlList);
		$dom->appendChild($accessControlPolicy);

		$this->LastRequest = $rest = new S3Request($this, 'PUT', $bucket, $uri);
		$rest->setParameter('acl', null);
		$rest->data = $dom->saveXML();
		$rest->size = strlen($rest->data);
		$rest->setHeader('Content-Type', 'application/xml');
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, {$uri}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}


	/**
	* Get object or bucket Access Control Policy
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return mixed | false
	*/
	public function getAccessControlPolicy($bucket, $uri = '')
	{
		$this->LastRequest = $rest = new S3Request($this, 'GET', $bucket, $uri);
		$rest->setParameter('acl', null);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, {$uri}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}

		$acp = array();
		if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
			$acp['owner'] = array(
				'id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->DisplayName
			);

		if (isset($rest->body->AccessControlList))
		{
			$acp['acl'] = array();
			foreach ($rest->body->AccessControlList->Grant as $grant)
			{
				foreach ($grant->Grantee as $grantee)
				{
					if (isset($grantee->ID, $grantee->DisplayName)) // CanonicalUser
						$acp['acl'][] = array(
							'type' => 'CanonicalUser',
							'id' => (string)$grantee->ID,
							'name' => (string)$grantee->DisplayName,
							'permission' => (string)$grant->Permission
						);
					elseif (isset($grantee->EmailAddress)) // AmazonCustomerByEmail
						$acp['acl'][] = array(
							'type' => 'AmazonCustomerByEmail',
							'email' => (string)$grantee->EmailAddress,
							'permission' => (string)$grant->Permission
						);
					elseif (isset($grantee->URI)) // Group
						$acp['acl'][] = array(
							'type' => 'Group',
							'uri' => (string)$grantee->URI,
							'permission' => (string)$grant->Permission
						);
					else continue;
				}
			}
		}
		return $acp;
	}


	/**
	* Delete an object
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return boolean
	*/
	public function deleteObject($bucket, $uri)
	{
		$this->LastRequest = $rest = new S3Request($this, 'DELETE', $bucket, $uri);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 204)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}

## ------------------------------------------------
## Tagging

/*
	TODO:
	x-amz-expected-bucket-owner: ExpectedBucketOwner
	x-amz-request-payer: RequestPayer
*/
##	https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObjectTagging.html
	public function getObjectTagging($bucket, $uri, $versionID=false)
	{
		$this->LastRequest = $rest = new S3Request($this, 'GET', $bucket, $uri);
		$rest->setParameter('tagging', null);
		if ($versionId)		$rest->setParameter('versionId', $versionId);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}

		$BucketTags = array();
		$TagSet = $rest->body->children()->TagSet;
		foreach($TagSet->children() as $TagData)
		{
			$TagElements = $TagData->children();
			$BucketTags[(string)$TagElements->Key] = (string)$TagElements->Value;
		}
		return $BucketTags;
	}		//	getObjectTagging

##	<Tagging><TagSet><Tag><Key>theanswer</Key><Value>42</Value></Tag></TagSet></Tagging>'




	/**
	* Set tagging for an object
	*
	* @param string $bucket Bucket name
	* @param string $targetBucket Target bucket (where logs are stored)
	* @param string $targetPrefixObject Log prefix (e,g; domain.com-)
	* @return boolean
	*/
##	https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObjectTagging.html
	public function setObjectTagging($bucket, $uri, $Tags, $versionID=false)
	{
		$dom = new DOMDocument;
		$tagging = $dom->createElement('Tagging');
		$tagging->setAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');
		$dom->appendChild($tagging);

		$tagset = $dom->createElement('TagSet');
		foreach($Tags as $tk => $tv)
		{
			$tag = $dom->createElement('Tag');

			$Key = $dom->createElement('Key');
			$Key->appendChild($dom->createTextNode($tk));
			$tag->appendChild($Key);

			$Value = $dom->createElement('Value');
			$Value->appendChild($dom->createTextNode($tv));
			$tag->appendChild($Value);

			$tagset->appendChild($tag);
		}
		$tagging->appendChild($tagset);

		$this->LastRequest = $rest = new S3Request($this, 'PUT', $bucket, $uri);
		$rest->setParameter('tagging', null);
		if ($versionId)		$rest->setParameter('versionId', $versionId);
		$rest->data = $dom->saveXML();
		$rest->size = strlen($rest->data);
		$rest->setHeader('Content-Type', 'application/xml');
#		$rest->setHeader('Content-MD5', md5($rest->data));
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, {$uri}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}



##	https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteObjectTagging.html
	/**
	* Delete object tagging
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param string $versionID Version to delete tagging from.
	* @return boolean
	*/
	public function deleteObjectTagging($bucket, $uri, $versionID=false)
	{
		$this->LastRequest = $rest = new S3Request($this, 'DELETE', $bucket, $uri);
		$rest->setParameter('tagging', null);
		if ($versionId)		$rest->setParameter('versionId', $versionId);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 204)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
							$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}


## ------------------------------------------------

	/**
	* Get a query string authenticated URL
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param integer $lifetime Lifetime in seconds
	* @param boolean $hostBucket Use the bucket name as the hostname
	* @param boolean $https Use HTTPS ($hostBucket should be false for SSL verification)
	* @return string
	*/
	public function getAuthenticatedURL($bucket, $uri, $lifetime, $hostBucket = false, $https = false, $headers = false)
	{
		$expires = self::__getTime() + $lifetime;
		$uri = str_replace(array('%2F', '%2B'), array('/', '+'), rawurlencode($uri));
		return sprintf(($https ? 'https' : 'http').'://%s/%s?AWSAccessKeyId=%s&Expires=%u&Signature=%s',
		// $hostBucket ? $bucket : $bucket.'.s3.amazonaws.com', $uri, $this->__accessKey, $expires,
			$hostBucket ? $bucket : $this->endpoint.'/'.$bucket, $uri, $this->__accessKey, $expires,
			urlencode(self::__getHash("GET\n\n\n{$expires}\n/{$bucket}/{$uri}")));
		
		$uri = str_replace(array('%2F', '%2B'), array('/', '+'), rawurlencode($uri)); // URI should be encoded (thanks Sean O'Dea)

		$finalUrl = sprintf(($https ? 'https' : 'http').'://%s/%s?',
		$hostBucket ? $bucket : $bucket.'.s3.amazonaws.com', $uri);
		$requestToSign = "GET\n\n\n{$expires}\n/{$bucket}/{$uri}";
		if (is_array($headers)) {
			ksort($headers); // AMZ servers reject signatures if headers are not in alphabetical order
			$appendString = '?';
			foreach ($headers as $header => $value) {
				$finalUrl .= $header . '=' . urlencode($value) . '&';
				$requestToSign .= $appendString . $header . '=' . $value;
				$appendString = '&';
			}
		}
		$finalUrl .= 'AWSAccessKeyId=' . self::$__accessKey . '&Expires=' . $expires . '&Signature=' . urlencode(self::__getHash($requestToSign));
		return $finalUrl;

	}


	/**
	* Get a CloudFront signed policy URL
	*
	* @param array $policy Policy
	* @return string
	*/
	public function getSignedPolicyURL($policy)
	{
		$data = json_encode($policy);
		$signature = '';
		if (!openssl_sign($data, $signature, $this->__signingKeyResource)) return false;

		$encoded = str_replace(array('+', '='), array('-', '_', '~'), base64_encode($data));
		$signature = str_replace(array('+', '='), array('-', '_', '~'), base64_encode($signature));

		$url = $policy['Statement'][0]['Resource'] . '?';
		foreach (array('Policy' => $encoded, 'Signature' => $signature, 'Key-Pair-Id' => $this->__signingKeyPairId) as $k => $v)
			$url .= $k.'='.str_replace('%2F', '/', rawurlencode($v)).'&';
		return substr($url, 0, -1);
	}


	/**
	* Get a CloudFront canned policy URL
	*
	* @param string $url URL to sign
	* @param integer $lifetime URL lifetime
	* @return string
	*/
	public function getSignedCannedURL($url, $lifetime)
	{
		return self::getSignedPolicyURL(array(
			'Statement' => array(
				array('Resource' => $url, 'Condition' => array(
					'DateLessThan' => array('AWS:EpochTime' => self::__getTime() + $lifetime)
				))
			)
		));
	}


	/**
	* Get upload POST parameters for form uploads
	*
	* @param string $bucket Bucket name
	* @param string $uriPrefix Object URI prefix
	* @param constant $acl ACL constant
	* @param integer $lifetime Lifetime in seconds
	* @param integer $maxFileSize Maximum filesize in bytes (default 5MB)
	* @param string $successRedirect Redirect URL or 200 / 201 status code
	* @param array $amzHeaders Array of x-amz-meta-* headers
	* @param array $headers Array of request headers or content type as a string
	* @param boolean $flashVars Includes additional "Filename" variable posted by Flash
	* @return object
	*/
	public function getHttpUploadPostParams($bucket, $uriPrefix = '', $acl = self::ACL_PRIVATE, $lifetime = 3600,
	$maxFileSize = 5242880, $successRedirect = "201", $amzHeaders = array(), $headers = array(), $flashVars = false)
	{
		// Create policy object
		$policy = new stdClass;
		$policy->expiration = gmdate('Y-m-d\TH:i:s\Z', (self::__getTime() + $lifetime));
		$policy->conditions = array();
		$obj = new stdClass; $obj->bucket = $bucket; array_push($policy->conditions, $obj);
		$obj = new stdClass; $obj->acl = $acl; array_push($policy->conditions, $obj);

		$obj = new stdClass; // 200 for non-redirect uploads
		if (is_numeric($successRedirect) && in_array((int)$successRedirect, array(200, 201)))
			$obj->success_action_status = (string)$successRedirect;
		else // URL
			$obj->success_action_redirect = $successRedirect;
		array_push($policy->conditions, $obj);

		if ($acl !== self::ACL_PUBLIC_READ)
			array_push($policy->conditions, array('eq', '$acl', $acl));

		array_push($policy->conditions, array('starts-with', '$key', $uriPrefix));
		if ($flashVars) array_push($policy->conditions, array('starts-with', '$Filename', ''));
		foreach (array_keys($headers) as $headerKey)
			array_push($policy->conditions, array('starts-with', '$'.$headerKey, ''));
		foreach ($amzHeaders as $headerKey => $headerVal)
		{
			$obj = new stdClass;
			$obj->{$headerKey} = (string)$headerVal;
			array_push($policy->conditions, $obj);
		}
		array_push($policy->conditions, array('content-length-range', 0, $maxFileSize));
		$policy = base64_encode(str_replace('\/', '/', json_encode($policy)));

		// Create parameters
		$params = new stdClass;
		$params->AWSAccessKeyId = $this->__accessKey;
		$params->key = $uriPrefix.'${filename}';
		$params->acl = $acl;
		$params->policy = $policy; unset($policy);
		$params->signature = self::__getHash($params->policy);
		if (is_numeric($successRedirect) && in_array((int)$successRedirect, array(200, 201)))
			$params->success_action_status = (string)$successRedirect;
		else
			$params->success_action_redirect = $successRedirect;
		foreach ($headers as $headerKey => $headerVal) $params->{$headerKey} = (string)$headerVal;
		foreach ($amzHeaders as $headerKey => $headerVal) $params->{$headerKey} = (string)$headerVal;
		return $params;
	}


	/**
	* Create a CloudFront distribution
	*
	* @param string $bucket Bucket name
	* @param boolean $enabled Enabled (true/false)
	* @param array $cnames Array containing CNAME aliases
	* @param string $comment Use the bucket name as the hostname
	* @param string $defaultRootObject Default root object
	* @param string $originAccessIdentity Origin access identity
	* @param array $trustedSigners Array of trusted signers
	* @return array | false
	*/
	public function createDistribution($bucket, $enabled = true, $cnames = array(), $comment = null, $defaultRootObject = null, $originAccessIdentity = null, $trustedSigners = array())
	{
		if (!extension_loaded('openssl'))
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, ".(int)$enabled.", [], '$comment'): %s",
			"CloudFront functionality requires SSL"), __FILE__, __LINE__);
			return false;
		}
		$useSSL = $this->useSSL;

		$this->useSSL = true; // CloudFront requires SSL
		$this->LastRequest = $rest = new S3Request($this, 'POST', '', '2010-11-01/distribution', self::DEFAULT_ENDPOINT_CF);
		$rest->data = self::__getCloudFrontDistributionConfigXML(
			$bucket.'.s3.amazonaws.com',
			$enabled,
			(string)$comment,
			(string)microtime(true),
			$cnames,
			$defaultRootObject,
			$originAccessIdentity,
			$trustedSigners
		);

		$rest->size = strlen($rest->data);
		$rest->setHeader('Content-Type', 'application/xml');
		$rest = self::__getCloudFrontResponse($rest);

		$this->useSSL = $useSSL;

		if ($rest->error === false && $rest->code !== 201)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$bucket}, ".(int)$enabled.", [], '$comment'): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		} elseif ($rest->body instanceof SimpleXMLElement)
			return self::__parseCloudFrontDistributionConfig($rest->body);
		return false;
	}


	/**
	* Get CloudFront distribution info
	*
	* @param string $distributionId Distribution ID from listDistributions()
	* @return array | false
	*/
	public function getDistribution($distributionId)
	{
		if (!extension_loaded('openssl'))
		{
			self::__triggerError(sprintf(__METHOD__."($distributionId): %s",
			"CloudFront functionality requires SSL"), __FILE__, __LINE__);
			return false;
		}
		$useSSL = $this->useSSL;

		$this->useSSL = true; // CloudFront requires SSL
		$this->LastRequest = $rest = new S3Request($this, 'GET', '', '2010-11-01/distribution/'.$distributionId, self::DEFAULT_ENDPOINT_CF);
		$rest = self::__getCloudFrontResponse($rest);

		$this->useSSL = $useSSL;

		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."($distributionId): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		elseif ($rest->body instanceof SimpleXMLElement)
		{
			$dist = self::__parseCloudFrontDistributionConfig($rest->body);
			$dist['hash'] = $rest->headers['hash'];
			$dist['id'] = $distributionId;
			return $dist;
		}
		return false;
	}


	/**
	* Update a CloudFront distribution
	*
	* @param array $dist Distribution array info identical to output of getDistribution()
	* @return array | false
	*/
	public function updateDistribution($dist)
	{
		if (!extension_loaded('openssl'))
		{
			self::__triggerError(sprintf(__METHOD__."({$dist['id']}): %s",
			"CloudFront functionality requires SSL"), __FILE__, __LINE__);
			return false;
		}

		$useSSL = $this->useSSL;

		$this->useSSL = true; // CloudFront requires SSL
		$this->LastRequest = $rest = new S3Request($this, 'PUT', '', '2010-11-01/distribution/'.$dist['id'].'/config', self::DEFAULT_ENDPOINT_CF);
		$rest->data = self::__getCloudFrontDistributionConfigXML(
			$dist['origin'],
			$dist['enabled'],
			$dist['comment'],
			$dist['callerReference'],
			$dist['cnames'],
			$dist['defaultRootObject'],
			$dist['originAccessIdentity'],
			$dist['trustedSigners']
		);

		$rest->size = strlen($rest->data);
		$rest->setHeader('If-Match', $dist['hash']);
		$rest = self::__getCloudFrontResponse($rest);

		$this->useSSL = $useSSL;

		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$dist['id']}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		} else {
			$dist = self::__parseCloudFrontDistributionConfig($rest->body);
			$dist['hash'] = $rest->headers['hash'];
			return $dist;
		}
		return false;
	}


	/**
	* Delete a CloudFront distribution
	*
	* @param array $dist Distribution array info identical to output of getDistribution()
	* @return boolean
	*/
	public function deleteDistribution($dist)
	{
		if (!extension_loaded('openssl'))
		{
			self::__triggerError(sprintf(__METHOD__."({$dist['id']}): %s",
			"CloudFront functionality requires SSL"), __FILE__, __LINE__);
			return false;
		}

		$useSSL = $this->useSSL;

		$this->useSSL = true; // CloudFront requires SSL
		$this->LastRequest = $rest = new S3Request($this, 'DELETE', '', '2008-06-30/distribution/'.$dist['id'], self::DEFAULT_ENDPOINT_CF);
		$rest->setHeader('If-Match', $dist['hash']);
		$rest = self::__getCloudFrontResponse($rest);

		$this->useSSL = $useSSL;

		if ($rest->error === false && $rest->code !== 204)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."({$dist['id']}): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		return true;
	}


	/**
	* Get a list of CloudFront distributions
	*
	* @return array
	*/
	public function listDistributions()
	{
		if (!extension_loaded('openssl'))
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
			"CloudFront functionality requires SSL"), __FILE__, __LINE__);
			return false;
		}

		$useSSL = $this->useSSL;
		$this->useSSL = true; // CloudFront requires SSL
		$this->LastRequest = $rest = new S3Request($this, 'GET', '', '2010-11-01/distribution', self::DEFAULT_ENDPOINT_CF);
		$rest = self::__getCloudFrontResponse($rest);
		$this->useSSL = $useSSL;

		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
					$rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
			return false;
		}
		elseif ($rest->body instanceof SimpleXMLElement && isset($rest->body->DistributionSummary))
		{
			$list = array();
			if (isset($rest->body->Marker, $rest->body->MaxItems, $rest->body->IsTruncated))
			{
				//$info['marker'] = (string)$rest->body->Marker;
				//$info['maxItems'] = (int)$rest->body->MaxItems;
				//$info['isTruncated'] = (string)$rest->body->IsTruncated == 'true' ? true : false;
			}
			foreach ($rest->body->DistributionSummary as $summary)
				$list[(string)$summary->Id] = self::__parseCloudFrontDistributionConfig($summary);

			return $list;
		}
		return array();
	}

	/**
	* List CloudFront Origin Access Identities
	*
	* @return array
	*/
	public function listOriginAccessIdentities()
	{
		if (!extension_loaded('openssl'))
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
			"CloudFront functionality requires SSL"), __FILE__, __LINE__);
			return false;
		}

		$this->useSSL = true; // CloudFront requires SSL
		$this->LastRequest = $rest = new S3Request($this, 'GET', '', '2010-11-01/origin-access-identity/cloudfront', self::DEFAULT_ENDPOINT_CF);
		$rest = self::__getCloudFrontResponse($rest);
		$useSSL = $this->useSSL;

		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
					$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}

		if (isset($rest->body->CloudFrontOriginAccessIdentitySummary))
		{
			$identities = array();
			foreach ($rest->body->CloudFrontOriginAccessIdentitySummary as $identity)
				if (isset($identity->S3CanonicalUserId))
					$identities[(string)$identity->Id] = array('id' => (string)$identity->Id, 's3CanonicalUserId' => (string)$identity->S3CanonicalUserId);
			return $identities;
		}
		return false;
	}


	/**
	* Invalidate objects in a CloudFront distribution
	*
	* Thanks to Martin Lindkvist for S3::invalidateDistribution()
	*
	* @param string $distributionId Distribution ID from listDistributions()
	* @param array $paths Array of object paths to invalidate
	* @return boolean
	*/
	public function invalidateDistribution($distributionId, $paths)
	{
		if (!extension_loaded('openssl'))
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
			"CloudFront functionality requires SSL"), __FILE__, __LINE__);
			return false;
		}

		$useSSL = $this->useSSL;
		$this->useSSL = true; // CloudFront requires SSL
		$this->LastRequest = $rest = new S3Request($this, 'POST', '', '2010-08-01/distribution/'.$distributionId.'/invalidation', self::DEFAULT_ENDPOINT_CF);
		$rest->data = self::__getCloudFrontInvalidationBatchXML($paths, (string)microtime(true));
		$rest->size = strlen($rest->data);
		$rest = self::__getCloudFrontResponse($rest);
		$this->useSSL = $useSSL;

		if ($rest->error === false && $rest->code !== 201)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."('{$distributionId}',{$paths}): [%s] %s",
					$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		return true;
	}


	/**
	* Get a InvalidationBatch DOMDocument
	*
	* @internal Used to create XML in invalidateDistribution()
	* @param array $paths Paths to objects to invalidateDistribution
	* @param int $callerReference
	* @return string
	*/
	private function __getCloudFrontInvalidationBatchXML($paths, $callerReference = '0')
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$invalidationBatch = $dom->createElement('InvalidationBatch');
		foreach ($paths as $path)
			$invalidationBatch->appendChild($dom->createElement('Path', $path));

		$invalidationBatch->appendChild($dom->createElement('CallerReference', $callerReference));
		$dom->appendChild($invalidationBatch);
		return $dom->saveXML();
	}


	/**
	* List your invalidation batches for invalidateDistribution() in a CloudFront distribution
	*
	* http://docs.amazonwebservices.com/AmazonCloudFront/latest/APIReference/ListInvalidation.html
	* returned array looks like this:
	*	Array
	*	(
	*		[I31TWB0CN9V6XD] => InProgress
	*		[IT3TFE31M0IHZ] => Completed
	*		[I12HK7MPO1UQDA] => Completed
	*		[I1IA7R6JKTC3L2] => Completed
	*	)
	*
	* @param string $distributionId Distribution ID from listDistributions()
	* @return array
	*/
	public function getDistributionInvalidationList($distributionId)
	{
		if (!extension_loaded('openssl'))
		{
			self::__triggerError(sprintf(__METHOD__."(): [%s] %s",
			"CloudFront functionality requires SSL"), __FILE__, __LINE__);
			return false;
		}

		$useSSL = $this->useSSL;
		$this->useSSL = true; // CloudFront requires SSL
		$this->LastRequest = $rest = new S3Request($this, 'GET', '', '2010-11-01/distribution/'.$distributionId.'/invalidation', self::DEFAULT_ENDPOINT_CF);
		$rest = self::__getCloudFrontResponse($rest);
		$this->useSSL = $useSSL;

		if ($rest->error === false && $rest->code !== 200)
					$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false)
		{
			self::__triggerError(sprintf(__METHOD__."('{$distributionId}'): [%s]",
					$rest->error['code'], $rest->error['message']), E_USER_WARNING);
			return false;
		}
		elseif ($rest->body instanceof SimpleXMLElement && isset($rest->body->InvalidationSummary))
		{
			$list = array();
			foreach ($rest->body->InvalidationSummary as $summary)
				$list[(string)$summary->Id] = (string)$summary->Status;

			return $list;
		}
		return array();
	}


	/**
	* Get a DistributionConfig DOMDocument
	*
	* http://docs.amazonwebservices.com/AmazonCloudFront/latest/APIReference/index.html?PutConfig.html
	*
	* @internal Used to create XML in createDistribution() and updateDistribution()
	* @param string $bucket S3 Origin bucket
	* @param boolean $enabled Enabled (true/false)
	* @param string $comment Comment to append
	* @param string $callerReference Caller reference
	* @param array $cnames Array of CNAME aliases
	* @param string $defaultRootObject Default root object
	* @param string $originAccessIdentity Origin access identity
	* @param array $trustedSigners Array of trusted signers
	* @return string
	*/
	private function __getCloudFrontDistributionConfigXML($bucket, $enabled, $comment, $callerReference = '0', $cnames = array(), $defaultRootObject = null, $originAccessIdentity = null, $trustedSigners = array())
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$distributionConfig = $dom->createElement('DistributionConfig');
		$distributionConfig->setAttribute('xmlns', 'http://cloudfront.amazonaws.com/doc/2010-11-01/');

		$origin = $dom->createElement('S3Origin');
		$origin->appendChild($dom->createElement('DNSName', $bucket));
		if ($originAccessIdentity !== null) $origin->appendChild($dom->createElement('OriginAccessIdentity', $originAccessIdentity));
		$distributionConfig->appendChild($origin);

		if ($defaultRootObject !== null) $distributionConfig->appendChild($dom->createElement('DefaultRootObject', $defaultRootObject));

		$distributionConfig->appendChild($dom->createElement('CallerReference', $callerReference));
		foreach ($cnames as $cname)
			$distributionConfig->appendChild($dom->createElement('CNAME', $cname));
		if ($comment !== '') $distributionConfig->appendChild($dom->createElement('Comment', $comment));
		$distributionConfig->appendChild($dom->createElement('Enabled', $enabled ? 'true' : 'false'));

		if (!empty($trustedSigners))
		{
			$trusted = $dom->createElement('TrustedSigners');
			foreach ($trustedSigners as $id => $type)
				$trusted->appendChild($id !== '' ? $dom->createElement($type, $id) : $dom->createElement($type));
			$distributionConfig->appendChild($trusted);
		}
		$dom->appendChild($distributionConfig);
		//var_dump($dom->saveXML());
		return $dom->saveXML();
	}


	/**
	* Parse a CloudFront distribution config
	*
	* See http://docs.amazonwebservices.com/AmazonCloudFront/latest/APIReference/index.html?GetDistribution.html
	*
	* @internal Used to parse the CloudFront DistributionConfig node to an array
	* @param object &$node DOMNode
	* @return array
	*/
	private function __parseCloudFrontDistributionConfig(&$node)
	{
		if (isset($node->DistributionConfig))
			return self::__parseCloudFrontDistributionConfig($node->DistributionConfig);

		$dist = array();
		if (isset($node->Id, $node->Status, $node->LastModifiedTime, $node->DomainName))
		{
			$dist['id'] = (string)$node->Id;
			$dist['status'] = (string)$node->Status;
			$dist['time'] = strtotime((string)$node->LastModifiedTime);
			$dist['domain'] = (string)$node->DomainName;
		}

		if (isset($node->CallerReference))
			$dist['callerReference'] = (string)$node->CallerReference;

		if (isset($node->Enabled))
			$dist['enabled'] = (string)$node->Enabled == 'true' ? true : false;

		if (isset($node->S3Origin))
		{
			if (isset($node->S3Origin->DNSName))
				$dist['origin'] = (string)$node->S3Origin->DNSName;

			$dist['originAccessIdentity'] = isset($node->S3Origin->OriginAccessIdentity) ?
			(string)$node->S3Origin->OriginAccessIdentity : null;
		}

		$dist['defaultRootObject'] = isset($node->DefaultRootObject) ? (string)$node->DefaultRootObject : null;

		$dist['cnames'] = array();
		if (isset($node->CNAME))
			foreach ($node->CNAME as $cname)
				$dist['cnames'][(string)$cname] = (string)$cname;

		$dist['trustedSigners'] = array();
		if (isset($node->TrustedSigners))
			foreach ($node->TrustedSigners as $signer)
			{
				if (isset($signer->Self))
					$dist['trustedSigners'][''] = 'Self';
				elseif (isset($signer->KeyPairId))
					$dist['trustedSigners'][(string)$signer->KeyPairId] = 'KeyPairId';
				elseif (isset($signer->AwsAccountNumber))
					$dist['trustedSigners'][(string)$signer->AwsAccountNumber] = 'AwsAccountNumber';
			}

		$dist['comment'] = isset($node->Comment) ? (string)$node->Comment : null;
		return $dist;
	}


	/**
	* Grab CloudFront response
	*
	* @internal Used to parse the CloudFront S3Request::getResponse() output
	* @param object &$rest S3Request instance
	* @return object
	*/
	private function __getCloudFrontResponse(&$rest)
	{
		$rest->getResponse();
		if ($rest->response->error === false && isset($rest->response->body) &&
		is_string($rest->response->body) && substr($rest->response->body, 0, 5) == '<?xml')
		{
			$rest->response->body = simplexml_load_string($rest->response->body);
			// Grab CloudFront errors
			if (isset($rest->response->body->Error, $rest->response->body->Error->Code,
			$rest->response->body->Error->Message))
			{
				$rest->response->error = array(
					'code' => (string)$rest->response->body->Error->Code,
					'message' => (string)$rest->response->body->Error->Message
				);
				unset($rest->response->body);
			}
		}
		return $rest->response;
	}


	/**
	* Get MIME type for file
	*
	* To override the putObject() Content-Type, add it to $requestHeaders
	*
	* To use fileinfo, ensure the MAGIC environment variable is set
	*
	* @internal Used to get mime types
	* @param string &$file File path
	* @return string
	*/
	private function __getMIMEType(&$file)
	{
		static $exts = array(
			'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
			'png' => 'image/png', 'ico' => 'image/x-icon', 'pdf' => 'application/pdf',
			'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml', 'swf' => 'application/x-shockwave-flash', 
			'zip' => 'application/zip', 'gz' => 'application/x-gzip',
			'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
			'bz2' => 'application/x-bzip2',  'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload', 'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed', 'txt' => 'text/plain',
			'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
			'css' => 'text/css', 'js' => 'text/javascript',
			'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
			'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
			'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
			'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
		);

		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if (isset($exts[$ext])) return $exts[$ext];

		// Use fileinfo if available
		if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
		($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false)
		{
			if (($type = finfo_file($finfo, $file)) !== false)
			{
				// Remove the charset and grab the last content-type
				$type = explode(' ', str_replace('; charset=', ';charset=', $type));
				$type = array_pop($type);
				$type = explode(';', $type);
				$type = trim(array_shift($type));
			}
			finfo_close($finfo);
			if ($type !== false && strlen($type) > 0) return $type;
		}

		return 'application/octet-stream';
	}


	/**
	* Get the current time
	*
	* @internal Used to apply offsets to sytem time
	* @return integer
	*/
	public function __getTime()
	{
		return time() + $this->__timeOffset;
	}


	/**
	* Generate the auth string: "AWS AccessKey:Signature"
	*
	* @internal Used by S3Request::getResponse()
	* @param string $string String to sign
	* @return string
	*/
	public function __getSignature($string)
	{
		return 'AWS '. $this->__accessKey .':'. self::__getHash($string);
	}


	/**
	* Creates a HMAC-SHA1 hash
	*
	* This uses the hash extension if loaded
	*
	* @internal Used by __getSignature()
	* @param string $string String to sign
	* @return string
	*/
	private function __getHash($string)
	{
		if (extension_loaded('hash'))
		{
			$hash = hash_hmac('sha1', $string, $this->__secretKey, true);
		}
		else
		{		// this needs explanation.
			$hash = pack('H*', sha1(
										(str_pad($this->__secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64)))
										. pack('H*', sha1((str_pad($this->__secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $string))
					  				)
					 			);
		}
		return base64_encode($hash);
	}


	/**
	* Generate the headers for AWS Signature V4
	* 
	* @internal Used by S3Request::getResponse()
	* @param array $amzHeaders
	* @param array $headers
	* @param string $method
	* @param string $uri
	* @param array $parameters
	* @return array
	*/
	public function __getSignatureV4($amzHeaders, $headers, $method, $uri, $parameters)
	{
		$service = 's3';
		$region = $this->getRegion();

		$algorithm = 'AWS4-HMAC-SHA256';
		$combinedHeaders = array();

		$amzDateStamp = substr($amzHeaders['x-amz-date'], 0, 8);

		// CanonicalHeaders
		foreach ($headers as $k => $v)
				if (!empty($v))		$combinedHeaders[strtolower($k)] = trim($v);
		foreach ($amzHeaders as $k => $v) 
				$combinedHeaders[strtolower($k)] = trim($v);
		uksort($combinedHeaders, array('self', '__sortMetaHeadersCmp'));

		// Convert null query string parameters to strings and sort
		$parameters = array_map('strval', $parameters); 
		uksort($parameters, array('self', '__sortMetaHeadersCmp'));
		$queryString = http_build_query($parameters, null, '&', PHP_QUERY_RFC3986);

		// Payload
		$amzPayload = array($method);

		$qsPos = strpos($uri, '?');
		$amzPayload[] = ($qsPos === false ? $uri : substr($uri, 0, $qsPos));

		$amzPayload[] = $queryString;
		// add header as string to requests
		foreach ($combinedHeaders as $k => $v ) 
		{
			$amzPayload[] = $k . ':' . $v;
		}
		// add a blank entry so we end up with an extra line break
		$amzPayload[] = '';
		// SignedHeaders
		$amzPayload[] = implode(';', array_keys($combinedHeaders));
		// payload hash
		$amzPayload[] = $amzHeaders['x-amz-content-sha256'];
		// request as string
		$amzPayloadStr = implode("\n", $amzPayload);

		// CredentialScope
		$credentialScope = array($amzDateStamp, $region, $service, 'aws4_request');

		// stringToSign
		$stringToSignStr = implode("\n", array($algorithm, $amzHeaders['x-amz-date'], 
		implode('/', $credentialScope), hash('sha256', $amzPayloadStr)));

		// Make Signature
		$kSecret = 'AWS4'. $this->__secretKey;
		$kDate = hash_hmac('sha256', $amzDateStamp, $kSecret, true);
		$kRegion = hash_hmac('sha256', $region, $kDate, true);
		$kService = hash_hmac('sha256', $service, $kRegion, true);
		$kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

		$signature = hash_hmac('sha256', $stringToSignStr, $kSigning);

		return $algorithm .' '. implode(',', array(
			'Credential='. $this->__accessKey .'/'. implode('/', $credentialScope),
			'SignedHeaders='. implode(';', array_keys($combinedHeaders)),
			'Signature='. $signature,
		));
	}


	/**
	* Sort compare for meta headers
	*
	* @internal Used to sort x-amz meta headers
	* @param string $a String A
	* @param string $b String B
	* @return integer
	*/
	private function __sortMetaHeadersCmp($a, $b)
	{
		$lenA = strlen($a);
		$lenB = strlen($b);
		$minLen = min($lenA, $lenB);
		$ncmp = strncmp($a, $b, $minLen);
		if ($lenA == $lenB) return $ncmp;
		if (0 == $ncmp) return $lenA < $lenB ? -1 : 1;
		return $ncmp;
	}

}		//	S3



/**
 * S3 Request class 
 *
 * @link http://undesigned.org.za/2007/10/22/amazon-s3-php-class
 * @version 0.5.0-dev
 */
final class S3Request
{

	/**
	 * S3
	 *
	 * @var S3 Instance
	 * @access private
	 * the target S3 system this request will be sent to.
	 */

	private $S3;

	
	/**
	 * Verb
	 *
	 * @var string
	 * @access private
	 */
	private $verb;
	
	/**
	 * S3 bucket name
	 *
	 * @var string
	 * @access private
	 */
	private $bucket;
	
	/**
	 * Object URI
	 *
	 * @var string
	 * @access private
	 */
	private $uri;
	
	/**
	 * Final object URI (with parameters)
	 *
	 * @var string
	 * @access private
	 */
	private $resource = '';
	

	/**
	 * Final object FULL URL
	 *
	 * @var string
	 * @access public
	 */
	public $URL = '';


	/**
	 * Additional request parameters
	 *
	 * @var array
	 * @access private
	 */
	private $parameters = array();
	
	/**
	 * Amazon specific request headers
	 *
	 * @var array
	 * @access private
	 */
	private $amzHeaders = array();

	/**
	 * HTTP request headers
	 *
	 * @var array
	 * @access private
	 */
	private $headers = array(
		'Host' => '', 'Date' => '', 
#		'Host' => '', 'Date' => '', 'Content-MD5' => '', 'Content-Type' => ''
	);


	public $sentHeaders = array();

	/**
	 * Use HTTP PUT?
	 *
	 * @var bool
	 * @access public
	 */
	public $fp = false;

	/**
	 * PUT file size
	 *
	 * @var int
	 * @access public
	 */
	public $size = 0;

	/**
	 * PUT post fields
	 *
	 * @var array
	 * @access public
	 */
	public $data = false;

	protected $bodyLen = 0;
	/**
	 * S3 request respone
	 *
	 * @var object
	 * @access public
	 */
	public $response;


## used by __responseHeaderCallback for multi-line headers
	private $lastRcvdHdr = false;


	/**
	* Constructor
	*
	* @param string $verb Verb
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param string $endpoint AWS endpoint URI
	* @return mixed
	*/

	function __construct($S3, $verb, $bucket='', $uri='')
	{
		$this->S3 = $S3;
		$this->verb = $verb;
		$this->bucket = $bucket;
		$this->uri = $uri !== '' ? '/'.str_replace('%2F', '/', rawurlencode($uri)) : '/';

		$this->headers['Host'] = $this->S3->endpoint;
		if (!empty($this->bucket))
		{
			if ( $this->S3->getHostBuckets() && $this->__dnsBucketName($this->bucket) )
			{
				$this->headers['Host'] = $this->bucket .'.'. $this->S3->endpoint;
				$this->resource = $this->uri;
			}
			else
			{
				$this->resource = '/'. $this->bucket . $this->uri;
			}
		}

		$this->headers['Date'] = gmdate('D, d M Y H:i:s T');
		$this->response = new STDClass;
		$this->response->error = false;
		$this->response->rawbody = null;
		$this->response->body = null;
		$this->response->headers = array();
	}


	/**
	* Set request parameter
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setParameter($key, $value)
	{
		$this->parameters[$key] = $value;
	}


	/**
	* Set request header
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setHeader($key, $value)
	{
		$this->headers[$key] = $value;
	}


	/**
	* Set x-amz-meta-* header
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setAmzHeader($key, $value)
	{
		$this->amzHeaders[$key] = $value;
	}


	/**
	* Get the S3 response
	*
	* @return object | false
	*/
	public function getResponse()
	{
		## construct the final resource URI from our parameters
		
		if (!empty($this->parameters))
		{
## if uri already ends in '?', append &, else ?
			$this->resource .= ('?' == substr($this->resource, -1)) ? '&' : '?';

			$QParams = array();
			foreach ($this->parameters as $var => $value)
			{
				$QParams[] = $var . (empty($value)	?	''	:	'='. rawurlencode($value) );
			}
## add the specified paramters to the URI
			$this->resource .= implode('&', $QParams);

### WHAT IS THIS?
#			if (array_key_exists('acl', $this->parameters) ||
#			array_key_exists('location', $this->parameters) ||
#			array_key_exists('torrent', $this->parameters) ||
#			array_key_exists('website', $this->parameters) ||
#			array_key_exists('logging', $this->parameters))
#			{
#				$this->resource .= $query;
#			}
		}
		$this->URL = ($this->S3->useSSL ? 'https://' : 'http://') . $this->headers['Host'] . $this->resource;
#var_export($this->URL);


		// Basic setup
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_USERAGENT, 'S3/php');

		if ($this->S3->useSSL)
		{
			// Set protocol version
			if ($this->S3->useSSLVersion !== null)
			{
				curl_setopt($curl, CURLOPT_SSLVERSION, $this->S3->useSSLVersion);
			}

			// SSL Validation can now be optional for those with broken OpenSSL installations
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->S3->useSSLValidation ? 2 : 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->S3->useSSLValidation ? 1 : 0);

			if ($this->S3->sslKey !== null) 			curl_setopt($curl, CURLOPT_SSLKEY, $this->S3->sslKey);
			if ($this->S3->sslCert !== null) 			curl_setopt($curl, CURLOPT_SSLCERT, $this->S3->sslCert);
			if ($this->S3->sslCACert !== null) 		curl_setopt($curl, CURLOPT_CAINFO, $this->S3->sslCACert);
		}

		curl_setopt($curl, CURLOPT_URL, $this->URL);

		if ($this->S3->proxy != null && isset($this->S3->proxy['host']))
		{
			curl_setopt($curl, CURLOPT_PROXY, $this->S3->proxy['host']);
			curl_setopt($curl, CURLOPT_PROXYTYPE, $this->S3->proxy['type']);
			if (isset($this->S3->proxy['user'], $this->S3->proxy['pass']) && $this->S3->proxy['user'] != null && $this->S3->proxy['pass'] != null)
			{
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, sprintf('%s:%s', $this->S3->proxy['user'], $this->S3->proxy['pass']));
			}
		}

		// Headers
		$httpHeaders = array(); 
		if ($this->S3->hasAuth())
		{
			// Authorization string (CloudFront stringToSign should only contain a date)
			if ($this->headers['Host'] == S3::DEFAULT_ENDPOINT_CF)
			{
				# TODO: Update CloudFront authentication
				foreach ($this->amzHeaders as $header => $value)
						if (strlen($value) > 0)			$httpHeaders[] = $header.': '.$value;

				foreach ($this->headers as $header => $value)
						if (strlen($value) > 0)			$httpHeaders[] = $header.': '.$value;

				$httpHeaders[] = 'Authorization: '. $this->S3->__getSignature($this->headers['Date']);
			}
			else
			{
				$this->amzHeaders['x-amz-date'] = gmdate('Ymd\THis\Z');

				if (!isset($this->amzHeaders['x-amz-content-sha256'])) 
					$this->amzHeaders['x-amz-content-sha256'] = hash('sha256', $this->data);

				foreach ($this->amzHeaders as $header => $value)
					if (strlen($value) > 0) $httpHeaders[] = $header.': '.$value;

				foreach ($this->headers as $header => $value)
					if (strlen($value) > 0) $httpHeaders[] = $header.': '.$value;

				$httpHeaders[] = 'Authorization: '. $this->S3->__getSignatureV4(
					$this->amzHeaders,
					$this->headers, 
					$this->verb, 
					$this->resource,
					$this->parameters
				);

			}
		}

		curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeaders);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array($this, '__responseWriteCallback'));
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, '__responseHeaderCallback'));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, $this->S3->curlTimeout);

		// Request types
		switch ($this->verb)
		{
			case 'GET':
				break;
			case 'PUT':
			case 'POST': // POST only used for CloudFront
				if ($this->fp !== false)
				{
					curl_setopt($curl, CURLOPT_PUT, true);
					curl_setopt($curl, CURLOPT_INFILE, $this->fp);
					if ($this->size >= 0)			curl_setopt($curl, CURLOPT_INFILESIZE, $this->size);
				}
				elseif ($this->data !== false)
				{
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
				}
				else
				{
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
				}
				break;
			case 'HEAD':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($curl, CURLOPT_NOBODY, true);
				break;
			case 'DELETE':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			default: break;
		}

		// set curl progress function callback
		if ($this->S3->progressFunction) {
			curl_setopt($curl, CURLOPT_NOPROGRESS, false);
			curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, $this->S3->progressFunction);
		}

		// Execute, grab errors
		$execResult = curl_exec($curl);
		$this->sentHeaders = explode("\n", trim(curl_getinfo($curl,CURLINFO_HEADER_OUT)));

		if ($execResult)
		{
			$this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE)+0;
		}
		else
			$this->response->error = array(
				'code' => curl_errno($curl),
				'message' => curl_error($curl),
				'resource' => $this->resource, 
			);

		@curl_close($curl);

		// Clean up file resources
		if ($this->fp !== false && is_resource($this->fp))		 fclose($this->fp);

#error_log($this->URL ."\n". $this->response->rawbody ."\n");
#		$this->rawrespbody &= $this->response->rawbody;

		// if the body is non-empty XML, then Parse it.
		if (false === $this->response->error)
		{
			if ($this->__responseIsXML() )
			{
				$BodyXML = simplexml_load_string($this->response->rawbody);

				// Grab S3 errors
				if (!in_array($this->response->code, array(200, 204, 206)) &&
						isset($BodyXML->Code, $BodyXML->Message))
				{		// this is an S3 error.
					$this->response->error = array(
						'code' => (string)$BodyXML->Code,
						'message' => (string)$BodyXML->Message
					);
					if (isset($BodyXML->Resource))		$this->response->error['resource'] = (string)$BodyXML->Resource;
				}
				else
				{		// this is an XML response that is NOT an S3 error
					$this->response->body = $BodyXML;
				}
			}
			else			$this->response->body =& $this->response->rawbody;		// this is NOT XML data.
		}
		return $this->response;
	}


	/**
	* CURL write callback
	*
	* @param resource &$curl CURL resource
	* @param string &$data Data
	* @return integer
	*/
	private function __responseWriteCallback(&$curl, &$data)
	{
		$chunkLen = strlen($data);
		$this->bodyLen += $chunkLen;
		
		if (in_array($this->response->code, array(200, 206)) && $this->fp !== false)
				return fwrite($this->fp, $data);

		$this->response->rawbody .= $data;

		return $chunkLen;
	}



	/**
	* CURL header callback
	*
	* @param resource $curl CURL resource
	* @param string $data Data
	* @return integer
	*/
	private function __responseHeaderCallback($curl, $data)
	{
		if (($strlen = strlen($data)) <= 2) return $strlen;
		if (substr($data, 0, 4) == 'HTTP')
			$this->response->code = (int)substr($data, 9, 3);
		else
		{
			if (' ' == substr($data, 0, 1))		// continuation	of previous header
			{
				if (!$this->lastRcvdHdr)
				{
					$this->response->headers[$this->lastRcvdHdr] .= $data;
					return $strlen;
				}
			}
			$data = trim($data);
			if (strpos($data, ': ') === false)		return $strlen;
			list($header, $value) = explode(': ', $data, 2);
			$this->lastRcvdHdr = $headerTest = strtolower($header);
			if ($headerTest == 'last-modified')
				$this->response->headers['time'] = strtotime($value);
			elseif ($headerTest == 'date')
				$this->response->headers['date'] = strtotime($value);
			elseif ($headerTest == 'content-length')
				$this->response->headers['size'] = (int)$value;
			elseif ($headerTest == 'content-type')
				$this->response->headers['type'] = $value;
			elseif ($headerTest == 'etag')
				$this->response->headers['hash'] = $value[0] == '"' ? substr($value, 1, -1) : $value;
			elseif (preg_match('/^x-amz-meta-.*$/', $headerTest))
				$this->response->headers[$headerTest] = $value;
			else $this->response->headers[$headerTest] = $value;
		}
		return $strlen;
	}

	private function __responseIsXML()
	{
		if (!$this->bodyLen)		return false;
		if (!array_key_exists('type', $this->response->headers))		return false;
		return ('application/xml' == $this->response->headers['type']);
	}


## ----------------------------------------------------------------

	/**
	* Check DNS conformity
	*
	* @param string $bucket Bucket name
	* @return boolean
	*/

	private static $lastDNSBucket = false;
	private static $lastDNSBucketOK = false;
	private function __dnsBucketName($bucket)
	{
		if ($bucket == self::$lastDNSBucket)		return self::$lastDNSBucketOK;
		
		self::$lastDNSBucket = $bucket;
		self::$lastDNSBucketOK = false;
		if (strlen($bucket) > 63 || preg_match("/[^a-z0-9\.-]/", $bucket) > 0)		return self::$lastDNSBucketOK;
		if ($this->S3->useSSL && strstr($bucket, '.') !== false)		return self::$lastDNSBucketOK;
		if (strstr($bucket, '-.') !== false)			return self::$lastDNSBucketOK;
		if (strstr($bucket, '..') !== false)			return self::$lastDNSBucketOK;
		if (!preg_match("/^[0-9a-z]/", $bucket))		return self::$lastDNSBucketOK;
		if (!preg_match("/[0-9a-z]$/", $bucket))		return self::$lastDNSBucketOK;
		return (self::$lastDNSBucketOK = true);
	}



}		//	S3Request

/**
 * S3 exception class
 *
 * @link http://undesigned.org.za/2007/10/22/amazon-s3-php-class
 * @version 0.5.0-dev
 */

class S3Exception extends Exception
{
	/**
	 * Class constructor
	 *
	 * @param string $message Exception message
	 * @param string $file File in which exception was created
	 * @param string $line Line number on which exception was created
	 * @param int $code Exception code
	 */

	

	function __construct($message, $file, $line, $code = 0, $LastS3Request)
	{
		parent::__construct($message, $code);
		$this->file = $file;
		$this->line = $line;
		$this->LastReqeust = $LastS3Request;
	}
}		//	S3Exception

