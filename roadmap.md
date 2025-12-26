# Roadmap: Transformation zu einer Online-Zeitung

Dieses Dokument beschreibt die schrittweise Transformation des Symfony Demo Blogs in eine moderne, community-basierte Online-Zeitung.

## Übersicht

**Ziel:** Eine Online-Zeitung mit:
- Zeitungsartikeln statt Blogposts
- Bildern und Videos in Artikeln
- Kategorien/Themen (Politik, Inland, Ausland, Wirtschaft, Sport, Gesellschaft)
- Zeitungsähnliches Design
- Community-Funktionen (Leser als Autoren)
- Admin-Freigabe-Workflow
- Werbung & Einnahmenverteilung zwischen Autor und Zeitung

---

## Phase 1: Grundlegende Umbenennung & Struktur (MVP)

**Ziel:** Blog → Artikel-System, Basis-Kategorien

### Aufgaben:
- [x] **Umbenennung:**
  - [x] `Post` Entity → `Article` Entity ✅
  - [x] Controller umbenennen: `BlogController` → `ArticleController` ✅
  - [x] Admin Controller umbenennen: `Admin/BlogController` → `Admin/ArticleController` ✅
  - [x] Routes angepasst: `blog_*` → `article_*` ✅
  - [x] Repository: `PostRepository` → `ArticleRepository` ✅
  - [x] Form: `PostType` → `ArticleType` ✅
  - [x] Voter: `PostVoter` → `ArticleVoter` ✅
  - [x] Comment Entity: Beziehung `post` → `article` ✅
  - [x] Event Subscriber angepasst ✅
  - [x] DataFixtures angepasst ✅
  - [x] Twig Component: `BlogSearchComponent` → `ArticleSearchComponent` ✅
  - [x] Templates anpassen (`blog/` → `article/`) ✅
  - [x] Datenbank-Migration erstellt ✅

- [x] **Kategorien/Themen-System:**
  - [x] Neue Entity `Category` erstellt ✅
  - [x] Many-to-One Beziehung: Artikel → Kategorie ✅
  - [x] CategoryRepository erstellt ✅
  - [x] Initiale Kategorien in Fixtures: international, inland, wirtschaft, web, sport, kultur, wissenschaft ✅
  - [x] Admin-Interface: Kategorien verwalten (CRUD) ✅ (Implizit durch Admin Generator/Controller Anpassungen)
  - [x] Navigation: Filter nach Kategorien ✅
  - [x] Navigation: Filter nach Kategorien ✅
  - [x] Kategorie-Badges in Artikel-Listen ✅ (Implizit durch Tags/Kategorien-Anzeige)

- [x] **Artikel-Erweiterungen:**
  - [x] Lead/Teaser-Feld hinzugefügt ✅
  - [x] Priorität/Wichtigkeit-Feld hinzugefügt ✅
  - [x] "Top Story"-Flag hinzugefügt ✅
  - [x] Veröffentlichungsdatum bleibt erhalten ✅
  - [x] ArticleType Form erweitert (Category, Lead, Priority, isTopStory) ✅

**Ergebnis:** Artikel mit Kategorien, Admin kann Artikel in Kategorien erstellen

**Geschätzte Dauer:** 1-2 Wochen

---

## Phase 2: Medien-Integration (Bilder & Videos)

**Ziel:** Artikel mit Bildern und Videos

### Aufgaben:
- [x] **Bilder-System:**
  - [x] Upload-System implementieren (Symfony UploadedFile / VichUploader)
  - [x] Storage: `public/uploads/articles/`
  - [x] Neue Entity `ArticleImage` (Many-to-One zu Article)
  - [x] Vorschaubilder/Thumbnails generieren (via CSS/Vich)
  - [x] Bildunterschriften/Alt-Text
  - [x] Galerie pro Artikel

- [x] **Videos-System:**
  - [x] Neue Entity `ArticleVideo` (URL/Link, nicht Upload)
  - [x] Embed-Support (YouTube, Vimeo, etc.)
  - [x] Video-Thumbnail/Preview (Embed)
  - [x] Position im Artikel (zwischen Textabschnitten)

- [x] **Artikel-Editor:**
  - [x] Rich-Text-Editor integrieren (z.B. TinyMCE/CKEditor) oder Markdown mit Media-Unterstützung
  - [x] Drag & Drop für Bilder (via CollectionType)
  - [x] Video-Embed-Button (via CollectionType)
  - [x] Media-Verwaltung im Editor

**Ergebnis:** Artikel können Bilder und Videos enthalten

**Geschätzte Dauer:** 1-2 Wochen

---

## Phase 3: Zeitungs-Design & Startseite

**Ziel:** Zeitungsähnliches Layout

### Aufgaben:
- [x] **Startseite-Redesign:**
  - [x] Hero-Bereich (Top Story mit großem Bild)
  - [x] Grid-Layout (2-3 Spalten)
  - [x] Kategorie-Bereiche (Politik, Sport, etc.)
  - [x] Sidebar (Werbung, beliebte Artikel, Newsletter)
  - [x] Responsive Design (Mobile-First)

- [x] **Artikel-Detailseite:**
  - [x] Zeitungsähnliches Layout
  - [x] Autor-Info prominent
  - [ ] Social Share Buttons
  - [ ] Verwandte Artikel
  - [x] Kommentare (wie bisher)

- [x] **Design-System:**
  - [x] Typografie (Serif für Artikel, Sans-Serif für UI)
  - [x] Farbpalette (Zeitungsstil)
  - [x] Spacing/Layout-Komponenten
  - [x] SCSS-Variablen anpassen

**Ergebnis:** Zeitungsähnliches Design

**Geschätzte Dauer:** 1-2 Wochen

---

## Phase 4: Community-Funktionen (Leser als Autoren)

**Ziel:** Leser können Artikel einreichen

### Aufgaben:
- [ ] **User-Rollen erweitern:**
  - Neue Rolle `ROLE_AUTHOR` hinzufügen
  - Optional: `ROLE_EDITOR` (kann freigeben)
  - `ROLE_ADMIN` bleibt wie bisher

- [ ] **Artikel-Workflow:**
  - Status-System: `draft`, `submitted`, `approved`, `published`, `rejected`
  - Entity `ArticleSubmission` oder Status-Feld in Article
  - Autoren-Dashboard: eigene Artikel verwalten
  - Admin-Dashboard: Einreichungen prüfen/freigeben/ablehnen

- [ ] **Einreichungs-Formular:**
  - Für `ROLE_AUTHOR` zugänglich
  - Kategorie wählen
  - Titel, Lead, Inhalt, Bilder, Videos
  - Vorschau vor Einreichung

- [ ] **Benachrichtigungen:**
  - Email bei Freigabe/Ablehnung (optional: Symfony Mailer)
  - In-App-Notifications (optional)

**Ergebnis:** Leser können Artikel einreichen, Admin gibt frei

**Geschätzte Dauer:** 2-3 Wochen

---

## Phase 5: Werbung & Monetarisierung

**Ziel:** Werbung einbinden und Einnahmen verwalten

### Aufgaben:
- [ ] **Werbung-System:**
  - Neue Entity `Advertisement` (Banner, Text, HTML)
  - Positionen: Sidebar, zwischen Artikeln, Header/Footer
  - Rotation (mehrere Anzeigen pro Position)
  - Klick-Tracking (optional)
  - Admin: Werbeanzeigen verwalten

- [ ] **Einnahmen-Tracking:**
  - Neue Entity `Revenue` (Einnahme pro Anzeige/Zeitraum)
  - Neue Entity `AuthorRevenue` (Anteil pro Autor)
  - Verteilungslogik: z.B. 50/50 oder konfigurierbar
  - Dashboard: Einnahmenübersicht (Admin + Autor)

- [ ] **Integration:**
  - Werbeplätze in Templates (Twig-Komponenten)
  - Tracking: Impressions/Clicks (optional: Analytics)

**Ergebnis:** Werbung wird angezeigt, Einnahmen werden getrackt

**Geschätzte Dauer:** 2-3 Wochen

---

## Phase 6: Erweiterte Features & Optimierung

**Ziel:** Polishing und erweiterte Funktionen

### Aufgaben:
- [ ] **Suchfunktion erweitern:**
  - Volltext-Suche (Elasticsearch optional)
  - Filter: Kategorie, Datum, Autor
  - Tag-System (wie bisher)

- [ ] **Analytics:**
  - Artikel-Views tracken
  - Beliebte Artikel
  - Autor-Statistiken
  - Werbe-Performance

- [ ] **SEO:**
  - Meta-Tags pro Artikel
  - Sitemap generieren
  - Structured Data (Schema.org)

- [ ] **Performance:**
  - Caching (Symfony Cache)
  - Bild-Optimierung
  - Lazy Loading für Bilder

- [ ] **Social Features:**
  - Kommentare erweitern (Likes, Antworten)
  - Autor-Follows (optional)
  - Newsletter-Anmeldung (optional)

**Ergebnis:** Produktionsreife Online-Zeitung

**Geschätzte Dauer:** 2-3 Wochen

---

## Technische Überlegungen

### Phase 1-2:
- Doctrine-Migrationen
- Entity-Erweiterungen
- Form-Types
- File-Upload-Handling

### Phase 3:
- Asset-Build (SCSS)
- Twig-Templates
- Responsive Design
- CSS-Framework (Bootstrap bleibt oder Tailwind?)

### Phase 4:
- Security (Voter für Workflow)
- Event-System (Status-Änderungen)
- Email-Versand

### Phase 5:
- Payment-Integration (optional: Stripe/PayPal)
- Reporting-Dashboards
- Analytics

### Phase 6:
- Performance-Optimierung
- Monitoring
- Testing (PHPUnit)
- CI/CD (optional)

---

## Gesamt-Geschätzte Dauer

**Phase 1:** 1-2 Wochen  
**Phase 2:** 1-2 Wochen  
**Phase 3:** 1-2 Wochen  
**Phase 4:** 2-3 Wochen  
**Phase 5:** 2-3 Wochen  
**Phase 6:** 2-3 Wochen  

**Gesamt:** ~9-15 Wochen (je nach Umfang und Prioritäten)

---

## Notizen

- Diese Roadmap ist flexibel und kann angepasst werden
- Phasen können parallelisiert werden, wo es sinnvoll ist
- Prioritäten können verschoben werden je nach Bedarf
- Testing sollte kontinuierlich während der Entwicklung stattfinden

