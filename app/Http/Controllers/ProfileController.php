<?php

namespace App\Http\Controllers;

use App\Enums\ChatCharacter;
use App\Http\Requests\ProfileDestroyRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserCharacterProfile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    private const SELECTABLE_CHARACTERS = [
        ChatCharacter::ZUNDAMON->value,
        ChatCharacter::METAN->value,
        ChatCharacter::TSUMUGI->value,
    ];

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $userId = $request->user()->id;

        $characterOptions = collect(ChatCharacter::optionsForFrontend())
            ->filter(fn ($opt) => in_array($opt['value'], self::SELECTABLE_CHARACTERS, true))
            ->values()
            ->all();

        $profiles = UserCharacterProfile::where('user_id', $userId)
            ->whereIn('character', self::SELECTABLE_CHARACTERS)
            ->get()
            ->keyBy('character');

        $characterProfiles = [];
        foreach (self::SELECTABLE_CHARACTERS as $char) {
            $p = $profiles->get($char);
            $characterProfiles[$char] = [
                'nickname' => $p?->nickname,
                'speech_style' => $p?->speech_style ?? 'friendly',
                'favorite_genres' => $p?->favorite_genres ?? [],
                'custom_note' => $p?->custom_note,
            ];
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'characterOptions' => $characterOptions,
            'characterProfiles' => $characterProfiles,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {

    // dd('hit update', $request->method(), $request->all(), $request->allFiles());

    logger()->info('profile update', [
  'content_type' => request()->header('content-type'),
  'keys' => array_keys(request()->all()),
  'has_file' => request()->hasFile('photo'),
  'files' => array_keys(request()->allFiles()),
]);

        $user = $request->user();

        $user->fill($request->validated());

        // プロフィール画像のアップロードがあれば処理
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');

            // 既存ファイルを削除
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }

            // 新しいファイルを保存
            $path = $file->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's AI character profiles (nickname, speech style, genres per character).
     */
    public function updateCharacters(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'characters' => ['required', 'array'],
            'characters.zundamon' => ['nullable', 'array'],
            'characters.zundamon.nickname' => ['nullable', 'string', 'max:100'],
            'characters.zundamon.speech_style' => ['nullable', 'string', Rule::in(['friendly', 'polite', 'logical'])],
            'characters.zundamon.favorite_genres' => ['nullable', 'array'],
            'characters.zundamon.favorite_genres.*' => ['string', 'max:50'],
            'characters.zundamon.custom_note' => ['nullable', 'string', 'max:500'],
            'characters.metan' => ['nullable', 'array'],
            'characters.metan.nickname' => ['nullable', 'string', 'max:100'],
            'characters.metan.speech_style' => ['nullable', 'string', Rule::in(['friendly', 'polite', 'logical'])],
            'characters.metan.favorite_genres' => ['nullable', 'array'],
            'characters.metan.favorite_genres.*' => ['string', 'max:50'],
            'characters.metan.custom_note' => ['nullable', 'string', 'max:500'],
            'characters.tsumugi' => ['nullable', 'array'],
            'characters.tsumugi.nickname' => ['nullable', 'string', 'max:100'],
            'characters.tsumugi.speech_style' => ['nullable', 'string', Rule::in(['friendly', 'polite', 'logical'])],
            'characters.tsumugi.favorite_genres' => ['nullable', 'array'],
            'characters.tsumugi.favorite_genres.*' => ['string', 'max:50'],
            'characters.tsumugi.custom_note' => ['nullable', 'string', 'max:500'],
        ]);

        $userId = (string) $request->user()->id;

        foreach (self::SELECTABLE_CHARACTERS as $character) {
            $data = $validated['characters'][$character] ?? [];
            $nickname = $data['nickname'] ?? null;
            $speechStyle = $data['speech_style'] ?? 'friendly';
            $favoriteGenres = $data['favorite_genres'] ?? [];
            $customNote = $data['custom_note'] ?? null;

            UserCharacterProfile::updateOrCreate(
                ['user_id' => $userId, 'character' => $character],
                [
                    'nickname' => $nickname ? trim($nickname) : null,
                    'speech_style' => $speechStyle,
                    'favorite_genres' => array_values(array_filter(array_map('trim', $favoriteGenres))),
                    'custom_note' => $customNote ? trim($customNote) : null,
                ]
            );
        }

        return Redirect::route('profile.edit')->with('status', 'characters-saved');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(ProfileDestroyRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
