<?php

namespace Omnilab\Oqmd\Tests;

use Omnilab\Exception\NotSupportedException;
use Omnilab\Exception\UnavailableException;
use Omnilab\Model\Domain;
use Omnilab\Model\Quantity;
use Omnilab\Oqmd\OqmdSource;
use Omnilab\Oqmd\OqmdSourceFactory;
use Omnilab\Source\Capability;
use Omnilab\Source\Query;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Not tested live (OQMD did not answer on 2026-10-16): the fixtures are
 * the example answer of OQMD's documentation and two cut from it
 * (Fixtures/README.md).
 */
final class OqmdSourceTest extends TestCase
{
    /** @var list<array<string, string>> */
    private array $calls = [];

    private function source(?MockResponse $response = null): OqmdSource
    {
        $http = new MockHttpClient(function (string $method, string $url) use ($response): MockResponse {
            self::assertStringStartsWith(OqmdSource::BASE_URI.'formationenergy?', $url);
            parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);
            $this->calls[] = $query;
            if ($response) {
                return $response;
            }
            $fixture = match (true) {
                'entry_id=16974' === ($query['filter'] ?? null) => 'formationenergy-entry-16974.json',
                'Al-Fe-O' === ($query['composition'] ?? null) => 'formationenergy-element-set.json',
                default => 'formationenergy-empty.json',
            };

            return new MockResponse((string) file_get_contents(__DIR__.'/Fixtures/'.$fixture));
        });
        $source = (new OqmdSourceFactory($http))->create();
        self::assertInstanceOf(OqmdSource::class, $source);

        return $source;
    }

    public function testAnEntryByItsId(): void
    {
        $entry = $this->source()->entity('oqmd:16974');

        self::assertSame('json', $this->calls[0]['format']);
        self::assertStringContainsString('delta_e', $this->calls[0]['fields']);
        self::assertSame('oqmd:16974', $entry->id->key());
        self::assertSame('NaAlH2CO5 (Imma)', $entry->label);
        self::assertSame(Domain::CHEMISTRY, $entry->domain);
        self::assertSame('material', $entry->kind);
        self::assertSame('https://oqmd.org/materials/entry/16974', $entry->url);
        self::assertSame('NaAlH2CO5', $entry->value('formula'));
        self::assertSame('Imma', $entry->value('space_group'));
        self::assertSame(5, $entry->value('element_types'));
        self::assertEquals(new Quantity(5.255, 'eV'), $entry->value('band_gap'));
        self::assertEquals(new Quantity(-2.05739610121138, 'eV/atom'), $entry->value('formation_energy_per_atom'));
    }

    public function testSearchACompositionInPages(): void
    {
        $page = $this->source()->search(new Query('Al-Fe-O', limit: 2));
        self::assertSame(['0', '2'], [$this->calls[0]['offset'], $this->calls[0]['limit']]);
        self::assertSame(['oqmd:16974', 'oqmd:16995'], array_map(static fn ($e) => $e->id->key(), $page->items));
        self::assertSame(1078, $page->total);
        self::assertSame('2', $page->next);
        self::assertCount(0, $this->source()->search(new Query('Al-Fe-O', Domain::ASTRONOMY)));
    }

    public function testUnknownNullOtherBasesRefusedServiceDown(): void
    {
        self::assertNull($this->source()->entity('1'));
        try {
            $this->source()->entity('mp-149');
            self::fail('mp');
        } catch (NotSupportedException) {
        }
        self::assertSame([Capability::ENTITY, Capability::SEARCH], $this->source()->capabilities());
        $this->expectException(UnavailableException::class);
        $this->source(new MockResponse('<html><head><title>502 Server Error</title></head></html>', ['http_code' => 502]))->entity('16974');
    }
}
