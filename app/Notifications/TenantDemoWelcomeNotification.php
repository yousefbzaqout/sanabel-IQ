<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantDemoWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $temporaryPassword,
        public string $loginUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('مرحباً بكم في سنابل IQ — بيئة مدرستكم التجريبية جاهزة')
            ->greeting('أهلاً '.$notifiable->name)
            ->line('تم تجهيز بيئة تجريبية لمدرسة: '.$this->tenant->name)
            ->line('النطاق: '.($this->tenant->domain ?? $this->tenant->slug))
            ->line('البريد لتسجيل الدخول: '.$notifiable->email)
            ->line('كلمة المرور المؤقتة: '.$this->temporaryPassword)
            ->action('تسجيل الدخول', $this->loginUrl)
            ->line('يُفضّل تغيير كلمة المرور بعد أول دخول.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tenant_demo_welcome',
            'title' => 'بيئة المدرسة التجريبية جاهزة',
            'message' => 'تم إنشاء بيئة تجريبية لـ '.$this->tenant->name,
            'tenant_id' => $this->tenant->id,
            'login_url' => $this->loginUrl,
        ];
    }
}
