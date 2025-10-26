<?php

namespace Core\Mail;

use Core\Config\Config;
use Core\View\ViewEngine;

/**
 * Email Service with Template Support
 * Supports multiple drivers (SMTP, Sendmail, Mail)
 */
class Mailer
{
    private string $driver;
    private array $config;
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private string $from;
    private string $fromName;
    private string $subject = '';
    private string $body = '';
    private string $altBody = '';
    private array $attachments = [];
    private array $headers = [];
    private bool $isHtml = true;
    private string $boundary = '';

    public function __construct()
    {
        $this->config = Config::get('mail', []);
        $this->driver = $this->config['driver'] ?? 'mail';
        $this->from = $this->config['from']['address'] ?? 'noreply@example.com';
        $this->fromName = $this->config['from']['name'] ?? 'Application';
    }

    /**
     * Set recipient(s)
     */
    public function to(string|array $email, ?string $name = null): self
    {
        if (is_array($email)) {
            foreach ($email as $e => $n) {
                if (is_numeric($e)) {
                    $this->to[] = ['email' => $n, 'name' => null];
                } else {
                    $this->to[] = ['email' => $e, 'name' => $n];
                }
            }
        } else {
            $this->to[] = ['email' => $email, 'name' => $name];
        }
        return $this;
    }

    /**
     * Set CC recipient(s)
     */
    public function cc(string|array $email, ?string $name = null): self
    {
        if (is_array($email)) {
            foreach ($email as $e => $n) {
                if (is_numeric($e)) {
                    $this->cc[] = ['email' => $n, 'name' => null];
                } else {
                    $this->cc[] = ['email' => $e, 'name' => $n];
                }
            }
        } else {
            $this->cc[] = ['email' => $email, 'name' => $name];
        }
        return $this;
    }

    /**
     * Set BCC recipient(s)
     */
    public function bcc(string|array $email, ?string $name = null): self
    {
        if (is_array($email)) {
            foreach ($email as $e => $n) {
                if (is_numeric($e)) {
                    $this->bcc[] = ['email' => $n, 'name' => null];
                } else {
                    $this->bcc[] = ['email' => $e, 'name' => $n];
                }
            }
        } else {
            $this->bcc[] = ['email' => $email, 'name' => $name];
        }
        return $this;
    }

    /**
     * Set sender
     */
    public function from(string $email, ?string $name = null): self
    {
        $this->from = $email;
        if ($name) {
            $this->fromName = $name;
        }
        return $this;
    }

    /**
     * Set email subject
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set HTML body
     */
    public function html(string $html): self
    {
        $this->body = $html;
        $this->isHtml = true;
        return $this;
    }

    /**
     * Set plain text body
     */
    public function text(string $text): self
    {
        $this->altBody = $text;
        return $this;
    }

    /**
     * Use email template
     */
    public function template(string $template, array $data = []): self
    {
        $this->body = ViewEngine::make("emails/{$template}", $data);
        $this->isHtml = true;
        return $this;
    }

    /**
     * Attach file
     */
    public function attach(string $path, ?string $name = null): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Attachment not found: {$path}");
        }
        
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?? basename($path)
        ];
        return $this;
    }

    /**
     * Add custom header
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send email
     */
    public function send(): bool
    {
        if (empty($this->to)) {
            throw new \RuntimeException("No recipients specified");
        }

        if (empty($this->subject)) {
            throw new \RuntimeException("No subject specified");
        }

        if (empty($this->body) && empty($this->altBody)) {
            throw new \RuntimeException("No message body specified");
        }

        return match($this->driver) {
            'smtp' => $this->sendViaSMTP(),
            'sendmail' => $this->sendViaSendmail(),
            default => $this->sendViaMail()
        };
    }

    /**
     * Send via PHP mail() function
     */
    private function sendViaMail(): bool
    {
        $headers = $this->buildHeaders();
        $message = $this->buildMessage();
        
        $toAddresses = array_map(fn($r) => $this->formatAddress($r['email'], $r['name']), $this->to);
        $to = implode(', ', $toAddresses);

        return mail($to, $this->subject, $message, $headers);
    }

    /**
     * Send via SMTP
     */
    private function sendViaSMTP(): bool
    {
        $host = $this->config['smtp']['host'] ?? 'localhost';
        $port = $this->config['smtp']['port'] ?? 587;
        $username = $this->config['smtp']['username'] ?? '';
        $password = $this->config['smtp']['password'] ?? '';
        $encryption = $this->config['smtp']['encryption'] ?? 'tls';

        $socket = $this->connectSMTP($host, $port, $encryption);
        
        if (!$socket) {
            return false;
        }

        try {
            // SMTP handshake
            $this->smtpCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            
            // Authentication
            if ($username && $password) {
                $this->smtpCommand($socket, "AUTH LOGIN");
                $this->smtpCommand($socket, base64_encode($username));
                $this->smtpCommand($socket, base64_encode($password));
            }

            // Send email
            $this->smtpCommand($socket, "MAIL FROM:<{$this->from}>");
            
            foreach ($this->to as $recipient) {
                $this->smtpCommand($socket, "RCPT TO:<{$recipient['email']}>");
            }
            
            $this->smtpCommand($socket, "DATA");
            
            $headers = $this->buildHeaders();
            $message = $this->buildMessage();
            $email = "Subject: {$this->subject}\r\n{$headers}\r\n\r\n{$message}\r\n.";
            
            fwrite($socket, $email . "\r\n");
            $this->smtpCommand($socket, "QUIT");
            
            fclose($socket);
            return true;
        } catch (\Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            return false;
        }
    }

    /**
     * Connect to SMTP server
     */
    private function connectSMTP(string $host, int $port, string $encryption)
    {
        $remote = ($encryption === 'ssl') ? "ssl://{$host}:{$port}" : "{$host}:{$port}";
        $socket = @fsockopen($remote, $port, $errno, $errstr, 30);
        
        if (!$socket) {
            return false;
        }

        stream_set_timeout($socket, 30);
        
        // Read welcome message
        fgets($socket, 515);
        
        // Start TLS if needed
        if ($encryption === 'tls') {
            $this->smtpCommand($socket, "STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }

        return $socket;
    }

    /**
     * Execute SMTP command
     */
    private function smtpCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        return fgets($socket, 515);
    }

    /**
     * Send via Sendmail
     */
    private function sendViaSendmail(): bool
    {
        $sendmailPath = $this->config['sendmail']['path'] ?? '/usr/sbin/sendmail -t -i';
        
        $headers = $this->buildHeaders();
        $message = $this->buildMessage();
        
        $toAddresses = array_map(fn($r) => $this->formatAddress($r['email'], $r['name']), $this->to);
        $to = implode(', ', $toAddresses);

        $email = "To: {$to}\r\n";
        $email .= "Subject: {$this->subject}\r\n";
        $email .= $headers . "\r\n\r\n";
        $email .= $message;

        $pipe = popen($sendmailPath, 'w');
        if (!$pipe) {
            return false;
        }

        fwrite($pipe, $email);
        return pclose($pipe) === 0;
    }

    /**
     * Build email headers
     */
    private function buildHeaders(): string
    {
        $headers = [];
        
        $headers[] = "From: " . $this->formatAddress($this->from, $this->fromName);
        $headers[] = "Reply-To: " . $this->formatAddress($this->from, $this->fromName);
        $headers[] = "X-Mailer: Custom PHP Mailer";
        $headers[] = "MIME-Version: 1.0";

        if (!empty($this->cc)) {
            $ccAddresses = array_map(fn($r) => $this->formatAddress($r['email'], $r['name']), $this->cc);
            $headers[] = "Cc: " . implode(', ', $ccAddresses);
        }

        if (!empty($this->bcc)) {
            $bccAddresses = array_map(fn($r) => $this->formatAddress($r['email'], $r['name']), $this->bcc);
            $headers[] = "Bcc: " . implode(', ', $bccAddresses);
        }

        // Custom headers
        foreach ($this->headers as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        // Content type
        if (!empty($this->attachments)) {
            $boundary = md5(uniqid(time()));
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
            $this->boundary = $boundary;
        } elseif ($this->isHtml && !empty($this->altBody)) {
            $boundary = md5(uniqid(time()));
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            $this->boundary = $boundary;
        } elseif ($this->isHtml) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }

        return implode("\r\n", $headers);
    }

    /**
     * Build email message body
     */
    private function buildMessage(): string
    {
        if (!empty($this->attachments)) {
            return $this->buildMultipartMessage();
        } elseif ($this->isHtml && !empty($this->altBody)) {
            return $this->buildAlternativeMessage();
        }

        return $this->body ?: $this->altBody;
    }

    /**
     * Build multipart message with attachments
     */
    private function buildMultipartMessage(): string
    {
        $message = "--{$this->boundary}\r\n";
        
        if ($this->isHtml && !empty($this->altBody)) {
            $altBoundary = md5(uniqid(time()));
            $message .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";
            $message .= "--{$altBoundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $this->altBody . "\r\n\r\n";
            $message .= "--{$altBoundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $this->body . "\r\n\r\n";
            $message .= "--{$altBoundary}--\r\n\r\n";
        } else {
            $contentType = $this->isHtml ? 'text/html' : 'text/plain';
            $message .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= ($this->body ?: $this->altBody) . "\r\n\r\n";
        }

        // Add attachments
        foreach ($this->attachments as $attachment) {
            $content = file_get_contents($attachment['path']);
            $encoded = chunk_split(base64_encode($content));
            
            $message .= "--{$this->boundary}\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"{$attachment['name']}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n\r\n";
            $message .= $encoded . "\r\n";
        }

        $message .= "--{$this->boundary}--";
        return $message;
    }

    /**
     * Build alternative message (HTML + plain text)
     */
    private function buildAlternativeMessage(): string
    {
        $message = "--{$this->boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $this->altBody . "\r\n\r\n";
        $message .= "--{$this->boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $this->body . "\r\n\r\n";
        $message .= "--{$this->boundary}--";
        return $message;
    }

    /**
     * Format email address with name
     */
    private function formatAddress(string $email, ?string $name = null): string
    {
        if ($name) {
            return "\"{$name}\" <{$email}>";
        }
        return $email;
    }

    /**
     * Queue email for later sending
     */
    public function queue(): bool
    {
        $queueDir = $_SERVER['DOCUMENT_ROOT'] . '/../storage/mail_queue';
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0755, true);
        }

        $queueFile = $queueDir . '/' . uniqid('mail_') . '.json';
        
        $data = [
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'from' => $this->from,
            'fromName' => $this->fromName,
            'subject' => $this->subject,
            'body' => $this->body,
            'altBody' => $this->altBody,
            'attachments' => $this->attachments,
            'headers' => $this->headers,
            'isHtml' => $this->isHtml,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return file_put_contents($queueFile, json_encode($data)) !== false;
    }

    /**
     * Process queued emails
     */
    public static function processQueue(int $limit = 10): int
    {
        $queueDir = $_SERVER['DOCUMENT_ROOT'] . '/../storage/mail_queue';
        if (!is_dir($queueDir)) {
            return 0;
        }

        $files = glob($queueDir . '/mail_*.json');
        $processed = 0;

        foreach (array_slice($files, 0, $limit) as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            $mailer = new self();
            
            foreach ($data['to'] as $recipient) {
                $mailer->to($recipient['email'], $recipient['name']);
            }
            
            foreach ($data['cc'] ?? [] as $recipient) {
                $mailer->cc($recipient['email'], $recipient['name']);
            }
            
            foreach ($data['bcc'] ?? [] as $recipient) {
                $mailer->bcc($recipient['email'], $recipient['name']);
            }
            
            $mailer->from($data['from'], $data['fromName']);
            $mailer->subject($data['subject']);
            
            if ($data['isHtml']) {
                $mailer->html($data['body']);
            } else {
                $mailer->text($data['altBody']);
            }
            
            if (!empty($data['altBody']) && $data['isHtml']) {
                $mailer->text($data['altBody']);
            }
            
            foreach ($data['attachments'] ?? [] as $attachment) {
                if (file_exists($attachment['path'])) {
                    $mailer->attach($attachment['path'], $attachment['name']);
                }
            }
            
            foreach ($data['headers'] ?? [] as $name => $value) {
                $mailer->addHeader($name, $value);
            }

            if ($mailer->send()) {
                unlink($file);
                $processed++;
            }
        }

        return $processed;
    }
}
