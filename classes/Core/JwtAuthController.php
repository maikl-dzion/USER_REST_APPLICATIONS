<?php

class JwtAuthController  implements  JwtAuthInterface {

    private static $secretKey;

    public function __construct(){ }

    public function encode($login, $password, $role, $host = ''){
        $jwtToken = $this->createToken($login, $password, $role);
        return  $jwtToken;
    }

    public function decode($token) {
        $result = $this->verify($token);
        return $result;
    }

    public static function setSecretKey($secretKey) {
        $this->secretKey = $secretKey;
    }

    protected function sign($headers64, $payload64) {
        $alg = 'sha256';
        $secretKey = $this->getSecretKey();
        $data      = $headers64 . '.' . $payload64;
        $hasheData = hash_hmac($alg, $data, $secretKey);
        $sign = base64_encode($hasheData);
        $sign = rtrim($sign, '=');
        return $sign;
    }

    protected function verify($token) {
        $verify = false;
        $jwt = explode('.', $token);
        $headers64 = $jwt[0];
        $payload64 = $jwt[1];
        $sign      = $jwt[2];
        $createSign = $this->sign($headers64, $payload64);
        if($sign === $createSign)
            $verify = true;
        return $verify;
    }

    protected function createToken($login, $password, $role, $host = 'http://bolderfest.ru') {

        $tokenId    = rtrim(base64_encode(time()), '=');
        $issuedAt   = time();
        $notBefore  = $issuedAt + 10;             //Adding 10 seconds
        $expire     = $notBefore + 60;            // Adding 60 seconds
        $serverName = $host; // Retrieve the server name from config file

        // sub — (subject) "тема", назначение токена
        // aud — (audience) аудитория, получатели токена
        $payload = [
            'iat'  => $issuedAt,   // iat — (issued at) время создания токена
            'jti'  => $tokenId,    // jti — (JWT id) идентификатор токена
            'iss'  => $serverName, // iss — (issuer) издатель токена
            'nbf'  => $notBefore,  // nbf — (not before) срок, до которого токен не действителен
            'exp'  => $expire,     // exp — (expire time) срок действия токена
            'data' => [
                'login'     => $login,
                'password'  => $password,
                'role'      => $role
            ]
        ];

        $headers = array(
            'alg' => 'HS256',
            'typ' => 'JWT'
        );

        $headers64 = base64_encode(json_encode($headers));
        $payload64 = base64_encode(json_encode($payload));
        $headers64 = rtrim($headers64, '=');
        $payload64 = rtrim($payload64, '=');

        $signature = $this->sign($headers64, $payload64);

        $jwtToken  = $headers64 . '.'. $payload64 .'.'. $signature;
        return $jwtToken;
    }

    protected function getSecretKey() {
        $secret = 'fdrteyyEr6348hGG87b';
        return $secret;
    }
}