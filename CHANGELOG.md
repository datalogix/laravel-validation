# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Nullable rule parameters accept an empty value or `null` to skip them (e.g. `length:,5`, `length:null,5`).
- Rules with an array constructor parameter (`containsAny`, `subset`) can be used as string rules: the remaining parameters become the array (e.g. `containsAny:foo,bar`).
- `length_min` and `length_max` messages for `length` with a single bound (e.g. `length:10`, `length:,5`), and `length_exclusive`, `length_min_exclusive` and `length_max_exclusive` messages for exclusive bounds (e.g. `length:10,20,false`), unless the application replaces the `length` message. The application's `validation.length_*` lines take precedence over the package ones.
- `ext-bcmath` (`iban`) and `ext-fileinfo` (`mimetype`) in the Composer `suggest` list.

### Changed

- English messages follow Laravel's "The :attribute field must ..." style, and several messages were reworded in `en` and `pt_BR` (`charset`, `key`, `key_nested`, `length`, `subset`, `subdivision_code`, `type` and others). Applications that compare message texts in their tests may need to update them.
- The `uploaded` rule shows the package message instead of Laravel's "The :attribute failed to upload." line, unless a custom message is given.
- A boolean rule parameter must be exactly `true` or `false`; any other value (e.g. `domain:abc`, `domain:1`, `domain:off`, `domain:TRUE`) throws an `InvalidArgumentException` instead of being treated as `false` or `true`.
- `illuminate/validation` is now an explicit dependency.

### Fixed

- `containsAny` and `subset` failed with a `TypeError` when used as string rules.
- The `pt_BR` message for `factor` described the opposite relation (dividend instead of divisor).
- The `key` and `key_nested` messages now show the expected key.
