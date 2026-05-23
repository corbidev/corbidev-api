# EXEMPLES SYMFONY

## Controller

```php
final class LoginController
{
    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->loginHandler->handle($request->toArray());

        return new JsonResponse($result);
    }
}
```

## Application

```php
final class LoginHandler
{
    public function handle(array $data): array
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user || !$this->passwordHasher->verify($data['password'], $user->password())) {
            throw new InvalidCredentialsException();
        }

        return $this->jwtGenerator->generate($user);
    }
}
```
