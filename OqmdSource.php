<?php

namespace Omnilab\Oqmd;

use Omnilab\Exception\NotSupportedException;
use Omnilab\Model\Domain;
use Omnilab\Model\Entity;
use Omnilab\Model\Identifier;
use Omnilab\Model\Identifiers;
use Omnilab\Model\Property;
use Omnilab\Model\Quantity;
use Omnilab\Model\Scheme;
use Omnilab\Source\Capability;
use Omnilab\Source\HttpSource;
use Omnilab\Source\Page;
use Omnilab\Source\Query;

/**
 * The Open Quantum Materials Database (Northwestern University), DFT
 * formation energies of some 700,000 materials, through its RESTful API
 * (oqmd.org/oqmdapi): an entry by its OQMD id - its name (formula),
 * space group, prototype, atoms and element types, volume, formation
 * energy, distance to the convex hull, band gap, its ICSD id, the entry it
 * duplicates -; the entries of a composition ("Al2O3") or a phase space
 * ("Fe-O").
 *
 * One call an entity or a page, no key. Not tested live: the service
 * did not answer on 2026-10-15 (README).
 */
final class OqmdSource extends HttpSource
{
    public const BASE_URI = 'https://oqmd.org/oqmdapi/';

    /** The most entries a page holds. */
    public const MAX_LIMIT = 100;

    /** The fields asked (the API's keywords). */
    public const FIELDS = ['name', 'entry_id', 'icsd_id', 'duplicate_entry_id', 'composition_generic', 'prototype', 'spacegroup', 'natoms', 'ntypes', 'volume', 'delta_e', 'stability', 'band_gap'];

    public function getName(): string
    {
        return 'oqmd';
    }

    public function capabilities(): array
    {
        return [Capability::ENTITY, Capability::SEARCH];
    }

    /**
     * An entry by its OQMD id ("oqmd:6", its page; a bare number read as
     * one); another base's identifier is refused before any call; null when
     * OQMD has none.
     */
    public function entity(Identifier|string $id): ?Entity
    {
        $identifier = self::identify($id, Scheme::OQMD) ?? throw NotSupportedException::identifier('oqmd', (string) $id);
        $data = $this->getJson('formationenergy', ['fields' => implode(',', self::FIELDS), 'filter' => 'entry_id='.$identifier->value, 'limit' => 1, 'format' => 'json'])['data'] ?? [];
        foreach ($data as $row) {
            if (\is_array($row) && (string) ($row['entry_id'] ?? '') === $identifier->value) {
                return $this->toEntity($row);
            }
        }

        return null;
    }

    /**
     * The entries of a composition ("Al2O3") or a phase space ("Fe-O"),
     * in OQMD's order; a domain other than chemistry answers nothing.
     */
    public function search(Query $query): Page
    {
        if (null !== $query->domain && Domain::CHEMISTRY !== $query->domain) {
            return new Page();
        }
        $text = (string) preg_replace('~\s+~', '', $query->text);
        if ('' === $text) {
            return new Page();
        }
        $offset = max(0, (int) $query->cursor);
        $limit = max(1, min(self::MAX_LIMIT, $query->limit));
        $answer = $this->getJson('formationenergy', ['composition' => $text, 'fields' => implode(',', self::FIELDS), 'limit' => $limit, 'offset' => $offset, 'format' => 'json']) ?? [];
        $items = array_values(array_filter(array_map(fn (mixed $row) => \is_array($row) ? $this->toEntity($row) : null, $answer['data'] ?? [])));
        $total = isset($answer['meta']['data_available']) ? (int) $answer['meta']['data_available'] : null;
        $more = (bool) ($answer['meta']['more_data_available'] ?? (null !== $total && $offset + $limit < $total));

        return new Page($items, $more ? (string) ($offset + $limit) : null, $total);
    }

    /** @param array<string, mixed> $row */
    private function toEntity(array $row): ?Entity
    {
        $id = Identifier::tryOf(Scheme::OQMD, isset($row['entry_id']) ? (string) $row['entry_id'] : null);
        if (null === $id) {
            return null;
        }
        $properties = [];
        $add = static function (string $key, string $label, mixed $value) use (&$properties): void {
            if (null !== $value && '' !== $value) {
                $properties[] = new Property($key, $label, $value, 'oqmd');
            }
        };
        $quantity = static fn (mixed $value, string $unit): ?Quantity => is_numeric($value) ? new Quantity(0 + $value, $unit) : null;
        $name = \is_string($row['name'] ?? null) ? $row['name'] : null;
        $add('formula', 'Formula', $name);
        $add('generic_formula', 'Generic formula', \is_string($row['composition_generic'] ?? null) ? $row['composition_generic'] : null);
        $add('prototype', 'Structure prototype', \is_string($row['prototype'] ?? null) ? $row['prototype'] : null);
        $add('space_group', 'Space group', \is_string($row['spacegroup'] ?? null) ? $row['spacegroup'] : null);
        $add('atoms', 'Atoms in the cell', is_numeric($row['natoms'] ?? null) ? (int) $row['natoms'] : null);
        $add('element_types', 'Element types', is_numeric($row['ntypes'] ?? null) ? (int) $row['ntypes'] : null);
        $add('volume', 'Cell volume', $quantity($row['volume'] ?? null, 'Å³'));
        $add('formation_energy_per_atom', 'Formation energy per atom', $quantity($row['delta_e'] ?? null, 'eV/atom'));
        $add('energy_above_hull', 'Distance to the convex hull', $quantity($row['stability'] ?? null, 'eV/atom'));
        $add('band_gap', 'Band gap', $quantity($row['band_gap'] ?? null, 'eV'));
        $add('xref', 'Other database', isset($row['icsd_id']) && '' !== (string) $row['icsd_id'] ? 'icsd: '.$row['icsd_id'] : null);
        $duplicate = Identifier::tryOf(Scheme::OQMD, isset($row['duplicate_entry_id']) ? (string) $row['duplicate_entry_id'] : null);

        return new Entity(
            id: $id,
            label: null === $name ? 'OQMD '.$id->value : $name.(\is_string($row['spacegroup'] ?? null) ? ' ('.$row['spacegroup'].')' : ''),
            domain: Domain::CHEMISTRY,
            kind: 'material',
            properties: $properties,
            url: $id->url(),
            source: 'oqmd',
            related: null !== $duplicate && !$duplicate->equals($id) ? ['duplicate_of' => Identifiers::of($duplicate)] : [],
        );
    }
}
