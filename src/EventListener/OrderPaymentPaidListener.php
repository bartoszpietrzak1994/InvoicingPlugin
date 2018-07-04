<?php

declare(strict_types=1);

namespace Sylius\InvoicingPlugin\EventListener;

use Knp\Snappy\GeneratorInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\InvoicingPlugin\Email\InvoiceEmailSenderInterface;
use Sylius\InvoicingPlugin\Entity\InvoiceInterface;
use Sylius\InvoicingPlugin\Event\OrderPaymentPaid;
use Sylius\InvoicingPlugin\Repository\InvoiceRepository;
use Symfony\Component\Templating\EngineInterface;

final class OrderPaymentPaidListener
{
    /** @var InvoiceRepository */
    private $invoiceRepository;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var GeneratorInterface */
    private $pdfGenerator;

    /** @var InvoiceEmailSenderInterface */
    private $emailSender;

    /** @var EngineInterface */
    private $templatingEngine;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        OrderRepositoryInterface $orderRepository,
        GeneratorInterface $pdfGenerator,
        InvoiceEmailSenderInterface $emailSender,
        EngineInterface $templatingEngine
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->pdfGenerator = $pdfGenerator;
        $this->emailSender = $emailSender;
        $this->orderRepository = $orderRepository;
        $this->templatingEngine = $templatingEngine;
    }

    public function __invoke(OrderPaymentPaid $event)
    {
        /** @var InvoiceInterface $invoice */
        $invoice = $this->invoiceRepository->findOneByOrderNumber($event->orderNumber());

        $filePath = $this->generateTemporaryPdfFilePathBasedOnInvoiceId($invoice->id());

        $this->pdfGenerator->generateFromHtml(
            $this->templatingEngine->render('@SyliusInvoicingPlugin/Resources/views/Invoice/Download/pdf.html.twig', [
                'invoice' => $invoice
            ]),
            $filePath
        );

        /** @var OrderInterface $order */
        $order = $this->orderRepository->findOneBy(['number' => $event->orderNumber()]);

        if (null === $order->getCustomer()) {
            return;
        }

        $this->emailSender->sendInvoiceEmail($invoice, $filePath, $order->getCustomer()->getEmail());

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
