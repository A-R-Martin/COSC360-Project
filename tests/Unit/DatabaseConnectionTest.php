<?php

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;

class DatabaseConnectionTest extends TestCase
{
    public function test_database_connection(): void
    {
        require 'c:\xampp\htdocs\COSC360-Project\db_connect.php';
        $this->assertInstanceOf(PDO::class, $conn);
    }
}