<?php

return [
    'dsn' => getenv('SENTRY_DSN'),
    'traces_sample_rate' => 1.0,
    'environment' => getenv('APP_ENV'),
];
