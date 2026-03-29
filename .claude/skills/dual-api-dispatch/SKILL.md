---
name: dual-api-dispatch
description: Implements a public router method that dispatches to REST or XML-RPC based on $this->apiType. Follows the remove()/licenseList() pattern with a conditional and two private/public backend methods. Use when user says 'add dual API support', 'support both XML and REST', or 'route based on apiType'. Do NOT use for REST-only or XML-only additions.
---
# dual-api-dispatch

## Critical

- The public router method MUST check `$this->apiType == 'xml'` (or `== 'rest'`); `'rest'` is the default — never invert the condition so REST is the fallback
- XML-RPC backend methods MUST wrap `$this->xmlClient->method_name(...)` in `try/catch (\Exception $e)` and return `false` on failure — never let exceptions bubble up
- REST backend methods MUST assign to `$this->response` via `$this->getcurlpage(...)` before decoding
- Token is never stored — always call `$this->authToken()` inline in the URL string
- Tabs for indentation; `camelCase` method and parameter names (enforced by `.scrutinizer.yml`)

## Instructions

1. **Name the three methods.** Choose a base name (e.g. `transfer`). The public router is `transfer()`, the REST backend is `restTransfer()`, the XML-RPC backend is `xmlTransfer()`. Verify no collision with existing methods in `src/Cloudlinux.php` before proceeding.

2. **Write the REST backend method** following `restRemove()` / `restList()` in `src/Cloudlinux.php`:
   ```php
   public function restTransfer($ipAddress, $type = 0)
   {
       $this->response = $this->getcurlpage(
           $this->restUrl.'ipl/transfer.json?ip='.urlencode($ipAddress)
           .'&type='.$type.'&token='.$this->authToken()
       );
       return json_decode($this->response, true);
   }
   ```
   Verify: `$this->response` is assigned, `json_decode(..., true)` returns the array.

3. **Write the XML-RPC backend method** following `removeLicense()` / `xmlIsLicensed()` in `src/Cloudlinux.php`:
   ```php
   public function xmlTransfer($ipAddress, $type = 0)
   {
       $this->log('info', "Calling CloudLinux->xmlClient->transfer({$this->authToken()}, {$ipAddress}, {$type})", __LINE__, __FILE__);
       try {
           return $this->response = $this->xmlClient->transfer($this->authToken(), $ipAddress, $type);
       } catch (\Exception $e) {
           $this->log('error', 'Caught exception code: '.$e->getCode(), __LINE__, __FILE__);
           $this->log('error', 'Caught exception message: '.$e->getMessage(), __LINE__, __FILE__);
           return false;
       }
   }
   ```
   Verify: both `log()` calls present, `return false` in catch, `$this->response` assigned in try.

4. **Write the public router** following `remove()` or `licenseList()` in `src/Cloudlinux.php`:
   ```php
   public function transfer($ipAddress, $type = 0)
   {
       if ($this->apiType == 'xml') {
           return $this->xmlTransfer($ipAddress, $type);
       } else {
           return $this->restTransfer($ipAddress, $type);
       }
   }
   ```
   Verify: `xml` branch first, `else` is the REST fallback (matches `'rest'` default).

5. **Add PHPUnit test** in `tests/CloudlinuxTest.php` following `testRemove()` / `testLicense_list()`:
   ```php
   public function testTransfer()
   {
       $this->object->apiType = 'xml';
       $response = $this->object->transfer('66.45.228.100');
       $this->assertFalse($response); // bad credentials → catch → false
       $this->object->apiType = 'rest';
       $response = $this->object->transfer('66.45.228.100');
       $this->assertTrue(is_array($response));
       $this->assertArrayHasKey('success', $response);
   }
   ```
   Run: `vendor/bin/phpunit tests/ -v`

## Examples

**User says:** "Add dual-dispatch support for a new `transfer` endpoint at `ipl/transfer.json`"

**Actions taken:**
1. Add `restTransfer($ipAddress, $type)` — calls `getcurlpage($this->restUrl.'ipl/transfer.json?ip=...&token=...')`, returns `json_decode`
2. Add `xmlTransfer($ipAddress, $type)` — wraps `$this->xmlClient->transfer(...)` in try/catch, logs errors, returns `false` on exception
3. Add `transfer($ipAddress, $type)` router — `if ($this->apiType == 'xml') return $this->xmlTransfer(...); else return $this->restTransfer(...);`
4. Add `testTransfer()` toggling `apiType` between `'xml'` and `'rest'`

**Result:** Matches the `remove()` + `restRemove()` + `removeLicense()` triple in `src/Cloudlinux.php`.

## Common Issues

- **`Call to undefined method XML_RPC2_Client::method_name()`** — the XML-RPC method name must match the remote API exactly (snake_case, e.g. `remove_license` not `removeLicense`). Check the CloudLinux XML-RPC API PDF linked in the class docblock.
- **`$this->xmlClient` is null at runtime** — `xmlClient` is only instantiated when `$apiType == 'xml'` or `$limitType === false` in `__construct()`. If you instantiated with `new Cloudlinux($login, $key, 'rest')`, `xmlClient` is null. Instantiate with `'xml'` or no third arg to use XML-RPC methods.
- **Router uses `== 'rest'` check instead of `== 'xml'`** — `'rest'` is the default, so checking `== 'rest'` first means a typo in `apiType` silently falls through to XML. Always check `== 'xml'` first as seen in `remove()` and `isLicensed()`.
- **Missing `$this->response =` assignment in REST method** — `getcurlpage()` stores the raw body; the test infrastructure and logging rely on `$this->response` being set. Forgetting it breaks introspection without a PHP error.
- **XML-RPC exception not caught** — forgetting `try/catch` causes uncaught `XML_RPC2_FaultException` to crash callers. Always wrap `$this->xmlClient->...` calls as shown in `removeLicense()` in `src/Cloudlinux.php`.
