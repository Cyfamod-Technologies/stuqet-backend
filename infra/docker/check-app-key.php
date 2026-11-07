<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Encryption\Encrypter;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$key = (string) config('app.key');
if (str_starts_with($key, 'base64:')) {
    $key = base64_decode(substr($key, 7), true);
}

if (!is_string($key) || !Encrypter::supported($key, (string) config('app.cipher'))) {
    fwrite(STDERR, "APP_KEY is missing or invalid. Set a persistent APP_KEY in the deployment environment before starting the application. Keep the existing key for an existing installation.\n");
    exit(1);
}

echo "Application key is configured.\n";
