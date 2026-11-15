<?php

namespace App\Services\Payments;

interface PaymentGateway
{
    public function charge(string $operator, string $phone, int $amount): PaymentResult;
}