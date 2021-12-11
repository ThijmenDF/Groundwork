<?php

namespace Groundwork\Mail;

use Groundwork\Config\Config;
use Groundwork\Exceptions\MailException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mail {

    protected PHPMailer $mail;

    public function __construct(bool $useSMTP = true)
    {
        $this->mail = new PHPMailer();

        $this->mail->setFrom(Config::get('EMAIL_FROM'), Config::get('EMAIL_FROM_NAME'));

        if ($useSMTP) {
            $this->mail->Host = Config::get('SMTP_HOST', 'localhost');
            $this->mail->Port = Config::get('SMTP_PORT', 25);
            $this->mail->Username = Config::get('SMTP_USERNAME');
            $this->mail->Password = Config::get('SMTP_PASSWORD');
        }

        if (Config::isTest()) {
            $this->debugSMTP();
        }
    }

    public function debugSMTP()
    {
        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }

    public function getMail() : PHPMailer
    {
        return $this->mail;
    }

    public function setTo(string $address, string $name = null) : self
    {
        $this->mail->addAddress($address, $name);
        
        return $this;
    }

    public function setSubject(string $subject) : self
    {
        $this->mail->Subject = $subject;

        return $this;
    }

    public function setMessage(string $content) : self
    {
        $this->mail->Body = $content;

        return $this;
    }

    public function setAltMessage(string $content) : self
    {
        $this->mail->AltBody = $content;

        return $this;
    }

    public function send() : bool
    {
        if (! $this->mail->send()) {
            throw new MailException($this->mail->ErrorInfo);
        }

        return true;
    }
}