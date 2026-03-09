<?php

declare(strict_types=1);

/**
 * Generates synthetic test data.
 * No external dependencies — uses only mt_rand and str_* functions.
 */
class DataGenerator
{
    // -------------------------------------------------------------------------
    // Word lists for usernames
    // -------------------------------------------------------------------------

    private const ADJECTIVES = [
        'Schneller', 'Wilder', 'Blauer', 'Stiller', 'Frecher', 'Alter', 'Junger',
        'Mutiger', 'Kluger', 'Fleißiger', 'Fauler', 'Starker', 'Großer', 'Kleiner',
        'Dicker', 'Dünner', 'Glücklicher', 'Trauriger', 'Böser', 'Guter', 'Lustiger',
        'Grüner', 'Roter', 'Schwarzer', 'Weißer', 'Flinker', 'Träger', 'Echter',
        'Neuer', 'Tapferer', 'Kühner', 'Sanfter', 'Rauer', 'Heller', 'Dunkler',
        'Wilder', 'Leiser', 'Lauter', 'Rascher', 'Schwerer', 'Leichter', 'Spitzer',
        'Runder', 'Langer', 'Kurzer', 'Breiter', 'Schmaler', 'Tiefer', 'Hoher', 'Flacher',
    ];

    private const NOUNS = [
        'Fuchs', 'Wolf', 'Bär', 'Adler', 'Falke', 'Tiger', 'Löwe', 'Panther',
        'Drache', 'Rabe', 'Luchs', 'Dachs', 'Biber', 'Otter', 'Marder',
        'Stier', 'Hengst', 'Widder', 'Eber', 'Hirsch', 'Ritter', 'Jäger',
        'Krieger', 'Magier', 'Druide', 'Söldner', 'Meister', 'Forscher',
        'Reisender', 'Händler', 'Fischer', 'Schmied', 'Bauer', 'Mönch',
        'Wächter', 'Knappe', 'Räuber', 'Pirat', 'Seemann', 'Bergmann',
        'Töpfer', 'Mahler', 'Gärtner', 'Jünger', 'Schüler', 'Lehrling',
        'Botschafter', 'Späher', 'Anführer', 'Verteidiger',
    ];

    // -------------------------------------------------------------------------
    // Words for message bodies (Latin placeholder)
    // -------------------------------------------------------------------------

    private const BODY_WORDS = [
        'Lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing',
        'elit', 'sed', 'eiusmod', 'tempor', 'incididunt', 'labore', 'dolore',
        'magna', 'aliqua', 'enim', 'minim', 'veniam', 'quis', 'nostrud',
        'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'commodo',
        'consequat', 'duis', 'aute', 'irure', 'reprehenderit', 'voluptate',
        'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur',
        'sint', 'occaecat', 'cupidatat', 'proident', 'sunt', 'culpa', 'officia',
        'deserunt', 'mollit', 'anim', 'laborum', 'perspiciatis', 'unde', 'omnis',
    ];

    private static int $adjCount  = 0;
    private static int $nounCount = 0;
    private static int $wordCount = 0;

    public static function init(): void
    {
        self::$adjCount  = count(self::ADJECTIVES);
        self::$nounCount = count(self::NOUNS);
        self::$wordCount = count(self::BODY_WORDS);
    }

    // -------------------------------------------------------------------------
    // Public generators
    // -------------------------------------------------------------------------

    /**
     * Generates a unique username based on the user ID.
     * Adjective + noun + number — sounds logical, only repeats with a different number.
     */
    public static function username(int $userId): string
    {
        $adj  = self::ADJECTIVES[$userId % self::$adjCount];
        $noun = self::NOUNS[intdiv($userId, self::$adjCount) % self::$nounCount];
        $num  = ($userId % 9_999) + 1;
        return $adj . $noun . $num;
    }

    /**
     * Generates a short random message body (80–120 characters).
     * No semantic content, pure placeholders.
     */
    public static function messageBody(): string
    {
        $result = '';
        $wc     = self::$wordCount;

        while (strlen($result) < 80) {
            $result .= ' ' . self::BODY_WORDS[mt_rand(0, $wc - 1)];
        }

        return ltrim(substr($result, 0, 120));
    }

    /**
     * Generates a message subject.
     */
    public static function subject(int $threadId, bool $isReply = false): string
    {
        return $isReply ? 'Re: Thema ' . $threadId : 'Thema ' . $threadId;
    }

    /**
     * Generates a deterministic, unique password key (32 characters, hex).
     */
    public static function passwordKey(int $userId): string
    {
        return md5('pxm_seeder_v1_' . $userId);
    }

    /**
     * Generates a test email address.
     */
    public static function email(int $userId): string
    {
        return 'user' . $userId . '@test.local';
    }

    /**
     * Returns a once-computed bcrypt hash for the test password.
     * Computed once and cached.
     */
    private static ?string $passwordHash = null;

    public static function passwordHash(): string
    {
        if (self::$passwordHash === null) {
            self::$passwordHash = password_hash('test1234', PASSWORD_BCRYPT, ['cost' => 10]);
        }
        return self::$passwordHash;
    }

    /**
     * Generates a random timestamp between $start and $end.
     */
    public static function randomTimestamp(int $start, int $end): int
    {
        return mt_rand($start, max($start, $end));
    }

    /**
     * Generates N ascending timestamps in the range [$start, $end].
     * Used for messages within a thread.
     *
     * @return int[]
     */
    public static function ascendingTimestamps(int $n, int $start, int $end): array
    {
        if ($n === 1) {
            return [$start];
        }

        $times = [];
        for ($i = 0; $i < $n; $i++) {
            $times[] = mt_rand($start, $end);
        }
        sort($times);
        return $times;
    }
}
