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

namespace Firstred\PostNL\Exception;

/**
 * Class ApiConnectionException.
 *
 * @since 1.0.0
 */
class ApiConnectionException extends ApiException
{
    /** @var string */
    protected ?string $body;
    /** @var object */
    protected ?object $jsonBody;
    /** @var array */
    protected ?array $headers;

    /**
     * ApiConnectionException constructor.
     *
     * @param string      $message
     * @param int         $code
     * @param string|null $body
     * @param object|null $jsonBody
     * @param array|null  $headers
     */
    public function __construct(string $message = '', int $code = 0, $body = null, object $jsonBody = null, array $headers = null)
    {
        parent::__construct(message: $message, code: $code, previous: null);

        $this->body = $body;
        $this->jsonBody = $jsonBody;
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @return object
     */
    public function getJsonBody(): ?object
    {
        return $this->jsonBody;
    }

    /**
     * @return array
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }
}
