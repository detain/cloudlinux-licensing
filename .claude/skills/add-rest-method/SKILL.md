---
name: add-rest-method
description: Adds a new REST API method to src/Cloudlinux.php following the getcurlpage/json_decode pattern. Appends auth token, stores raw response in $this->response, returns decoded array. Use when user says 'add endpoint', 'new API call', 'wrap REST method', or adds a method to Cloudlinux.php. Do NOT use for XML-RPC-only methods or dual-dispatch router methods.
---
# add-rest-method

## Critical

- **Never** interpolate raw user input into the URL — always `urlencode()` any parameter value.
- **Always** assign `$this->response` before decoding — it stores the raw response for callers.
- **Token placement**: `token=` must be appended via `$this->authToken()` on every authenticated endpoint. Unauthenticated endpoints (e.g. `status()`) omit it.
- **No PDO, no `$_GET`/`$_POST`** — this is a library; all inputs come through method parameters.
- Indentation is **tabs**, properties and parameters are **camelCase** (enforced by `.scrutinizer.yml`).

## Instructions

1. **Identify the REST endpoint** from the CloudLinux API docs or the existing method table in `src/Cloudlinux.php`. Note whether it takes parameters beyond the token.

2. **Add the method to `src/Cloudlinux.php`** inside `class Cloudlinux`, following this exact pattern:

   ```php
   /**
    * One-line description of what this does.
    *
    * @param string $paramName  description
    * @return array
    */
   public function methodName($paramName)
   {
       $this->response = $this->getcurlpage($this->restUrl.'endpoint/path.json?token='.$this->authToken().'&param='.urlencode($paramName));
       $return = json_decode($this->response, true);
       return $return;
   }
   ```

   - If the method has **no parameters** (like `status()`), omit `&param=…` and the token if unauthenticated.
   - If a parameter is **optional**, use a ternary append: `.($note !== false ? '&note='.urlencode($note) : '')`
   - If the response has a known success/data envelope and you want to unwrap it, return `$return['data']` or use `$return['registered'] ?? $return` (see `register()`).

   Verify: the method is inside the class braces, uses tabs, and `$this->response` is assigned before `json_decode`.

3. **If this method has both REST and XML-RPC variants**, add a public router method that delegates:

   ```php
   public function doThing($param)
   {
       return $this->apiType == 'rest' ? $this->restDoThing($param) : $this->xmlDoThing($param);
   }
   ```

   Rename the REST implementation to `restMethodName()` and the XML-RPC one to `xmlMethodName()`.

4. **Add a PHPUnit test** to `tests/CloudlinuxTest.php`:

   ```php
   /**
    * @covers Detain\Cloudlinux\Cloudlinux::methodName
    */
   public function testMethodName()
   {
       $response = $this->object->methodName('someValue');
       $this->assertTrue(is_array($response));
       $this->assertArrayHasKey('success', $response, 'Missing success status in response');
       $this->assertEquals(1, $response['success'], 'The command wasnt successfull and should have been.');
   }
   ```

   Verify: test method name starts with `test` (lowercase), `@covers` annotation matches the fully-qualified method.

5. **Run the tests** to confirm no regressions:

   ```bash
   vendor/bin/phpunit tests/ -v
   ```

## Examples

**User says:** "Add a method to list KernelCare keys"

**Actions taken:**
- Endpoint: `kcare/key/list.json` — no parameters beyond token
- Added to `src/Cloudlinux.php`:
  ```php
  public function kcareList()
  {
      $this->response = $this->getcurlpage($this->restUrl.'kcare/key/list.json?token='.$this->authToken());
      $return = json_decode($this->response, true);
      return $return;
  }
  ```
- Added test `testKcareList()` asserting `is_array($response)` and `$response['success'] == 1`.

**Result:** Method matches `imunifyList()` and `restList()` exactly in structure.

## Common Issues

- **`json_decode` returns `null`**: `$this->response` is `false` — curl failed. Check that `$this->restOptions[CURLOPT_SSL_VERIFYHOST]` is set (done in `__construct`) and the URL is correct. Add `var_dump($this->response)` temporarily to inspect the raw string.
- **Token rejected (HTTP 403 / `success: false`)**: `authToken()` returns `login|timestamp|sha1(key.timestamp)`. Verify `CLOUDLINUX_LOGIN` and `CLOUDLINUX_KEY` environment variables are set.
- **Test not discovered by PHPUnit**: Method name doesn't start with `test`. Rename `methodName()` → `testMethodName()`.
- **`urlencode` missing on IP parameter**: CloudLinux rejects colons in IPv6 addresses if not encoded. Always wrap IP/key/code parameters in `urlencode()`.
- **Optional parameter appended as literal `false`**: Use `($param !== false ? '&key='.urlencode($param) : '')` — see `imunifyCreate()` for the exact pattern.
