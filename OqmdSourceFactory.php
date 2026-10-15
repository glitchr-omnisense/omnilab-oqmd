<?php

namespace Omnilab\Oqmd;

use Omnilab\Config;
use Omnilab\Source\SourceFactory;
use Omnilab\Source\SourceInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * The Open Quantum Materials Database: DFT formation energies, hull
 * distances and band gaps of materials by OQMD id, composition or phase
 * space - no key needed.
 *
 *   options:
 *     mailto: 'support@glitchr.io'     # in the User-Agent
 *     throttle: 0                      # seconds between two calls: no rate published
 *     base_uri: 'https://oqmd.org/oqmdapi/'
 */
final class OqmdSourceFactory extends SourceFactory
{
    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnilab.factory_name' => 'oqmd',
            'omnilab.required_options' => [],
            'throttle' => 0.0,
            'base_uri' => OqmdSource::BASE_URI,
        ]);
    }

    protected function build(Config $c): SourceInterface
    {
        return new OqmdSource(
            $this->http ?? HttpClient::create(),
            (string) $c->get('base_uri', OqmdSource::BASE_URI),
            ['User-Agent' => self::userAgent($c)],
            (float) $c->get('throttle'),
        );
    }
}
