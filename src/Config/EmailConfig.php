<?php

namespace NimblePHP\Email\Config;

use NimblePHP\Email\Exception\EmailException;

class EmailConfig
{

    /** @var array */
    private array $config;

    /** @var string|null */
    private ?string $oauthToken = null;

    /**
     * Construct class
     */
    public function __construct()
    {
        $emailConfig = $_ENV['EMAIL_CONFIG'] ?? '';
        $config = [
            'host' => $_ENV['EMAIL_HOST'] ?? 'localhost',
            'port' => $_ENV['EMAIL_PORT'] ?? 25,
            'username' => $_ENV['EMAIL_USERNAME'] ?? '',
            'password' => $_ENV['EMAIL_PASSWORD'] ?? '',
            'auth' => $_ENV['EMAIL_AUTH'] ?? false,
            'secure' => $_ENV['EMAIL_SECURE'] ?? '',
            'from' => $_ENV['EMAIL_FROM'] ?? '',
            'from_name'=> $_ENV['EMAIL_FROM_NAME'] ?? ''
        ];

        if (!empty($emailConfig)) {
            switch (strtoupper($emailConfig)) {
                case 'GMAIL':
                    $config = array_merge($config, [
                        'host' => 'smtp.gmail.com',
                        'port' => 587,
                        'auth' => true,
                        'secure' => 'tls',
                    ]);
                    break;

                case 'OUTLOOK':
                case 'HOTMAIL':
                case 'OFFICE365':
                    $config = array_merge($config, [
                        'host' => 'smtp.office365.com',
                        'port' => 587,
                        'auth' => true,
                        'secure' => 'tls',
                        'auth_type' => 'XOAUTH2'
                    ]);
                    break;

                case 'YAHOO':
                    $config = array_merge($config, [
                        'host' => 'smtp.mail.yahoo.com',
                        'port' => 587,
                        'auth' => true,
                        'secure' => 'tls',
                    ]);
                    break;

                case 'ZOHO':
                    $config = array_merge($config, [
                        'host' => 'smtp.zoho.com',
                        'port' => 587,
                        'auth' => true,
                        'secure' => 'tls',
                    ]);
                    break;

                case 'SENDGRID':
                    $config = array_merge($config, [
                        'host' => 'smtp.sendgrid.net',
                        'port' => 587,
                        'auth' => true,
                        'secure' => 'tls',
                    ]);
                    break;

                case 'MAILGUN':
                    $config = array_merge($config, [
                        'host' => 'smtp.mailgun.org',
                        'port' => 587,
                        'auth' => true,
                        'secure' => 'tls',
                    ]);
                    break;

                case 'MAILTRAP':
                    $config = array_merge($config, [
                        'host' => 'sandbox.smtp.mailtrap.io',
                        'port' => 2525,
                        'auth' => true,
                        'secure' => '',
                    ]);
                    break;

                case 'AMAZON_SES':
                    $config = array_merge($config, [
                        'host' => $_ENV['SES_ENDPOINT'] ?? 'email-smtp.us-east-1.amazonaws.com',
                        'port' => 587,
                        'auth' => true,
                        'secure' => 'tls',
                    ]);
                    break;
            }
        }

        $this->config = $config;

        if (!empty($this->config['oauth_token'])) {
            $this->oauthToken = $this->config['oauth_token'];
        }
    }

    /**
     * Sets OAuth2 token for authentication
     * @param string $token OAuth2 token
     * @return $this
     */
    public function setOAuthToken(string $token): self
    {
        $this->oauthToken = $token;
        $this->config['oauth_token'] = $token;

        return $this;
    }

    /**
     * Set SMTP configuration directly
     * @param array $config SMTP configuration options
     * @return $this
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Get complete SMTP configuration
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get SMTP host
     * @return string
     */
    public function getHost(): string
    {
        return $this->config['host'];
    }

    /**
     * Get SMTP port
     * @return int
     */
    public function getPort(): int
    {
        return (int)$this->config['port'];
    }

    /**
     * Check if authentication is enabled
     * @return bool
     */
    public function isAuthEnabled(): bool
    {
        return (bool)$this->config['auth'];
    }

    /**
     * Get SMTP secure type (tls, ssl, etc.)
     * @return string|null
     */
    public function getSecureType(): ?string
    {
        return $this->config['secure'] ?? null;
    }

    /**
     * Get SMTP username
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->config['username'] ?? null;
    }

    /**
     * Get SMTP password
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->config['password'] ?? null;
    }

    /**
     * Get authentication type
     * @return string|null
     */
    public function getAuthType(): ?string
    {
        return $this->config['auth_type'] ?? null;
    }

    /**
     * Get OAuth2 token
     * @return string|null
     */
    public function getOAuthToken(): ?string
    {
        return $this->oauthToken;
    }

    /**
     * Get default from address
     * @return string|null
     */
    public function getFromAddress(): ?string
    {
        return $this->config['from'] ?? null;
    }

    /**
     * Get default from name
     * @return string|null
     */
    public function getFromName(): ?string
    {
        return $this->config['from_name'] ?? null;
    }

    /**
     * Validate OAUTH2 token for services that require it
     * @throws EmailException
     */
    public function validateOAuthForServices(): void
    {
        $authType = $this->getAuthType();

        if ($authType === 'XOAUTH2' && empty($this->oauthToken)) {
            throw new EmailException('OAuth2 token jest wymagany dla uwierzytelnienia Outlook/Office365. Ustaw go przez EMAIL_OAUTH_TOKEN lub metodę setOAuthToken()');
        }
    }

}