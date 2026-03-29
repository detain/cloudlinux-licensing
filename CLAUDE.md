# cloudlinux-licensing

PHP library wrapping the CloudLinux Licensing API (REST + XML-RPC). Manages IP licenses, Imunify keys, and KernelCare keys for the `Detain\Cloudlinux\Cloudlinux` class in `src/Cloudlinux.php`.

## Commands

```bash
composer install
vendor/bin/phpunit tests/ -v
phpdbg -qrr vendor/bin/phpunit tests/ -v --coverage-clover coverage.xml --whitelist src/
```

## Architecture

- **Class**: `Detain\Cloudlinux\Cloudlinux` → `src/Cloudlinux.php` · PSR-4 autoload
- **REST base**: `https://cln.cloudlinux.com/api/` via `getcurlpage()` + `CURLOPT_*` options in `$restOptions`
- **XML-RPC base**: `https://cln.cloudlinux.com/clweb/xmlrpc` via `pear/xml_rpc2` `$xmlClient`
- **Auth**: `authToken()` → `login|timestamp|sha1(key.timestamp)` — appended as `&token=` on REST calls
- **Dispatch pattern**: `$apiType` (`'rest'` default or `'xml'`) controls which backend each public method calls
- **Logging**: `log()` delegates to `myadmin_log()` if available — safe to call without MyAdmin
- **StatisticClient**: `getcurlpage()` optionally reports to workerman stats if `StatisticClient.php` is present
- **IDE config**: `.idea/` — PhpStorm project settings: `inspectionProfiles/` (code inspection rules), `cloudlinux-licensing.iml` (module definition), `deployment.xml` (server deployment mappings)

## Key Methods

| Public method | REST endpoint | XML-RPC method |
|---|---|---|
| `check($ip)` | `ipl/check.json` | — |
| `isLicensed($ip)` | → `check()` | → `xmlIsLicensed()` |
| `register($ip, $type)` | `ipl/register.json` | — |
| `remove($ip, $type)` | `ipl/remove.json` | → `removeLicense()` |
| `licenseList()` | → `restList()` | → `reconcile()` |
| `types()` | `v2/ip-license/licenses/types` | — |
| `imunifyCreate($code, $limit)` | `im/key/create.json` | — |
| `kcareList()` | `kcare/key/list.json` | — |

## Tests

- `tests/CloudlinuxTest.php` — PHPUnit tests, requires live API credentials
- `setUp()` loads environment variables and instantiates `$this->object`
- Helper methods without `test` prefix (e.g. `Check()`, `ListRestResponse()`) are assertion helpers called from test methods, not standalone tests
- XML-RPC exception paths tested by passing `'BAD_KEY'` to trigger `catch(\Exception $e)` blocks

```bash
# Run a single test method by name
vendor/bin/phpunit tests/ -v --filter testCheck
```

## Conventions

- New REST methods: call `$this->getcurlpage($this->restUrl.'endpoint.json?token='.$this->authToken().'&param='.urlencode($val))`, decode with `json_decode($this->response, true)`, return the result array
- New XML-RPC methods: wrap `$this->xmlClient->method_name(...)` in `try/catch`, call `$this->log('error', ...)` on exception, return `false` on failure
- Dual-dispatch: add a public router method that checks `$this->apiType == 'rest'` and delegates to the appropriate private method
- Coding style: tabs for indentation, `camelCase` properties and parameters (enforced by `.scrutinizer.yml`)
- No direct `$_GET`/`$_POST` — this is a library, not a web endpoint
- Token never stored; regenerated fresh per-request via `authToken()`

```php
// Instantiate and call REST or XML-RPC backend
$client = new Detain\Cloudlinux\Cloudlinux('mylogin', 'myapikey');
$result = $client->check('192.168.1.100');
// Switch to XML-RPC backend
$client->apiType = 'xml';
$licensed = $client->isLicensed('192.168.1.100');
```

<!-- caliber:managed:pre-commit -->
## Before Committing

Run `caliber refresh` before creating git commits to keep docs in sync with code changes.
After it completes, stage any modified doc files before committing:

```bash
caliber refresh && git add CLAUDE.md .claude/ .cursor/ .github/copilot-instructions.md AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null
```
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
