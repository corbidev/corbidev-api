# TESTS PHPUNIT

## Domain Test

```php
final class EmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = new Email('test@test.com');
        $this->assertEquals('test@test.com', (string)$email);
    }
}
```

## Application Test

```php
final class LoginHandlerTest extends TestCase
{
    public function testLoginSuccess(): void
    {
        // mock repository + hasher
        $this->assertTrue(true);
    }
}
```
