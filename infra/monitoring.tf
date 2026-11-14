# Monitoring: Prometheus collects, Grafana shows.
#   node-exporter      -> CPU, memory and disk of the Ubuntu host
#   blackbox-exporter  -> probes /healthz.php through Nginx every 15s (uptime + response time)

resource "docker_volume" "prometheus_data" {
  name = "pertanian_prometheus_data"
}

resource "docker_volume" "grafana_data" {
  name = "pertanian_grafana_data"
}

# ---------- exporters ----------

resource "docker_image" "node_exporter" {
  name         = "prom/node-exporter:v1.8.2"
  keep_locally = true
}

resource "docker_container" "node_exporter" {
  name     = "pertanian-node-exporter"
  image    = docker_image.node_exporter.image_id
  restart  = "unless-stopped"
  pid_mode = "host"
  command  = ["--path.rootfs=/host"]

  mounts {
    target    = "/host"
    source    = "/"
    type      = "bind"
    read_only = true

    bind_options {
      propagation = "rslave"
    }
  }

  networks_advanced {
    name    = docker_network.pertanian.name
    aliases = ["node-exporter"]
  }
}

resource "docker_image" "blackbox_exporter" {
  name         = "prom/blackbox-exporter:v0.25.0"
  keep_locally = true
}

resource "docker_container" "blackbox_exporter" {
  name    = "pertanian-blackbox-exporter"
  image   = docker_image.blackbox_exporter.image_id
  restart = "unless-stopped"

  networks_advanced {
    name    = docker_network.pertanian.name
    aliases = ["blackbox-exporter"]
  }
}

# ---------- Prometheus ----------

resource "docker_image" "prometheus" {
  name         = "prom/prometheus:v2.54.1"
  keep_locally = true
}

resource "docker_container" "prometheus" {
  name    = "pertanian-prometheus"
  image   = docker_image.prometheus.image_id
  restart = "unless-stopped"

  command = [
    "--config.file=/etc/prometheus/prometheus.yml",
    "--storage.tsdb.path=/prometheus",
    "--storage.tsdb.retention.time=15d",
  ]

  networks_advanced {
    name    = docker_network.pertanian.name
    aliases = ["prometheus"]
  }

  volumes {
    volume_name    = docker_volume.prometheus_data.name
    container_path = "/prometheus"
  }

  upload {
    file    = "/etc/prometheus/prometheus.yml"
    content = file("${path.module}/config/prometheus.yml")
  }

  depends_on = [
    docker_container.nginx,
    docker_container.node_exporter,
    docker_container.blackbox_exporter,
  ]
}

# ---------- Grafana ----------

resource "docker_image" "grafana" {
  name         = "grafana/grafana:11.2.0"
  keep_locally = true
}

resource "docker_container" "grafana" {
  name    = "pertanian-grafana"
  image   = docker_image.grafana.image_id
  restart = "unless-stopped"

  env = [
    "GF_SECURITY_ADMIN_USER=admin",
    "GF_SECURITY_ADMIN_PASSWORD=${var.grafana_admin_password}",
    "GF_USERS_ALLOW_SIGN_UP=false",
    "GF_ANALYTICS_REPORTING_ENABLED=false",
    "GF_DASHBOARDS_DEFAULT_HOME_DASHBOARD_PATH=/etc/grafana/dashboards/pertanian.json",
  ]

  ports {
    internal = 3000
    external = var.grafana_port
  }

  networks_advanced {
    name    = docker_network.pertanian.name
    aliases = ["grafana"]
  }

  volumes {
    volume_name    = docker_volume.grafana_data.name
    container_path = "/var/lib/grafana"
  }

  # Datasource and dashboard are created automatically on start
  upload {
    file    = "/etc/grafana/provisioning/datasources/prometheus.yml"
    content = file("${path.module}/config/grafana/datasource.yml")
  }

  upload {
    file    = "/etc/grafana/provisioning/dashboards/pertanian.yml"
    content = file("${path.module}/config/grafana/dashboards.yml")
  }

  upload {
    file    = "/etc/grafana/dashboards/pertanian.json"
    content = file("${path.module}/config/grafana/pertanian-dashboard.json")
  }

  depends_on = [docker_container.prometheus]
}
