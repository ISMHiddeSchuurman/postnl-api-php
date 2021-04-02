.. _sendandtrackshipments:
.. _send and track shipments:

========================
Send and track shipments
========================

Barcode service
---------------

.. note::

    | PostNL API documentation for this service:
    | https://developer.postnl.nl/apis/barcode-webservice/overview

The barcode service allows you to generate barcodes for your shipment labels.
Usually you would reserve an amount of barcodes, generate shipping labels and eventually confirm those labels.
According to PostNL, this flow is necessary for a higher delivery success rate.

Generate a single barcode
~~~~~~~~~~~~~~~~~~~~~~~~~

You can generate a single barcode for domestic shipments as follows:

.. code-block:: php

    $postnl->generateBarcode();

This will generate a 3S barcode meant for domestic shipments only.

The function accepts the following arguments:

type
    ``string`` - `optional, defaults to 3S`

    The barcode type. This is 2S/3S for the Netherlands and EU Pack Special shipments.
    For other destinations this is your GlobalPack barcode type.
    For more info, check the `PostNL barcode service page <https://developer.postnl.nl/apis/barcode-webservice/how-use#toc-7>`_.

range
    ``string`` - `optional, can be found automatically`

     For domestic and EU shipments this is your customer code. Otherwise, your GlobalPack customer code.

serie
    ``string`` - `optional, can be found automatically`

    This is the barcode range for your shipment(s).
    Check the `PostNL barcode service page <https://developer.postnl.nl/apis/barcode-webservice/how-use#toc-7>`_
    for the ranges that are available.

eps
    ``bool`` - `optional, defaults to false`

    Indicates whether this is an EU Pack Special shipment.

Generate a barcode by country code
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

It is possible to generate a barcode by country code. This will let the library figure out what
type, range, serie to use.

Example:

.. code-block:: php

    $postnl->generateBarcodeByCountryCode('BE');

This will generate a 3S barcode meant for domestic shipments only.

The function accepts the following arguments:

iso
    ``string`` - `required`

    The two letter country ISO code. Make sure you use UPPERCASE.

Generate multiple barcodes by using country codes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can generate a whole batch of barcodes at once by providing country codes and the
amounts you would like to generate.

Example:

.. code-block:: php

    $postnl->generateBarcodeByCountryCode(['NL' => 2, 'DE' => 3]);

This will return a list of barcodes:

.. code-block:: php

    [
        'NL' => [
            '3SDEVC11111111111',
            '3SDEVC22222222222',
        ],
        'DE' => [
            '3SDEVC111111111',
            '3SDEVC222222222',
            '3SDEVC333333333',
        ],
    ];

The function accepts the following argument:

type
    ``string`` - `required`

    An associative array with country codes as key and the amount of barcodes you'd like to generate
    per country as the value.

Labelling service
-----------------

.. note::

    | PostNL API documentation for this service:
    | https://developer.postnl.nl/apis/labelling-webservice

The labelling service allows you to create shipment labels and optionally confirm the shipments.
The library has a built-in way to merge labels automatically, so you can request labels for multiple shipments at once.

Generate a single label
~~~~~~~~~~~~~~~~~~~~~~~

The following example generates a single shipment label for a domestic shipment:

.. code-block:: php

    $postnl = new PostNL(...);
    $postnl->generateLabel(
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
            ->setBarcode($barcode)
            ->setDeliveryAddress('01')
            ->setDimension(new Dimension('2000'))
            ->setProductCodeDelivery('3085'),
        'GraphicFile|PDF',
        false
    );

This will create a standard shipment (product code 3085). You can access the label (base64 encoded PDF) this way:

.. code-block:: php

    $pdf = base64_decode($label->getResponseShipments()[0]->getLabels()[0]->getContent());

This function accepts the following arguments:

shipment
    ``Shipment`` - `required`

    The Shipment object. Visit the PostNL API documentation to find out what a Shipment object consists of. The Shipment object is based on the SOAP API: https://developer.postnl.nl/browse-apis/send-and-track/labelling-webservice/documentation-soap/

printerType
    ``string`` - `optional, defaults to GraphicFile|PDF`

    The list of supported printer types can be found on this page: https://developer.postnl.nl/browse-apis/send-and-track/labelling-webservice/documentation-soap/

confirm
    ``string`` - `optional, defaults to true`

    Indicates whether the shipment should immediately be confirmed.

Generate multiple shipment labels
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The following example shows how a label can be merged:

.. code-block:: php

    $shipments = [
        Shipment::create([
            'Addresses'           => [
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
            ],
            'Barcode'             => $barcodes['NL'][0],
            'Dimension'           => new Dimension('1000'),
            'ProductCodeDelivery' => '3085',
        ]),
        Shipment::create([
            'Addresses'           => [
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
            ],
            'Barcode'             => $barcodes['NL'][1],
            'Dimension'           => new Dimension('1000'),
            'ProductCodeDelivery' => '3085',
        ]),
    ];

    $label = $postnl->generateLabels(
        $shipments,
        'GraphicFile|PDF', // Printertype (only PDFs can be merged -- no need to use the Merged types)
        true, // Confirm immediately
        true, // Merge
        Label::FORMAT_A4, // Format -- this merges multiple A6 labels onto an A4
        [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
        ] // Positions
    );

    file_put_contents('labels.pdf', $label);

By setting the `merge` flag it will automatically merge the labels into a PDF string.

The function accepts the following arguments:

shipments
    ``Shipment[]`` - `required`

    The Shipment objects. Visit the PostNL API documentation to find out what a Shipment object consists of. The Shipment object is based on the SOAP API: https://developer.postnl.nl/browse-apis/send-and-track/labelling-webservice/documentation-soap/

printerType
    ``string`` - `optional, defaults to GraphicFile|PDF`

    The list of supported printer types can be found on this page: https://developer.postnl.nl/browse-apis/send-and-track/labelling-webservice/documentation-soap/

confirm
    ``string`` - `optional, defaults to true`

    Indicates whether the shipment should immediately be confirmed.

merge
    ``bool`` - `optional, default to false`

    This will merge the labels and make the function return a pdf string of the merged label.

format
    ``int`` - `optional, defaults to 1 (FORMAT_A4)`

    This sets the paper format (can be A4 or A4).

positions
    ``bool[]`` - `optional, defaults to all positions`

    This will set the positions of the labels. The following image shows the available positions, use `true` or `false` to resp. enable or disable a position:

.. image:: img/positions.png

Shipping service
----------------

.. note::

    | PostNL API documentation for this service:
    | https://developer.postnl.nl/browse-apis/send-and-track/shipping-webservice/

The shipping service combines all the functionality of the labeling, confirming, barcode and easy return service.
The service is only available as REST.

Generate a single shipping
~~~~~~~~~~~~~~~~~~~~~

The following example generates a single shipment for a domestic shipment:

.. code-block:: php

    $postnl = new PostNL(...);
    $postnl->generateShipping(
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
            ->setDeliveryAddress('01')
            ->setDimension(new Dimension('2000'))
            ->setProductCodeDelivery('3085'),
        'GraphicFile|PDF',
        false
    );

This will create a standard shipment (product code 3085). You can access the label (base64 encoded PDF) this way:

.. code-block:: php

    $pdf = base64_decode($shipping->getResponseShipments()[0]->getLabels()[0]->getContent());

This function accepts the following arguments:

shipment
    ``Shipment`` - `required`

    The Shipment object. Visit the PostNL API documentation to find out what a Shipment object consists of.

printertype
    ``string`` - `optional, defaults to GraphicFile|PDF`

    The list of supported printer types can be found on this page: https://developer.postnl.nl/browse-apis/send-and-track/shipping-webservice/documentation/

confirm
    ``string`` - `optional, defaults to true`

    Indicates whether the shipment should immediately be confirmed.

Generate multiple shipments
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The following example shows how labels of multiple shipments can be merged:

.. code-block:: php

    $shipments = [
        Shipment::create([
            'Addresses'           => [
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
            ],
            'Dimension'           => new Dimension('1000'),
            'ProductCodeDelivery' => '3085',
        ]),
        Shipment::create([
            'Addresses'           => [
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
            ],
            'Dimension'           => new Dimension('1000'),
            'ProductCodeDelivery' => '3085',
        ]),
    ];

    $label = $postnl->generateShippings(
        $shipments,
        'GraphicFile|PDF', // Printertype (only PDFs can be merged -- no need to use the Merged types)
        true, // Confirm immediately
        true, // Merge
        Label::FORMAT_A4, // Format -- this merges multiple A6 labels onto an A4
        [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
        ] // Positions
    );

    file_put_contents('labels.pdf', $label);

By setting the `merge` flag it will automatically merge the labels into a PDF string.

The function accepts the following arguments:

shipments
    ``Shipment[]`` - `required`

    The Shipment objects. Visit the PostNL API documentation to find out what a Shipment object consists of.

printertype
    ``string`` - `optional, defaults to GraphicFile|PDF`

    The list of supported printer types can be found on this page: https://developer.postnl.nl/browse-apis/send-and-track/shipping-webservice/documentation/

confirm
    ``string`` - `optional, defaults to true`

    Indicates whether the shipment should immediately be confirmed.

merge
    ``bool`` - `optional, default to false`

    This will merge the labels and make the function return a pdf string of the merged label.

format
    ``int`` - `optional, defaults to 1 (FORMAT_A4)`

    This sets the paper format (can be A4 or A4).

positions
    ``bool[]`` - `optional, defaults to all positions`

    This will set the positions of the labels. The following image shows the available positions, use `true` or `false` to resp. enable or disable a position:

.. image:: img/positions.png

Confirming service
------------------

.. note::

    | PostNL API documentation for this service:
    | https://developer.postnl.nl/apis/confirming-webservice

You can confirm shipments that have previously not been confirmed. The available methods are ``confirmShipment`` and ``confirmShipments``.
The first method accepts a single :php:class:`Firstred\\PostNL\\Entity\\Shipment` object whereas the latter accepts an array of :php:class:`Firstred\\PostNL\\Entity\\Shipment`s.
The output is a boolean, or an array with booleans in case you are confirming multiple shipments. The results will be tied to the keys of your request array.

Shipping status service
-----------------------

.. note::

    | PostNL API documentation for this service:
    | https://developer.postnl.nl/apis/shippingstatus-webservice

.. code-block:: php

    $shipments = [
        Shipment::create([
            'Addresses'           => [
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
            ],
            'Barcode'             => $barcodes['NL'][0],
            'Dimension'           => new Dimension('1000'),
            'ProductCodeDelivery' => '3085',
        ]),
        Shipment::create([
            'Addresses'           => [
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
            ],
            'Barcode'             => $barcodes['NL'][1],
            'Dimension'           => new Dimension('1000'),
            'ProductCodeDelivery' => '3085',
        ]),
    ];

    $label = $postnl->generateLabels(
        $shipments,
        'GraphicFile|PDF', // Printertype (only PDFs can be merged -- no need to use the Merged types)
        true, // Confirm immediately
        true, // Merge
        Label::FORMAT_A4, // Format -- this merges multiple A6 labels onto an A4
        [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
        ] // Positions
    );

    file_put_contents('labels.pdf', $label);

This service can be used to retrieve shipping statuses. For a short update use the `CurrentStatus` method, otherwise `CompleteStatus` will provide you with a long list containing the shipment's history.

Current Status by Barcode
~~~~~~~~~~~~~~~~~~~~~~~~~

Gets the current status by Barcode

.. code-block:: php

     $postnl = new PostNL(...);
     $postnl->getCurrentStatus((new CurrentStatus())
         ->setShipment(
             (new Shipment())
                 ->setBarcode('3SDEVC98237423')
         )
     );

statusrequest
    ``CurrentStatus`` - `required`

    The CurrentStatus object. Check the API documentation for all possibilities.


Current Status by Reference
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Gets the current status by reference. Note that you must have set the reference on the shipment label first.

.. code-block:: php

     $postnl = new PostNL(...);
     $postnl->getCurrentStatusByReference((new CurrentStatusByReference())
         ->setShipment(
             (new Shipment())
                 ->setReference('myref')
         )
     );

statusrequest
    ``CurrentStatusByReference`` - `required`

    The CurrentStatusByReference object. Check the API documentation for all possibilities.

Current Status by Status Code
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
.. warning::
    This is no longer supported by the PostNL API.

Current Status by Phase Code
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Gets the current status by phase code. Note that the date range is required.

.. warning::
    This is no longer supported by the PostNL API

Complete Status by Barcode
~~~~~~~~~~~~~~~~~~~~~~~~~~

Gets the complete status by Barcode

.. code-block:: php

    $postnl = new PostNL(...);
    $postnl->getCompleteStatus((new CompleteStatus())
        ->setShipment(
            (new Shipment())
                ->setBarcode('3SDEVC98237423')
        )
    );

statusrequest
    ``CompleteStatus`` - `required`

    The CompleteStatus object. Check the API documentation for all possibilities.

Complete Status by Reference
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Gets the complete status by reference. Note that you must have set the reference on the shipment label first.

.. code-block:: php

    $postnl = new PostNL(...);
    $postnl->getCompleteStatusByReference((new CompleteStatusByReference())
        ->setShipment(
            (new Shipment())
                ->setReference('myref')
        )
    );

statusrequest
    ``CompleteStatusByReference`` - `required`

    The CompleteStatusByReference object. Check the API documentation for all possibilities.

Complete Status by Status Code
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. warning::
    This is no longer supported by the PostNL API.

Complete Status by Phase Code
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. warning::
    This is no longer supported by the PostNL API.


Get Signature
~~~~~~~~~~~~~

Gets the signature of the shipment when available. A signature can be accessed by barcode only.

.. code-block:: php

    $postnl = new PostNL(...);
    $postnl->getSignature(
        (new GetSignature())
            ->setShipment((new Shipment)
                ->setBarcode('3SDEVC23987423')
            )
    );

It accepts the following arguments

getsignature
    ``GetSignature`` - `required`

    The `GetSignature` object. It needs to have one `Shipment` set with a barcode.
