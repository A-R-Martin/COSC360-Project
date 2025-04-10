<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class ApiTrackingTest extends TestCase
{
    protected static $client;
    protected static $cookieJar;

    public static function setUpBeforeClass(): void
    {
        $baseUri = 'http://localhost/COSC360-Project/'; // Adjust the base URI if needed
        self::$cookieJar = new CookieJar();
        self::$client = new Client([
            'base_uri' => $baseUri,
            'cookies' => self::$cookieJar,
            'http_errors' => false,
            'verify' => false,
        ]);

        self::login('z@a.com', 'Zainsali123$'); // Assuming this user exists for testing
    }

    protected static function login(string $email, string $password): void
    {
        self::$client->post('signin.php', [
            'form_params' => [
                'email' => $email,
                'password' => $password,
            ],
        ]);
    }

    public function test_log_event_page_view_success(): void
    {
        $response = self::$client->post('api_tracking.php', [
            'query' => ['action' => 'log_event'],
            'json' => [
                'event_type' => 'page_view',
                'event_data' => json_encode(['page' => '/test_page', 'referrer' => '/test_referrer']),
            ],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertEquals('Event logged successfully', $body['message'] ?? '', "Message should indicate success");
    }

    public function test_log_event_api_call_success(): void
    {
        $response = self::$client->post('api_tracking.php', [
            'query' => ['action' => 'log_event'],
            'json' => [
                'event_type' => 'api_call',
                'event_data' => json_encode(['endpoint' => '/test_api', 'method' => 'GET', 'response_time' => 100, 'status' => 200]),
            ],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertEquals('Event logged successfully', $body['message'] ?? '', "Message should indicate success");
    }

    public function test_log_event_activity_log_success(): void
    {
        $response = self::$client->post('api_tracking.php', [
            'query' => ['action' => 'log_event'],
            'json' => [
                'event_type' => 'test_event',
                'event_data' => 'test_data',
            ],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertEquals('Event logged successfully', $body['message'] ?? '', "Message should indicate success");
    }

    public function test_get_page_views_returns_data(): void
    {
        $response = self::$client->get('api_tracking.php', [
            'query' => ['action' => 'get_page_views', 'period' => 'day'],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertArrayHasKey('data', $body, 'Response should contain data key');
        $this->assertIsArray($body['data'], 'Data should be an array');
    }

    public function test_get_user_activity_returns_data(): void
    {
        $response = self::$client->get('api_tracking.php', [
            'query' => ['action' => 'get_user_activity', 'period' => 'week'],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertArrayHasKey('data', $body, 'Response should contain data key');
        $this->assertIsArray($body['data'], 'Data should be an array');
    }

    public function test_get_api_usage_returns_data(): void
    {
        $response = self::$client->get('api_tracking.php', [
            'query' => ['action' => 'get_api_usage', 'period' => 'month'],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertArrayHasKey('data', $body, 'Response should contain data key');
        $this->assertIsArray($body['data'], 'Data should be an array');
    }

    public function test_get_analytics_dashboard_returns_data(): void
    {
        $response = self::$client->get('api_tracking.php', [
            'query' => ['action' => 'get_analytics_dashboard'],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertArrayHasKey('data', $body, 'Response should contain data key');
        $this->assertIsArray($body['data'], 'Data should be an array');
    }

    public function test_get_book_status_returns_data(): void
    {
        $response = self::$client->get('api_tracking.php', [
            'query' => ['action' => 'get_book_status'],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertArrayHasKey('data', $body, 'Response should contain data key');
        $this->assertIsArray($body['data'], 'Data should be an array');
    }

    public function test_invalid_action_returns_error(): void
    {
        $response = self::$client->get('api_tracking.php', [
            'query' => ['action' => 'invalid_action'],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('error', $body['status'] ?? 'success', "API should return an error for invalid action");
        $this->assertEquals('Invalid action', $body['message'] ?? '', "Message should indicate invalid action");
    }
}