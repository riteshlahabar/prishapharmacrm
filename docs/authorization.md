# Authorization        Status: ✅ done (Phase 6)

Who may do what, and how a refusal reads. Envelope: `api/v1-common.md`.

## Layers
A request passes three gates, outermost first:

1. **`api.auth`** — the middleware group every authenticated API route uses
   instead of `auth:sanctum`. It authenticates the token, then re-checks the
   account behind it: a Sanctum token is a snapshot taken at login, so without
   this a deactivated or re-roled user kept working access until the token was
   deleted by hand. Denials: `account_inactive`, `role_changed`.
2. **`ability:role:*`** — Sanctum token abilities, so a token minted by the
   other app is refused even though it authenticates. `abilities:` accepts any
   one of several (`/persons` takes both apps).
3. **Policies** — one per module under `app/Modules/<Name>/Policies/`,
   registered from the provider's `policies()` hook, so authorization arrives
   with the module.

## 403 vs 404
**403** is about the account: your role or app may not use this feature.
**404** is about the row: absent, *or* outside your scope. The API never
distinguishes those two, because a 403 would confirm the row exists.

So a policy denial is rendered twice over: the panel calls `authorize()` and
gets 403; the mobile path runs through the service, which translates the same
denial into `ModelNotFoundException` → 404.

## Writing a policy
- Key on the **current** account (`canSignIn()`), never on the token.
- An `mr` holds **no panel permissions** — `mr` is app-only — so a role check
  stands *beside* each `$user->can('…')`, not behind it.
- Scope differs per module and is deliberate: a visit is scoped by
  **territory** (anyone covering the region reads it), a call log by its
  **caller** (notes are a personal working record). `CallLogPolicy::update`
  and `MessagePolicy::markRead` have no admin bypass.

## Panel
Web routes still gate on spatie `permission:` middleware. Phase 7 moves them
onto these policies; the row rules are already shared through the services.
