<?php

namespace tests\Feature;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class ApiUserBooksTest extends TestCase
{
    protected static $client;
    protected static $cookieJar;

    public static function setUpBeforeClass(): void
    {
        // Base URI for local development server
        $baseUri = 'http://localhost/COSC360-Project/';

        self::$cookieJar = new CookieJar();
        self::$client = new Client([
            'base_uri' => $baseUri,
            'cookies' => self::$cookieJar,
            'http_errors' => false, // Don't throw exceptions for 4xx/5xx responses
            'verify' => false // false for local testing
        ]);

        // Perform login to get session cookie 
        self::login('z@a.com', 'Zainsali123$');
    }

    protected static function login(string $email, string $password): void
    {
        self::$client->post('signin.php', [
            'form_params' => [
                'email' => $email,
                'password' => $password
            ]
        ]);
    }

    public function test_can_borrow_book(): void
    {
        $bookId = 1; // Replace with a valid book ID from your database

        $response = self::$client->post('api_user_books.php', [
            'json' => [
                'action' => 'borrow',
                'book_id' => $bookId
            ]
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode, "API response status code was not 200.");
        $this->assertIsArray($body, "API response is not valid JSON.");
        $this->assertEquals('success', $body['status'] ?? 'error', "API did not return a success status.");
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('borrow_date', $body['data']);
        $this->assertArrayHasKey('return_date', $body['data']);
    }

    public function test_can_reserve_book(): void
    {
        $bookId = 2; // MOck with a real book ID that is available for reservation

        $response = self::$client->post('api_user_books.php', [
            'json' => [
                'action' => 'reserve',
                'book_id' => $bookId
            ]
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode, "API response status code was not 200.");
        $this->assertIsArray($body, "API response is not valid JSON.");
        $this->assertEquals('success', $body['status'] ?? 'error', "API did not return a success status.");
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('reserve_date', $body['data']);
    }

    public function test_can_return_book(): void
    {
        $bookId = 1; // Mock with real book ID that is borrowed

        $response = self::$client->post('api_user_books.php', [
            'json' => [
                'action' => 'return',
                'book_id' => $bookId
            ]
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode, "API response status code was not 200.");
        $this->assertIsArray($body, "API response is not valid JSON.");
        $this->assertEquals('success', $body['status'] ?? 'error', "API did not return a success status.");
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('return_date', $body['data']);
    }

    public function test_can_cancel_reservation(): void
    {
        $bookId = 2; // Mock using a book ID that is actually reserved

        $response = self::$client->post('api_user_books.php', [
            'json' => [
                'action' => 'cancel_reservation',
                'book_id' => $bookId
            ]
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode, "API response status code was not 200.");
        $this->assertIsArray($body, "API response is not valid JSON.");
        $this->assertEquals('success', $body['status'] ?? 'error', "API did not return a success status.");
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('book_id', $body['data']);
    }

    public function test_can_fetch_borrowed_books(): void
    {
        $response = self::$client->get('api_user_books.php?action=borrowed');

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode, "API response status code was not 200.");
        $this->assertIsArray($body, "API response is not valid JSON.");
        $this->assertEquals('success', $body['status'] ?? 'error', "API did not return a success status.");
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }

    public function test_can_fetch_reserved_books(): void
    {
        $response = self::$client->get('api_user_books.php?action=reserved');

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode, "API response status code was not 200.");
        $this->assertIsArray($body, "API response is not valid JSON.");
        $this->assertEquals('success', $body['status'] ?? 'error', "API did not return a success status.");
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }
}