<?php

namespace App\Service;

use App\Entity\Produit;
use App\Entity\ShopAutomationEvent;
use App\Repository\ProduitRepository;
use App\Repository\ShopAutomationEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ShopAutomationService
{
    public const TYPE_RESTOCK = 'restock';
    public const TYPE_STOCK_ALERT = 'stock_alert';
    public const TYPE_STOCK_ZERO = 'stock_zero';
    public const STATUS_DONE = 'done';

    private int $stockThreshold = 10;
    private int $restockAmount = 60;
    private int $cooldownHours = 24;

    public function __construct(
        private ProduitRepository $produitRepository,
        private ShopAutomationEventRepository $eventRepository,
        private EntityManagerInterface $em,
        private MailerInterface $mailer
    ) {}

    public function runStockReplenishment(): array
    {
        $cutoff = new \DateTimeImmutable(sprintf('-%d hours', $this->cooldownHours));

        $products = $this->produitRepository->createQueryBuilder('p')
            ->andWhere('p.quantite_disponible <= :threshold')
            ->setParameter('threshold', $this->stockThreshold)
            ->getQuery()
            ->getResult();

        $processed = 0;
        $skipped = 0;

        foreach ($products as $product) {
            if ($this->eventRepository->hasRecentEvent($product, self::TYPE_RESTOCK, $cutoff)) {
                $skipped++;
                continue;
            }

            $before = (int) $product->getQuantiteDisponible();
            $after = $before + $this->restockAmount;

            $product->setQuantiteDisponible($after);

            $event = (new ShopAutomationEvent())
                ->setType(self::TYPE_RESTOCK)
                ->setStatus(self::STATUS_DONE)
                ->setProduct($product)
                ->setProcessedAt(new \DateTimeImmutable())
                ->setPayload([
                    'before_stock' => $before,
                    'after_stock' => $after,
                    'threshold' => $this->stockThreshold,
                    'restock_amount' => $this->restockAmount,
                ]);

            $this->em->persist($event);
            $processed++;
        }

        if ($processed > 0) {
            $this->em->flush();
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'threshold' => $this->stockThreshold,
            'restock_amount' => $this->restockAmount,
        ];
    }

    public function runStockAlerts(): array
    {
        $cutoff = new \DateTimeImmutable(sprintf('-%d hours', $this->cooldownHours));

        $products = $this->produitRepository->createQueryBuilder('p')
            ->andWhere('p.quantite_disponible <= :threshold')
            ->setParameter('threshold', $this->stockThreshold)
            ->getQuery()
            ->getResult();

        $created = 0;
        $skipped = 0;

        foreach ($products as $product) {
            if ($this->eventRepository->hasRecentEvent($product, self::TYPE_STOCK_ALERT, $cutoff)) {
                $skipped++;
                continue;
            }

            $event = (new ShopAutomationEvent())
                ->setType(self::TYPE_STOCK_ALERT)
                ->setStatus(self::STATUS_DONE)
                ->setProduct($product)
                ->setProcessedAt(new \DateTimeImmutable())
                ->setPayload([
                    'stock' => (int) $product->getQuantiteDisponible(),
                    'threshold' => $this->stockThreshold,
                ]);

            $this->em->persist($event);
            $created++;
        }

        if ($created > 0) {
            $this->em->flush();
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'threshold' => $this->stockThreshold,
        ];
    }

    public function runStockZeroEmails(): array
    {
        $cutoff = new \DateTimeImmutable(sprintf('-%d hours', $this->cooldownHours));

        $products = $this->produitRepository->createQueryBuilder('p')
            ->andWhere('p.quantite_disponible = 0')
            ->getQuery()
            ->getResult();

        $sent = 0;
        $skipped = 0;

        foreach ($products as $product) {
            if ($this->sendStockZeroEmailForProduct($product, $cutoff) === true) {
                $sent++;
            } else {
                $skipped++;
            }
        }

        if ($sent > 0) {
            $this->em->flush();
        }

        return [
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }

    public function sendStockZeroEmailForProduct(Produit $product, ?\DateTimeImmutable $cutoff = null): bool
    {
        $cutoff = $cutoff ?? new \DateTimeImmutable(sprintf('-%d hours', $this->cooldownHours));

        if ($this->eventRepository->hasRecentEvent($product, self::TYPE_STOCK_ZERO, $cutoff)) {
            return false;
        }

        $recipient = null;
        if ($product->getCreatedBy() !== null) {
            $recipient = $product->getCreatedBy()->getEmail();
        } elseif ($product->getCreatorEmail() !== null) {
            $recipient = $product->getCreatorEmail();
        }

        if ($recipient === null || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $productName = $product->getNomProduit();
        $restockMax = $this->restockAmount;

        $email = (new Email())
            ->from('salahchebil123@gmail.com')
            ->to($recipient)
            ->subject('Alerte stock epuise - ' . $productName)
            ->text(
                "Bonjour,\n\n" .
                "Le stock du produit \"{$productName}\" est a zero.\n" .
                "Reappro max suggere: {$restockMax} unites.\n" .
                "Merci de reapprovisionner ce produit.\n"
            )
            ->html(
                '<!DOCTYPE html>' .
                '<html lang="fr">' .
                '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>' .
                '<body style="margin:0;padding:0;background:#0B0F0E;color:#E9FFF7;font-family:Arial, sans-serif;">' .
                '<div style="max-width:620px;margin:0 auto;padding:32px 20px;">' .
                '<div style="background:#121A19;border:1px solid #22302E;border-radius:16px;overflow:hidden;">' .
                '<div style="padding:24px 28px;background:linear-gradient(135deg,#0E1514,#14201E);">' .
                '<div style="font-size:20px;font-weight:700;color:#20E3B2;">Midgar Shop</div>' .
                '<div style="font-size:14px;color:#8EA39E;margin-top:4px;">Alerte stock epuise</div>' .
                '</div>' .
                '<div style="padding:28px;">' .
                '<h1 style="margin:0 0 12px 0;font-size:20px;">Stock a zero</h1>' .
                '<p style="margin:0 0 12px 0;color:#B7C8C2;line-height:1.6;">Le produit <strong>' . htmlspecialchars($productName, ENT_QUOTES) . '</strong> n\'a plus de stock.</p>' .
                '<div style="background:#0F1615;border:1px solid #22302E;border-radius:12px;padding:16px;">' .
                '<div style="display:flex;justify-content:space-between;">' .
                '<span style="color:#8EA39E;">Reappro max suggere</span>' .
                '<span style="font-weight:700;color:#20E3B2;">' . $restockMax . ' unites</span>' .
                '</div>' .
                '</div>' .
                '<p style="margin:16px 0 0 0;color:#B7C8C2;">Vous pouvez relancer la production ou le fournisseur depuis l\'admin.</p>' .
                '</div>' .
                '<div style="padding:18px 28px;border-top:1px solid #22302E;color:#8EA39E;font-size:12px;">' .
                'Email automatique - Midgar Shop.' .
                '</div>' .
                '</div>' .
                '</div>' .
                '</body></html>'
            );

        try {
            $this->mailer->send($email);

            $event = (new ShopAutomationEvent())
                ->setType(self::TYPE_STOCK_ZERO)
                ->setStatus(self::STATUS_DONE)
                ->setProduct($product)
                ->setProcessedAt(new \DateTimeImmutable())
                ->setPayload([
                    'stock' => 0,
                    'recipient' => $recipient,
                    'restock_max' => $restockMax,
                ]);

            $this->em->persist($event);
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }
}
