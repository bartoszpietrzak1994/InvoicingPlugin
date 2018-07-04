<?php

declare(strict_types=1);

namespace Sylius\InvoicingPlugin\Email;

use Knp\Snappy\GeneratorInterface;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\InvoicingPlugin\Entity\InvoiceInterface;
use Sylius\InvoicingPlugin\File\TemporaryFilePathGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;

final class InvoiceEmailSender implements InvoiceEmailSenderInterface
{
    /** @var SenderInterface */
    private $emailSender;

    /** @var GeneratorInterface */
    private $pdfGenerator;

    /** @var EngineInterface */
    private $templatingEngine;

    /** @var TemporaryFilePathGeneratorInterface */
    private $temporaryFilePathGenerator;

    public function __construct(
        SenderInterface $emailSender,
        GeneratorInterface $pdfGenerator,
        EngineInterface $templatingEngine,
        TemporaryFilePathGeneratorInterface $temporaryFilePathGenerator
    ) {
        $this->emailSender = $emailSender;
        $this->pdfGenerator = $pdfGenerator;
        $this->templatingEngine = $templatingEngine;
        $this->temporaryFilePathGenerator = $temporaryFilePathGenerator;
    }

    public function sendInvoiceEmail(
        InvoiceInterface $invoice,
        string $customerEmail
    ): void {
        $filePath = $this->temporaryFilePathGenerator->generate('invoice-%s.pdf', $invoice->id());

        $this->pdfGenerator->generateFromHtml(
            $this->templatingEngine->render('@SyliusInvoicingPlugin/Resources/views/Invoice/Download/pdf.html.twig', [
                'invoice' => $invoice
            ]),
            $filePath
        );

        $this->emailSender->send(Emails::INVOICE_GENERATED, [$customerEmail], ['invoice' => $invoice], [$filePath]);

        $this->temporaryFilePathGenerator->removeFile($filePath);
    }
}
