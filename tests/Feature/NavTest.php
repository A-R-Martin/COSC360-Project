<?php

namespace tests\Feature;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class NavTest extends TestCase
{
    protected static $client;
    protected static $cookieJar;

    public static function setUpBeforeClass(): void
    {
        $baseUri = 'http://localhost/COSC360-Project/';

        self::$cookieJar = new CookieJar();
        self::$client = new Client([
            'base_uri' => $baseUri,
            'cookies' => self::$cookieJar,
            'http_errors' => false,
            'verify' => false
        ]);
    }

    public function test_guest_sees_signin_and_signup_links(): void
    {
        $response = self::$client->get('index.php');
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Sign In', $body);
        $this->assertStringContainsString('Sign Up', $body);
        $this->assertStringNotContainsString('Logout', $body);
    }

    public function test_logged_in_user_sees_profile_and_logout_links(): void
    {
        // Simulate login by setting session cookie
        self::$client->post('signin.php', [
            'form_params' => [
                'email' => 'user@example.com',
                'password' => 'password123'
            ]
        ]);

        $response = self::$client->get('index.php');
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Profile', $body);
        $this->assertStringContainsString('Logout', $body);
        $this->assertStringNotContainsString('Sign In', $body);
    }

    public function test_admin_sees_admin_and_analytics_links(): void
    {
        // Simulate admin login by setting session cookie
        self::$client->post('signin.php', [
            'form_params' => [
                'email' => 'z@a.com',
                'password' => 'Zainsali123$'
            ]
        ]);

        $response = self::$client->get('index.php');
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Admin', $body);
        $this->assertStringContainsString('Analytics', $body);
    }
}