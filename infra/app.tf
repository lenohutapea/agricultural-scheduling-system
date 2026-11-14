# Application stack: MariaDB -> app (PHP/Apache) -> Nginx reverse proxy

resource "docker_network" "pertanian" {
  name = "pertanian"
}

resource "docker_volume" "db_data" {
  name = "pertanian_db_data"
}

# ---------- database ----------

resource "docker_image" "mariadb" {
  name         = "mariadb:11.4"
  keep_locally = true
}

resource "docker_container" "db" {
  name         = "pertanian-db"
  image        = docker_image.mariadb.image_id
  restart      = "unless-stopped"
  wait         = true
  wait_timeout = 180

  env = [
    "MARIADB_DATABASE=pertanian",
    "MARIADB_USER=${var.db_user}",
    "MARIADB_PASSWORD=${var.db_password}",
    "MARIADB_ROOT_PASSWORD=${var.db_root_password}",
  ]

  networks_advanced {
    name    = docker_network.pertanian.name
    aliases = ["db"]
  }

  volumes {
    volume_name    = docker_volume.db_data.name
    container_path = "/var/lib/mysql"
  }

  # Runs once, on the first start with an empty volume: tables + sample crops
  upload {
    file    = "/docker-entrypoint-initdb.d/01-schema.sql"
    content = file("${path.module}/../database.sql")
  }

  healthcheck {
    test         = ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
    interval     = "10s"
    timeout      = "5s"
    retries      = 10
    start_period = "30s"
  }
}

# ---------- app ----------

# Looks up the digest of the latest published image, so `tofu apply`
# redeploys the app whenever CI has published a new version.
data "docker_registry_image" "app" {
  name = var.app_image
}

resource "docker_image" "app" {
  name          = data.docker_registry_image.app.name
  pull_triggers = [data.docker_registry_image.app.sha256_digest]
  keep_locally  = true
}

resource "docker_container" "app" {
  name         = "pertanian-app"
  image        = docker_image.app.image_id
  restart      = "unless-stopped"
  wait         = true
  wait_timeout = 120

  env = [
    "DB_HOST=db",
    "DB_PORT=3306",
    "DB_NAME=pertanian",
    "DB_USER=${var.db_user}",
    "DB_PASS=${var.db_password}",
    "APP_TIMEZONE=Asia/Jakarta",
  ]

  networks_advanced {
    name    = docker_network.pertanian.name
    aliases = ["app"]
  }

  depends_on = [docker_container.db]
}

# ---------- reverse proxy ----------

resource "docker_image" "nginx" {
  name         = "nginx:1.27-alpine"
  keep_locally = true
}

resource "docker_container" "nginx" {
  name    = "pertanian-nginx"
  image   = docker_image.nginx.image_id
  restart = "unless-stopped"

  ports {
    internal = 80
    external = var.http_port
  }

  networks_advanced {
    name    = docker_network.pertanian.name
    aliases = ["nginx"]
  }

  upload {
    file    = "/etc/nginx/conf.d/default.conf"
    content = file("${path.module}/config/nginx.conf")
  }

  depends_on = [docker_container.app]
}
