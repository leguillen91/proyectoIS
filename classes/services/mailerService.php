<?php
class MailerService {
  private $from = 'no-reply@unahsystems.edu';
  private $headers;

  public function __construct() {
    $this->headers  = "MIME-Version: 1.0" . "\r\n";
    $this->headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";
    $this->headers .= "From: {$this->from}" . "\r\n";
  }

  public function send($to, $subject, $message) {
    if (empty($to) || empty($subject) || empty($message)) {
      throw new Exception("Invalid mail parameters");
    }

    // En entornos de desarrollo solo simula el envío
    if (getenv('APP_ENV') === 'dev') {
      return [
        'simulated' => true,
        'to' => $to,
        'subject' => $subject,
        'message' => $message
      ];
    }

    $sent = mail($to, $subject, $message, $this->headers);
    if (!$sent) {
      throw new Exception("Mail delivery failed");
    }

    return ['ok' => true, 'to' => $to];
  }
}
