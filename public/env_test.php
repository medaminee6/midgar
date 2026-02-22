<?php
// public/env_test.php
echo "<h3>Test Environnement</h3>";
echo "getenv('MAILER_DSN'): " . (getenv('MAILER_DSN') ?: 'NON DEFINI') . "<br>";
echo "\$_ENV['MAILER_DSN']: " . ($_ENV['MAILER_DSN'] ?? 'NON DEFINI') . "<br>";
echo "\$_SERVER['MAILER_DSN']: " . ($_SERVER['MAILER_DSN'] ?? 'NON DEFINI') . "<br>";
phpinfo(INFO_ENVIRONMENT);