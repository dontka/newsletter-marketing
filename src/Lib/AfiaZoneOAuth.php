<?php

class AfiaZoneOAuth
{
    private string $appId;
    private string $appSecret;
    private string $apiBase;
    private string $redirectUri;

    public function __construct()
    {
        $this->appId = getenv('AFIAZONE_APP_ID') ?: '';
        $this->appSecret = getenv('AFIAZONE_APP_SECRET') ?: '';
        $this->apiBase = getenv('AFIAZONE_API_BASE') ?: 'https://afiazone.com/api';
        $this->redirectUri = getenv('AFIAZONE_REDIRECT_URI') ?: '';
    }

    public function getAuthorizeUrl(): string
    {
        return sprintf('%s/oauth?app_id=%s', rtrim($this->apiBase, '/'), urlencode($this->appId));
    }

    public function exchangeAuthKey(string $authKey): ?string
    {
        $postData = http_build_query([
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret,
            'auth_key' => $authKey,
        ]);

        $ch = curl_init($this->apiBase . '/authorize');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        return $json['access_token'] ?? null;
    }

    public function getUserInfo(string $accessToken): ?array
    {
        $url = sprintf('%s/get_user_info?access_token=%s', rtrim($this->apiBase, '/'), urlencode($accessToken));
        $response = file_get_contents($url);
        if ($response === false) {
            return null;
        }

        $json = json_decode($response, true);
        return $json['user_info'] ?? null;
    }
}
