<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;

/**
 * GET /email/verify/{id}/{hash} — the link in the verification email.
 * The URL is signed (it can't be forged or edited) and expires after 60 minutes;
 * the hash must match the account's email address.
 * Afterwards the person lands on their profile in the SPA.
 */
class EmailVerificationController extends Controller
{
    public function __invoke(string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'This verification link is not valid.');
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect('/profile?verified=1');
    }
}
