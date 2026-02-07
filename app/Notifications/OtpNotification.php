<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * OTP Notification
 * 
 * Sends OTP codes via email notification channel.
 * Supports different OTP types with customized messages.
 */
class OtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The OTP code to send.
     */
    protected string $code;

    /**
     * The OTP type (login, update_password, etc.).
     */
    protected string $type;

    /**
     * The expiry time in minutes.
     */
    protected int $expiryMinutes;

    /**
     * Additional data for the notification.
     */
    protected array $data;

    /**
     * Create a new notification instance.
     *
     * @param string $code The OTP code
     * @param string $type The OTP type
     * @param int $expiryMinutes Minutes until expiry
     * @param array $data Additional data
     */
    public function __construct(string $code, string $type, int $expiryMinutes, array $data = [])
    {
        $this->code = $code;
        $this->type = $type;
        $this->expiryMinutes = $expiryMinutes;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $typeConfig = config("otp.types.{$this->type}", []);
        $description = $typeConfig['description'] ?? $this->type;

        // Build the email based on type
        $mailMessage = (new MailMessage)
            ->subject($this->getSubject($appName, $description))
            ->greeting($this->getGreeting())
            ->line($this->getIntroLine($description));

        // Add the OTP code prominently
        $mailMessage->line('**Your verification code is:**');
        $mailMessage->line("# {$this->code}");

        // Add expiry warning
        $mailMessage->line("This code will expire in **{$this->expiryMinutes} minutes**.");

        // Add type-specific content
        $this->addTypeSpecificContent($mailMessage);

        // Add security notice
        $mailMessage->line('---');
        $mailMessage->line('If you did not request this code, please ignore this email or contact support if you have concerns.');

        // Add salutation
        $mailMessage->salutation("Best regards,\n{$appName} Team");

        return $mailMessage;
    }

    /**
     * Get the email subject based on type.
     *
     * @param string $appName Application name
     * @param string $description Type description
     * @return string
     */
    protected function getSubject(string $appName, string $description): string
    {
        return match ($this->type) {
            'signup' => "Complete your {$appName} signup",
            'login' => "{$this->code} is your {$appName} login code",
            'update_password' => "Reset your {$appName} password",
            'update_email' => "Verify your email for {$appName}",
            'update_mobile' => "Verify your mobile for {$appName}",
            default => "Your {$appName} verification code",
        };
    }

    /**
     * Get the greeting based on notifiable.
     *
     * @return string
     */
    protected function getGreeting(): string
    {
        return 'Hello!';
    }

    /**
     * Get the intro line based on type.
     *
     * @param string $description Type description
     * @return string
     */
    protected function getIntroLine(string $description): string
    {
        return match ($this->type) {
            'signup' => 'Welcome! Please verify your account by entering the code below.',
            'login' => 'You requested to log in to your account. Use the code below to complete your login.',
            'update_password' => 'You requested to reset your password. Use the code below to proceed with the reset.',
            'update_email' => 'Please verify your email address by entering the code below.',
            'update_mobile' => 'Please verify your mobile number by entering the code below.',
            default => "You requested a verification code for: {$description}.",
        };
    }

    /**
     * Add type-specific content to the email.
     *
     * @param MailMessage $mailMessage The mail message instance
     */
    protected function addTypeSpecificContent(MailMessage $mailMessage): void
    {
        switch ($this->type) {
            case 'signup':
                if (isset($this->data['value']['name'])) {
                    $mailMessage->line("Welcome, {$this->data['value']['name']}!");
                }
                break;

            case 'login':
                if (isset($this->data['value']['ip_address'])) {
                    $mailMessage->line("Request from IP: {$this->data['value']['ip_address']}");
                }
                if (isset($this->data['value']['device_info'])) {
                    $mailMessage->line("Device: {$this->data['value']['device_info']}");
                }
                break;
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'code' => $this->code, // Note: Consider removing in production for security
            'expiry_minutes' => $this->expiryMinutes,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Static Factory Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create notification for signup OTP.
     *
     * @param string $code The OTP code
     * @param array $data Additional data
     * @return static
     */
    public static function forSignup(string $code, array $data = []): static
    {
        $expiryMinutes = config('otp.types.signup.expiry_minutes', 5);
        return new static($code, 'signup', $expiryMinutes, $data);
    }

    /**
     * Create notification for login OTP.
     *
     * @param string $code The OTP code
     * @param array $data Additional data
     * @return static
     */
    public static function forLogin(string $code, array $data = []): static
    {
        $expiryMinutes = config('otp.types.login.expiry_minutes', 5);
        return new static($code, 'login', $expiryMinutes, $data);
    }

    /**
     * Create notification for password reset OTP.
     *
     * @param string $code The OTP code
     * @param array $data Additional data
     * @return static
     */
    public static function forPasswordReset(string $code, array $data = []): static
    {
        $expiryMinutes = config('otp.types.update_password.expiry_minutes', 15);
        return new static($code, 'update_password', $expiryMinutes, $data);
    }

    /**
     * Create notification for email verification OTP.
     *
     * @param string $code The OTP code
     * @param array $data Additional data
     * @return static
     */
    public static function forEmailVerification(string $code, array $data = []): static
    {
        $expiryMinutes = config('otp.types.update_email.expiry_minutes', 30);
        return new static($code, 'update_email', $expiryMinutes, $data);
    }
}
