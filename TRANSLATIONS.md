# Übersetzungen (DE / EN / FR) – Anleitung

## Hintergrund

OroCommerce lädt Übersetzungen **nicht automatisch** aus den YAML-Dateien.
Sie müssen manuell in die OroCommerce-Datenbank importiert werden.

Die eigenen (spot7) Übersetzungen liegen in:

```
translations/
  messages.en.yml      → Englisch
  messages.de_DE.yml   → Deutsch
  messages.fr_FR.yml   → Französisch
```

---

## Neue Übersetzung hinzufügen oder ändern

1. Die gewünschte YAML-Datei bearbeiten, z. B. einen neuen Key hinzufügen:

```yaml
# translations/messages.fr_FR.yml
spot7:
  landing:
    neuer_key: 'Texte en français'
```

2. Den gleichen Key in **allen** Sprachdateien ergänzen.

3. Änderungen committen und auf den Server deployen.

4. Auf dem Server die folgenden drei Befehle ausführen (siehe unten).

---

## Befehle nach jeder Änderung an Übersetzungsdateien

Diese Befehle müssen **lokal und auf dem Produktionsserver** ausgeführt werden:

```bash
# 1. Übersetzungen aus YAML in die Datenbank laden
php bin/console oro:translation:load --env=prod

# 2. Translation-Cache neu aufbauen
php bin/console oro:translation:rebuild-cache --env=prod

# 3. Symfony-Cache leeren
php bin/console cache:clear --env=prod
```

> Mit `symfony console` statt `php bin/console` falls Symfony CLI genutzt wird.

---

## Warum zeigt die Seite den Schlüssel statt dem Text?

Wenn auf der Seite z. B. `spot7.landing.title_prefix` statt dem übersetzten Text erscheint, wurden die Übersetzungen noch nicht in die Datenbank importiert.

→ Die drei Befehle oben ausführen.

---

## Wichtig: Sprachauswahl in OroCommerce

OroCommerce nutzt **Localizations** (Admin → System → Localizations) um zu steuern, welche Sprach-Codes aktiv sind. Folgende Locales müssen dort aktiviert sein:

| Locale  | Name              |
|---------|-------------------|
| `en`    | English           |
| `de_DE` | Deutsch           |
| `fr_FR` | Français          |

Falls eine Sprache im Frontend wählbar ist, die Übersetzungen aber auf Englisch bleiben, fehlt der Import (Befehle oben) oder der Locale-Code stimmt nicht überein.
