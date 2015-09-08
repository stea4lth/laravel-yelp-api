<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

require_once('OAuth.php');



class YelpApi extends Model
{
	/**
	 * Yelp API v2.0 code sample.
	 *
	 * This program demonstrates the capability of the Yelp API version 2.0
	 * by using the Search API to query for businesses by a search term and location,
	 * and the Business API to query additional information about the top result
	 * from the search query.
	 * 
	 * Please refer to http://www.yelp.com/developers/documentation for the API documentation.
	 * 
	 * This program requires a PHP OAuth2 library, which is included in this branch and can be
	 * found here:
	 *      http://oauth.googlecode.com/svn/code/php/
	 * 
	 * Sample usage of the program:
	 * `php sample.php --term="bars" --location="San Francisco, CA"`
	 */

	// Enter the path that the oauth library is in relation to the php file

	// Set your OAuth credentials here  
	// These credentials can be obtained from the 'Manage API Access' page in the
	// developers documentation (http://www.yelp.com/developers)
public $CONSUMER_KEY = NULL;
public $CONSUMER_SECRET = NULL;
public $TOKEN = NULL;
public $TOKEN_SECRET = NULL;
public $API_HOST = 'api.yelp.com';
public $DEFAULT_TERM = 'dinner';
public $DEFAULT_LOCATION = 'San Francisco, CA';
public $SEARCH_LIMIT = 3;
public $SEARCH_PATH = '/v2/search/';
public $BUSINESS_PATH = '/v2/business/';


	/** 
	 * Makes a request to the Yelp API and returns the response
	 * 
	 * @param    $host    The domain host of the API 
	 * @param    $path    The path of the APi after the domain
	 * @return   The JSON response from the request      
	 */
	function request($host, $path) {
		$unsigned_url = "http://" . $host . $path;

	    // Token object built using the OAuth library
		$token = new OAuthToken($this->TOKEN, $this->TOKEN_SECRET);

	    // Consumer object built using the OAuth library
		$consumer = new OAuthConsumer($this->CONSUMER_KEY, $this->CONSUMER_SECRET);

	    // Yelp uses HMAC SHA1 encoding
		$signature_method = new OAuthSignatureMethod_HMAC_SHA1();

		$oauthrequest = OAuthRequest::from_consumer_and_token(
			$consumer, 
			$token, 
			'GET', 
			$unsigned_url
			);

	    // Sign the request
		$oauthrequest->sign_request($signature_method, $consumer, $token);

	    // Get the signed URL
		$signed_url = $oauthrequest->to_url();

	    // Send Yelp API Call
		$ch = curl_init($signed_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}

	/**
	 * Query the Search API by a search term and location 
	 * 
	 * @param    $term        The search term passed to the API 
	 * @param    $location    The search location passed to the API 
	 * @return   The JSON response from the request 
	 */
	function search($term, $location) {
		$url_params = array();

		$url_params['term'] = $term ?: $this->DEFAULT_TERM;
		$url_params['location'] = $location?: $this->DEFAULT_LOCATION;
		$url_params['limit'] = $this->SEARCH_LIMIT;
		$search_path = $this->SEARCH_PATH . "?" . http_build_query($url_params);

		return $this->request($this->API_HOST, $search_path);
	}

	/**
	 * Query the Business API by business_id
	 * 
	 * @param    $business_id    The ID of the business to query
	 * @return   The JSON response from the request 
	 */
	function get_business($business_id) {
		$business_path = $this->BUSINESS_PATH . $business_id;

		return $this->request($this->API_HOST, $business_path);
	}

	/**
	 * Queries the API by the input values from the user 
	 * 
	 * @param    $term        The search term to query
	 * @param    $location    The location of the business to query
	 */
	function query_api($term, $location) {     
		$response = json_decode($this->search($term, $location));
		$business_id = $response->businesses[0]->id;

		print sprintf(
			"%d businesses found, querying business info for the top result \"%s\"\n\n",         
			count($response->businesses),
			$business_id
			);

		$response = $this->get_business($business_id);
		return $response;

		//print sprintf("Result for business \"%s\" found:\n", $business_id);
		//print "$response\n";
	}



}
