# Changelog

## [1.2.1] - 2025-10-23

### Fixed

- Declare compatibility with Nextcloud 32 (#22).

## [1.2.0] - 2025-09-11

### Changed

- Enforce configured encodings strictly, rejecting documents matching none of them. This makes it possible to reject binary documents from being indexed (#15).

### Fixed

- Delete documents from DB when they are deleted from Nextcloud (#19).
- Actually use PostgreSQL fulltext index, fixing slow search query performance (#14, #16). Thanks to [@joergsch](https://github.com/joergsch)!

## [1.1.0] - 2025-08-13

### Added

- Admin settings, including allowed encodings and excerpt length
- Encoding detection, making it possible to index documents in non-UTF-8 encoding.