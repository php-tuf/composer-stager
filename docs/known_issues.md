# Known Issues

1. rsync is not supported on Windows.

1. [Universal naming convention (UNC) paths](https://docs.microsoft.com/en-us/dotnet/standard/io/file-path-formats#unc-paths) are not supported.

1. The following scenarios are not currently supported:

   | Scenario | Syncer | Example active dir | Example staging dir |
   |---|---|---|---|
   | Siblings: temp directory | `RsyncFileSyncer` | `/tmp/active-dir` | `/tmp/staging-dir` |
   | Siblings: active as "dot" (`.`) | `PhpFileSyncer` on Windows | `.` | `../staging-dir` |
   | Siblings: staging as CWD with trailing slash | `PhpFileSyncer`, `RsyncFileSyncer` | `../active-dir` | `./` |
   | Siblings: staging as "dot" (`.`) | `PhpFileSyncer`, `RsyncFileSyncer` | `../active-dir` | `.` |
   | Nested: simple | `PhpFileSyncer` on Windows, `RsyncFileSyncer` | `active-dir` | `active-dir/staging-dir` |
   | Nested: with directory depth | `PhpFileSyncer` on Windows, `RsyncFileSyncer` | `active-dir` | `active-dir/some/directory/depth/staging-dir` |
   | Nested: absolute paths | `PhpFileSyncer` on Windows, `RsyncFileSyncer` | `/var/www/active-dir` | `/var/www/active-dir/staging-dir` |
   | Nested: both dirs relative, staging as "hidden" dir | `PhpFileSyncer` on Windows, `RsyncFileSyncer` | `active-dir` | `active-dir/.composer_staging` |
   | Nested: Both dirs absolute, staging as "hidden" dir | `PhpFileSyncer` on Windows, `RsyncFileSyncer` | `/var/www/active-dir` | `/var/www/active-dir/.composer_staging` |
   | Nested: temp directory | `PhpFileSyncer`, `RsyncFileSyncer` | `/tmp/active-dir` | `/tmp/active-dir/staging-dir` |

---

[Back to the README](README.md)
