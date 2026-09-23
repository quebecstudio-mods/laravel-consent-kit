# Recording consents

The package shows the banner and honours the answer. It does not keep a
register: where decisions are stored, how long, and who may read them are
decisions an application makes for itself.

What follows is what the core gives you and how to assemble it. Everything on
this page is yours to write — about thirty lines.

> The Craft CMS plugin ships this ready to use in its Pro edition, with a
> control panel, exports, retention and permissions.

## What you get

| Piece | What it does |
|---|---|
| `qsm-consent-kit:change` | Event on `document`, fired the moment a decision is made |
| `RecordScript::build()` | The inline script that reports it over `sendBeacon` |
| `CookieConsent::config()` | The configuration the server resolved for itself |
| `Presentation::payload()` / `hash()` | The fingerprint of what the banner was showing |
| `Decision::reconcile()` / `outcome()` | A reported answer, read against the categories you offer |

## The one rule

**Never accept the fingerprint from the browser.** Compute it on the server,
from your own configuration. A fingerprint a visitor could send is a
fingerprint a visitor could choose, which is worth nothing as evidence.

For the same reason, the report carries no fingerprint, no timestamp and no
identity: the server has all three, and its own are the ones that count. The
cookie's timestamp lives on the visitor's device and proves nothing.

## The table

Two tables, so the wording of a screen is stored once however many decisions
saw it:

```php
Schema::create('consent_presentations', function (Blueprint $table) {
    $table->id();
    $table->char('hash', 64)->unique();
    $table->string('language', 12);
    $table->json('payload');
});

Schema::create('consent_decisions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('presentation_id')->constrained('consent_presentations')->restrictOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamp('decided_at');
    $table->string('action', 20);       // accept-all, refuse-all, save
    $table->string('origin', 20);       // banner, dialog, gpc
    $table->string('outcome', 10);      // all, some, none
    $table->json('categories');
    $table->timestamps();

    $table->index('decided_at');
});
```

## The endpoint

`sendBeacon` sends no CSRF token, so the route has to be exempt — this is the
step that is easy to miss, and it fails as a silent 419.

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['consent/record']);
})
```

```php
Route::post('consent/record', RecordConsentController::class)->name('consent.record');
```

```php
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use QuebecStudioMods\ConsentKit\Core\Decision;
use QuebecStudioMods\ConsentKit\Core\Presentation;
use QuebecStudioMods\ConsentKit\Laravel\Facades\CookieConsent;

class RecordConsentController
{
    public function __invoke(Request $request): Response
    {
        $config = CookieConsent::config();
        $answer = Decision::reconcile((array) $request->input('categories', []), $config['categories']);

        // An answer naming a category this site does not offer: no banner of
        // ours produced it.
        if ($answer === null) {
            return response()->noContent(202);
        }

        $payload = Presentation::payload($config);

        $presentation = ConsentPresentation::firstOrCreate(
            ['hash' => Presentation::hash($payload)],
            ['language' => $payload['language'], 'payload' => $payload],
        );

        ConsentDecision::create([
            'presentation_id' => $presentation->id,
            'user_id' => $request->user()?->id,
            'decided_at' => now(),
            'action' => $request->input('action'),
            'origin' => $request->input('origin'),
            'outcome' => Decision::outcome($answer, $config['categories']),
            'categories' => $answer,
        ]);

        return response()->noContent(202);
    }
}
```

Always 202, whatever happened: the endpoint is anonymous, and an answer would
tell a prober what it found.

## The script

Anywhere in the layout — it listens on `document`:

```blade
<script>{!! QuebecStudioMods\ConsentKit\Core\RecordScript::build(route('consent.record')) !!}</script>
```

It reports `action` (`accept-all`, `refuse-all` or `save`), `origin` (`banner`,
`dialog` or `gpc`) and the categories answered. Pass a second argument to add
your own fields, such as a tenant id.

## What a fingerprint is worth

Two decisions sharing one saw exactly the same wording. It covers the policy
version, the language, the policy link, the categories in display order with
their labels, descriptions and cookies, and the banner's own wording. Styling
is left out, so restyling a site does not invalidate its proofs.

Store the payload alongside the hash and anyone can recompute it without this
package: sort every object's keys, keep list order, encode with
`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`, take the SHA-256.

## What it does not prove

That a screen was shown to a given person. It proves what the site was showing
when a decision was reported to it. Recording the visitor's address and browser
is possible and makes the register personal data — declare it in the privacy
policy, and set a retention.

Purging frees nobody: consent lives in the visitor's cookie and keeps applying.
What goes is the proof of it.

---

[← Documentation](../README.md)
