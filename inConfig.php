<?php
//Include LinkedIn client library 
require_once 'src/http.php';
require_once 'src/oauth_client.php';

/*
 * Configuration and setup LinkedIn API
 */
$apiKey = '81nyjy0awydxke';
$apiSecret = 'rDLXQrvpRF5CMw34';
$redirectURL = 'http://localhost/linkedin_login_php/';
$scope = 'r_basicprofile r_emailaddress'; //API permissions
?>