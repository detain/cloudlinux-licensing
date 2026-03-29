---
name: phpunit-test-method
description: Writes a PHPUnit test method for Cloudlinux.php following the CloudlinuxTest.php patterns — uses $this->object, asserts array structure with assertArrayHasKey, tests both success and failure paths. Use when user says 'add test', 'write test for', or 'test coverage for [method]'. Do NOT use for non-PHPUnit test frameworks or non-Cloudlinux library code.
---
# phpunit-test-method

## Critical

- All test methods MUST be in `tests/CloudlinuxTest.php` inside `class CloudlinuxTest extends TestCase`
- Test methods that PHPUnit runs MUST be prefixed with `test` (e.g., `testTypes`); helper assertion methods must NOT have the `test` prefix (e.g., `Check()`, `ListRestResponse()`)
- `$this->object` is always available — it is a live `Cloudlinux` instance initialized in `setUp()` with real credentials
- These are **live API tests** — always include a failure-path case using an invalid IP (`'66.45.228.100.1'`) or bad key (`'BAD_KEY'`) to test error branches
- Never mock `$this->object`; for XML-RPC exception paths, construct a new `Cloudlinux` instance with `'BAD_KEY'` directly in the test method

## Instructions

1. **Identify the method to test** in `src/Cloudlinux.php`. Note its return type: REST methods return `array` (decoded JSON with `success` + `data` keys); XML-RPC methods return `array|false`; dual-dispatch methods have both paths.
   - Verify the method exists in `src/Cloudlinux.php` before writing the test.

2. **Add a `@covers` docblock** referencing the fully-qualified method:
   ```php
   /**
    * @covers Detain\Cloudlinux\Cloudlinux::methodName
    */
   ```

3. **Write the success-path assertions** using this pattern for REST methods that return `['success'=>1, 'data'=>...]`:
   ```php
   $response = $this->object->methodName($args);
   $this->assertTrue(is_array($response));
   $this->assertArrayHasKey('success', $response, 'Missing success status in response');
   $this->assertEquals(1, $response['success'], 'The command wasnt successfull and should  have been.');
   $this->assertArrayHasKey('data', $response, 'Missing data in response');
   // assert specific data fields:
   $this->assertArrayHasKey('fieldName', $response['data'], 'Missing fieldName field');
   ```
   For methods returning a bare array/list (e.g., `check()`), assert count or element types directly.

4. **Write the failure-path assertions** — always test at least one invalid input:
   - Invalid IP format: pass `'66.45.228.100.1'` and assert `$this->assertFalse($response)` or `$this->assertEquals(false, $response['success'], '...')`
   - XML-RPC bad credentials: `$object = new Cloudlinux(getenv('CLOUDLINUX_LOGIN'), 'BAD_KEY'); $this->assertFalse($object->xmlMethodName(...));`

5. **For dual-dispatch methods** (those that check `$this->apiType`), test both paths:
   ```php
   $this->object->apiType = 'xml';
   $response = $this->object->method(...);
   // assert XML path result
   $this->object->apiType = 'rest';
   $response = $this->object->method(...);
   // assert REST path result
   ```

6. **Run the test** to confirm it passes:
   ```bash
   vendor/bin/phpunit tests/ -v --filter testMethodName
   ```
   Verify: output shows `OK (1 test, N assertions)` before committing.

## Examples

**User says:** "Add a test for the `types()` method"

**Actions taken:**
- Read `src/Cloudlinux.php` — `types()` calls `v2/ip-license/licenses/types`, returns `json_decode($this->response, true)` (array with `success` + `data`)
- Add to `tests/CloudlinuxTest.php`:

```php
/**
 * @covers Detain\Cloudlinux\Cloudlinux::types
 */
public function testTypes()
{
    $response = $this->object->types();
    $this->assertTrue(is_array($response));
    $this->assertArrayHasKey('success', $response, 'Missing success status in response');
    $this->assertEquals(1, $response['success'], 'The command wasnt successfull and should  have been.');
    $this->assertArrayHasKey('data', $response, 'Missing data in response');
    $this->assertTrue(is_array($response['data']), 'data should be an array of license types');
}
```

**Result:** One test, 4 assertions, mirrors the style of `testStatus()` and `testAvailability()`.

## Common Issues

- **`No environment variables!`** — `setUp()` requires `CLOUDLINUX_LOGIN` and `CLOUDLINUX_KEY` environment variables. Set them before running the suite.
- **`PHP Fatal error: Class 'Dotenv\Dotenv' not found`** — run `composer install` to restore `vendor/`.
- **`XML_RPC2_Client` not found** — `include_once 'XML/RPC2/Client.php'` requires `pear/xml_rpc2`. Run `composer install` and verify `composer.json` includes it.
- **Test named `Check()` or `ListRestResponse()` not running** — these are intentionally helper methods without the `test` prefix; they are called from real test methods. Do not rename them.
- **`assertFalse` failing on XML-RPC path** — confirm you passed `'BAD_KEY'` as the second arg to `new Cloudlinux(...)`, not as the first, to trigger the `catch(\Exception $e)` block that returns `false`.
