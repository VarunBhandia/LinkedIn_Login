<?php
//start session
if(!session_id()){
	session_start();
}

//Include Twitter config file && User class
include_once 'inConfig.php';
include_once 'User.class.php';

$authUrl = $output = '';

//If user already verified 
if(isset($_SESSION['oauth_status']) && $_SESSION['oauth_status'] == 'verified' && !empty($_SESSION['userData'])){
	//Prepare output to show to the user
	$userInfo = $_SESSION['userData'];
	$output = '<div class="login-form">
        <div class="head">
            <img src="'.$userInfo['picture'].'" alt=""/>
        </div>
        <form>
        <li>
            <p>'.$userInfo['first_name'].' '.$userInfo['last_name'].'</p>
        </li>
        <li>
            <p>'.$userInfo['email'].'</p>
        </li>
		<li>
            <p>'.$userInfo['locale'].'</p>
        </li>
        <div class="foot">
            <a href="logout.php">Logout</a>
            <a href="'.$userInfo['link'].'" target="_blank">View Profile</a>
            <div class="clear"> </div>
        </div>
        </form>
	</div>';
}elseif((isset($_GET["oauth_init"]) && $_GET["oauth_init"] == 1) || (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier']))){
	$client = new oauth_client_class;
	
	$client->client_id = $apiKey;
	$client->client_secret = $apiSecret;
	$client->redirect_uri = $redirectURL;
	$client->scope = $scope;
	$client->debug = false;
	$client->debug_http = true;
	$application_line = __LINE__;
	
	if(strlen($client->client_id) == 0 || strlen($client->client_secret) == 0){
		die('Please go to LinkedIn Apps page https://www.linkedin.com/secure/developer?newapp= , '.
			'create an application, and in the line '.$application_line.
			' set the client_id to Consumer key and client_secret with Consumer secret. '.
			'The Callback URL must be '.$client->redirect_uri.'. Make sure you enable the '.
			'necessary permissions to execute the API calls your application needs.');
	}
	
	//If authentication returns success
	if($success = $client->Initialize()){
		if(($success = $client->Process())){
			if(strlen($client->authorization_error)){
				$client->error = $client->authorization_error;
				$success = false;
			}elseif(strlen($client->access_token)){
				$success = $client->CallAPI('http://api.linkedin.com/v1/people/~:(id,email-address,first-name,last-name,location,picture-url,public-profile-url,formatted-name)', 
				'GET',
				array('format'=>'json'),
				array('FailOnAccessError'=>true), $userInfo);
			}
		}
		$success = $client->Finalize($success);
	}
	
	if($client->exit) exit;
	
	if($success){
		//Initialize User class
		$user = new User();
		
		//Insert or update user data to the database
		$fname = $userInfo->firstName;
		$lname = $userInfo->lastName;
		$inUserData = array(
			'oauth_provider'=> 'linkedin',
			'oauth_uid'     => $userInfo->id,
			'first_name'    => $fname,
			'last_name'     => $lname,
			'email'         => $userInfo->emailAddress,
			'gender'        => '',
			'locale'        => $userInfo->location->name,
			'picture'       => $userInfo->pictureUrl,
			'link'          => $userInfo->publicProfileUrl,
			'username'		=> ''
		);
		
		$userData = $user->checkUser($inUserData);
		
		//Storing user data into session
		$_SESSION['userData'] = $userData;
		$_SESSION['oauth_status'] = 'verified';
		
		//Redirect the user back to the same page
		header('Location: ./');
	}else{
		 $output = '<h3 style="color:red">Error connecting to LinkedIn! try again later!</h3>';
	}
}elseif(isset($_GET["oauth_problem"]) && $_GET["oauth_problem"] <> ""){
	$output = '<h3 style="color:red">'.$_GET["oauth_problem"].'</h3>';
}else{
	$authUrl = '?oauth_init=1';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Login with LinkedIn using PHP by CodexWorld</title>
    <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<!-- Display login button / profile information -->
<?php echo $output; ?>
<?php
if(!empty($authUrl)){
	echo '<div class="linkedin_btn"><a href="'.$authUrl.'"><img src="images/sign-in-with-linkedin.png" /></a></div>';
}
?>
</body>
</html>