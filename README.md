# omnilab/oqmd

> **Not tested live.** OQMD did not answer on 2026-10-15 (a 502 "Server Error", then time-outs on
> oqmd.org and on its API, retried over forty minutes). The package follows OQMD's published API
> documentation, and its tests read the example answer of that documentation. On 2026-11-13 the
> API answered the lookup by id (`formationenergy?filter=entry_id=16974`) with a 400 "Unknown
> property: entry_id", then timed out twice: `entity()` may need another filter, not checked while
> the service does not answer. Details in [docs/index.md](docs/index.md#verified-and-not).

The Open Quantum Materials Database (Northwestern University) for
[omnisense/omnilab](https://github.com/glitchr-omnisense/omnilab): DFT formation energies of some
700,000 materials - an entry by its OQMD id: its formula, space group, prototype, atoms and element
types, volume, formation energy, distance to the convex hull, band gap, its ICSD id, the entry it
duplicates -; the entries of a composition (`Al2O3`) or a phase space (`Fe-O`). No key.

```php
$oqmd = (new OqmdSourceFactory($http))->create(['mailto' => 'support@glitchr.io']);
$entry = $oqmd->entity('oqmd:16974');           // or its page oqmd.org/materials/entry/16974
$entry->property('formation_energy_per_atom');  // eV/atom
$oqmd->search(new Query('Fe-O', limit: 50));    // the entries of that phase space
```

```yaml
omnilab:
    sources:
        oqmd:
            factory: oqmd
            options:
                mailto: 'support@glitchr.io'   # in the User-Agent
```

## Install and a first call

```sh
composer require omnilab/oqmd
```

OQMD takes no key, but its API did not answer a first call on 2026-11-13 (a 400 "Unknown property:
entry_id" to the lookup by id, then time-outs), nor on 2026-10-15. This first call needs none: the
entry 16974, its answer the example of OQMD's API documentation (trimmed), so nothing leaves the
process (Symfony's `MockHttpClient` answers in place of the service). A real call gives the factory
no client (it makes its own) and the source `['mailto' => 'you@example.org']`.

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnilab\Oqmd\OqmdSourceFactory;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

// The service's answer, recorded: no account, and nothing leaves the process.
$http = new MockHttpClient(new MockResponse('{"data": [{"name": "NaAlH2CO5", "entry_id": 16974, "spacegroup": "Imma", "ntypes": 5, "band_gap": 5.255,
    "delta_e": -2.05739610121138}], "meta": {"data_returned": 1, "data_available": 1}, "response_message": "OK"}'));
$oqmd = (new OqmdSourceFactory($http))->create([]);
$entry = $oqmd->entity('oqmd:16974');
echo $entry->label, ', ', $entry->value('space_group'), ', formation energy ', $entry->property('formation_energy_per_atom'), "\n";
```

Its output:

```
NaAlH2CO5 (Imma), Imma, formation energy -2.0573961012 eV/atom
```

## Terms of use

OQMD states its terms on oqmd.org (not reachable when this was written). Cite: J. E. Saal, S.
Kirklin, M. Aykol, B. Meredig, C. Wolverton, Materials Design and Discovery with High-Throughput
Density Functional Theory: The Open Quantum Materials Database (OQMD), JOM 65, 1501-1509 (2013);
and S. Kirklin et al., The Open Quantum Materials Database (OQMD): assessing the accuracy of DFT
formation energies, npj Computational Materials 1, 15010 (2015).

## Usage policy

Declared as data in [`policy.json`](policy.json) (read at https://oqmd.org/download/ on 2026-11-10), and honoured on every request by the
core: a limit reached waits when its window ends within `max_wait` seconds, else the request is not
sent ([Usage policies](https://github.com/glitchr-omnisense/omnilab/blob/1.x/docs/policies.md)).

- Limits: not published (unknown).
- Licence: CC-BY-4.0; commercial use: allowed.
- Credit: required: Saal J.E. et al., JOM 65, 1501-1509 (2013); Kirklin S. et al., npj Comput. Mater. 1, 15010 (2015) (https://oqmd.org/documentation/publications).
- Needs: nothing.

## Responsibility

This package is an independent, unofficial client: it is not affiliated with, endorsed by or
sponsored by the OQMD team (Northwestern University). It reaches their service on behalf of whoever runs it, and it does not
redistribute their data beyond the short excerpts recorded as test fixtures. Its MIT licence
covers this package's code only and grants no right to the service or to its data.

Whoever installs and runs this package is responsible for complying with the terms above, for
obtaining whatever they require (an account, a key, a licence or subscription, an attribution or
a citation, a rate limit to respect), and for any use made of what it returns. The authors
publish it free of charge, make no commercial use of it, and accept no liability for how it is
used (see [LICENSE](LICENSE)).

License (of this package's code): MIT.
