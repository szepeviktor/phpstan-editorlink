# Editor link error formatter for PHPStan

### Installation

Use `phpstan/extension-installer` or add this manually to your `phpstan.neon`

```neon
includes:
    - vendor/szepeviktor/phpstan-editorlink/extension.neon
```

### Usage

Add this command line option: `vendor/bin/phpstan analyze --error-format=editorLink`
