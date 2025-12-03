<?php

declare(strict_types=1);

namespace Saloon\Contracts;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Saloon\Data\MultipartValue;

interface MultipartBodyFactory
{
    /**
     * Create a multipart body
     *
     * @param  array<MultipartValue>  $multipartValues
     */
    public function create(StreamFactoryInterface $streamFactory, array $multipartValues, string $boundary): StreamInterface;
}
