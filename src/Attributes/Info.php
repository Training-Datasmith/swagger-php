<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Attributes;

use Open_Api\Annotations as OA;
use Open_Api\Generator;
#[\Attribute(\Attribute::TARGET_CLASS)]
class Info extends OA\Info
{
    /**
     * @param array<string,mixed>|null $x
     * @param list<Attachable>|null    $attachables
     */
    public function __construct(
        ?string $version = null,
        ?string $description = Generator::UNDEFINED,
        ?string $title = null,
        ?string $terms_of_service = null,
        ?Contact $contact = null,
        ?License $license = null,
        // abstract annotation
        ?array $x = null,
        ?array $attachables = null
    )
    {
        parent::__construct(['version' => $version ?? Generator::UNDEFINED, 'description' => $description, 'title' => $title ?? Generator::UNDEFINED, 'termsOfService' => $terms_of_service ?? Generator::UNDEFINED, 'x' => $x ?? Generator::UNDEFINED, 'attachables' => $attachables ?? Generator::UNDEFINED, 'value' => $this->combine($contact, $license)]);
    }
}