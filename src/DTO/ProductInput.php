<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ProductInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\Length(max: 65535)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Price must be a decimal with up to 2 places')]
    public string $price;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public int $quantity;

    #[Assert\Url]
    #[Assert\NotBlank]
    public string $imageUrl;
}
