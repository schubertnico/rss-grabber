<?php
/**
 * -----------------------------------------
 * RSS Grabber free v3.0
 * -----------------------------------------
 * Datenzugriff fuer Admin-Anmeldedaten.
 *
 * @version free v3.0 (PHP 8.5)
 */
class AdminRepository
{
    public function __construct(private mysqli $link)
    {
    }

    /**
     * Prueft Benutzername/Passwort gegen die admin-Tabelle (bcrypt).
     */
    public function verifyLogin(string $username, string $password): bool
    {
        $stmt = mysqli_prepare($this->link, "SELECT password_hash FROM `admin` WHERE `username` = ? LIMIT 1");
        if ($stmt === false) {
            return false;
        }
        $hash = '';
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $hash);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        return is_string($hash) && $hash !== '' && password_verify($password, $hash);
    }
}
