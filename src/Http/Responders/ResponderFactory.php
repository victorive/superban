<?php

declare(strict_types=1);

namespace Victorive\Superban\Http\Responders;

use Victorive\Superban\Contracts\BanResponder;
use Victorive\Superban\Exceptions\InvalidConfigurationException;

final class ResponderFactory
{
    public static function make(string $format): BanResponder
    {
        return match ($format) {
            'json' => new JsonResponder,
            'problem+json' => new ProblemDetailsResponder,
            'html' => new HtmlResponder,
            default => throw new InvalidConfigurationException("Unsupported response format [{$format}]."),
        };
    }
}
