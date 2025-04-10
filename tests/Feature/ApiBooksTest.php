<?php
// tests/Feature/ApiBooksTest.php

namespace tests\Feature;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client; // Using Guzzle for HTTP requests (install via composer require guzzlehttp/guzzle)
use GuzzleHttp\Cookie\CookieJar;


class ApiBooksTest extends TestCase
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
            'verify' => false // Disable SSL verification for local testing if needed
        ]);

        // Perform login to get session cookie (Replace with actual login details)
        // This step is crucial as most API actions require authentication.
        self::login('z@a.com', 'Zainsali123$'); 
    }

    protected static function login(string $email, string $password): void
    {
        // Assuming signin.php sets session cookies upon successful POST
        self::$client->post('signin.php', [
            'form_params' => [
                'email' => $email,
                'password' => $password
            ]
        ]);
    }

    /**
     * Test adding a book via the API.
     */
    public function test_can_add_book_via_api(): void
    {
        $bookData = [
            'action' => 'add',
            'title' => 'PHPUnit Test Book ' . uniqid(),
            'author' => 'PHPUnit',
            'description' => 'A book added via automated testing.',
            'isbn' => '1234567890123',
            'year' => date('Y'),
            'genre' => 'Testing',
            'rating' => 4.5
        ];

        $response = self::$client->post('api_books.php', [
            'json' => $bookData // Send data as JSON
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        // Assert that the request was successful (HTTP 200 OK)
        $this->assertEquals(200, $statusCode, "API response status code was not 200. Body: " . json_encode($body));

        // Assert the API returned a success status in its JSON response
        $this->assertIsArray($body, "API response is not valid JSON.");
        $this->assertEquals('success', $body['status'] ?? 'error', "API did not return a success status. Message: " . ($body['message'] ?? 'N/A'));

        // Assert that a book ID was returned
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('book_id', $body['data']);
        $this->assertIsNumeric($body['data']['book_id']);

    }

    public function test_can_fetch_books_from_catalog_api(): void
    {
         $response = self::$client->get('api_books.php?page=1&limit=5');

         $statusCode = $response->getStatusCode();
         $body = json_decode((string) $response->getBody(), true);

         $this->assertEquals(200, $statusCode, "API response status code was not 200.");
         $this->assertIsArray($body, "API response is not valid JSON.");
         $this->assertEquals('success', $body['status'] ?? 'error', "API did not return a success status.");
         $this->assertArrayHasKey('data', $body);
         $this->assertIsArray($body['data']);
    }


}