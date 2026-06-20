# de_DE - Stable Readme Translation Draft

## Short Description

Governance, Freigabe, Commit-Vorprüfung und Audit-Logs für KI-gestützte WordPress-Operationen.

## Description

Npcink Governance Core ist die Npcink-AI-Governance-Schicht für WordPress-Operationen. Sie gibt Website-Administratoren und vertrauenswürdigen Host-Plugins eine überprüfbare Freigabeschicht, bevor KI-gestützte Aktionen auf einer WordPress-Website übernommen werden.

Core erkennt verfügbare WordPress-Abilities-API-Operationen, speichert von KI vorgeschlagene Aktionen, unterstützt Freigabe und Ablehnung, gibt Commit-Preflight-Kontext zurück und speichert Audit-Nachweise. Es ist für Installationen gedacht, in denen KI-Tools, Adapter oder Produkt-Plugins WordPress-Änderungen anfordern können, die Website aber weiterhin einen klaren Governance-Nachweis benötigt.

### Was Core macht

- Erkennt verfügbare WordPress abilities für die Governance-Aufnahme.
- Speichert proposals für KI-gestützte WordPress-Operationen.
- Unterstützt approve- und reject-Entscheidungen mit Audit-Nachweisen.
- Stellt Commit-Preflight-Kontext bereit, bevor ein vertrauenswürdiger Host oder Adapter finale Schreibvorgänge ausführt.
- Verwaltet scoped app keys für vertrauenswürdige Governance-Clients.
- Erfasst Audit-Ereignisse für proposal-Erstellung, policy evaluation, approval, rejection, commit preflight und execution-result handoff.
- Trennt Governance von Inhaltserzeugung, Modell-Routing, Cloud-Abrechnung und finaler Schreibausführung.

### Für wen dieses Plugin gedacht ist

Npcink Governance Core ist für WordPress-Administratoren, Host-Plugins, Adapter und Entwickler gedacht, die eine lokale Freigabe- und Audit-Grenze für KI-gestützte WordPress-Operationen benötigen. Es ist nützlich, wenn KI-Tools Änderungen vorbereiten oder anfordern können, aber ein Website-Eigentümer oder vertrauenswürdiger Governance-Client diese Änderungen prüfen, freigeben und nachverfolgen muss.

Dieses Plugin ist kein Ein-Klick-KI-Autor, kein SEO-Assistent, kein Bildgenerator, kein Chatbot und keine Workflow-Runtime. Produkt-Workflows und ability callbacks gehören in separate Provider-, Adapter- oder Produkt-Plugins.

### Anforderungen und Integrationen

Core funktioniert am besten mit WordPress 7.0 oder neuer und WordPress-Abilities-API-Providern. Der First-Party-Referenzprovider ist Npcink Abilities Toolkit, aber der grundlegende Governance-Lebenszyklus kann auch Drittanbieter-Provider der WordPress Abilities API steuern, wenn sie stabile ability ids, schemas, permission callbacks, risk metadata und dry-run previews bereitstellen.

Core stellt Governance-REST-Endpoints unter `/wp-json/npcink-governance-core/v1/` bereit. Vertrauenswürdige Adapter und Host-Plugins können diese Endpoints verwenden, um proposals zu erstellen, zu genehmigen oder abzulehnen, Commit Preflight anzufordern und externe Ausführungsergebnisse aufzuzeichnen.

### Datenschutz und Daten

Npcink Governance Core ruft keine externen KI-Dienste auf, lädt keine entfernten Assets und sendet keine Website-Daten an Dritte. Es speichert Governance-Datensätze lokal in WordPress-Datenbanktabellen, einschließlich proposal data, audit events, app-key metadata und rate-limit state. App-key secrets werden gehasht gespeichert, und einmalige bearer tokens werden nur bei der Erstellung angezeigt.

### Grenzen

Core erzeugt keine Inhalte, routet keine Modelle, führt keine MCP- oder workflow runtimes aus, speichert keine provider credentials, proxyt keine ability execution und führt keine finalen WordPress-Mutationen aus. Finale Schreibvorgänge gehören nach Abschluss des Governance-Schritts zu einem vertrauenswürdigen Host, Adapter oder Runtime außerhalb von Core.

## Installation

1. Lade das Plugin nach `wp-content/plugins/npcink-governance-core` hoch.
2. Aktiviere Npcink Governance Core in WordPress.
3. Öffne Npcink AI > Core, um Governance-Status, proposals, audit entries und erweiterte Core-app-key-Steuerungen zu prüfen.

## FAQ

### Für wen ist dieses Plugin gedacht?

Es ist für Website-Administratoren, Host-Plugins, Adapter und Entwickler gedacht, die überprüfbare Governance für KI-gestützte WordPress-Operationen benötigen. Besonders nützlich ist es, wenn KI-Tools Änderungen vorbereiten können, die Website aber vor der Anwendung Freigabe, commit preflight und Audit-Nachweise benötigt.

### Ist dies ein KI-Inhaltsgenerator?

Nein. Core schreibt keine Artikel, erzeugt keine Medien, erstellt keine SEO-Texte, beantwortet keine Kommentare, wählt keine KI-Modelle aus und speichert keine provider credentials. Es steuert proposed operations, die von separaten Tools, Adaptern oder WordPress-Abilities-API-Providern erstellt werden.

### Führt Core KI-Schreibvorgänge aus?

Nein. Core speichert Governance-proposals und gibt Commit-Preflight-Kontext zurück. Finale Schreibvorgänge gehören zu einem vertrauenswürdigen Host, Adapter oder Runtime außerhalb von Core.

### Was ist eine proposal?

Eine proposal ist eine gespeicherte Anfrage für eine KI-gestützte WordPress-Operation. Sie enthält die Ziel-ability, Eingabezusammenfassung, preview oder dry-run evidence, Status, caller metadata und den für die Prüfung benötigten audit trail.

### Was ist commit preflight?

Commit preflight ist die Governance-Prüfung nach der Freigabe und vor dem finalen Schreibvorgang durch eine vertrauenswürdige externe Komponente. Sie gibt begrenzten Kontext, correlation data und input binding zurück, damit die nachgelagerte Komponente prüfen kann, dass sie auf die freigegebene Anfrage reagiert.

### Benötige ich ein weiteres Plugin für abilities?

Für vollständige ability intake ja. Core steuert abilities, die von WordPress-Abilities-API-Providern bereitgestellt werden. Npcink Abilities Toolkit ist der Referenzprovider, und Drittanbieter können sich ebenfalls integrieren, indem sie stabile ability ids, schemas, permissions, risk metadata und dry-run previews bereitstellen.

### Sendet dieses Plugin Daten an einen externen KI-Dienst?

Nein. Core ruft keine externen KI-Dienste auf und sendet keine Website-Daten an Dritte. Governance-Datensätze werden lokal in WordPress-Datenbanktabellen gespeichert.

### Welche Daten speichert Core?

Core speichert proposal records, approval und rejection decisions, commit-preflight evidence, execution-result handoff records, audit events, app-key metadata und rate-limit state. App-key secrets werden gehasht gespeichert, und einmalige bearer tokens werden nur bei der Erstellung angezeigt.

### Wofür werden scoped app keys verwendet?

Scoped app keys erlauben vertrauenswürdigen Governance-Clients, bestimmte Core-REST-Endpoints aufzurufen, ohne ihnen umfassende Administratorrechte zu geben. Sie sind für kontrollierte Hosts, Adapter und interne Governance-Clients gedacht.

### Sollte OpenClaw direkt mit Core verbunden werden?

Eine produktisierte OpenClaw-Einrichtung sollte über einen vertrauenswürdigen Adapter verbunden werden. Direkte Core app keys sind nur für interne Governance-Clients und fallback testing gedacht.

### Können Drittanbieter-ability-Provider Core verwenden?

Ja. Der grundlegende proposal lifecycle ist provider-neutral. Drittanbieter können WordPress Abilities API definitions mit schemas, permission callbacks, risk metadata und dry-run previews bereitstellen und dann Schreib- oder destruktive Operationen zur Prüfung an Core übermitteln.

## Changelog

### 0.1.0

Erstes Governance-Plugin mit ability intake, proposals, approval/rejection, commit preflight, scoped app keys, rate limiting und audit records.
