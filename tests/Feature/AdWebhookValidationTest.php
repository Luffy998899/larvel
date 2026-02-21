<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdWebhookValidationTest extends TestCase
{
    public function test_ad_webhook_requires_required_fields(): void
    {
        $this->postJson('/api/ad/reward', [])->assertStatus(422);
    }
}
