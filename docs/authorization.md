# Authorization        Status: ✅ done (Phases 6–7)

Who may do what, and how a refusal reads. Envelope: `api/v1-common.md`.

## Layers
A request passes three gates, outermost first:

1. **`api.auth`** — the group every authenticated API route uses instead of
   `auth:sanctum`. It authenticates the token, then re-checks the account: a
   Sanctum token is a snapshot taken at login, so without this a deactivated
   or re-roled user kept access until the token was deleted by hand. Denials:
   `account_inactive`, `role_changed`.
2. **`ability:role:*`** — Sanctum token abilities, so a token minted by the
   other app is refused though it authenticates. `abilities:` accepts any one
   of several (`/persons` takes both apps).
3. **Policies** — one per module under `app/Modules/<Name>/Policies/`,
   registered from the provider's `policies()` hook.

## 403 vs 404
**403** is the account: your role or app may not use this feature. **404**
is the row: absent, *or* outside your scope — the API never distinguishes
them, since a 403 would confirm the row exists.

A denial renders twice over: the panel calls `authorize()` and gets 403; the
mobile path goes through the service, which turns the same denial into
`ModelNotFoundException` → 404.

## Writing a policy
- Key on the **current** account (`canSignIn()`), never the token.
- An `mr` holds **no panel permissions** — it is app-only — so a role check
  stands *beside* each `$user->can('…')`, not behind it.
- Scope differs per module, deliberately: a visit by **territory** (anyone
  covering the region reads it), a call log by its **caller** (notes are a
  personal record). `CallLogPolicy::update` and `MessagePolicy::markRead`
  have no admin bypass.

## Panel
Web routes gate on spatie `permission:` middleware; their row checks already
run through these policies via the services. The dashboard is the first
direct caller of the capability methods: it asks each module `viewAny` to
decide which cards a viewer gets.
