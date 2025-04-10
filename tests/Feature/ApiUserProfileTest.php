<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class ApiUserProfileTest extends TestCase
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

    public function test_get_user_profile_returns_data(): void
    {
        $response = self::$client->get('api_user_profile.php', [
            'query' => ['action' => 'get_profile'],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertIsArray($body);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertArrayHasKey('data', $body, 'Response should contain data key');
        $this->assertArrayHasKey('username', $body['data'], 'Data should contain username');
        $this->assertArrayHasKey('email', $body['data'], 'Data should contain email');
        $this->assertArrayHasKey('comments', $body['data'], 'Data should contain comments');
    }

    public function test_update_user_profile_returns_success(): void
    {
        $response = self::$client->post('api_user_profile.php', [
            'form_params' => [
                'action' => 'update_profile',
                'username' => 'updated_user',
                'email' => 'updated@example.com',
                'bio' => 'Updated bio',
            ],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertEquals('Profile updated successfully', $body['message'] ?? '', "Message should indicate success");
        $this->assertEquals('updated_user', $body['data']['username'] ?? '', "Username should be updated");
        $this->assertEquals('updated@example.com', $body['data']['email'] ?? '', "Email should be updated");
        $this->assertEquals('Updated bio', $body['data']['bio'] ?? '', "Bio should be updated");
    }

    public function test_update_user_password_returns_success(): void
    {
        $response = self::$client->post('api_user_profile.php', [
            'form_params' => [
                'action' => 'update_password',
                'current_password' => 'Zainsali123$', // Use the correct current password
                'new_password' => 'NewPass@123',
                'confirm_password' => 'NewPass@123',
            ],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertEquals('Password updated successfully', $body['message'] ?? '', "Message should indicate success");
    }

    public function test_delete_user_profile_image_returns_success(): void
    {
        $response = self::$client->post('api_user_profile.php', [
            'form_params' => [
                'action' => 'delete_profile_image',
            ],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('success', $body['status'] ?? 'error', "API returned an error: " . json_encode($body));
        $this->assertEquals('Profile image removed successfully', $body['message'] ?? '', "Message should indicate success");
    }

    public function test_invalid_action_returns_error(): void
    {
        $response = self::$client->get('api_user_profile.php', [
            'query' => ['action' => 'invalid_action'],
        ]);
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $statusCode);
        $this->assertEquals('error', $body['status'] ?? 'success', "API should return an error for invalid action");
        $this->assertEquals('Invalid action', $body['message'] ?? '', "Message should indicate invalid action");
    }
}