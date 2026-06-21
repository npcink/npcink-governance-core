# fr_FR - Stable Readme Translation Draft

## Short Description

Gouvernance, approbation, vérification avant commit et journaux d'audit pour les opérations WordPress assistées par IA.

## Description

Npcink Governance Core est la couche de gouvernance Npcink AI pour les opérations WordPress. Elle fournit aux administrateurs de site et aux plugins hôtes de confiance une couche d'approbation vérifiable avant que les actions assistées par IA soient validées sur un site WordPress.

Core découvre les opérations WordPress Abilities API disponibles, enregistre les actions proposées par l'IA, prend en charge l'approbation et le rejet, renvoie un contexte de commit preflight et conserve les preuves d'audit. Il est conçu pour les installations où des outils d'IA, des adapters ou des plugins produit peuvent demander des changements WordPress, tout en gardant un registre de gouvernance clair.

### Ce que fait Core

- Découvre les WordPress abilities disponibles pour l'entrée en gouvernance.
- Stocke les proposals pour les opérations WordPress assistées par IA.
- Prend en charge les décisions approve et reject avec preuve d'audit.
- Fournit un contexte commit-preflight avant qu'un host ou adapter de confiance effectue les écritures finales.
- Gère les scoped app keys pour les clients de gouvernance de confiance.
- Enregistre les événements d'audit de création de proposal, policy evaluation, approval, rejection, commit preflight et execution-result handoff.
- Sépare la gouvernance de la génération de contenu, du routage de modèles, de la facturation cloud et de l'exécution des écritures finales.

### À qui s'adresse ce plugin

Npcink Governance Core s'adresse aux administrateurs WordPress, plugins hôtes, adapters et développeurs qui ont besoin d'une limite locale d'approbation et d'audit pour les opérations WordPress assistées par IA. Il est utile lorsque des outils d'IA peuvent préparer ou demander des changements, mais qu'un propriétaire de site ou un client de gouvernance de confiance doit les examiner, les approuver et les suivre.

Ce plugin n'est pas un rédacteur IA en un clic, un assistant SEO, un générateur d'images, un chatbot ou un runtime de workflow. Les workflows produit et les ability callbacks appartiennent à des plugins provider, adapter ou produit distincts.

### Prérequis et intégrations

Core fonctionne le mieux avec WordPress 7.0 ou supérieur et des fournisseurs WordPress Abilities API. Le fournisseur de référence de première partie est Npcink Abilities Toolkit, mais le cycle de gouvernance de base peut aussi gouverner des fournisseurs tiers WordPress Abilities API qui exposent des ability ids, schemas, permission callbacks, risk metadata et dry-run previews stables.

Core expose des endpoints REST de gouvernance sous `/wp-json/npcink-governance-core/v1/`. Les adapters et plugins hôtes de confiance peuvent utiliser ces endpoints pour créer des proposals, les approuver ou les rejeter, demander un commit preflight et enregistrer des résultats d'exécution externes.

### Confidentialité et données

Npcink Governance Core n'appelle pas de services d'IA externes, ne charge pas de ressources distantes et n'envoie pas de données du site à des tiers. Il stocke localement les enregistrements de gouvernance dans des tables de base de données WordPress, notamment les données de proposal, audit events, app-key metadata et rate-limit state. Les secrets d'app keys sont stockés sous forme de hachage, et les bearer tokens à usage unique ne sont affichés qu'à leur création.

### Limites

Core ne génère pas de contenu, ne route pas les modèles, n'exécute pas MCP ni workflow runtimes, ne stocke pas de provider credentials, ne proxifie pas ability execution et n'effectue pas les mutations finales WordPress. Les écritures finales appartiennent à un host, adapter ou runtime de confiance en dehors de Core une fois l'étape de gouvernance terminée.

## Installation

1. Téléversez le plugin dans `wp-content/plugins/npcink-governance-core`.
2. Activez Npcink Governance Core dans WordPress.
3. Ouvrez Npcink AI > Core pour examiner l'état de gouvernance, les proposals, les audit entries et les contrôles avancés Core app-key.

## FAQ

### À qui s'adresse ce plugin ?

Il s'adresse aux administrateurs de site, plugins hôtes, adapters et développeurs qui ont besoin d'une gouvernance vérifiable pour les opérations WordPress assistées par IA. Il est particulièrement utile lorsque des outils d'IA peuvent préparer des changements, mais que le site exige approbation, commit preflight et preuve d'audit avant application.

### Est-ce un générateur de contenu IA ?

Non. Core ne rédige pas d'articles, ne génère pas de médias, ne crée pas de texte SEO, ne répond pas aux commentaires, ne choisit pas de modèles d'IA et ne stocke pas de provider credentials. Il gouverne les proposed operations créées par des outils, adapters ou fournisseurs WordPress Abilities API distincts.

### Core exécute-t-il les écritures IA ?

Non. Core enregistre les proposals de gouvernance et renvoie un contexte commit-preflight. Les écritures finales appartiennent à un host, adapter ou runtime de confiance en dehors de Core.

### Qu'est-ce qu'une proposal ?

Une proposal est une demande enregistrée pour une opération WordPress assistée par IA. Elle inclut l'ability cible, le résumé d'entrée, la preview ou dry-run evidence, le statut, les caller metadata et l'audit trail nécessaire à l'examen.

### Qu'est-ce que commit preflight ?

Commit preflight est le contrôle de gouvernance exécuté après approbation et avant qu'un composant externe de confiance effectue l'écriture finale. Il renvoie un contexte borné, des correlation data et un input binding pour que le composant aval vérifie qu'il agit sur la demande approuvée.

### Ai-je besoin d'un autre plugin pour les abilities ?

Oui, pour une ability intake complète. Core gouverne les abilities exposées par les fournisseurs WordPress Abilities API. Npcink Abilities Toolkit est le fournisseur de référence, et des fournisseurs tiers peuvent aussi s'intégrer en exposant des ability ids, schemas, permissions, risk metadata et dry-run previews stables.

### Ce plugin envoie-t-il des données à un service d'IA externe ?

Non. Core n'appelle pas de services d'IA externes et n'envoie pas de données du site à des tiers. Il stocke les enregistrements de gouvernance localement dans des tables de base de données WordPress.

### Quelles données Core stocke-t-il ?

Core stocke les proposal records, approval et rejection decisions, commit-preflight evidence, execution-result handoff records, audit events, app-key metadata et rate-limit state. Les secrets d'app keys sont hachés, et les bearer tokens à usage unique ne sont affichés qu'à leur création.

### À quoi servent les scoped app keys ?

Les scoped app keys permettent aux clients de gouvernance de confiance d'appeler des endpoints REST Core spécifiques sans leur donner un accès administrateur large. Elles sont destinées aux hosts contrôlés, aux adapters et aux clients internes de gouvernance.

### OpenClaw doit-il se connecter directement à Core ?

Une configuration OpenClaw productisée doit se connecter via un adapter de confiance. Les app keys Core directes sont réservées aux clients internes de gouvernance et au fallback testing.

### Les fournisseurs tiers d'abilities peuvent-ils utiliser Core ?

Oui. Le proposal lifecycle de base est provider-neutral. Les fournisseurs tiers peuvent exposer des WordPress Abilities API definitions avec schemas, permission callbacks, risk metadata et dry-run previews, puis soumettre des opérations d'écriture ou destructives à l'examen de Core.

## Changelog

### 0.1.0

Plugin initial de gouvernance avec ability intake, proposals, approval/rejection, commit preflight, scoped app keys, rate limiting et audit records.
