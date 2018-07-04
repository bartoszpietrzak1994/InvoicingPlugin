<?php

declare(strict_types=1);

namespace Sylius\InvoicingPlugin\Email;

use Knp\Snappy\GeneratorInterface;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\InvoicingPlugin\Entity\InvoiceInterface;
use Symfony\Component\Templating\EngineInterface;

final class InvoiceEmailSender implements InvoiceEmailSenderInterface
{
    /** @var SenderInterface */
    private $emailSender;

    /** @var GeneratorInterface */
    private $pdfGenerator;

    /** @var EngineInterface */
    private $templatingEngine;

    public function __construct(
        SenderInterface $emailSender,
        GeneratorInterface $pdfGenerator,
        EngineInterface $templatingEngine
    ) {
        $this->emailSender = $emailSender;
        $this->pdfGenerator = $pdfGenerator;
        $this->templatingEngine = $templatingEngine;
    }

    public function sendInvoiceEmail(
        InvoiceInterface $invoice,
        string $customerEmail
    ): void {
        $filePath = $this->generateTemporaryPdfFilePathBasedOnInvoiceId($invoice->id());

        $this->pdfGenerator->generateFromHtml(
            $this->templatingEngine->render('@SyliusInvoicingPlugin/Resources/views/Invoice/Download/pdf.html.twig', [
                'invoice' => $invoice
            ]),
            $filePath
        );

        $this->emailSender->send(Emails::INVOICE_GENERATED, [$customerEmail], ['invoice' => $invoice], [$filePath]);

        $this->removeTemporaryPdfFile($filePath);
    }

    private function generateTemporaryPdfFilePathBasedOnInvoiceId(string $invoiceId): string
    {
        return sys_get_temp_dir() . '/' . sprintf('invoice-%s.pdf', $invoiceId);
    }

    private function removeTemporaryPdfFile(string $filePath): void
    {
        unlink($filePath);
    }
}
