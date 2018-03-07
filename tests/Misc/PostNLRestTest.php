<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2017-2018 Thirty Development, LLC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 * associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software
 * is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
 * NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author    Michael Dekker <michael@thirtybees.com>
 * @copyright 2017-2018 Thirty Development, LLC
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace ThirtyBees\PostNL\Tests\Misc;

use ThirtyBees\PostNL\Entity\Address;
use ThirtyBees\PostNL\Entity\Customer;
use ThirtyBees\PostNL\Entity\SOAP\UsernameToken;
use ThirtyBees\PostNL\PostNL;

/**
 * Class PostNLRestTest
 *
 * @package ThirtyBees\PostNL\Tests\Misc
 *
 * @testdox The PostNL object
 */
class PostNLRestTest extends \PHPUnit_Framework_TestCase
{
    /** @var PostNL $postnl */
    protected $postnl;

    /**
     * @before
     * @throws \ThirtyBees\PostNL\Exception\InvalidArgumentException
     */
    public function setupPostNL()
    {
        $this->postnl = new PostNL(
            Customer::create()
                ->setCollectionLocation('123456')
                ->setCustomerCode('DEVC')
                ->setCustomerNumber('11223344')
                ->setContactPerson('Test')
                ->setAddress(Address::create([
                    'AddressType' => '02',
                    'City'        => 'Hoofddorp',
                    'CompanyName' => 'PostNL',
                    'Countrycode' => 'NL',
                    'HouseNr'     => '42',
                    'Street'      => 'Siriusdreef',
                    'Zipcode'     => '2132WT',
                ]))
                ->setGlobalPackBarcodeType('AB')
                ->setGlobalPackCustomerCode('1234')
            , new UsernameToken(null, 'test'),
            true,
            PostNL::MODE_REST
        );
    }

    /**
     * @testdox returns a valid customer code in REST mode
     */
    public function testPostNLRest()
    {
        $this->assertEquals('DEVC', $this->postnl->getCustomer()->getCustomerCode());
    }

    /**
     * @testdox returns a valid customer
     */
    public function testCustomer()
    {
        $this->assertInstanceOf('\\ThirtyBees\\PostNL\\Entity\\Customer', $this->postnl->getCustomer());
    }

    /**
     * @testdox accepts a string token
     *
     * @throws \ThirtyBees\PostNL\Exception\InvalidArgumentException
     */
    public function testSetTokenString()
    {
        $this->postnl->setToken('test');
        $this->assertInstanceOf('\\ThirtyBees\\PostNL\\Entity\\SOAP\\UsernameToken', $this->postnl->getToken());
    }

    /**
     * @testdox accepts a token object
     *
     * @throws \ThirtyBees\PostNL\Exception\InvalidArgumentException
     */
    public function testSetTokenObject()
    {
        $this->postnl->setToken(new UsernameToken(null, 'test'));
        $this->assertInstanceOf('\\ThirtyBees\\PostNL\\Entity\\SOAP\\UsernameToken', $this->postnl->getToken());
    }

    /**
     * @testdox accepts a `null` logger
     */
    public function testSetNullLogger()
    {
        $this->postnl->setLogger();

        $this->assertNull($this->postnl->getLogger());
    }

    /**
     * @testdox does not accept an invalid token object
     *
     * @throws \ThirtyBees\PostNL\Exception\InvalidArgumentException
     */
    public function testNegativeInvalidToken()
    {
        $this->expectException('\\ThirtyBees\\PostNL\\Exception\\InvalidArgumentException');
        $this->postnl->setToken(new Address());
    }

    /**
     * @testdox returns `false` when the API key is missing
     *
     * @throws \ReflectionException
     */
    public function testNegativeKeyMissing()
    {
        $reflection = new \ReflectionClass('\\ThirtyBees\\PostNL\\PostNL');
        /** @var PostNL $postnl */
        $postnl = $reflection->newInstanceWithoutConstructor();

        $this->assertFalse($postnl->getRestApiKey());
    }

    /**
     * @testdox throws an exception when setting an invalid mode
     *
     * @throws \ThirtyBees\PostNL\Exception\InvalidArgumentException
     */
    public function testNegativeInvalidMode()
    {
        $this->expectException('\\ThirtyBees\\PostNL\\Exception\\InvalidArgumentException');

        $this->postnl->setMode('invalid');
    }
}
