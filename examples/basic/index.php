<?php

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;

include __DIR__ . '/vendor/autoload.php';


$secretKey = '************';

$provider = new GenericProvider([
    'clientId' => 'test1',
    'clientSecret' => $secretKey,
    'urlAuthorize' => 'https://auth.minter-service.online/request',
    'urlAccessToken' => 'https://auth.minter-service.online/token',
    'urlResourceOwnerDetails' => 'https://auth.minter-service.online/api/get/auth_mx'

]);

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    $authorizationUrl2 = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    echo "<p><a href='" . $authorizationUrl2 . "'>" . $authorizationUrl2 . "</a></p>";

    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }

    exit('Invalid state');

} else {

    $state = $_GET['state'];
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code'],
//            'scope' => ['READ'],
        ]);

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo 'ResourceOwnerId: ' . $accessToken->getResourceOwnerId() . "<br>";
        echo 'Access Token: ' . $accessToken->getToken() . "<br>";
        echo 'Refresh Token: ' . $accessToken->getRefreshToken() . "<br>";
        echo 'Expired in: ' . $accessToken->getExpires() . "<br>";
        echo 'Already expired? ' . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br>";


        $request = $provider->getAuthenticatedRequest(
            'GET',
            'https://auth.minter-service.online/api/get/auth_mx?state=' . $state,
            $accessToken
        );

        $resource2 = $provider->getResponse($request);

        var_export($resource2->getBody()->getContents());


    } catch (IdentityProviderException $e) {

        // Failed to get the access token or user details.
        exit($e->getMessage());

    }

}