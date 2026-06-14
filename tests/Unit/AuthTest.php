<?php

declare(strict_types=1);

namespace RssGrabber\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

require_once RSSG_ROOT . '/classes/function.php';

final class AuthTest extends TestCase
{
    public function testEscapingKodiertHtmlSonderzeichen(): void
    {
        self::assertSame('&lt;b&gt;&quot;x&quot;&amp;&lt;/b&gt;', rssg_e('<b>"x"&</b>'));
        self::assertSame('', rssg_e(null));
        self::assertSame('Schöne Grüße äöüß', rssg_e('Schöne Grüße äöüß'));
    }

    public function testSafeUrlErlaubtNurHttpUndHttps(): void
    {
        self::assertSame('https://example.com', rssg_safe_url('https://example.com'));
        self::assertSame('http://example.com', rssg_safe_url('http://example.com'));
        self::assertSame('#', rssg_safe_url('javascript:alert(1)'));
        self::assertSame('#', rssg_safe_url('  '));
        self::assertSame('#', rssg_safe_url(null));
        // gefährliche Zeichen werden escaped
        self::assertStringContainsString('&quot;', rssg_safe_url('https://e.com/?a="x"'));
    }

    public function testPasswortHashRoundtrip(): void
    {
        $hash = password_hash('geheim', PASSWORD_DEFAULT);
        self::assertTrue(password_verify('geheim', $hash));
        self::assertFalse(password_verify('falsch', $hash));
    }

    public function testDefaultAdminHashPasstZuAdmin(): void
    {
        // Der im Init-Skript hinterlegte Default-Hash muss zu 'admin' passen.
        $hash = '$2y$12$u7JQc1MKJTjjJBY7e6Y61uWg4Sy4MxxvFKpnpen1.mlUQ1PaINhTm';
        self::assertTrue(password_verify('admin', $hash));
    }

    #[RunInSeparateProcess]
    public function testCsrfTokenIstStabilUndPruefbar(): void
    {
        require_once RSSG_ROOT . '/inc/auth.php';
        $token = rssg_csrf_token();
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        self::assertSame($token, rssg_csrf_token());
        self::assertTrue(rssg_csrf_check($token));
        self::assertFalse(rssg_csrf_check('falsch'));
        self::assertFalse(rssg_csrf_check(null));
    }
}
