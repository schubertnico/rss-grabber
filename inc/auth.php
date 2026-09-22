<?php
/**
 * Authentifizierung & CSRF-Schutz fuer den RSS-Grabber-Verwaltungsbereich.
 *
 * @version free v3.0 (PHP 8.5)
 */

// Öffentliche Seiten (ausgabe.php, premium-version.php) binden diese Datei
// nur ein, um den Anmeldestatus für die Navigation zu kennen. Sie setzen
// vorher RSSG_SESSION_NUR_MIT_COOKIE und bekommen dann keine Sitzung für
// jeden Besucher: Wer kein Sitzungscookie mitbringt, kann nicht angemeldet
// sein - die Frage lässt sich also ohne session_start() beantworten.
if (
    session_status() === PHP_SESSION_NONE
    && (defined('RSSG_SESSION_NUR_MIT_COOKIE') === false || isset($_COOKIE[session_name()]))
) {
    // Sichere Session-Cookies (HttpOnly, SameSite=Lax).
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (function_exists('rssg_csrf_token') === false) {
    /**
     * Liefert das CSRF-Token der aktuellen Session (erzeugt es bei Bedarf).
     */
    function rssg_csrf_token(): string
    {
        if (empty($_SESSION['rssg_csrf']) || !is_string($_SESSION['rssg_csrf'])) {
            $_SESSION['rssg_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['rssg_csrf'];
    }

    /**
     * Prueft ein uebermitteltes CSRF-Token gegen die Session (zeitkonstant).
     */
    function rssg_csrf_check(mixed $token): bool
    {
        return isset($_SESSION['rssg_csrf'])
            && is_string($_SESSION['rssg_csrf'])
            && is_string($token)
            && hash_equals($_SESSION['rssg_csrf'], $token);
    }

    /**
     * Versteckte CSRF-Eingabe fuer Formulare.
     */
    function rssg_csrf_field(): string
    {
        return '<input type="hidden" name="csrf" value="' . htmlspecialchars(rssg_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Ist ein Admin angemeldet?
     */
    function rssg_is_logged_in(): bool
    {
        return !empty($_SESSION['rssg_admin']);
    }

    /**
     * Der Zugangspunkt der Navigation: abgemeldet "Anmelden", sonst "Logout".
     *
     * Vorher stand dort immer "Logout". Wer die oeffentliche Beitragsanzeige
     * aufrief, fand damit keinen Weg in den Verwaltungsbereich und musste
     * login.php erraten.
     */
    function rssg_nav_zugang(): string
    {
        return rssg_is_logged_in()
            ? '<a href="logout.php">Logout</a>'
            : '<a href="login.php">Anmelden</a>';
    }

    /**
     * Erzwingt eine Anmeldung; leitet sonst auf login.php um.
     */
    function rssg_require_login(): void
    {
        if (rssg_is_logged_in() === false) {
            if (headers_sent() === false) {
                header('Location: ./login.php');
            }
            exit;
        }
    }
}
