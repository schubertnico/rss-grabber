---
description: Erstellt Demonstrations-Screenshots aller Unterseiten der App nach Screenshots/v<version>/
argument-hint: "[version]"
---

Erstelle Demonstrations-Screenshots aller Unterseiten der RSS-Grabber-App fuer
die angegebene (oder aus `inc/config.php` ermittelte) Version.

## Schritte

1. Stelle sicher, dass der Docker-Stack laeuft (Container `rss-grabber_web`).
   Falls nicht: `cd .docker && docker compose up -d` und kurz warten.

2. Fuehre das Screenshot-Skript im Playwright-Image gegen die laufende App
   (`http://web` im Projekt-Netzwerk) aus. Wenn ein Versionsargument uebergeben
   wurde, reiche es als letztes Argument an das Skript weiter:

   ```
   docker run --rm --network rss-grabber_rss-grabber-network \
     -e BASE_URL=http://web \
     -v "<PROJEKT-PFAD>:/work" -w /work \
     mcr.microsoft.com/playwright:v1.50.0-noble \
     bash -c "node build/screenshots.mjs $ARGUMENTS"
   ```

   (Unter Windows/Git-Bash `MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL="*"` voranstellen.)

3. Das Skript meldet sich mit `admin`/`admin` an und legt nach
   `Screenshots/v<version>/` ab: Login, Beitragsanzeige, Feeds verwalten,
   Feed hinzufuegen, Feed bearbeiten, Synchronisieren, Premium, Installation.

4. Pruefe stichprobenartig, dass die PNGs sinnvolle Inhalte zeigen (z. B. mit
   Read auf 1-2 Bilder), und berichte die Anzahl und den Zielordner.

## Hinweise

- Die Beitragsanzeige (`ausgabe.php`) zeigt nur dann Inhalte, wenn bereits Feeds
  synchronisiert wurden. Sind die externen Feeds nicht erreichbar, vorab einmal
  ueber "Feeds synchronisieren" Inhalte anlegen.
- Die Screenshots werden eingecheckt (Demo-Material), liegen also bewusst nicht
  im `.gitignore`.
