<?php

namespace App\Gateways\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentTransaction\Provider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @return void
     */
    public function __construct(
        private Container $container,
        private ConfigRepository $config,
    ) {}

    public function driver(int|Provider $provider): PaymentGateway
    {
        $provider = $provider instanceof Provider ? $provider : Provider::tryFrom($provider);
        $gatewayClass = $provider instanceof Provider
            ? $this->config->get("services.payment.gateways.{$provider->value}")
            : null;

        if (! is_string($gatewayClass)) {
            throw new InvalidArgumentException('Unsupported payment provider.');
        }

        $gateway = $this->container->make($gatewayClass);

        if (! $gateway instanceof PaymentGateway) {
            throw new InvalidArgumentException('Payment gateway must implement PaymentGateway.');
        }

        return $gateway;
    }
}