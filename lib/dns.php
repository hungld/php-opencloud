<?php
/**
 * The Rackspace Cloud DNS service
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud;

require_once(__DIR__.'/asyncresponse.php');
require_once(__DIR__.'/domain.php');
require_once(__DIR__.'/record.php');
require_once(__DIR__.'/service.php');

class DNS extends Service {

	/**
	 * creates a new DNS object
	 *
	 * @param \OpenCloud\OpenStack $conn connection object
	 * @param string $serviceName the name of the service
	 * @param string $serviceRegion (not currently used; DNS is regionless)
	 * @param string $urltype the type of URL
	 */
	public function __construct(OpenStack $conn,
	        $serviceName, $serviceRegion, $urltype) {
		$this->debug(_('initializing DNS...'));
		parent::__construct(
			$conn,
			'rax:dns',
			$serviceName,
			$serviceRegion,
			$urltype
		);
	} // function __construct()

	/**
	 * Returns the selected endpoint URL of this Service
	 *
	 * @param string $resource - a child resource. For example,
	 *      passing 'servers' would return .../servers. Should *not* be
	 *    prefixed with a slash (/).
	 * @param array $args (optional) an array of key-value pairs for query
	 *      strings to append to the URL
	 * @returns string - the requested URL
	 */
	public function Url($resource='', $args=array()) {
	    $baseurl = parent::Url();
	    if ($resource != '')
	        $baseurl = noslash($baseurl).'/'.$resource;
	    if (!empty($args))
	        $baseurl .= '?'.$this->MakeQueryString($args);
		return $baseurl;
	}
	
	/**
	 * returns a DNS::Domain object
	 * 
	 * @api
	 * @param mixed $info either the ID, an object, or array of parameters
	 * @return DNS\Domain
	 */
	public function Domain($info=NULL) {
		return new DNS\Domain($this, $info);
	}

	/**
	 * returns a Collection of DNS::Domain objects
	 *
	 * @api
	 * @param array $filter key/value pairs to use as query strings
	 * @return \OpenCloud\Collection
	 */
	public function DomainList($filter=array()) {
		$url = $this->Url(DNS\Domain::ResourceName(), $filter);
		return $this->Collection('\OpenCloud\DNS\Domain', $url);
	}
	
	/**
	 * performs a HTTP request
	 *
	 * This method overrides the request with JSON content type
	 *
	 * @param string $url the URL to target
	 * @param string $method the HTTP method to use
	 * @param array $headers key/value pairs for headers to include
	 * @param string $body the body of the request (for PUT and POST)
	 * @return \OpenCloud\HttpResponse
	 */
	public function Request($url,$method='GET',$headers=array(),$body=NULL) {
		$headers['Accept'] = 'application/json';
		$headers['Content-Type'] = 'application/json';
		return parent::Request($url, $method, $headers, $body);
	}
	
	/**
	 * retrieves an asynchronous response
	 *
	 * This method calls the provided `$url` and expects an asynchronous
	 * response. It checks for various HTTP error codes and returns
	 * an `AsyncResponse` object. This object can then be used to poll
	 * for the status or to retrieve the final data as needed. 
	 *
	 * @param string $url the URL of the request
	 * @param string $method the HTTP method to use
	 * @param array $headers key/value pairs for headers to include
	 * @param string $body the body of the request (for PUT and POST)
	 * @return DNS\AsyncResponse
	 */
	public function AsyncRequest(
			$url, $method='GET', $headers=array(), $body=NULL) {
		
		// perform the initial request
		$resp = $this->Request($url, $method, $headers, $body);
		
		// check response status
		if ($resp->HttpStatus() > 204)
			throw new DNS\AsyncHttpError(sprintf(
				_('Unexpected HTTP status for async request: '.
				  'URL [%s] method [%s] status [%s] response [%s]'),
				$url, $method, $resp->HttpStatus(), $resp->HttpBody()));
		
		// return an AsyncResponse object
		return new DNS\AsyncResponse($this, $resp->HttpBody());
	}

} // end class DNS