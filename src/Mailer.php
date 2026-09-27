<?php
declare(strict_types=1);

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

/**
 * Envío de correos a partir de plantillas de templates/emails.
 * Cada plantilla define $subject e imprime el cuerpo HTML; la versión en texto
 * plano se genera automáticamente para lectores de correo sin HTML.
 */
final class Mailer
{
    public static function send(string $toEmail, string $toName, string $template, array $data = []): bool
    {
        try {
            [$subject, $body] = self::render($template, $data);
            $html = render_template('emails/_layout', ['subject' => $subject, 'body' => $body]);
            $text = self::htmlToText($body);

            $driver = (string) App::config('mail.driver', 'log');
            if ($driver === 'log') {
                self::log($toEmail, $subject, $text);
                return true;
            }

            $mail = new PHPMailer(true);
            if ($driver === 'smtp') {
                $mail->isSMTP();
                $mail->Host = (string) App::config('mail.host');
                $mail->Port = (int) App::config('mail.port', 587);
                $username = (string) App::config('mail.username', '');
                $mail->SMTPAuth = $username !== '';
                $mail->Username = $username;
                $mail->Password = (string) App::config('mail.password', '');
                $mail->SMTPSecure = match (App::config('mail.encryption', 'tls')) {
                    'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                    'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                    default => '',
                };
                $mail->SMTPAutoTLS = App::config('mail.encryption', 'tls') !== 'none';
                $mail->Timeout = 15;
            } else {
                $mail->isMail();
            }

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom((string) App::config('mail.from_address'), (string) App::config('mail.from_name', ''));
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
            return true;
        } catch (Throwable $e) {
            // Un fallo de correo no debe romper la acción del usuario; queda registrado
            error_log(sprintf('No se pudo enviar el correo "%s" a %s: %s', $template, $toEmail, $e->getMessage()));
            return false;
        }
    }

    /** @return array{0: string, 1: string} asunto y cuerpo HTML */
    private static function render(string $template, array $data): array
    {
        $file = BASE_PATH . '/templates/emails/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Plantilla de correo no encontrada: ' . $template);
        }
        $subject = '';
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $body = (string) ob_get_clean();
        return [trim(str_replace(["\r", "\n"], ' ', $subject)), $body];
    }

    private static function htmlToText(string $html): string
    {
        $html = (string) preg_replace_callback(
            '#<a\s[^>]*href="([^"]*)"[^>]*>(.*?)</a>#is',
            static function (array $m): string {
                $href = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $label = trim(strip_tags($m[2]));
                return $label === $href || $label === '' ? $href : $label . ': ' . $href;
            },
            $html
        );
        $html = (string) preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = (string) preg_replace('#<li[^>]*>#i', '- ', $html);
        $html = (string) preg_replace('#</li>#i', "\n", $html);
        $html = (string) preg_replace('#</(p|div|h[1-6]|blockquote|ul|ol)>#i', "\n\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = array_map(static fn (string $l): string => trim((string) preg_replace('/[ \t]+/', ' ', $l)), explode("\n", $text));
        return trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }

    private static function log(string $to, string $subject, string $text): void
    {
        $entry = sprintf(
            "==== %s ====\nPara: %s\nAsunto: %s\n\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $text
        );
        file_put_contents(BASE_PATH . '/storage/logs/mail.log', $entry, FILE_APPEND | LOCK_EX);
    }
}
