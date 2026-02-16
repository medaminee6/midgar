<?php
// test_mail_simple.php
require 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Récupérer le DSN directement depuis l'environnement
$dsn = getenv('MAILER_DSN');
if (!$dsn) {
    $dsn = $_ENV['MAILER_DSN'] ?? null;
}

if (!$dsn) {
    die("❌ MAILER_DSN non défini dans l'environnement\n");
}

echo "📧 DSN utilisé : " . $dsn . "\n";

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('zeinebsgh466@gmail.com')
        ->to('zeinebsgh466@gmail.com')
        ->subject('Test mail depuis console')
        ->text('Si vous lisez ceci, le mail fonctionne !');
    
    $mailer->send($email);
    echo "✅ Email envoyé avec succès !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Type d'erreur : " . get_class($e) . "\n";
}