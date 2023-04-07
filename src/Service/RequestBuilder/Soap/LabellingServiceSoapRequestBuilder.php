<?php

/**
 * The MIT License (MIT).
 *
 * Copyright (c) 2017-2023 Michael Dekker (https://github.com/firstred)
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
 * @copyright 2017-2023 Michael Dekker
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

declare(strict_types=1);

namespace Firstred\PostNL\Service\RequestBuilder\Soap;

use DateTimeImmutable;
use Firstred\PostNL\Entity\AbstractEntity;
use Firstred\PostNL\Entity\Request\GenerateLabel;
use Firstred\PostNL\Entity\Soap\Security;
use Firstred\PostNL\Entity\Soap\UsernameToken;
use Firstred\PostNL\Enum\SoapNamespace;
use Firstred\PostNL\Exception\InvalidArgumentException;
use Firstred\PostNL\Exception\InvalidArgumentException as PostNLInvalidArgumentException;
use Firstred\PostNL\Service\LabellingService;
use Firstred\PostNL\Service\LabellingServiceInterface;
use Firstred\PostNL\Service\RequestBuilder\LabellingServiceRequestBuilderInterface;
use ParagonIE\HiddenString\HiddenString;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Sabre\Xml\Service as XmlService;

use function in_array;
use function str_replace;

/**
 * @deprecated 2.0.0
 *
 * @internal
 */
class LabellingServiceSoapRequestBuilder extends AbstractSoapRequestBuilder implements LabellingServiceRequestBuilderInterface
{
    // Endpoints
    private const LIVE_ENDPOINT = 'https://api.postnl.nl/shipment/v2_2/label';
    private const SANDBOX_ENDPOINT = 'https://api-sandbox.postnl.nl/shipment/v2_2/label';

    // SOAP API specific
    private const SOAP_ACTION = 'http://postnl.nl/cif/services/LabellingWebService/ILabellingWebService/GenerateLabel';
    private const SOAP_ACTION_NO_CONFIRM = 'http://postnl.nl/cif/services/LabellingWebService/ILabellingWebService/GenerateLabelWithoutConfirm';

    /**
     * @param HiddenString            $apiKey
     * @param bool                    $sandbox
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface  $streamFactory
     */
    public function __construct(
        HiddenString $apiKey,
        bool $sandbox,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        parent::__construct(
            apiKey: $apiKey,
            sandbox: $sandbox,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );

        $this->namespaces = array_merge($this->namespaces, [
            SoapNamespace::Services->value => LabellingService::SERVICES_NAMESPACE,
            SoapNamespace::Domain->value   => LabellingService::DOMAIN_NAMESPACE,
        ]);
    }

    private static array $insuranceProductCodes = [3534, 3544, 3087, 3094];

    /**
     * Build the GenerateLabel request for the SOAP API.
     *
     * @param GenerateLabel $generateLabel
     * @param bool          $confirm
     *
     * @return RequestInterface
     *
     * @throws PostNLInvalidArgumentException
     *
     * @deprecated 2.0.0
     */
    public function buildGenerateLabelRequest(GenerateLabel $generateLabel, bool $confirm = true): RequestInterface
    {
        $soapAction = $confirm ? static::SOAP_ACTION : static::SOAP_ACTION_NO_CONFIRM;
        $xmlService = new XmlService();
        foreach ($this->namespaces as $namespacePrefix => $namespace) {
            $xmlService->namespaceMap[$namespace] = $namespacePrefix;
        }
        $xmlService->classMap[DateTimeImmutable::class] = [static::class, 'defaultDateFormat'];

        $security = new Security(UserNameToken: new UsernameToken(Password: $this->getApiKey()->getString()));

        $this->setService(entity: $security);
        $this->setService(entity: $generateLabel);

        $request = $xmlService->write(
            rootElementName: '{'.LabellingService::ENVELOPE_NAMESPACE.'}Envelope',
            value: [
                '{'.LabellingService::ENVELOPE_NAMESPACE.'}Header' => [
                    ['{'.Security::SECURITY_NAMESPACE.'}Security' => $security],
                ],
                '{'.LabellingService::ENVELOPE_NAMESPACE.'}Body'   => [
                    '{'.LabellingService::SERVICES_NAMESPACE.'}GenerateLabel' => $generateLabel,
                ],
            ]
        );

        $endpoint = $this->isSandbox() ? static::SANDBOX_ENDPOINT : static::LIVE_ENDPOINT;

        foreach ($generateLabel->getShipments() as $shipment) {
            if (in_array(needle: $shipment->getProductCodeDelivery(), haystack: self::$insuranceProductCodes)) {
                // Insurance behaves a bit strange w/ v2.2, falling back on v2.1
                $endpoint = str_replace(search: 'v2_2', replace: 'v2_1', subject: $endpoint);
            }
        }

        return $this->getRequestFactory()->createRequest(method: 'POST', uri: $endpoint)
            ->withHeader('SOAPAction', value: "\"$soapAction\"")
            ->withHeader('Accept', value: 'text/xml')
            ->withHeader('Content-Type', value: 'text/xml;charset=UTF-8')
            ->withBody(body: $this->getStreamFactory()->createStream(content: $request));
    }

    /**
     * @param AbstractEntity $entity
     *
     * @return void
     *
     * @throws InvalidArgumentException
     *
     * @deprecated 2.0.0
     */
    protected function setService(AbstractEntity $entity): void
    {
        $entity->setCurrentService(
            currentService: LabellingServiceInterface::class,
            namespaces: $this->namespaces,
        );

        parent::setService(entity: $entity);
    }
}
