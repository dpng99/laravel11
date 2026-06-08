<?php

return [
    'python_binary' => env(
        'BI_PYTHON_BINARY',
        PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'
    ),
    'timeout' => (int) env('BI_TIMEOUT', 60),
];
