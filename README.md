# omnilab/oqmd

> **Not tested live.** OQMD did not answer on 2026-10-15 (a 502 "Server Error", then time-outs on
> oqmd.org and on its API, retried over forty minutes). The package follows OQMD's published API
> documentation, and its tests read the example answer of that documentation. Details in
> [docs/index.md](docs/index.md#verified-and-not).

The Open Quantum Materials Database (Northwestern University) for
[glitchr/omnilab](https://github.com/glitchr-studio/omnilab): DFT formation energies of some
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

## Terms of use

OQMD states its terms on oqmd.org (not reachable when this was written). Cite: J. E. Saal, S.
Kirklin, M. Aykol, B. Meredig, C. Wolverton, Materials Design and Discovery with High-Throughput
Density Functional Theory: The Open Quantum Materials Database (OQMD), JOM 65, 1501-1509 (2013);
and S. Kirklin et al., The Open Quantum Materials Database (OQMD): assessing the accuracy of DFT
formation energies, npj Computational Materials 1, 15010 (2015).

License (of this package's code): MIT.
