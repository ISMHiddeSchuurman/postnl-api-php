<?php
/**
 * The MIT License (MIT).
 *
 * Copyright (c) 2017-2021 Michael Dekker
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
 * @author    Michael Dekker <git@michaeldekker.nl>
 * @copyright 2017-2021 Michael Dekker
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace ThirtyBees\PostNL\Tests\Service;

use Cache\Adapter\Void\VoidCachePool;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Message as PsrMessage;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ThirtyBees\PostNL\Entity\Address;
use ThirtyBees\PostNL\Entity\Customer;
use ThirtyBees\PostNL\Entity\Dimension;
use ThirtyBees\PostNL\Entity\Message\LabellingMessage;
use ThirtyBees\PostNL\Entity\Request\Confirming;
use ThirtyBees\PostNL\Entity\Response\ConfirmingResponseShipment;
use ThirtyBees\PostNL\Entity\Shipment;
use ThirtyBees\PostNL\Entity\SOAP\UsernameToken;
use ThirtyBees\PostNL\Entity\Warning;
use ThirtyBees\PostNL\Exception\ResponseException;
use ThirtyBees\PostNL\HttpClient\MockClient;
use ThirtyBees\PostNL\PostNL;
use ThirtyBees\PostNL\Service\ConfirmingService;
use ThirtyBees\PostNL\Service\ConfirmingServiceInterface;
use function file_get_contents;
use const _RESPONSES_DIR_;

/**
 * Class ConfirmingServiceRestTest.
 *
 * @testdox The ConfirmingService (REST)
 */
class ConfirmingServiceRestTest extends ServiceTest
{
    /** @var PostNL */
    protected $postnl;
    /** @var ConfirmingServiceInterface */
    protected $service;
    /** @var */
    protected $lastRequest;

    /**
     * @before
     *
     * @throws \ThirtyBees\PostNL\Exception\InvalidArgumentException
     * @throws ReflectionException
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
                ->setGlobalPackCustomerCode('1234'), new UsernameToken(null, 'test'),
            true,
            PostNL::MODE_REST
        );

        $this->service = $this->postnl->getConfirmingService();
        $this->service->cache = new VoidCachePool();
        $this->service->ttl = 1;
    }

    /**
     * @after
     */
    public function logPendingRequest()
    {
        if (!$this->lastRequest instanceof Request) {
            return;
        }

        global $logger;
        if ($logger instanceof LoggerInterface) {
            $logger->debug($this->getName()." Request\n".\GuzzleHttp\Psr7\str($this->lastRequest));
        }
        $this->lastRequest = null;
    }

    /**
     * @testdox returns a valid service object
     */
    public function testHasValidConfirmingService()
    {
        $this->assertInstanceOf(ConfirmingService::class, $this->service);
    }

    /**
     * @testdox confirms a label properly
     */
    public function testConfirmsALabelRequestRest()
    {
        $message = new LabellingMessage();

        $this->lastRequest = $request = $this->service->buildConfirmRequestREST(
            Confirming::create()
                ->setShipments([
                    Shipment::create()
                        ->setAddresses([
                            Address::create([
                                'AddressType' => '01',
                                'City'        => 'Utrecht',
                                'Countrycode' => 'NL',
                                'FirstName'   => 'Peter',
                                'HouseNr'     => '9',
                                'HouseNrExt'  => 'a bis',
                                'Name'        => 'de Ruijter',
                                'Street'      => 'Bilderdijkstraat',
                                'Zipcode'     => '3521VA',
                            ]),
                            Address::create([
                                'AddressType' => '02',
                                'City'        => 'Hoofddorp',
                                'CompanyName' => 'PostNL',
                                'Countrycode' => 'NL',
                                'HouseNr'     => '42',
                                'Street'      => 'Siriusdreef',
                                'Zipcode'     => '2132WT',
                            ]),
                        ])
                        ->setBarcode('3S1234567890123')
                        ->setDeliveryAddress('01')
                        ->setDimension(new Dimension('2000'))
                        ->setProductCodeDelivery('3085'),
                ])
                ->setMessage($message)
                ->setCustomer($this->postnl->getCustomer())
        );

        $this->assertEquals([
            'Customer' => [
                'Address' => [
                    'AddressType' => '02',
                    'City'        => 'Hoofddorp',
                    'CompanyName' => 'PostNL',
                    'Countrycode' => 'NL',
                    'HouseNr'     => '42',
                    'Street'      => 'Siriusdreef',
                    'Zipcode'     => '2132WT',
                ],
                'CollectionLocation' => '123456',
                'ContactPerson'      => 'Test',
                'CustomerCode'       => 'DEVC',
                'CustomerNumber'     => '11223344',
            ],
            'Message' => [
                'MessageID'        => (string) $message->getMessageID(),
                'MessageTimeStamp' => $message->getMessageTimeStamp()->format('d-m-Y H:i:s'),
                'Printertype'      => 'GraphicFile|PDF',
            ],
            'Shipments' => [
                'Addresses' => [
                    [
                        'AddressType' => '01',
                        'City'        => 'Utrecht',
                        'Countrycode' => 'NL',
                        'FirstName'   => 'Peter',
                        'HouseNr'     => '9',
                        'HouseNrExt'  => 'a bis',
                        'Name'        => 'de Ruijter',
                        'Street'      => 'Bilderdijkstraat',
                        'Zipcode'     => '3521VA',
                    ],
                    [
                        'AddressType' => '02',
                        'City'        => 'Hoofddorp',
                        'CompanyName' => 'PostNL',
                        'Countrycode' => 'NL',
                        'HouseNr'     => '42',
                        'Street'      => 'Siriusdreef',
                        'Zipcode'     => '2132WT',
                    ],
                ],
                'Barcode'         => '3S1234567890123',
                'DeliveryAddress' => '01',
                'Dimension'       => [
                    'Weight' => '2000',
                ],
                'ProductCodeDelivery' => '3085',
            ],
        ],
            json_decode((string) $request->getBody(), true));
        $this->assertEquals('test', $request->getHeaderLine('apikey'));
        $this->assertEquals('application/json;charset=UTF-8', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
    }

    /**
     * @testdox      can generate a single label
     * @dataProvider singleLabelConfirmationsProvider
     *
     * @param ResponseInterface
     *
     * @throws ReflectionException
     */
    public function testConfirmsALabelRest($response)
    {
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);
        $mockClient = new MockClient();
        $mockClient->setHandler($handler);
        $this->postnl->setHttpClient($mockClient);

        $confirm = $this->postnl->confirmShipment(
            (new Shipment())
                ->setAddresses([
                    Address::create([
                        'AddressType' => '01',
                        'City'        => 'Utrecht',
                        'Countrycode' => 'NL',
                        'FirstName'   => 'Peter',
                        'HouseNr'     => '9',
                        'HouseNrExt'  => 'a bis',
                        'Name'        => 'de Ruijter',
                        'Street'      => 'Bilderdijkstraat',
                        'Zipcode'     => '3521VA',
                    ]),
                    Address::create([
                        'AddressType' => '02',
                        'City'        => 'Hoofddorp',
                        'CompanyName' => 'PostNL',
                        'Countrycode' => 'NL',
                        'HouseNr'     => '42',
                        'Street'      => 'Siriusdreef',
                        'Zipcode'     => '2132WT',
                    ]),
                ])
                ->setBarcode('3SDEVC201611210')
                ->setDeliveryAddress('01')
                ->setDimension(new Dimension('2000'))
                ->setProductCodeDelivery('3085')
        );

        $this->assertInstanceOf(ConfirmingResponseShipment::class, $confirm);
        $this->assertEquals('3SDEVC201611210', $confirm->getBarcode());
        $this->assertInstanceOf(Warning::class, $confirm->getWarnings()[0]);
        $this->assertNotTrue($this->containsStdClass($confirm));
    }

    /**
     * @testdox      can confirm multiple labels
     * @dataProvider multipleLabelsConfirmationsProvider
     *
     * @param ResponseInterface[] $responses
     *
     * @throws ReflectionException
     */
    public function testConfirmMultipleLabelsRest($responses)
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $mockClient = new MockClient();
        $mockClient->setHandler($handler);
        $this->postnl->setHttpClient($mockClient);

        $confirms = $this->postnl->confirmShipments([
                (new Shipment())
                    ->setAddresses([
                        Address::create([
                            'AddressType' => '01',
                            'City'        => 'Utrecht',
                            'Countrycode' => 'NL',
                            'FirstName'   => 'Peter',
                            'HouseNr'     => '9',
                            'HouseNrExt'  => 'a bis',
                            'Name'        => 'de Ruijter',
                            'Street'      => 'Bilderdijkstraat',
                            'Zipcode'     => '3521VA',
                        ]),
                        Address::create([
                            'AddressType' => '02',
                            'City'        => 'Hoofddorp',
                            'CompanyName' => 'PostNL',
                            'Countrycode' => 'NL',
                            'HouseNr'     => '42',
                            'Street'      => 'Siriusdreef',
                            'Zipcode'     => '2132WT',
                        ]),
                    ])
                    ->setBarcode('3SDEVC201611210')
                    ->setDeliveryAddress('01')
                    ->setDimension(new Dimension('2000'))
                    ->setProductCodeDelivery('3085'),
                (new Shipment())
                    ->setAddresses([
                        Address::create([
                            'AddressType' => '01',
                            'City'        => 'Utrecht',
                            'Countrycode' => 'NL',
                            'FirstName'   => 'Peter',
                            'HouseNr'     => '9',
                            'HouseNrExt'  => 'a bis',
                            'Name'        => 'de Ruijter',
                            'Street'      => 'Bilderdijkstraat',
                            'Zipcode'     => '3521VA',
                        ]),
                        Address::create([
                            'AddressType' => '02',
                            'City'        => 'Hoofddorp',
                            'CompanyName' => 'PostNL',
                            'Countrycode' => 'NL',
                            'HouseNr'     => '42',
                            'Street'      => 'Siriusdreef',
                            'Zipcode'     => '2132WT',
                        ]),
                    ])
                    ->setBarcode('3SDEVC201611210')
                    ->setDeliveryAddress('01')
                    ->setDimension(new Dimension('2000'))
                    ->setProductCodeDelivery('3085'),
            ]
        );

        $this->assertInstanceOf(ConfirmingResponseShipment::class, $confirms[1]);
        $this->assertEquals('3SDEVC201611210', $confirms[1]->getBarcode());
        $this->assertInstanceOf(Warning::class, $confirms[1]->getWarnings()[0]);
    }

    /**
     * @testdox throws exception on invalid response
     */
    public function testNegativeGenerateLabelInvalidResponseRest()
    {
        $this->expectException(ResponseException::class);

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json;charset=UTF-8'], 'asdfojasuidfo'),
        ]);
        $handler = HandlerStack::create($mock);
        $mockClient = new MockClient();
        $mockClient->setHandler($handler);
        $this->postnl->setHttpClient($mockClient);

        $this->postnl->confirmShipment(
            (new Shipment())
                ->setAddresses([
                    Address::create([
                        'AddressType' => '01',
                        'City'        => 'Utrecht',
                        'Countrycode' => 'NL',
                        'FirstName'   => 'Peter',
                        'HouseNr'     => '9',
                        'HouseNrExt'  => 'a bis',
                        'Name'        => 'de Ruijter',
                        'Street'      => 'Bilderdijkstraat',
                        'Zipcode'     => '3521VA',
                    ]),
                    Address::create([
                        'AddressType' => '02',
                        'City'        => 'Hoofddorp',
                        'CompanyName' => 'PostNL',
                        'Countrycode' => 'NL',
                        'HouseNr'     => '42',
                        'Street'      => 'Siriusdreef',
                        'Zipcode'     => '2132WT',
                    ]),
                ])
                ->setBarcode('3S1234567890123')
                ->setDeliveryAddress('01')
                ->setDimension(new Dimension('2000'))
                ->setProductCodeDelivery('3085')
        );
    }

    /**
     * @return ResponseInterface[][]
     * @psalm-return non-empty-list<non-empty-list<ResponseInterface>>
     */
    public function singleLabelConfirmationsProvider()
    {
        return [
            [PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel.http'))],
            [PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel2.http'))],
            [PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel3.http'))],
        ];
    }

    /**
     * @return ResponseInterface[][][]
     * @psalm-return non-empty-list<non-empty-list<non-empty-list<ResponseInterface>>>
     */
    public function multipleLabelsConfirmationsProvider()
    {
        return [
            [[
                PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel.http')),
                PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel.http')),
            ]],
            [[
                PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel2.http')),
                PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel2.http')),
            ]],
            [[
                PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel3.http')),
                PsrMessage::parseResponse(file_get_contents(_RESPONSES_DIR_.'/rest/confirming/confirmsinglelabel3.http')),
            ]],
        ];
    }
}
