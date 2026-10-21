# Security Policy

## Reporting a vulnerability

Please report vulnerabilities privately through [GitHub Security Advisories](https://github.com/SeadSilajdzic/ssdev-headless/security/advisories/new). Do not open a public issue. You can expect a first answer within 7 days. Once a fix is released, the advisory is published with credit to the reporter, unless you prefer otherwise.

## Supported versions

| Version | Supported |
|---|---|
| 0.x (latest minor) | Yes |

## Security matrix

Every item of the project's security checklist, with how the package handles it.

| Status | Meaning |
|---|---|
| Covered | Implemented by the package and, where possible, proven by a test |
| Partial | The package handles its part; the rest depends on the application or a later release |
| Planned (Plan N) | Arrives with a feature that is not released yet: 3 JSON API and tokens, 4 two-factor authentication, 5 roles, 6 user management, 7 teams, 8 multi-tenancy |
| Application responsibility | Belongs to the application, server or infrastructure, not to the package |
| Not applicable | The package has no such functionality in v1 |

Tests are named `file › test`, relative to `tests/`.

### Frontend — Vue / Inertia

| Item | Status | Evidence |
|---|---|---|
| Honeypot | Covered | `RejectHoneypotSubmissions` on login, registration and forgot password; `Feature/Auth/LoginTest › rejects submissions that fill the honeypot`, `js/components/HoneypotField.test.ts` |
| Double-submit protection | Covered | Every form uses Inertia `useForm` with a disabled, busy button while processing; `js/components/BaseButton.test.ts › renders a disabled busy button while loading` |
| Client-side validation | Partial | Inputs declare their type and required state; the server is the single source of validation messages |
| Strict form field whitelisting | Covered | FormRequest rules per form; services only receive validated data |
| XSS prevention / proper escaping | Covered | Vue templates and mail messages escape output |
| `v-html` usage review | Covered | The package uses no `v-html` |
| Sensitive field cleanup | Covered | Password fields are cleared after every submit |
| Prevent unnecessary data exposure | Covered | Props come from explicit resources (`AuthUserResource`, `BrowserSessionResource`) |
| Do not expose secrets in Vite/frontend bundle | Covered | The frontend reads only `VITE_APP_NAME` |
| Do not trust hidden inputs | Covered | Reset tokens and signed links are verified on the server |
| Do not trust disabled/readonly inputs | Covered | No privileged field is accepted from a form; changing the email needs the password on the server |
| CSRF token handling | Covered | Laravel CSRF protection in the `web` group; expired tokens redirect back with a message (`Feature/Exceptions/InertiaErrorRendererTest › redirects back with a flash message on expired pages`) |
| Prevent accidental duplicate requests | Covered | Busy buttons; registration and email change are safe to repeat |
| Frontend permission checks | Covered | `useCan()` (`js/composables/useCan.test.ts`); navigation hides items without permission (`Feature/Navigation/NavigationRegistryTest`) |
| Safe redirect handling | Covered | `SafeRedirect`; `Feature/Auth/LoginTest › ignores an external intended url after login` |
| Avoid exposing internal routes/configuration | Covered | Pages receive only the URLs they need; no route list is shared |
| Minimize sensitive data stored in client-side state | Covered | Nothing is written to browser storage; passwords leave the form state after submit |
| Verify all Inertia props contain only required data | Covered | `Feature/Settings/ProfileTest › renders the profile page without sensitive data` |

### Backend — Laravel

| Item | Status | Evidence |
|---|---|---|
| Authentication | Covered | Laravel Fortify behind package routes; `Feature/Auth/LoginTest` |
| Authorization | Partial | Code-defined permissions checked with `can()` (`Feature/Permissions`); feature policies arrive with Plans 5–8 |
| BOLA / IDOR | Covered | Account endpoints act on the signed-in user only; sessions are addressed by keyed hashes (`Feature/Settings/BrowserSessionsTest › returns not found for another user session`) |
| Broken Function Level Authorization | Planned (Plan 5) | Admin endpoints arrive with roles and user management |
| Horizontal Privilege Escalation | Covered | Same as BOLA/IDOR for every account endpoint |
| Vertical Privilege Escalation | Partial | Permissions are granted only from the console (`headless:permissions:grant`, `headless:create-admin`); the roles UI follows in Plan 5 |
| Mass Assignment | Covered | Models are filled with `forceFill()` from validated fields; the application's `$fillable` is never changed |
| Strict Request Validation | Covered | A FormRequest for every write route |
| SQL Injection | Covered | Query builder bindings only |
| XSS protection | Covered | Escaped output in Vue, Blade and mail |
| CSRF protection | Covered | `web` middleware; the GET email confirmation is a signed, expiring URL |
| Rate Limiting | Covered | `RateLimits`, values in `config/headless.php`; throttle tests in every `Feature/Auth` file |
| Brute Force Protection | Covered | `Feature/Auth/LoginTest › throttles login attempts per email and ip`, `Feature/Auth/PasswordConfirmationTest › throttles password confirmation attempts` |
| Credential Stuffing Protection | Covered | `Feature/Auth/LoginTest › throttles attempts across many emails from one ip` |
| Session Security | Covered | Session settings checked by the installer (`Feature/Installation/CheckSessionConfigurationTest`) |
| Session Fixation Protection | Covered | `Feature/Auth/LoginTest › logs in and regenerates the session` |
| Session Hijacking Protection | Partial | HttpOnly, SameSite and Secure are checked by the installer; HTTPS is the application's job |
| Account Takeover Protection | Covered | Email changes need the password and a confirmation from the new address, the old address is notified (`Feature/Settings/EmailChangeTest`); password changes end other sessions (`Feature/Settings/PasswordUpdateTest`); resets end every session (`Feature/Auth/PasswordResetTest`) |
| Race Conditions | Covered | `Feature/Auth/RegistrationTest › treats a duplicate insert race as an existing account`; email confirmation locks the user row |
| Database Transactions | Covered | Email change, account deletion and admin creation |
| Database Row Locking | Covered | `EmailChangeService::confirm()` uses `lockForUpdate()` |
| Atomic Operations | Covered | Registration is a single insert guarded by the unique email index |
| Replay Attack Protection | Covered | `Feature/Settings/EmailChangeTest › rejects a link issued before a newer email change`, `› rejects a link once the email changed away and back again`; reset tokens are single use |
| Idempotency | Partial | Email confirmation and registration are safe to repeat; invitations and tenant creation arrive with Plans 7–8 |
| Business Logic Abuse Protection | Partial | Enumeration-safe registration and reset, throttled mail endpoints; more rules arrive with each feature |
| Webhook Signature Verification | Not applicable | No webhooks in v1 |
| Webhook Replay Protection | Not applicable | No webhooks in v1 |
| Webhook Idempotency | Not applicable | No webhooks in v1 |
| SSRF Protection | Not applicable | The package never fetches user-provided URLs |
| File Upload Security | Covered | `Feature/Settings/AvatarTest` |
| File Type Validation | Covered | `Feature/Settings/AvatarTest › rejects svg files`, `› rejects files that are not real images` |
| File Size Limits | Covered | `Feature/Settings/AvatarTest › rejects avatars over the size limit` |
| Safe File Storage | Covered | Server-generated names on the configured disk; `Feature/Settings/AvatarTest › stores an uploaded avatar under a generated name` |
| Resource Exhaustion Protection | Partial | Rate limits and image dimension limits; PHP and web server upload limits belong to the application |
| Denial of Wallet Protection | Partial | Every endpoint that sends mail is throttled; mail provider quotas belong to the application |
| Pagination Limits | Planned (Plan 6) | Lists arrive with user management |
| Bulk Operation Limits | Not applicable | No bulk operations in the core |
| Export Limits | Not applicable | No exports in v1 |
| External Request Timeouts | Not applicable | The package makes no outgoing requests |
| Input Type Validation | Covered | `string`, `email`, `image` and dimension rules |
| HTTP Parameter Pollution Protection | Covered | Arrays are rejected where strings are expected |
| Type Confusion / Type Juggling Protection | Covered | `hash_equals()` for fingerprints and session hashes, strict comparisons |
| Open Redirect Protection | Covered | `Feature/Auth/LoginTest › ignores a protocol-relative intended url after login`, `Feature/Auth/PasswordConfirmationTest › ignores an external intended url after confirming` |
| Host Header Injection Protection | Partial | Redirects are checked against `app.url`, not the Host header; configure `TrustHosts` in the application |
| Multi-Tenant Data Isolation | Planned (Plan 8) | |
| Cache Isolation | Planned (Plan 8) | |
| Cache Key Security | Planned (Plan 8) | Rate limiter keys already include email, IP or user id |
| Sensitive Data Protection | Covered | Hidden attributes, explicit resources, `#[\SensitiveParameter]` on password arguments |
| Sensitive Data Logging Protection | Covered | The package logs nothing; password arguments are `#[\SensitiveParameter]`; Laravel does not flash password fields |
| Error Information Disclosure Protection | Covered | `Feature/Exceptions/InertiaErrorRendererTest › renders server errors without exposing the exception message` |
| Production Debug Protection | Application responsibility | Keep `APP_DEBUG=false` in production |

### Database & data access

| Item | Status | Evidence |
|---|---|---|
| Eloquent/query builder parameter binding | Covered | No raw queries |
| No raw user input in SQL | Covered | No raw queries |
| Validate dynamic ORDER BY fields | Planned (Plan 6) | Sorting whitelists arrive with lists |
| Validate dynamic column/filter fields | Planned (Plan 6) | Criteria classes with whitelists |
| Prevent unauthorized resource access | Covered | Account endpoints are scoped to the signed-in user |
| Enforce ownership checks | Covered | `Feature/Settings/BrowserSessionsTest › returns not found for another user session` |
| Enforce tenant boundaries | Planned (Plan 8) | |
| Prevent N+1 queries | Covered | Core pages run a fixed number of queries |
| Use transactions for atomic operations | Covered | See Database Transactions |
| Use lockForUpdate() where required | Covered | Email confirmation |
| Use atomic updates where appropriate | Covered | Single-row updates |
| Prevent duplicate records where uniqueness is required | Covered | Unique email index and validation |
| Database constraints for security-critical invariants | Covered | Unique `users.email`; unique permission names per guard |

### Inertia / data exposure

| Item | Status | Evidence |
|---|---|---|
| Do not expose complete Eloquent models by default | Covered | `AuthUserResource`, `BrowserSessionResource` |
| Explicitly define Inertia props | Covered | Every page passes an explicit array |
| Review all serialized model attributes | Covered | `Feature/Http/DashboardTest › renders the dashboard with shared props` checks that password fields are missing |
| Do not expose passwords | Covered | `expectNoSensitiveProps()` in `Feature/Settings/ProfileTest` |
| Do not expose password hashes | Covered | Same test checks the hash value |
| Do not expose API tokens | Covered | Plain text only in the creation response or a one-time flash (`Feature/Settings/ApiTokensTest › shows the new token only once`); lists never contain it or its hash (`Feature/Api/ApiTokensApiTest › lists tokens without plain text or hashes`) |
| Do not expose session identifiers | Covered | `Feature/Settings/BrowserSessionsTest › lists only the current user sessions without raw ids` |
| Do not expose private keys | Covered | None are shared |
| Do not expose internal credentials | Covered | None are shared |
| Do not expose internal notes unless required | Not applicable | The core has no notes |
| Do not expose unnecessary database fields | Covered | Explicit resources |
| Do not expose data belonging to other users/tenants | Covered | Sessions of other users are never listed; tenants arrive in Plan 8 |
| Do not rely on frontend hiding sensitive data | Covered | Every check also runs on the server |

### Authentication & account security

| Item | Status | Evidence |
|---|---|---|
| Login rate limiting | Covered | `Feature/Auth/LoginTest › throttles login attempts per email and ip` |
| Registration rate limiting | Covered | `Feature/Auth/RegistrationTest › throttles registration per ip` |
| Password reset rate limiting | Covered | `Feature/Auth/PasswordResetTest › throttles reset link requests` |
| OTP/verification rate limiting | Partial | Email verification is throttled; two-factor codes arrive in Plan 4 |
| Brute-force protection | Covered | See Brute Force Protection |
| Credential stuffing protection | Covered | See Credential Stuffing Protection |
| Account enumeration protection | Covered | `Feature/Auth/RegistrationTest › responds identically when the email is already registered`, `Feature/Auth/PasswordResetTest › responds identically for unknown and throttled emails`; reset mails go out after the response, so timing does not reveal accounts (`Feature/Auth/PasswordResetTest › sends the reset link only after the response is ready`) |
| Session regeneration after login | Covered | `Feature/Auth/LoginTest › logs in and regenerates the session` |
| Secure session cookies | Covered | Installer check |
| HttpOnly cookies | Covered | `Feature/Installation/CheckSessionConfigurationTest › warns about cookies readable by scripts or sent cross-site` |
| Secure cookies | Partial | `Feature/Installation/CheckSessionConfigurationTest › warns about missing secure cookies on https apps`; the application sets the value |
| Appropriate SameSite configuration | Covered | Installer check |
| Re-authentication for sensitive actions | Covered | Password required to change the email or password, delete the account and log out other sessions |
| Secure password change flow | Covered | `Feature/Settings/PasswordUpdateTest` |
| Secure email change flow | Covered | `Feature/Settings/EmailChangeTest` |
| Secure MFA enable/disable flow | Planned (Plan 4) | |
| Secure account recovery flow | Covered | `Feature/Auth/PasswordResetTest` |
| Session/token revocation | Covered | Sessions: `Feature/Settings/BrowserSessionsTest`; tokens: `Feature/Api/AuthTokenTest › revokes only the current token on logout`, `Feature/Settings/ApiTokensTest › revokes a token of the current user`; deleting an account deletes its tokens |
| Protection against account takeover | Covered | See Account Takeover Protection |

### Business logic & state

| Item | Status | Evidence |
|---|---|---|
| Validate all business rules on backend | Covered | Services enforce every rule |
| Validate allowed state transitions | Covered | An email confirmation only applies to the email it was issued for |
| Do not trust client-provided prices | Not applicable | No commerce in v1 |
| Do not trust client-provided discounts | Not applicable | No commerce in v1 |
| Do not trust client-provided balances | Not applicable | No commerce in v1 |
| Do not trust client-provided payment statuses | Not applicable | No commerce in v1 |
| Do not trust client-provided ownership | Covered | The acting user always comes from the session |
| Prevent duplicate operations | Covered | Unique email index |
| Prevent replayed operations | Covered | See Replay Attack Protection |
| Prevent race conditions | Covered | See Race Conditions |
| Use idempotency for non-repeatable operations | Partial | See Idempotency |
| Use transactions for multi-step operations | Covered | See Database Transactions |
| Use locks for concurrent state changes | Covered | See Database Row Locking |
| Protect financial operations | Not applicable | No financial operations in v1 |
| Protect points/rewards operations | Not applicable | No rewards in v1 |
| Protect coupon operations | Not applicable | No coupons in v1 |
| Protect inventory/quota operations | Not applicable | No inventory in v1 |

### API security

| Item | Status | Evidence |
|---|---|---|
| Strict request validation | Covered | The API reuses the web FormRequests; `Feature/Api/PublicAuthApiTest › validates api registration` |
| Explicit input types | Covered | `string`, `email`, `array`, `image` rules |
| Explicit array/object validation | Covered | Token abilities: `present`, `array`, each a granted permission (`Feature/Api/ApiTokensApiTest › refuses abilities the owner does not have`) |
| Maximum payload sizes | Partial | Field lengths and avatar size are limited; body size limits belong to PHP and the web server |
| Rate limiting | Covered | `Feature/Api/ApiErrorsTest › throttles the api per client`, `Feature/Api/AuthTokenTest › throttles token requests with the login limiter` |
| Authorization on every protected endpoint | Covered | `auth:sanctum` on every account route; `Feature/Api/ProfileApiTest › requires a token for the account endpoints`; token abilities limited by `TokenAbilityGate` (`Feature/Api/TokenAbilitiesTest`) |
| Resource ownership validation | Covered | `Feature/Api/ApiTokensApiTest › cannot revoke another user token` |
| Prevent arbitrary field updates | Covered | Only validated fields are saved |
| Prevent arbitrary IDs from bypassing authorization | Covered | Tokens are looked up through their owner; other ids answer 404 |
| Prevent duplicate requests | Covered | Registration and token login are safe to repeat |
| Idempotency where required | Partial | Account operations are idempotent; invitations arrive with Plans 6–7 |
| Secure error responses | Covered | `ApiErrorRenderer`; `Feature/Api/ApiErrorsTest › hides the model class of a missing record`, `› hides server errors`, `› answers unknown api routes with json even without an accept header` |
| No sensitive data in API responses | Covered | `Feature/Api/ProfileApiTest › returns the current user without sensitive fields`; strict response contracts (`Feature/Api/ContractTest`) |
| API login does not reveal accounts | Covered | `Feature/Api/AuthTokenTest › answers wrong passwords and unknown emails the same way`, `› answers unknown emails no faster than wrong passwords` |

### Webhooks

**Not applicable** — the package has no webhooks in v1: signature verification, origin validation, replay protection, timestamps/nonces, idempotent processing, duplicate deliveries, state transitions, unverified status values, webhook-triggered privilege escalation.

### File uploads

| Item | Status | Evidence |
|---|---|---|
| Validate file size | Covered | `Feature/Settings/AvatarTest › rejects avatars over the size limit` |
| Validate MIME type | Covered | `File::image()` with a configured whitelist |
| Validate actual file content where necessary | Covered | `Feature/Settings/AvatarTest › rejects files that are not real images` |
| Whitelist allowed file formats | Covered | `headless.avatars.mimes` |
| Do not trust original filename | Covered | The stored name is a random hash |
| Do not trust file extension | Covered | The extension is guessed from the content |
| Generate server-side filenames | Covered | `Feature/Settings/AvatarTest › stores an uploaded avatar under a generated name` |
| Store uploads outside executable application paths | Partial | Avatars go to the configured disk (default `public`, outside `public/` until `storage:link`); the web server must never execute files from storage |
| Prevent uploaded PHP/code execution | Covered | Only image types are accepted and SVG is rejected (`Feature/Settings/AvatarTest › rejects svg files`) |
| Limit archive extraction | Not applicable | No archives are accepted |
| Protect against ZIP/decompression bombs | Covered | Image dimensions are limited (`Feature/Settings/AvatarTest › rejects avatars with excessive dimensions`) |
| Limit number of uploaded files | Covered | One file per request |

### Resource exhaustion

| Item | Status | Evidence |
|---|---|---|
| Rate-limit expensive endpoints | Covered | Login, registration, reset, verification and every password check |
| Limit PDF generation | Not applicable | No PDFs |
| Limit image processing | Covered | Avatars are stored as uploaded, never processed; dimensions are limited |
| Limit exports | Not applicable | No exports |
| Limit bulk operations | Not applicable | No bulk operations |
| Limit expensive searches | Planned (Plan 6) | |
| Limit external API calls | Not applicable | No outgoing calls |
| Limit queue job creation | Covered | Mails are sent from throttled endpoints only |
| Limit pagination size | Planned (Plan 6) | |
| Limit request payload size | Application responsibility | `post_max_size` and web server limits |
| Use queues for long-running operations | Application responsibility | Configure a queue for mail |
| Apply timeouts to external requests | Not applicable | No outgoing requests |
| Prevent Denial of Wallet scenarios | Partial | See Denial of Wallet Protection |

### SSRF

**Not applicable** — the package never fetches user-provided URLs: URL validation, host allowlists, private and internal IP ranges, localhost, cloud metadata endpoints, request timeouts, redirect restrictions.

### Cache security

| Item | Status | Evidence |
|---|---|---|
| Do not cache private responses incorrectly | Application responsibility | The package sets no HTTP caching; keep authenticated pages out of shared caches |
| Include user/tenant context in cache keys where required | Planned (Plan 8) | Rate limiter keys already include email, IP or user id |
| Include relevant permissions/context in cache keys | Covered | The package caches nothing per user; Spatie keeps its own permission cache |
| Prevent cross-user cache leakage | Covered | No per-user cache entries |
| Prevent cross-tenant cache leakage | Planned (Plan 8) | |
| Review cached Inertia responses | Application responsibility | |
| Review CDN/reverse-proxy caching rules | Application responsibility | |
| Protect against cache poisoning | Application responsibility | |

### Multi-tenancy

**Planned (Plan 8)** — client-provided `tenant_id`, tenant scope on reads, updates, deletes, exports, jobs, repositories and background processing, and explicit cross-tenant tests.

### Logging & error handling

| Item | Status | Evidence |
|---|---|---|
| Never log passwords | Covered | The package logs nothing; password arguments are `#[\SensitiveParameter]` |
| Never log API tokens | Covered | The package logs nothing |
| Never log session IDs | Covered | Session ids never leave `BrowserSessionService` |
| Never log OTPs | Planned (Plan 4) | |
| Never log private keys | Covered | |
| Never log Authorization headers | Covered | |
| Never blindly log `$request->all()` | Covered | Not used |
| Mask sensitive values | Covered | `#[\SensitiveParameter]` |
| Disable APP_DEBUG in production | Application responsibility | |
| Do not expose stack traces | Covered | `Feature/Exceptions/InertiaErrorRendererTest` |
| Do not expose SQL errors | Covered | Same renderer |
| Do not expose filesystem paths | Covered | Same renderer |
| Do not expose internal hostnames | Covered | Same renderer |
| Do not expose infrastructure details | Covered | Same renderer |
| Audit security-sensitive actions where appropriate | Covered | Events: `PasswordChanged`, `EmailChanged`, `AccountDeleted`, plus Laravel's `Login`, `Failed`, `Lockout`, `Registered`, `Verified`, `PasswordReset` |

### Dependencies & supply chain

| Item | Status | Evidence |
|---|---|---|
| Keep composer.lock committed | Application responsibility | A library does not commit its lock file; applications should |
| Keep package-lock/pnpm-lock/yarn.lock committed | Covered | `package-lock.json` |
| Review new Composer dependencies | Covered | The core adds only `laravel/fortify` and `spatie/laravel-permission` |
| Review new npm dependencies | Covered | Vue, Inertia, Reka UI and Lucide only |
| Avoid abandoned packages | Covered | `lucide-vue-next` was replaced by `@lucide/vue` |
| Avoid suspicious packages | Covered | |
| Watch for typosquatting | Covered | |
| Monitor dependency vulnerabilities | Partial | `composer audit` runs on every install; no automated alerts in the repository yet |
| Do not modify unrelated dependencies | Covered | |
| Never commit .env files | Covered | |
| Never commit API keys | Covered | |
| Never commit private keys | Covered | |
| Never commit credentials | Covered | |
| Never expose secrets in frontend builds | Covered | |

### Advanced / infrastructure

| Item | Status | Evidence |
|---|---|---|
| HTTP Request Smuggling | Application responsibility | |
| Proxy Desynchronization | Application responsibility | |
| Host Header Injection | Partial | See Host Header Injection Protection |
| Cache Poisoning | Application responsibility | |
| Cache Key Confusion | Application responsibility | |
| Prototype Pollution | Covered | No user data is merged into objects on the client |
| ReDoS | Covered | The only regular expressions on user input (user agents) are linear |
| ZIP/Decompression Bombs | Covered | See File uploads |
| Open Redirect | Covered | See Open Redirect Protection |
| CORS Misconfiguration | Application responsibility | The package configures no CORS |
| Security Header Configuration | Application responsibility | |
| TLS/HTTPS Configuration | Application responsibility | |
| Nginx/Apache Configuration | Application responsibility | |
| Reverse Proxy Configuration | Application responsibility | Configure `TrustProxies` |
| Load Balancer Configuration | Application responsibility | |
| Redis Access Control | Application responsibility | |
| Database Access Control | Application responsibility | |
| Cloud/IAM Permissions | Application responsibility | |

### Security testing

| Item | Status | Evidence |
|---|---|---|
| Test every protected endpoint without using the frontend | Covered | Feature tests call the HTTP endpoints directly |
| Test changing resource IDs manually | Covered | `Feature/Settings/BrowserSessionsTest › returns not found for another user session` |
| Test accessing another user's resources | Covered | Same test |
| Test accessing another tenant's resources | Planned (Plan 8) | |
| Test normal user access to admin endpoints | Planned (Plan 5) | |
| Test manipulating hidden/disabled fields | Covered | `Feature/Settings/ProfileTest › requires the current password to change the email` |
| Test modifying privileged fields | Partial | Only validated fields are saved; dedicated tests arrive with user management |
| Test duplicate requests | Covered | `Feature/Auth/RegistrationTest › responds identically when the email is already registered` |
| Test concurrent requests | Covered | `Feature/Auth/RegistrationTest › treats a duplicate insert race as an existing account` |
| Test replaying valid requests | Covered | `Feature/Settings/EmailChangeTest › rejects a link issued before a newer email change` |
| Test expired tokens/codes | Covered | `Feature/Settings/EmailChangeTest › rejects an expired link`, `Feature/Auth/PasswordResetTest › rejects an invalid reset token` |
| Test invalid/modified webhook signatures | Not applicable | No webhooks |
| Test oversized payloads | Covered | `Feature/Settings/AvatarTest › rejects avatars over the size limit` |
| Test excessive pagination values | Planned (Plan 6) | |
| Test unauthorized exports | Not applicable | No exports |
| Test unauthorized bulk operations | Not applicable | No bulk operations |
| Test sensitive data in Inertia props | Covered | `Feature/Settings/ProfileTest › renders the profile page without sensitive data` |
| Test authentication rate limits | Covered | `Feature/Auth/LoginTest` |
| Test password reset abuse | Covered | `Feature/Auth/PasswordResetTest` |
| Test account enumeration | Covered | See Account enumeration protection |
| Test file upload restrictions | Covered | `Feature/Settings/AvatarTest` |
| Test arbitrary URL fetching | Not applicable | No URL fetching |
| Test production error responses | Covered | `Feature/Exceptions/InertiaErrorRendererTest` |
| Test that frontend restrictions cannot bypass backend authorization | Covered | Backend tests never rely on the UI |
