<?php

namespace MagicProSrc\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use MagicProDatabaseModels\MagicProEvent;
use MagicProSrc\Config\MagicGlobals;

/**
 * Sign-up and sign-in of visitors of the site. Public: `POST /api/auth` for a
 * form, `API_SiteAuth::run()` for a blade.
 *
 * Nothing is written to the database before a person follows the link from a
 * letter: everything needed travels in an encrypted token. A person who asked
 * for a letter twice and clicked the first one is not turned away — any token
 * within its lifetime works.
 *
 * Every refusal carries `data.errorCode`, the page chooses its text by it.
 * Settings — group AUTH, edited on the admin screen «Пользователи».
 */
class API_SiteAuth extends AbstractApi
{
    use SiteUserTools;

    /** Kinds of tokens: the form between steps and the links from letters. */
    protected const TOKEN_EMAIL = 'email';
    protected const TOKEN_REGISTER = 'register';
    protected const TOKEN_RESET = 'reset';

    /** A letter of one kind to one address — not more often than this. */
    protected const LETTER_PAUSE_MINUTES = 10;

    /**
     * What each link letter needs: the page the link leads to, the blade, the
     * variable of the link in the blade, the lifetime setting and the key of
     * the pause between letters.
     */
    protected const LETTERS = [
        self::TOKEN_REGISTER => [
            'url'     => 'registerUrl',
            'blade'   => 'authLetter',
            'var'     => 'authLinkUrl',
            'minutes' => 'registerTokenMinutes',
            'event'   => 'registration',
            'subject' => 'Confirm your email',
        ],
        self::TOKEN_RESET => [
            'url'     => 'resetPasswordUrl',
            'blade'   => 'resetPasswordLetter',
            'var'     => 'authResetPasswordUrl',
            'minutes' => 'resetPasswordTokenMinutes',
            'event'   => 'reset_password',
            'subject' => 'Password change',
        ],
    ];

    protected const POST_LETTER_SUBJECT = 'Registration completed';

    protected array $map = [
        'checkEmail'              => 'checkEmail',
        'sendRegisterEmail'       => 'sendRegisterEmail',
        'auth'                    => 'auth',
        'sendChangePasswordEmail' => 'sendChangePasswordEmail',
        'registerUserByToken'     => 'registerUserByToken',
        'changePassword'          => 'changePassword',
        'renewLink'               => 'renewLink',
    ];

    // ==================================================================
    //                     commands
    // ==================================================================

    /** reCAPTCHA, a valid email, and whether such a user exists. */
    protected static function checkEmail(array $params): array
    {
        self::requireCaptcha($params);

        $email = self::cleanEmail($params['email'] ?? '')
            ?? throw new ApiError('bad_email', 'invalid email');

        return [
            'emailToken'  => self::makeToken(self::TOKEN_EMAIL, $email),
            'emailActive' => User::where('email', $email)->exists(),
        ];
    }

    /** The letter authLetter with the link to registerUrl. */
    protected static function sendRegisterEmail(array $params): array
    {
        self::requireCaptcha($params);

        $token = self::readLiveToken($params['emailToken'] ?? '', [self::TOKEN_EMAIL]);

        self::sendLinkLetter(self::TOKEN_REGISTER, $token['email'], self::postUrl($params));

        return [];
    }

    /** Sign in by the email of emailToken and a password. */
    protected static function auth(array $params): array
    {
        $token = self::readLiveToken($params['emailToken'] ?? '', [self::TOKEN_EMAIL]);

        if (!User::where('email', $token['email'])->exists()) {
            throw new ApiError('user_not_found', 'user not found');
        }

        $password = trim((string) ($params['password'] ?? ''));
        $error = self::attemptLogin($token['email'], $password);

        if ($error !== '') {
            throw new ApiError($error, str_replace('_', ' ', $error));
        }

        return [
            'postUrl' => self::postUrl([]),
        ];
    }

    /** The letter resetPasswordLetter with the link to resetPasswordUrl. */
    protected static function sendChangePasswordEmail(array $params): array
    {
        self::requireCaptcha($params);

        $token = self::readLiveToken($params['emailToken'] ?? '', [self::TOKEN_EMAIL]);

        if (!User::where('email', $token['email'])->exists()) {
            throw new ApiError('user_not_found', 'user not found');
        }

        self::sendLinkLetter(self::TOKEN_RESET, $token['email'], self::postUrl($params));

        return [];
    }

    /**
     * The page registerUrl: first call without a password. A user that
     * already exists is signed in quietly; a new one needs a password, then
     * gets created with a confirmed email and receives postLetter.
     */
    protected static function registerUserByToken(array $params): array
    {
        $token = self::readToken($params['token'] ?? '', [self::TOKEN_REGISTER]);
        $postUrl = $token['postUrl'];

        $current = Auth::user();

        if ($current) {
            if (mb_strtolower((string) $current->email) === $token['email']) {
                return ['postUrl' => $postUrl];
            }

            throw new ApiError('logged_in_as_other', 'logged in as another user');
        }

        if ($token['expired']) {
            throw new ApiError('token_expired', 'token expired');
        }

        $user = User::where('email', $token['email'])->first();

        if ($user) {
            self::loginUser($user);

            return ['postUrl' => $postUrl];
        }

        $password = trim((string) ($params['password'] ?? ''));

        if ($password === '') {
            throw new ApiError('password_required', 'password required');
        }

        $password = self::cleanPassword($password)
            ?? throw new ApiError('bad_password', 'Password must be at least 8 characters');

        try {
            $user = User::create([
                'name'     => '',
                'email'    => $token['email'],
                'password' => Hash::make($password),
            ]);

            // пришёл по ссылке из письма — значит, адрес его. Поле не в
            // fillable модели хоста, поэтому пишется мимо create()
            User::whereKey($user->id)->update(['email_verified_at' => now()]);
        } catch (\Throwable $e) {
            throw new ApiError('registration_failed', $e->getMessage());
        }

        self::loginUser($user);
        self::sendPostLetter($token['email'], $password);

        return ['postUrl' => $postUrl];
    }

    /** The page resetPasswordUrl: a new password, then signed in. */
    protected static function changePassword(array $params): array
    {
        $token = self::readLiveToken($params['token'] ?? '', [self::TOKEN_RESET]);

        $user = User::where('email', $token['email'])->first()
            ?? throw new ApiError('user_not_found', 'user not found');

        $password = self::cleanPassword($params['password'] ?? '')
            ?? throw new ApiError('bad_password', 'Password must be at least 8 characters');

        $user->password = Hash::make($password);
        $user->save();

        self::loginUser($user);

        return ['postUrl' => $token['postUrl']];
    }

    /**
     * A fresh letter of the same kind by an old link: the email and postUrl
     * are still inside an expired token, nothing has to be typed again.
     */
    protected static function renewLink(array $params): array
    {
        self::requireCaptcha($params);

        $token = self::readToken($params['token'] ?? '', [self::TOKEN_REGISTER, self::TOKEN_RESET]);

        self::sendLinkLetter($token['type'], $token['email'], $token['postUrl']);

        return [];
    }

    // ==================================================================
    //                     reCAPTCHA
    // ==================================================================

    /**
     * Checks a reCAPTCHA token with Google.
     *
     * An empty RECAPTCHA_SECRET_KEY is a broken site, not a robot: HTTP 500,
     * so the owner sees it at once instead of every visitor being refused.
     * The key is read by env(): after `config:cache` it is empty until the
     * cache is cleared.
     */
    public static function verifyCaptcha(string $token): bool
    {
        $secret = (string) env('RECAPTCHA_SECRET_KEY');

        if ($secret === '') {
            abort(500, 'RECAPTCHA_SECRET_KEY is not configured');
        }

        if ($token === '') {
            return false;
        }

        try {
            $res = Http::asForm()
                ->connectTimeout(2)
                ->timeout(4)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Throwable) {
            return false;
        }

        return $res->successful() && ($res->json('success') === true);
    }

    protected static function requireCaptcha(array $params): void
    {
        if (!self::verifyCaptcha((string) ($params['gToken'] ?? ''))) {
            throw new ApiError('captcha_failed', 'captcha verification failed');
        }
    }

    // ==================================================================
    //                     tokens
    // ==================================================================

    protected static function makeToken(string $type, string $email, string $postUrl = ''): string
    {
        $encrypted = Crypt::encryptString(json_encode([
            'type'    => $type,
            'email'   => $email,
            'postUrl' => $postUrl,
            'time'    => now()->timestamp,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    /**
     * Opens a token of one of the given kinds.
     *
     * An expired token still opens — `expired` says so: by an old link the
     * email is known and a new letter can go out. A token that does not
     * decrypt, or is of another kind, is `token_invalid`.
     */
    protected static function readToken(mixed $token, array $types): array
    {
        $token = is_string($token) ? $token : '';

        try {
            $token .= str_repeat('=', (4 - strlen($token) % 4) % 4);
            $encrypted = base64_decode(strtr($token, '-_', '+/'), true);

            if ($encrypted === false) {
                throw new \Exception('not base64');
            }

            $data = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new ApiError('token_invalid', 'token invalid');
        }

        if (
            !is_array($data)
            || !in_array($data['type'] ?? null, $types, true)
            || !is_string($data['email'] ?? null)
            || !is_int($data['time'] ?? null)
        ) {
            throw new ApiError('token_invalid', 'token invalid');
        }

        $minutes = (int) self::setting(
            $data['type'] === self::TOKEN_EMAIL ? 'emailTokenMinutes' : self::LETTERS[$data['type']]['minutes']
        );

        return [
            'type'    => $data['type'],
            'email'   => $data['email'],
            'postUrl' => self::localPath($data['postUrl'] ?? ''),
            'expired' => now()->timestamp - $data['time'] > $minutes * 60,
        ];
    }

    protected static function readLiveToken(mixed $token, array $types): array
    {
        $data = self::readToken($token, $types);

        if ($data['expired']) {
            throw new ApiError('token_expired', 'token expired');
        }

        return $data;
    }

    // ==================================================================
    //                     letters
    // ==================================================================

    /** A letter with a link: registration or password change. */
    protected static function sendLinkLetter(string $type, string $email, string $postUrl): void
    {
        $letter = self::LETTERS[$type];
        $page = trim((string) self::setting($letter['url']));
        $blade = trim((string) self::setting($letter['blade']));

        if ($page === '' || $blade === '') {
            throw new ApiError('settings_missing', 'not set: ' . $letter['url'] . ' or ' . $letter['blade']);
        }

        $event = 'mail_' . $email . '_' . $letter['event'];

        if (!MagicProEvent::addEvent($event, now()->addMinutes(self::LETTER_PAUSE_MINUTES))) {
            throw new ApiError('letter_already_sent', 'the letter has already been sent');
        }

        $link = url(rtrim($page, '/') . '/' . self::makeToken($type, $email, $postUrl));

        try {
            $html = view($blade, [$letter['var'] => $link])->render();
            $res = \MproHelper::sendMail([
                'email' => $email,
                'subj'  => self::subject($html, $letter['subject']),
                'html'  => $html,
            ]);

            if (!$res['status']) {
                throw new \Exception($res['errorMsg']);
            }
        } catch (\Throwable $e) {
            // письмо не ушло — пауза не нужна, человек может попробовать снова
            MagicProEvent::where('key', mb_strtolower($event))->delete();

            throw new ApiError('letter_failed', $e->getMessage());
        }
    }

    /**
     * After registration. The account already exists, so a letter that did
     * not go out breaks nothing: it is logged, the registration stands.
     */
    protected static function sendPostLetter(string $email, string $password): void
    {
        $blade = trim((string) self::setting('postLetter'));

        if ($blade === '') {
            return;
        }

        try {
            $html = view($blade, ['userEmail' => $email, 'userPassword' => $password])->render();

            \MproHelper::sendMail([
                'email' => $email,
                'subj'  => self::subject($html, self::POST_LETTER_SUBJECT),
                'html'  => $html,
            ]);
        } catch (\Throwable $e) {
            \MproHelper::addLog('api', [
                'api'     => static::class,
                'command' => 'postLetter',
                'error'   => $e->getMessage(),
                'where'   => $e->getFile() . ' ' . $e->getLine(),
            ]);
        }
    }

    /** The subject is the <title> of the letter, otherwise a plain default. */
    protected static function subject(string $html, string $default): string
    {
        if (preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5));

            if ($title !== '') {
                return $title;
            }
        }

        return $default;
    }

    // ==================================================================
    //                     settings and paths
    // ==================================================================

    /** A value of the group AUTH; the schema default until settings are saved. */
    protected static function setting(string $key): mixed
    {
        $value = MagicGlobals::$INI['AUTH'][$key] ?? null;

        if ($value === null) {
            $schema = require MagicGlobals::$dataSchema;
            $value = $schema['AUTH']['data'][$key]['default'] ?? '';
        }

        return $value;
    }

    /** postUrl from the request, otherwise from settings; a path of this site. */
    protected static function postUrl(array $params): string
    {
        $postUrl = trim((string) ($params['postUrl'] ?? ''));

        return self::localPath($postUrl !== '' ? $postUrl : (string) self::setting('postUrl'));
    }

    /**
     * Where to send a person: a path on this site, or `/`.
     *
     * postUrl may come from the request, and a form would take
     * `https://evil.site` just as well — the site would send its own user
     * there. So only a path is accepted: it starts with `/`, and not with `//`
     * or `/\`, which browsers read as another host.
     */
    protected static function localPath(mixed $path): string
    {
        $path = trim(is_string($path) ? $path : '');

        if (
            !str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || str_starts_with($path, '/\\')
            || preg_match('/[\x00-\x1F\x7F]/', $path)
        ) {
            return '/';
        }

        return $path;
    }
}
