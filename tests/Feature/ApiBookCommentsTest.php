<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class ApiBookCommentsTest extends TestCase
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
            'verify' => false,
        ]);

        self::login('z@a.com', 'Zainsali123$');
    }

    protected static function login(string $email, string $password): void
    {
        self::$client->post('signin.php', [
            'form_params' => [
                'email' => $email,
                'password' => $password,
            ]
        ]);
    }

    public function test_fetch_book_comments(): void
    {
        $response = self::$client->get('api_book_comments.php?book_id=1');
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertIsArray($body);
        $this->assertEquals('success', $body['status'] ?? 'error');
    }
}
