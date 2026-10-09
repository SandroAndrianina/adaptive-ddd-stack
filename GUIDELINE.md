# README — adaptive-ddd-stack

## 1. Déploiement & compilation

### Démarrer tout
```bash
docker compose up -d --build
```

### Rebuild un seul service (plus rapide)
```bash
docker compose up -d --build java-service   # après changement Java
docker compose up -d --build php-app        # après changement PHP
```

### Reset complet (repartir de zéro)
```bash
docker compose down -v --rmi local
docker compose up -d --build
```
`-v` supprime la base MySQL → `entities.json` sera relu, les tables recréées.

### Vérifier l'état
```bash
docker compose ps
docker compose logs -f java-service
docker compose logs -f php-app
```

### Ports (hôte → conteneur)
| Service | Hôte | Conteneur |
|---|---|---|
| MySQL | 3307 | 3306 |
| Java | 8082 | 8080 |
| PHP | 8083 | 80 |

**Règle absolue** : entre conteneurs → noms de service (`db`, `java-service`, `php-app`). Jamais `localhost`.

### Compilation Java (sans Docker, debug)
```bash
cd java-service
mvn -q clean package -DskipTests
# produit target/adaptive-service.war
```

---

## 2. Accès fichiers

### Côté PHP (CI4)
- `WRITEPATH` = `/var/www/html/writable/`
- Mirror JSON : `writable/data/{entity}.json`
- Log Flow B  : `writable/data/activity.log`
- Uploads      : `writable/uploads/`

Exemple (dans `FileActivityLog.php`) :
```php
$path = WRITEPATH . 'data/activity.log';
file_put_contents($path, $line, FILE_APPEND);
```

### Côté Java
- Config partagée : `/etc/adaptive/entities.json` (monté en **read-only** depuis `./config/`)

### Permissions
Le Dockerfile fait `chown -R www-data:www-data writable`. Si tu ajoutes un fichier à la main depuis ton Mac :
```bash
docker compose exec php-app chmod -R 775 writable
```

### Lire un fichier depuis l'intérieur d'un conteneur
```bash
docker compose exec php-app sh -c 'cat writable/data/scan.json'
docker compose exec java-service cat /etc/adaptive/entities.json
```

---

## 3. Création de classe et de fonction

### Ajouter une **entité** (le cas le plus fréquent)
**Aucun code à écrire.** Édite `config/entities.json` :
```json
"doctor": {
  "table": "doctors",
  "fields": [
    { "name": "id",         "type": "int",    "column": "id",        "primary": true, "auto": true },
    { "name": "fullName",   "type": "string", "column": "full_name", "required": true },
    { "name": "specialty",  "type": "string", "column": "specialty" },
    { "name": "createdAt",  "type": "timestamp", "column": "created_at", "autoCreate": true }
  ]
}
```
Puis :
```bash
docker compose restart java-service
```
→ table créée, REST disponible (`/api/doctor`), UI auto-générée, mirror fichier auto.

**Types disponibles** : `int`, `string`, `boolean`, `timestamp`.

### Ajouter une **fonction** (logique métier Java)
1. `java-service/src/main/java/com/adaptive/application/MonService.java`
```java
package com.adaptive.application;

public class MonService {
    public String compute(String input) { return input.toUpperCase(); }
}
```
2. Appelle-le depuis le servlet ou le repository, jamais depuis `domain/` (qui ne doit rien importer).

### Ajouter un **endpoint spécial**
Nouvelle méthode dans `GenericServlet.java` sur un chemin réservé (`/api/_quelquechose`) — **avant** le catch-all `api/*`.

### Ajouter une **page** PHP
1. `php-app/app/Controllers/MaPage.php`
```php
<?php
namespace App\Controllers;

class MaPage extends BaseController {
    public function index(): string { return view('ma_page'); }
}
```
2. `php-app/app/Views/ma_page.php` (HTML)
3. Route dans `app/Config/Routes.php` **avant** les routes génériques :
```php
$routes->get('ma-page', 'MaPage::index');
```

### Ordre des routes (piège classique)
```
/api/logs          ← spécifique d'abord
/api/_entities
/api/_schema/(:segment)
/api/(:segment)    ← générique en dernier
```
Sinon `logs` devient une entité.

---

## 4. Structure des couches (DDD)

**Java** (`java-service/src/main/java/com/adaptive/`)
```
domain/            ← entités + interfaces, zéro import framework
application/       ← orchestration (services), dépend du domain
infrastructure/    ← implémentations (JDBC, HTTP, JSON, config)
interfaces/        ← servlets (entrée HTTP)
bootstrap/         ← listener de démarrage
```

**PHP** (`php-app/app/`)
```
Domain/            ← entités (miroir) + interfaces
Application/       ← services d'orchestration
Infrastructure/    ← Http, File, Config
Controllers/       ← Api (JSON), Home (UI)
Views/             ← HTML
```

**Règle** : une couche n'appelle **que** les couches du dessous, jamais l'inverse.

---

## 5. Derniers conseils pour réussir l'examen

1. **Teste depuis zéro la veille.** `docker compose down -v && up -d --build`. Si ça passe, tu es prêt.
2. **Écris le README avant le code**, pas après. Il devient ta checklist.
3. **Un commit par étape** avec un message clair. GitHub Desktop suffit.
4. **Curl d'abord, navigateur ensuite.** `curl -i` te dit tout, l'UI te ment.
5. **Logs avant hypothèses.** `docker compose logs <service>` résout 80 % des bugs.
6. **`localhost` dans un conteneur = le conteneur lui-même.** Utilise les noms de service.
7. **Deux pièges MySQL 8** : `allowPublicKeyRetrieval=true` dans l'URL JDBC, et `Class.forName("com.mysql.cj.jdbc.Driver")` dans le code Java.
8. **Un formulaire HTML avec `enctype="multipart/form-data"`** pour tout upload.
9. **`http_errors => false`** dans CI4 curl, sinon les 4xx/5xx deviennent des exceptions.
10. **Ne code pas en direct devant l'examinateur.** Aie un squelette qui marche, puis ajoute ta logique métier.
11. **Explique l'architecture, pas les détails.** L'examinateur veut voir : DDD, PHP↔Java, JDBC, files. Le reste est bonus.
12. **Si un truc casse : `docker compose restart <service>`** avant de paniquer. Si toujours cassé : `down -v` et repartir.

**Phrase à retenir pour la soutenance** :
> « J'ai construit un pipeline découplé : PHP (CI4) gère l'UI et les fichiers, Java porte la logique métier et parle à MySQL, les deux communiquent en HTTP+JSON. L'architecture est config-driven : ajouter une entité se fait dans `entities.json`, sans recompiler. »