<?php

declare(strict_types=1);

namespace spec\Sylius\InvoicingPlugin\Email;

use Knp\Snappy\GeneratorInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\InvoicingPlugin\Email\Emails;
use Sylius\InvoicingPlugin\Email\InvoiceEmailSender;
use Sylius\InvoicingPlugin\Email\InvoiceEmailSenderInterface;
use Sylius\InvoicingPlugin\Entity\InvoiceInterface;
use Sylius\InvoicingPlugin\File\TemporaryFilePathGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

final class InvoiceEmailSenderSpec extends ObjectBehavior
{
    public function let(
        SenderInterface $sender,
        GeneratorInterface $pdfGenerator,
        EngineInterface $templatingEngine,
        TemporaryFilePathGeneratorInterface $temporaryFilePathGenerator
    ) : void {
        $this->beConstructedWith($sender, $pdfGenerator, $templatingEngine, $temporaryFilePathGenerator);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(InvoiceEmailSender::class);
    }

    public function it_implements_invoice_email_sender_interface(): void
    {
        $this->shouldImplement(InvoiceEmailSenderInterface::class);
    }

    public function it_sends_an_invoice_to_a_given_email_address(
        EngineInterface $templatingEngine,
        GeneratorInterface $pdfGenerator,
        InvoiceInterface $invoice,
        SenderInterface $sender,
        TemporaryFilePathGeneratorInterface $temporaryFilePathGenerator,
        TemplateReferenceInterface $templateReference
    ): void {
        $invoice->id()->willReturn('0000001');

        $temporaryFilePathGenerator->generate('invoice-%s.pdf', '0000001')->willReturn('test/path');

        $templatingEngine->render(
            '@SyliusInvoicingPlugin/Resources/views/Invoice/Download/pdf.html.twig', [
                'invoice' => $invoice
            ]
        )->willReturn($templateReference);

        $pdfGenerator->generateFromHtml($templateReference, 'test/path')->shouldBeCalled();

        $sender->send(
            Emails::INVOICE_GENERATED, ['sylius@example.com'], ['invoice' => $invoice], ['test/path']
        )->shouldBeCalled();

        $temporaryFilePathGenerator->removeFile('test/path')->shouldBeCalled();

        $this->sendInvoiceEmail($invoice, 'sylius@example.com');
    }
}
