<?php

namespace App\Services\Payment;

use App\Exceptions\Payment\PaymentGatewayException;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\NullPaymentGateway;
use App\Services\Payment\Gateways\RazorpayPaymentGateway;
use Closure;
use Illuminate\Contracts\Foundation\Application;

class PaymentGatewayManager
{
    /**
     * The resolved gateway instances.
     *
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $gateways = [];

    /**
     * Registered custom gateway creators.
     *
     * @var array<string, Closure>
     */
    protected array $customCreators = [];

    public function __construct(
        protected Application $app
    ) {}

    /**
     * Get a payment gateway instance by name.
     */
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        if (! isset($this->gateways[$name])) {
            $this->gateways[$name] = $this->resolve($name);
        }

        return $this->gateways[$name];
    }

    /**
     * Resolve the given gateway.
     *
     * @throws PaymentGatewayException
     */
    protected function resolve(string $name): PaymentGatewayInterface
    {
        if (isset($this->customCreators[$name])) {
            return $this->customCreators[$name]($this->app);
        }

        return match ($name) {
            'null' => $this->app->make(NullPaymentGateway::class),
            'razorpay' => $this->app->make(RazorpayPaymentGateway::class),
            default => throw new PaymentGatewayException(
                message: "Unsupported payment gateway driver: [{$name}].",
                gateway: $name
            ),
        };
    }

    /**
     * Register a custom gateway creator Closure.
     */
    public function extend(string $driver, Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get the default gateway driver name.
     */
    public function getDefaultDriver(): string
    {
        return (string) config('payment.default', 'null');
    }
}
