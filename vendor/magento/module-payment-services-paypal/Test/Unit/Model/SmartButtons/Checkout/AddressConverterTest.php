<?php

/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

declare(strict_types=1);

namespace Magento\PaymentServicesPaypal\Test\Unit\Model\SmartButtons\Checkout;

use Magento\PaymentServicesPaypal\Model\SmartButtons\Checkout\AddressConverter;
use PHPUnit\Framework\TestCase;

class AddressConverterTest extends TestCase
{
    /**
     * @var AddressConverter
     */
    private $addressConverter;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->addressConverter = new AddressConverter();
    }

    /**
     * @return void
     */
    public function testConvertShippingAddress(): void
    {
        $order = [
            'paypal-order' => [
                'payer' => [
                    'email' => 'test@test.com',
                    'phone_number' => '1234567',
                ],
                'shipping-address' => [
                    'address_line_1' => 'street 1',
                    'address_line_2' => 'street 2',
                    'postal_code' => '08005',
                    'admin_area_1' => 'region',
                    'admin_area_2' => 'city',
                    'country_code' => 'ESP',
                    'full_name' => 'John Doe',
                ],
            ],
        ];

        $address = $this->addressConverter->convertShippingAddress($order);

        $expected = [
            'street' => [
                0 => 'street 1',
                1 => 'street 2',
             ],
            'postcode' => '08005',
            'region' => 'region',
            'region_id' => '',
            'city' => 'city',
            'country_id' => 'ESP',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'telephone' => '1234567',
            'email' => 'test@test.com',
        ];

        $this->assertEquals($expected, $address);
    }

    /**
     * @return void
     */
    public function testConvertBillingAddress(): void
    {
        $order = [
            'paypal-order' => [
                'payer' => [
                    'name' => [
                        'given_name' => 'John',
                        'surname' => 'Doe',
                    ],
                    'email' => 'test@test.com',
                    'phone_number' => '2345678',
                ],
                'billing-address' => [
                    'address_line_1' => 'street 1',
                    'address_line_2' => 'street 2',
                    'postal_code' => '08005',
                    'admin_area_1' => 'region',
                    'admin_area_2' => 'city',
                    'country_code' => 'ESP',
                    'full_name' => 'John Doe',
                ],
            ],
        ];

        $address = $this->addressConverter->convertBillingAddress($order);

        $expected = [
            'street' => [
                0 => 'street 1',
                1 => 'street 2',
            ],
            'postcode' => '08005',
            'region' => 'region',
            'region_id' => '',
            'city' => 'city',
            'country_id' => 'ESP',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'telephone' => '2345678',
            'email' => 'test@test.com',
        ];

        $this->assertEquals($expected, $address);
    }
}
