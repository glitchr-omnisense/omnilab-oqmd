# omnilab/oqmd

**Not tested live**: OQMD did not answer on 2026-10-15 (see [Verified, and not](#verified-and-not)).

## Installation

```sh
composer require omnilab/oqmd
```

```php
use Omnilab\Oqmd\OqmdSourceFactory;

$oqmd = (new OqmdSourceFactory($httpClient))->create(['mailto' => 'support@glitchr.io']);
```

## Credentials

None: OQMD's API takes no key and no account. Give `mailto`, so that the service can reach the
caller before it blocks them.

## Options

The factory's options, with the defaults it applies (`OqmdSourceFactory`):

| Option | Type | Default | Required | Effect |
|---|---|---|---|---|
| `mailto` | `?string` | null | no | a contact address, sent in the User-Agent |
| `user_agent` | `?string` | null | no | the whole User-Agent, in place of the one made from `mailto` |
| `throttle` | `float` | `0` | no | seconds left between two calls: OQMD publishes no rate |
| `base_uri` | `string` | `https://oqmd.org/oqmdapi/` | no | the API the source calls |

## Capabilities

`capabilities()` answers `entity`, `search`; an operation it does not answer throws `NotSupportedException` before any call.

| Capability | Supported | |
|---|---|---|
| `entity` | yes | an OQMD id: `oqmd:16974`, its page `oqmd.org/materials/entry/16974`, or a bare number; any other identifier refused before any call |
| `search` | yes | a composition (`Al2O3`) or a phase space (`Fe-O`), white space removed; 100 entries a page at most; a domain other than chemistry answers an empty page |
| `series` | no |  |
| `products` | no |  |
| `occurrences` | no |  |
| `datasets` | no |  |
| `jobs` | no |  |
| `open` | no |  |
| `structure` | no |  |
| `cross_references` | no | none: the ICSD id is a property (`xref`), the entry it duplicates a related thing (`duplicate_of`) |
| `forecast` | no |  |
| `historical` | no |  |
| `area` | no |  |
| `station` | no |  |
| `cloud_cover` | no |  |

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

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnilab\Exception\UnavailableException;
use Omnilab\Oqmd\OqmdSourceFactory;
use Omnilab\Source\Query;
use Symfony\Component\HttpClient\HttpClient;

$oqmd = (new OqmdSourceFactory(HttpClient::create()))->create(['mailto' => 'you@example.org']);

try {
    $page = $oqmd->search(new Query('Al2O3', limit: 3));
    printf("%s entries of Al2O3:\n", $page->total ?? '?');
    foreach ($page as $entry) {
        printf("  %s  %s  %s, hull %s\n", $entry->id, $entry->label, $entry->property('formation_energy_per_atom'), $entry->property('energy_above_hull'));
    }
    $entry = $oqmd->entity('oqmd:16974');
    echo $entry?->label ?? 'unknown', "\n";
} catch (UnavailableException $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}
```

```
Omnilab\Exception\UnavailableException: [oqmd] the service answered 502
```

(as answered on 2026-10-27: OQMD was down again - a 502, and time-outs on other tries that day -
so the script shows the exception a caller gets, never an empty answer)

```yaml
# config/packages/omnilab.yaml
omnilab:
    sources:
        oqmd: { factory: oqmd, options: { mailto: '%env(OMNILAB_MAILTO)%' } }
```

## Errors

Every call goes through `HttpSource`: a 404 or a 410 (and a 204) is null - an empty page for a list -; a 429 is a `RateLimitedException` (`retryAfter` from `Retry-After`); a 5xx, a time-out or a host that does not answer an `UnavailableException`; any other 4xx a `ProviderException` with its `status` and what the service said; an answer that is not JSON a `ProviderException`, a web page answered for JSON an `UnavailableException`.

Before any call, `entity()` refuses an identifier that is not of the `oqmd` scheme
(`NotSupportedException`: `The "oqmd" source cannot look up "..."`), and the operations OQMD does
not have (series, products, occurrences, datasets, files) are `HttpSource`'s refusals. An id OQMD
answers no row for is null; a row whose `entry_id` is not the one asked is ignored.

## Verified, and not

**Not tested live.** On 2026-10-15 `oqmd.org/oqmdapi/formationenergy` answered 502 ("Server
Error... Please try again in 30 seconds"), then every call to oqmd.org timed out (30 to 40 s),
retried for forty minutes; the documentation (static.oqmd.org) answered. The fixtures are that
documentation's example answer, as published (two entries of `element_set=(Al-Fe),O`), one cut to
its first entry, and one emptied (`Tests/Fixtures/README.md`). Not verified: every call of the
package, `filter=entry_id=...` above all; the units; the rate the service bears (none published).

Tried again on 2026-10-27, four runs of the example below: once the search for `Al2O3` answered
(its first entry 14842) and the `filter=entry_id=14842` call that followed timed out; then a search
timed out; then twice a 502. The entity call is still unverified.

## Responsibility

This package is an independent, unofficial client: it is not affiliated with, endorsed by or
sponsored by the OQMD team (Northwestern University). OQMD states its terms on oqmd.org, and asks
that the two papers the README's [Terms of use](https://github.com/glitchr-omnisense/omnilab-oqmd#terms-of-use)
names be cited. Whoever runs the package is responsible for complying with those terms and for any
use made of what it returns: see the README's
[Responsibility](https://github.com/glitchr-omnisense/omnilab-oqmd#responsibility) section.
