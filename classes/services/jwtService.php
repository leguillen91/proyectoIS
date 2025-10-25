<?php


class JwtService {
  private string $secret;
  private string $issuer;
  private string $audience;
  private int $expiresIn;

  public function __construct(array $jwtConfig) {
    $this->secret    = $jwtConfig['secret'];
    $this->issuer    = $jwtConfig['issuer'];
    $this->audience  = $jwtConfig['audience'];
    $this->expiresIn = (int)$jwtConfig['expiresIn'];
  }

  private function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  private function base64UrlDecode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
      $padLen = 4 - $remainder;
      $data .= str_repeat('=', $padLen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
  }

  public function issue(array $claims, ?string $jti = null): string {
    $now = time();
    $payload = array_merge($claims, [
      'iss' => $this->issuer,
      'aud' => $this->audience,
      'iat' => $now,
      'exp' => $now + $this->expiresIn
    ]);
    if ($jti) $payload['jti'] = $jti;

    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $h = $this->base64UrlEncode(json_encode($header));
    $p = $this->base64UrlEncode(json_encode($payload));
    $sig = hash_hmac('sha256', "$h.$p", $this->secret, true);
    $s = $this->base64UrlEncode($sig);

    return "$h.$p.$s";
  }

  public function verify(string $jwt): array {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) throw new Exception('Invalid token format');
    [$h, $p, $s] = $parts;

    // Validar firma
    $calc = $this->base64UrlEncode(hash_hmac('sha256', "$h.$p", $this->secret, true));
    if (!hash_equals($calc, $s)) throw new Exception('Invalid signature');

    // Decodificar payload
    $payloadJson = $this->base64UrlDecode($p);
    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) throw new Exception('Invalid payload');

    // Validar expiración
    if (isset($payload['exp']) && time() > (int)$payload['exp']) {
      throw new Exception('Token expired');
    }

    // (Opcional) Validar iss/aud si querés más dureza
    if (!empty($payload['iss']) && $payload['iss'] !== $this->issuer) {
      throw new Exception('Invalid issuer');
    }
    if (!empty($payload['aud']) && $payload['aud'] !== $this->audience) {
      throw new Exception('Invalid audience');
    }

    return $payload;
  }
}
