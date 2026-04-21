<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AuthService;
use Mockery;

class AuthOptionalTest extends TestCase
{
    private function mockAuthService()
    {
        $mock = Mockery::mock(AuthService::class);
        $mock->shouldReceive('login')->andReturnUsing(function ($identifier, $password, $major = null, $deviceType = 'mobile') {
            return [
                'token' => 'dummy-token',
                'user' => [
                    'id' => 1,
                    'name' => 'John Doe',
                    'email' => filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $identifier : null,
                    'phone' => filter_var($identifier, FILTER_VALIDATE_EMAIL) ? null : $identifier,
                    'image' => null,
                ],
            ];
        });
        $mock->shouldReceive('register')->andReturnUsing(function ($data) {
            return [
                'token' => 'dummy-token',
                'user' => [
                    'id' => 1,
                    'name' => $data['name'] ?? 'John Doe',
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'image' => null,
                ],
            ];
        });
        $this->app->instance(AuthService::class, $mock);
    }

    public function test_login_with_email_only_succeeds()
    {
        $this->mockAuthService();
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_login_with_phone_only_succeeds()
    {
        $this->mockAuthService();
        $response = $this->postJson('/api/auth/login', [
            'phone' => '0912345678',
            'password' => 'secret123',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_login_without_identifier_fails()
    {
        $this->mockAuthService();
        $response = $this->postJson('/api/auth/login', [
            'password' => 'secret123',
        ]);
        $response->assertStatus(400)->assertJson(['success' => false]);
    }

    public function test_register_with_email_only_succeeds()
    {
        $this->mockAuthService();
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_register_with_phone_only_succeeds()
    {
        $this->mockAuthService();
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane',
            'phone' => '0912345678',
            'password' => 'secret123',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_register_without_phone_or_email_fails()
    {
        $this->mockAuthService();
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane',
            'password' => 'secret123',
        ]);
        $response->assertStatus(400)->assertJson(['success' => false]);
    }
}
