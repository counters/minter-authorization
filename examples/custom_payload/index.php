<?php

use Base64Url\Base64Url;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;

include __DIR__ . '/vendor/autoload.php';

/**
 * @param string $plaintext
 * @param string $secretKey
 * @return string
 */
function encodeBase64UrlAES($plaintext, $secretKey)
{
    $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $secretKey, $options = OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, $secretKey, $as_binary = true);
    $ciphertext = Base64Url::encode($iv . $hmac . $ciphertext_raw);
    return $ciphertext;
}

$secretKey = '*************';

$provider = new GenericProvider([
    'clientId' => 'test1',
    'clientSecret' => $secretKey,
    'urlAuthorize' => 'https://auth.minter-service.online/request',
    'urlAccessToken' => 'https://auth.minter-service.online/token',
    'urlResourceOwnerDetails' => 'https://auth.minter-service.online/api/get/auth_mx'
]);


// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    $big_number = mt_rand(1000000, 40000000);
    $hex_number = dechex($big_number);

    $customPayload = encodeBase64UrlAES($big_number, $secretKey);
    $authorizationUrl = $provider->getAuthorizationUrl(['payload' => $customPayload]);

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();
    echo "<p>" . $big_number . " " . $hex_number . "</p>";

    echo "<p><a href='" . $authorizationUrl . "'>" . $authorizationUrl . "</a></p>";

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