<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Tests\TestCase;

/**
 * The live site sends email through Brevo's HTTPS API (Railway blocks SMTP).
 * Checks the "brevo" mailer is wired up; no email is actually sent.
 */
class MailTransportTest extends TestCase
{
    public function test_the_brevo_mailer_uses_the_brevo_api_transport(): void
    {
        config(['services.brevo.key' => 'test-key']);

        $transport = Mail::mailer('brevo')->getSymfonyTransport();

        $this->assertInstanceOf(BrevoApiTransport::class, $transport);
        $this->assertStringStartsWith('brevo+api://', (string) $transport);
    }
}
