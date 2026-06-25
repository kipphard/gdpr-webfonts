=== DSGVO Webfonts & externe Anfragen ===
Contributors: kipphard
Tags: dsgvo, gdpr, google fonts, datenschutz, privacy
Requires at least: 6.4
Tested up to: 6.7
Stable tag: 0.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Findet externe Anfragen (Google Fonts, YouTube, Analytics, Maps, Gravatar …), die Daten an Dritte übertragen, und hilft sie DSGVO-konform zu entfernen. Ehrlicher Scan – kein Cookie-Banner.

== Description ==

**DSGVO Webfonts & externe Anfragen** ist ein ehrliches Analyse-Werkzeug für WordPress-Websites. Es scannt das tatsächlich gerenderte HTML deiner Seiten und erkennt externe Anfragen, die personenbezogene Daten (IP-Adressen u. a.) an Drittanbieter übertragen – darunter Google Fonts, Google Analytics, YouTube-Embeds, Google Maps, Gravatar, reCAPTCHA, Facebook-Pixel, Hotjar und viele weitere.

Das Plugin gibt dir einen klaren Überblick über DSGVO-relevante Datenschutzrisiken und liefert konkrete Handlungsempfehlungen. Außerdem kannst du häufige Probleme direkt über die Plugin-Einstellungen beheben.

**Was dieses Plugin tut:**

* Scannt die Startseite sowie bis zu 4 zuletzt veröffentlichte Seiten und Beiträge (Free)
* Erkennt 14 bekannte externe Dienste-Kategorien: Fonts, Analytics/Tracking, Video-Embeds, Karten, Avatare, CAPTCHAs, Social-Media-Pixel, CDN-Skripte u. v. m.
* Klassifiziert jede Anfrage nach Risiko-Level (Hoch / Mittel / Niedrig) mit deutschen Handlungsempfehlungen
* Bietet direkte Schnell-Maßnahmen: Google Fonts entfernen, WordPress-Emojis deaktivieren, Gravatar lokal ersetzen
* Liefert Beispiel-URLs für jede erkannte externe Anfrage

**Was dieses Plugin NICHT tut:**

Dieses Plugin ist *kein Cookie-Banner-Overlay* und behauptet *keine automatische DSGVO-Konformität*. Der Scan ist statisch – per JavaScript dynamisch nachgeladene Anfragen werden ggf. nicht erkannt. Das Plugin ersetzt keine Rechtsberatung.

*This plugin is an honest GDPR audit tool for German-speaking WordPress sites. It scans your rendered HTML for external requests that may transfer personal data (IP addresses) to third parties, and provides actionable guidance.*

== Features (Free) ==

* Scan der Startseite + bis zu 4 weiterer Seiten/Beiträge
* Erkennung von 14 bekannten externen Dienst-Kategorien
* Risiko-Priorisierung (Hoch / Mittel / Niedrig)
* Deutsche Handlungsempfehlungen je Dienst
* Schnell-Maßnahmen: Google Fonts entfernen, Emojis deaktivieren, Gravatar lokal ersetzen
* SSRF-Schutz: nur eigene URLs werden gescannt
* Keine externen Anfragen, kein Tracking durch das Plugin selbst

== Pro ==

Eine Pro-Version mit erweiterten Funktionen ist in Vorbereitung:

* Google Fonts automatisch lokal hosten (Schriften bleiben erhalten)
* YouTube (nocookie), Google Maps und Vimeo als Klick-zum-Laden-Platzhalter
* Geplanter automatischer Re-Scan + E-Mail-Bericht
* Scan aller veröffentlichten Seiten (bis zu 200 URLs)
* Multisite-Unterstützung
* PDF-Konformitätsbericht

Mehr Informationen: https://products.kipphard.com/dsgvo-webfonts

== Installation ==

1. Lade das Plugin-Verzeichnis `dsgvo-webfonts` in das `/wp-content/plugins/`-Verzeichnis hoch.
2. Aktiviere das Plugin unter "Plugins" in der WordPress-Administration.
3. Navigiere zu **DSGVO Webfonts → Dashboard** und klicke auf "Scan starten".
4. Prüfe die Ergebnisse und aktiviere unter **DSGVO Webfonts → Einstellungen** die gewünschten Schnell-Maßnahmen.

== Frequently Asked Questions ==

= Macht dieses Plugin meine Website automatisch DSGVO-konform? =

Nein. Dieses Plugin ist ein Analyse-Werkzeug. Es zeigt dir, welche externen Anfragen von deiner Website ausgehen, und gibt Handlungsempfehlungen. Die eigentliche Umsetzung – Schriften lokal hosten, Einwilligungen einholen, Dienste ersetzen – musst du oder dein Entwicklungsteam selbst vornehmen. Lass dich im Zweifelsfall von einer Datenschutzfachkraft oder Anwältin/einem Anwalt beraten.

= Wie viele Seiten werden gescannt? =

Die kostenlose Version scannt die Startseite sowie bis zu 4 zuletzt veröffentlichte Seiten und Beiträge. Die Pro-Version scannt alle veröffentlichten Seiten (bis zu 200 URLs).

= Werden durch den Scan Daten an externe Server übertragen? =

Nein. Das Plugin ruft nur deine eigenen URLs intern per HTTP ab und analysiert das zurückgegebene HTML lokal. Es werden keine Daten an Dritte übertragen.

= Was bedeutet der Hinweis "per JavaScript dynamisch nachgeladene Anfragen werden ggf. nicht erkannt"? =

Das Plugin analysiert das initial gerenderte HTML. Anfragen, die erst nach dem Laden der Seite durch JavaScript ausgelöst werden (z. B. beim Scrollen oder Klicken), sind im statischen HTML nicht sichtbar und werden daher nicht erkannt. Eine vollständige Prüfung erfordert zusätzlich Werkzeuge wie den Browser-Netzwerk-Tab oder spezialisierte Datenschutz-Audit-Dienste.

= Der Scan schlägt fehl oder liefert keine Ergebnisse. Was kann ich tun? =

Stelle sicher, dass deine Website von sich selbst erreichbar ist (kein "Demnächst verfügbar"-Modus, kein HTTP-Authentifizierungsschutz). Das Plugin ruft deine eigenen URLs intern per HTTP ab. Prüfe auch, ob die PHP-Extensions `dom` und `libxml` verfügbar sind.

= Warum gilt Google Fonts als "Hohes Risiko"? =

Das Landgericht München I hat am 20. Januar 2022 (Az. 3 O 17493/20) entschieden, dass die Einbindung von Google Fonts ohne Einwilligung der Besucher einen Verstoß gegen die DSGVO darstellt, da dabei die IP-Adresse an Google-Server in den USA übertragen wird. Das Plugin weist daher auf dieses Risiko hin.

== Changelog ==

= 0.1.0 =
* Erstveröffentlichung
* Erkennung von 14 externen Dienst-Kategorien (Google Fonts, Analytics, YouTube, Maps, Gravatar, reCAPTCHA, Facebook, Hotjar, Font Awesome, JS-CDNs, WordPress-Emojis u. a.)
* Risiko-Klassifikation (Hoch / Mittel / Niedrig) mit deutschen Handlungsempfehlungen
* Schnell-Maßnahmen: Google Fonts entfernen, Emojis deaktivieren, Gravatar lokal ersetzen
* SSRF-Schutz: nur URLs des eigenen Hosts werden gescannt
* Keine externen Abhängigkeiten, kein Tracking
