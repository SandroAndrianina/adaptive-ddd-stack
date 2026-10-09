SRC=../irm-cancer-detector
cp "$SRC/docker-compose.yml" "$SRC/.env.example" "$SRC/.gitignore" .
cp -R "$SRC/db" "$SRC/java-service" "$SRC/php-app" .
rm -rf java-service/target php-app/vendor php-app/writable/data php-app/writable/uploads php-app/.env
rm -f java-service/src/main/java/com/irm/scan/domain/model/Scan.java \
      java-service/src/main/java/com/irm/scan/domain/repository/ScanRepository.java \
      java-service/src/main/java/com/irm/scan/application/ScanService.java \
      java-service/src/main/java/com/irm/scan/application/ActivityNotifier.java \
      java-service/src/main/java/com/irm/scan/infrastructure/persistence/ScanJdbcRepository.java \
      java-service/src/main/java/com/irm/scan/infrastructure/http/CiActivityNotifier.java \
      java-service/src/main/java/com/irm/scan/interfaces/rest/ScanServlet.java
cd java-service/src/main/java/com && \
  mkdir -p adaptive/infrastructure/persistence adaptive/infrastructure/json && \
  mv irm/scan/infrastructure/persistence/DatabaseConnection.java adaptive/infrastructure/persistence/ && \
  mv irm/scan/infrastructure/json/JsonMapper.java adaptive/infrastructure/json/ && \
  rm -rf irm && cd - >/dev/null
rm -rf php-app/app/Domain/Scan \
       php-app/app/Application/ScanService.php \
       php-app/app/Application/ImageIntensityService.php \
       php-app/app/Infrastructure/Http/JavaApiClient.php \
       php-app/app/Infrastructure/File/FileScanRepository.php \
       php-app/app/Controllers/Api/Scan.php \
       php-app/app/Views/scans
sed -i '' 's|package com.irm.scan.infrastructure.persistence;|package com.adaptive.infrastructure.persistence;|' java-service/src/main/java/com/adaptive/infrastructure/persistence/DatabaseConnection.java
sed -i '' 's|package com.irm.scan.infrastructure.json;|package com.adaptive.infrastructure.json;|' java-service/src/main/java/com/adaptive/infrastructure/json/JsonMapper.java
sed -i '' 's/irm-/adaptive-/g' docker-compose.yml
sed -i '' 's|<groupId>com.irm</groupId>|<groupId>com.adaptive</groupId>|;s|<finalName>java-service</finalName>|<finalName>adaptive-service</finalName>|' java-service/pom.xml
sed -i '' 's|java-service.war|adaptive-service.war|g' java-service/Dockerfile
echo "-- Tables are generated at runtime by the Java SchemaGenerator (Step 2)." > db/init.sql
cat > php-app/app/Config/Routes.php <<'PHP'
<?php
namespace Config;
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->get('/',         'Home::index');
$routes->post('api/logs', 'Api\Logs::create');
PHP
cat > php-app/app/Controllers/Home.php <<'PHP'
<?php
namespace App\Controllers;
class Home extends BaseController
{
    public function index(): string { return 'adaptive-ddd-stack up'; }
}
PHP
cat > java-service/src/main/webapp/WEB-INF/web.xml <<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<web-app xmlns="https://jakarta.ee/xml/ns/jakartaee"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="https://jakarta.ee/xml/ns/jakartaee https://jakarta.ee/xml/ns/jakartaee/web-app_6_0.xsd"
         version="6.0">
  <display-name>adaptive-ddd-stack</display-name>
</web-app>
XML
cat > .env.example <<'ENV'
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=adaptive
MYSQL_USER=adaptive_user
MYSQL_PASSWORD=adaptive_pass
HOST_MYSQL_PORT=3307
HOST_JAVA_PORT=8082
HOST_PHP_PORT=8083
JAVA_BASE_URL=http://java-service:8080
PHP_BASE_URL=http://php-app:80
ENV
cp .env.example .env
docker compose down -v 2>/dev/null
docker compose up -d --build
sleep 20
docker compose ps
curl -i http://localhost:8082/
curl -i http://localhost:8083/