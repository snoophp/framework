# Database

The `database` directory contains the migration scripts.

You should have a php script for each schema you want to build. The name of the file must reflect the database configuration file.

To run the migration on the `example` schema, run `schema.php` from terminal with one of the following arguments:

```shell
> php database/example.php [migrate|drop|reset]
```

- `migrate` to run migration: new tables are created, modified tables are changed (where possible), removed tables are preserved
- `drop` all tables are dropped
- `reset` first drop, than migrate