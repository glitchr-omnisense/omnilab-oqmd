# omnilab/oqmd

**Not tested live**: OQMD did not answer on 2026-10-16 (see [Verified, and not](#verified-and-not)).

## Installation

```sh
composer require omnilab/oqmd
```

```php
use Omnilab\Oqmd\OqmdSourceFactory;

$oqmd = (new OqmdSourceFactory($httpClient))->create(['mailto' => 'support@glitchr.io']);
```

## The scheme

An entry is named in the core's `oqmd` scheme by its number (`16974`), read from `oqmd:16974` and
from its page `oqmd.org/materials/entry/16974`; a bare number is read as one by this source.

## Which API

OQMD's RESTful API, `oqmd.org/oqmdapi/formationenergy` (documented at
static.oqmd.org/static/docs/restful.html), `format=json`, the fields asked (`name`, `entry_id`,
`icsd_id`, `duplicate_entry_id`, `composition_generic`, `prototype`, `spacegroup`, `natoms`,
`ntypes`, `volume`, `delta_e`, `stability`, `band_gap`):

| Method | Call |
|---|---|
| `entity()` | `filter=entry_id={id}&limit=1` - **an assumption**: the documentation lists `entry_id` among the fields, not among the filter's keywords; the row whose `entry_id` is the one asked is kept, any other ignored |
| `search()` | `composition={text}&limit=...&offset=...` (a composition, `Al2O3`, or a phase space, `Fe-O`: the documentation's "composition" key); `total` from `meta.data_available`, the next page from `meta.more_data_available` |

## What an entity holds

`id`, `label` (its name and space group: `NaAlH2CO5 (Imma)`), `domain` chemistry, `kind`
`material`, `url` (its page), and: `formula`, `generic_formula`, `prototype`, `space_group`,
`atoms`, `element_types`, `volume` (Å³, the cell's), `formation_energy_per_atom` (`delta_e`,
eV/atom), `energy_above_hull` (`stability`, the hull distance, eV/atom), `band_gap` (eV), `xref`
(`icsd: ...`); `related`: `duplicate_of` (the preferred entry with the same structure). The units
are those OQMD's papers use for these quantities; the API's documentation gives none.

## Verified, and not

**Not tested live.** On 2026-10-16 `oqmd.org/oqmdapi/formationenergy` answered 502 ("Server
Error... Please try again in 30 seconds"), then every call to oqmd.org timed out (30 to 40 s),
retried for two hours; the documentation (static.oqmd.org) answered. The fixtures are that
documentation's example answer, as published (two entries of `element_set=(Al-Fe),O`), one cut to
its first entry, and one emptied (`Tests/Fixtures/README.md`). Not verified: every call of the
package, `filter=entry_id=...` above all; the units; the rate the service bears (none published).
