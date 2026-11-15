<?php

namespace App\Services\Payments;

use Illuminate\Support\Str;

class SimulatedPaymentGateway implements PaymentGateway
{
    public function charge(string $operator, string $phone, int $amount): PaymentResult
    {
        // Simulation : un numéro qui se termine par 0 est refusé.
        if (str_ends_with($phone, '0')) {
            return new PaymentResult(false, null, 'Paiement refusé par l’opérateur.');
        }

        return new PaymentResult(true, 'SIM-'.strtoupper(Str::random(10)));
    }
}