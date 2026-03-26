<?php
namespace App\Chapter07_Sagas\Application;

final class OrderFulfillmentSaga
{
    /** @return string[] */
    public function execute(string $orderId, bool $stockAvailable, bool $paymentSuccess): array
    {
        $log = [];
        $compensations = [];

        if (!$stockAvailable) {
            $log[] = 'ReserveStock: FAILED (out of stock)';
            return $log;
        }
        $log[] = 'ReserveStock: OK';
        $compensations[] = function () use (&$log) {
            $log[] = 'ReserveStock: COMPENSATED (stock released)';
        };

        if (!$paymentSuccess) {
            $log[] = 'ProcessPayment: FAILED';
            foreach (array_reverse($compensations) as $compensate) {
                $compensate();
            }
            return $log;
        }
        $log[] = 'ProcessPayment: OK';
        $compensations[] = function () use (&$log) {
            $log[] = 'ProcessPayment: COMPENSATED (payment refunded)';
        };

        $log[] = 'ShipOrder: OK';
        return $log;
    }
}
