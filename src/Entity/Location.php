<?php
declare(strict_types=1);
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

namespace Firstred\PostNL\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Firstred\PostNL\Attribute\SerializableDateTimeProperty;
use Firstred\PostNL\Attribute\SerializableProperty;
use Firstred\PostNL\Attribute\SerializableScalarProperty;
use Firstred\PostNL\Attribute\SerializableStringArrayProperty;
use Firstred\PostNL\Enum\SoapNamespace;
use Firstred\PostNL\Exception\InvalidArgumentException;
use Firstred\PostNL\Exception\ServiceNotSetException;
use Sabre\Xml\Writer;
use function in_array;

/**
 * @since 1.0.0
 */
class Location extends AbstractEntity
{
    /** @var string|null $AllowSundaySorting */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $AllowSundaySorting = null;

    /** @var DateTimeInterface|null $DeliveryDate */
    #[SerializableDateTimeProperty(namespace: SoapNamespace::Domain)]
    protected ?DateTimeInterface $DeliveryDate = null;

    /** @var string[]|null $DeliveryOptions */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?array $DeliveryOptions = null;

    /** @var string|null $OpeningTime */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $OpeningTime = null;

    /** @var string[]|null $Options */
    #[SerializableStringArrayProperty(namespace: SoapNamespace::Domain)]
    protected ?array $Options = null;

    /** @var string|null $City */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $City = null;

    /** @var string|null $HouseNr */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $HouseNr = null;

    /** @var string|null $HouseNrExt */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $HouseNrExt = null;

    /** @var string|null $Postalcode */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $Postalcode = null;

    /** @var string|null $Street */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $Street = null;

    /** @var Coordinates|null $Coordinates */
    #[SerializableEntityProperty(namespace: SoapNamespace::Domain)]
    protected ?Coordinates $Coordinates = null;

    /** @var CoordinatesNorthWest|null $CoordinatesNorthWest */
    #[SerializableEntityProperty(namespace: SoapNamespace::Domain)]
    protected ?CoordinatesNorthWest $CoordinatesNorthWest = null;

    /** @var CoordinatesSouthEast|null $CoordinatesSouthEast */
    #[SerializableEntityProperty(namespace: SoapNamespace::Domain)]
    protected ?CoordinatesSouthEast $CoordinatesSouthEast = null;

    /** @var string|null $LocationCode */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $LocationCode = null;

    /** @var string|null $Saleschannel */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $Saleschannel = null;

    /** @var string|null $TerminalType */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $TerminalType = null;

    /** @var string|null $RetailNetworkID */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $RetailNetworkID = null;

    /** @var string|null $DownPartnerID */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $DownPartnerID = null;

    /** @var string|null $DownPartnerLocation */
    #[SerializableScalarProperty(namespace: SoapNamespace::Domain)]
    protected ?string $DownPartnerLocation = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        ?string                       $Postalcode = null,
        ?string                       $AllowSundaySorting = null,
        /** @param string|DateTimeInterface|null $DeliveryDate */
        string|DateTimeInterface|null $DeliveryDate = null,
        /** @param string[]|null $DeliveryOptions */
        array                         $DeliveryOptions = null,
        /** @param string[]|null $Options */
        array                         $Options = null,
        Coordinates                   $Coordinates = null,
        CoordinatesNorthWest          $CoordinatesNorthWest = null,
        CoordinatesSouthEast          $CoordinatesSouthEast = null,
        ?string                       $City = null,
        ?string                       $Street = null,
        ?string                       $HouseNr = null,
        ?string                       $HouseNrExt = null,
        ?string                       $LocationCode = null,
        ?string                       $Saleschannel = null,
        ?string                       $TerminalType = null,
        ?string                       $RetailNetworkID = null,
        ?string                       $DownPartnerID = null,
        ?string                       $DownPartnerLocation = null
    ) {
        parent::__construct();

        $this->setAllowSundaySorting(AllowSundaySorting: $AllowSundaySorting);
        try {
            $this->setDeliveryDate(DeliveryDate: $DeliveryDate ?: (new DateTimeImmutable(datetime: 'next monday', timezone: new DateTimeZone(timezone: 'Europe/Amsterdam'))));
        } catch (Exception $e) {
            throw new InvalidArgumentException(message: $e->getMessage(), code: 0, previous: $e);
        }
        $this->setDeliveryOptions(DeliveryOptions: $DeliveryOptions);
        $this->setOptions(Options: $Options);
        $this->setPostalcode(Postalcode: $Postalcode);
        $this->setCoordinates(Coordinates: $Coordinates);
        $this->setCoordinatesNorthWest(CoordinatesNorthWest: $CoordinatesNorthWest);
        $this->setCoordinatesSouthEast(CoordinatesSouthEast: $CoordinatesSouthEast);
        $this->setCity(City: $City);
        $this->setStreet(Street: $Street);
        $this->setHouseNr(HouseNr: $HouseNr);
        $this->setHouseNrExt(HouseNrExt: $HouseNrExt);
        $this->setLocationCode(LocationCode: $LocationCode);
        $this->setSaleschannel(Saleschannel: $Saleschannel);
        $this->setTerminalType(TerminalType: $TerminalType);
        $this->setRetailNetworkID(RetailNetworkID: $RetailNetworkID);
        $this->setDownPartnerID(DownPartnerID: $DownPartnerID);
        $this->setDownPartnerLocation(DownPartnerLocation: $DownPartnerLocation);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @since 1.2.0
     */
    public function setDeliveryDate(string|DateTimeInterface|null $DeliveryDate = null): static
    {
        if (is_string(value: $DeliveryDate)) {
            try {
                $DeliveryDate = new DateTimeImmutable(datetime: $DeliveryDate, timezone: new DateTimeZone(timezone: 'Europe/Amsterdam'));
            } catch (Exception $e) {
                throw new InvalidArgumentException(message: $e->getMessage(), code: 0, previous: $e);
            }
        }

        $this->DeliveryDate = $DeliveryDate;

        return $this;
    }

    /**
     * @param string|null $Postalcode
     *
     * @return $this
     */
    public function setPostalcode(?string $Postalcode = null): static
    {
        if (is_null(value: $Postalcode)) {
            $this->Postalcode = null;
        } else {
            $this->Postalcode = strtoupper(string: str_replace(search: ' ', replace: '', subject: $Postalcode));
        }

        return $this;
    }

    /**
     * @since 1.0.0
     * @since 1.3.0 Accept bool and int
     */
    public function setAllowSundaySorting(string|bool|int|null $AllowSundaySorting = null): static
    {
        if (null !== $AllowSundaySorting) {
            $AllowSundaySorting = in_array(needle: $AllowSundaySorting, haystack: [true, 'true', 1], strict: true) ? 'true' : 'false';
        }

        $this->AllowSundaySorting = $AllowSundaySorting;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getDeliveryOptions(): ?array
    {
        return $this->DeliveryOptions;
    }

    /**
     * @param string[]|null $DeliveryOptions
     *
     * @return static
     */
    public function setDeliveryOptions(?array $DeliveryOptions): static
    {
        if (is_array(value: $DeliveryOptions)) {
            foreach ($DeliveryOptions as $option) {
                if (!is_string(value: $option)) {
                    throw new \TypeError(message: 'Expected a string');
                }
            }
        }

        $this->DeliveryOptions = $DeliveryOptions;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOpeningTime(): ?string
    {
        return $this->OpeningTime;
    }

    /**
     * @param string|null $OpeningTime
     *
     * @return $this
     */
    public function setOpeningTime(?string $OpeningTime): static
    {
        $this->OpeningTime = $OpeningTime;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getOptions(): ?array
    {
        return $this->Options;
    }

    /**
     * @param string[]|null $Options
     *
     * @return static
     */
    public function setOptions(?array $Options): static
    {
        if (is_array(value: $Options)) {
            foreach ($Options as $option) {
                if (!is_string(value: $option)) {
                    throw new \TypeError(message: 'Expected a string');
                }
            }
        }

        $this->Options = $Options;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->City;
    }

    /**
     * @param string|null $City
     *
     * @return $this
     */
    public function setCity(?string $City): static
    {
        $this->City = $City;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHouseNr(): ?string
    {
        return $this->HouseNr;
    }

    /**
     * @param string|null $HouseNr
     *
     * @return $this
     */
    public function setHouseNr(?string $HouseNr): static
    {
        $this->HouseNr = $HouseNr;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHouseNrExt(): ?string
    {
        return $this->HouseNrExt;
    }

    /**
     * @param string|null $HouseNrExt
     *
     * @return $this
     */
    public function setHouseNrExt(?string $HouseNrExt): static
    {
        $this->HouseNrExt = $HouseNrExt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->Street;
    }

    /**
     * @param string|null $Street
     *
     * @return $this
     */
    public function setStreet(?string $Street): static
    {
        $this->Street = $Street;

        return $this;
    }

    /**
     * @return Coordinates|null
     */
    public function getCoordinates(): ?Coordinates
    {
        return $this->Coordinates;
    }

    /**
     * @param Coordinates|null $Coordinates
     *
     * @return $this
     */
    public function setCoordinates(?Coordinates $Coordinates): static
    {
        $this->Coordinates = $Coordinates;

        return $this;
    }

    /**
     * @return CoordinatesNorthWest|null
     */
    public function getCoordinatesNorthWest(): ?CoordinatesNorthWest
    {
        return $this->CoordinatesNorthWest;
    }

    /**
     * @param CoordinatesNorthWest|null $CoordinatesNorthWest
     *
     * @return $this
     */
    public function setCoordinatesNorthWest(?CoordinatesNorthWest $CoordinatesNorthWest): static
    {
        $this->CoordinatesNorthWest = $CoordinatesNorthWest;

        return $this;
    }

    /**
     * @return CoordinatesSouthEast|null
     */
    public function getCoordinatesSouthEast(): ?CoordinatesSouthEast
    {
        return $this->CoordinatesSouthEast;
    }

    /**
     * @param CoordinatesSouthEast|null $CoordinatesSouthEast
     *
     * @return $this
     */
    public function setCoordinatesSouthEast(?CoordinatesSouthEast $CoordinatesSouthEast): static
    {
        $this->CoordinatesSouthEast = $CoordinatesSouthEast;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocationCode(): ?string
    {
        return $this->LocationCode;
    }

    /**
     * @param string|null $LocationCode
     *
     * @return $this
     */
    public function setLocationCode(?string $LocationCode): static
    {
        $this->LocationCode = $LocationCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSaleschannel(): ?string
    {
        return $this->Saleschannel;
    }

    /**
     * @param string|null $Saleschannel
     *
     * @return $this
     */
    public function setSaleschannel(?string $Saleschannel): static
    {
        $this->Saleschannel = $Saleschannel;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTerminalType(): ?string
    {
        return $this->TerminalType;
    }

    /**
     * @param string|null $TerminalType
     *
     * @return $this
     */
    public function setTerminalType(?string $TerminalType): static
    {
        $this->TerminalType = $TerminalType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRetailNetworkID(): ?string
    {
        return $this->RetailNetworkID;
    }

    /**
     * @param string|null $RetailNetworkID
     *
     * @return $this
     */
    public function setRetailNetworkID(?string $RetailNetworkID): static
    {
        $this->RetailNetworkID = $RetailNetworkID;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDownPartnerID(): ?string
    {
        return $this->DownPartnerID;
    }

    /**
     * @param string|null $DownPartnerID
     *
     * @return $this
     */
    public function setDownPartnerID(?string $DownPartnerID): static
    {
        $this->DownPartnerID = $DownPartnerID;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDownPartnerLocation(): ?string
    {
        return $this->DownPartnerLocation;
    }

    /**
     * @param string|null $DownPartnerLocation
     *
     * @return $this
     */
    public function setDownPartnerLocation(?string $DownPartnerLocation): static
    {
        $this->DownPartnerLocation = $DownPartnerLocation;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAllowSundaySorting(): ?string
    {
        return $this->AllowSundaySorting;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDeliveryDate(): ?DateTimeInterface
    {
        return $this->DeliveryDate;
    }

    /**
     * @return string|null
     */
    public function getPostalcode(): ?string
    {
        return $this->Postalcode;
    }

    /**
     * @param Writer $writer
     *
     * @return void
     * @throws ServiceNotSetException
     */
    public function xmlSerialize(Writer $writer): void
    {
        $xml = [];
        if (!isset($this->currentService)) {
            throw new ServiceNotSetException(message: 'Service not set before serialization');
        }

        foreach ($this->getSerializableProperties() as $propertyName => $namespace) {
            if (!isset($this->$propertyName)) {
                continue;
            }

            if ('DeliveryDate' === $propertyName) {
                if ($this->DeliveryDate instanceof DateTimeImmutable) {
                    $xml["{{$namespace}}DeliveryDate"] = $this->DeliveryDate->format(format: 'd-m-Y');
                }
            } elseif ('Options' === $propertyName) {
                if (is_array(value: $this->Options)) {
                    $options = [];
                    foreach ($this->Options as $option) {
                        $options[] = ['{http://schemas.microsoft.com/2003/10/Serialization/Arrays}string' => $option];
                    }
                    $xml["{{$namespace}}Options"] = $options;
                }
            } elseif ('DeliveryOptions' === $propertyName) {
                if (is_array(value: $this->DeliveryOptions)) {
                    $options = [];
                    foreach ($this->DeliveryOptions as $option) {
                        $options[] = ['{http://schemas.microsoft.com/2003/10/Serialization/Arrays}string' => $option];
                    }
                    $xml["{{$namespace}}DeliveryOptions"] = $options;
                }
            } elseif ('AllowSundaySorting' === $propertyName) {
                if (isset($this->AllowSundaySorting)) {
                    $xml["{{$namespace}}AllowSundaySorting"] = $this->AllowSundaySorting;
                }
            } else {
                $xml["{{$namespace}}{$propertyName}"] = $this->$propertyName;
            }
        }
        // Auto extending this object with other properties is not supported with SOAP
        $writer->write(value: $xml);
    }
}
