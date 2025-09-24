<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/test/create-user', 'POST', [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => 'password123'
]);

$request->headers->set('Content-Type', 'application/json');

$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getContent() . "\n";

$kernel->terminate($request, $response);
