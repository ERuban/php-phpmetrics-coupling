# php-phpmetrics-coupling
PHP coupling issues detector upon the phpmetrics

## Configuration

Top-level keys:
- `paths`: array of directories (relative or absolute) to scan.
- `metrics`: metric-specific configuration.

## Skipping classes (exact & wildcard)

Inside each metric you can define a `skip` array. Entries can be:

1. Exact fully-qualified class names (FQCN) e.g. `App\\Entity\\SomeEntity`.
2. Wildcard patterns over FQCN (no `/` allowed). Supported wildcards:
   - `*` – any sequence of characters (greedy)
   - `?` – exactly one character

Examples:
```
"skip": [
  "App\Entity\Legacy*",      // any class in App\Entity starting with Legacy
  "App\Service\*Generated*", // any class whose name contains Generated in that namespace path
  "App\Domain\??Helper"      // exactly two characters in place of ??
]
```

Rules:
- Treated as pattern if it contains `*` or `?` and does not contain `/`.
- Patterns are matched against the full FQCN.
- Exact and pattern matches are removed from the violation list for that metric.

See `pmc.json.example` for a configuration sample.
