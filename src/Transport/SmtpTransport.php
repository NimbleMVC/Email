<?php

namespace NimblePHP\Email\Transport;

use NimblePHP\Email\Config\EmailConfig;
use NimblePHP\Email\Exception\EmailException;
use NimblePHP\Framework\Traits\LogTrait;

class SmtpTransport implements TransportInterface
{

    use LogTrait;

    /** @var EmailConfig */
    private EmailConfig $config;

    /** @var int */
    private int $connectionTimeout = 30;

    /** @var int */
    private int $timeout = 30;

    /** @var resource|null */
    private $socket = null;

    /**
     * Construct the SMTP transport
     * @param EmailConfig $config
     */
    public function __construct(EmailConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Sets connection timeout
     * @param int $seconds Timeout in seconds
     * @return $this
     */
    public function setConnectionTimeout(int $seconds): self
    {
        $this->connectionTimeout = $seconds;

        return $this;
    }

    /**
     * Sets stream timeout
     * @param int $seconds Timeout in seconds
     * @return $this
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Send an email message via SMTP
     * @param string $from From address
     * @param string $to To address
     * @param string $subject Email subject
     * @param string $body Email body
     * @param array $headers Additional headers
     * @param array $attachments File attachments
     * @param array $embeddedImages Embedded images
     * @param bool $isHtml Whether the email is HTML format
     * @param array $cc CC recipients
     * @param array $bcc BCC recipients
     * @return bool Success status
     * @throws EmailException On SMTP error
     */
    public function send(
        string $from,
        string $to,
        string $subject,
        string $body,
        array $headers = [],
        array $attachments = [],
        array $embeddedImages = [],
        bool $isHtml = false,
        array $cc = [],
        array $bcc = []
    ): bool {
        $this->log('Sending email via SMTP', 'INFO');

        $this->connect();
        $this->authenticate();

        // Format message
        $message = $this->formatMessage(
            $from,
            $to,
            $subject,
            $body,
            $headers,
            $attachments,
            $embeddedImages,
            $isHtml,
            $cc,
            $bcc
        );

        // Send the message
        $this->sendMessage($from, $to, $cc, $bcc, $message);

        $this->disconnect();

        return true;
    }

    /**
     * Connect to the SMTP server
     * @throws EmailException If connection fails
     */
    private function connect(): void
    {
        $errno = 0;
        $errstr = '';

        $isSecureSSL = $this->config->getSecureType() === 'ssl';
        $hostPrefix = $isSecureSSL ? 'ssl://' : '';

        $this->socket = fsockopen(
            $hostPrefix . $this->config->getHost(),
            $this->config->getPort(),
            $errno,
            $errstr,
            $this->connectionTimeout
        );

        if (!$this->socket) {
            throw new EmailException("Could not connect to SMTP server: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->timeout);

        $this->getResponse();

        $hostname = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        fputs($this->socket, "EHLO $hostname\r\n");
        $this->getResponse();

        if ($this->config->getSecureType() === 'tls') {
            fputs($this->socket, "STARTTLS\r\n");
            $this->getResponse();

            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new EmailException("Failed to enable TLS encryption");
            }

            fputs($this->socket, "EHLO $hostname\r\n");
            $this->getResponse();
        }
    }

    /**
     * Authenticate with the SMTP server
     * @throws EmailException If authentication fails
     */
    private function authenticate(): void
    {
        if (!$this->config->isAuthEnabled()) {
            return;
        }

        if ($this->config->getAuthType() === 'XOAUTH2') {
            $this->config->validateOAuthForServices();

            fputs($this->socket, "AUTH XOAUTH2\r\n");
            $this->getResponse();

            $authString = base64_encode("user=" . $this->config->getUsername() . "\1auth=Bearer " . $this->config->getOAuthToken() . "\1\1");
            fputs($this->socket, $authString . "\r\n");
            $this->getResponse();
        } else {
            fputs($this->socket, "AUTH LOGIN\r\n");
            $this->getResponse();

            fputs($this->socket, base64_encode($this->config->getUsername()) . "\r\n");
            $this->getResponse();

            fputs($this->socket, base64_encode($this->config->getPassword()) . "\r\n");
            $this->getResponse();
        }
    }

    /**
     * Format the email message with headers, body, and attachments
     */
    private function formatMessage(
        string $from,
        string $to,
        string $subject,
        string $body,
        array $headers,
        array $attachments,
        array $embeddedImages,
        bool $isHtml,
        array $cc,
        array $bcc
    ): string {
        $message = "To: {$to}\r\n";
        $message .= "From: {$from}\r\n";
        $message .= "Subject: {$subject}\r\n";

        if (!empty($cc)) {
            $message .= "Cc: " . implode(", ", $cc) . "\r\n";
        }

        foreach ($headers as $name => $value) {
            $message .= "$name: $value\r\n";
        }

        $hasAttachments = !empty($attachments) || !empty($embeddedImages);
        if ($hasAttachments) {
            $boundary = md5(time());
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
            $message .= "\r\n";

            $message .= "--$boundary\r\n";
            $message .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $message .= $body . "\r\n\r\n";

            // Add embedded images
            foreach ($embeddedImages as $image) {
                $content = file_get_contents($image['path']);
                $content = chunk_split(base64_encode($content));
                $message .= "--$boundary\r\n";
                $message .= "Content-Type: {$image['mime']}; name=\"" . basename($image['path']) . "\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n";
                $message .= "Content-ID: <{$image['cid']}>\r\n";
                $message .= "Content-Disposition: inline; filename=\"" . basename($image['path']) . "\"\r\n\r\n";
                $message .= $content . "\r\n\r\n";
            }

            foreach ($attachments as $attachment) {
                if (isset($attachment['path'])) {
                    $content = file_get_contents($attachment['path']);
                } else {
                    $content = $attachment['content'];
                }

                $content = chunk_split(base64_encode($content));
                $message .= "--$boundary\r\n";
                $message .= "Content-Type: " . ($attachment['mime'] ?? "application/octet-stream") . "; name=\"{$attachment['name']}\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n";
                $message .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n\r\n";
                $message .= $content . "\r\n\r\n";
            }

            $message .= "--$boundary--\r\n";
        } else {
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
            $message .= "\r\n";
            $message .= $body . "\r\n";
        }

        return $message;
    }

    /**
     * Send the formatted message to recipients
     */
    private function sendMessage(string $from, string $to, array $cc, array $bcc, string $message): void
    {
        preg_match('/<(.+?)>/', $from, $matches);
        $fromEmail = $matches[1] ?? $from;
        $fromEmail = trim($fromEmail, '<>');

        fputs($this->socket, "MAIL FROM:<$fromEmail>\r\n");
        $this->getResponse();

        preg_match('/<(.+?)>/', $to, $matches);
        $toEmail = $matches[1] ?? $to;
        $toEmail = trim($toEmail, '<>');

        fputs($this->socket, "RCPT TO:<$toEmail>\r\n");
        $this->getResponse();

        foreach ($cc as $ccAddress) {
            preg_match('/<(.+?)>/', $ccAddress, $matches);
            $ccEmail = $matches[1] ?? $ccAddress;
            $ccEmail = trim($ccEmail, '<>');
            fputs($this->socket, "RCPT TO:<$ccEmail>\r\n");
            $this->getResponse();
        }

        // Send to BCC recipients
        foreach ($bcc as $bccAddress) {
            preg_match('/<(.+?)>/', $bccAddress, $matches);
            $bccEmail = $matches[1] ?? $bccAddress;
            $bccEmail = trim($bccEmail, '<>');
            fputs($this->socket, "RCPT TO:<$bccEmail>\r\n");
            $this->getResponse();
        }

        fputs($this->socket, "DATA\r\n");
        $this->getResponse();

        fputs($this->socket, $message);
        fputs($this->socket, "\r\n.\r\n");
        $this->getResponse();
    }

    /**
     * Disconnect from the SMTP server
     */
    private function disconnect(): void
    {
        if ($this->socket) {
            fputs($this->socket, "QUIT\r\n");
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Reads SMTP server response
     * @return string Server response
     * @throws EmailException If server returned an error
     */
    private function getResponse(): string
    {
        $response = '';

        while ($line = fgets($this->socket, 515)) {
            $response .= $line;

            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }

        $code = intval(substr($response, 0, 3));

        if ($code >= 400) {
            $this->log('SMTP error', 'ERR', ['response' => $response, 'code' => $code]);
            throw new EmailException("SMTP Error: $response");
        }

        $this->log('SMTP response', 'INFO', ['response' => $response, 'code' => $code]);

        return $response;
    }

}