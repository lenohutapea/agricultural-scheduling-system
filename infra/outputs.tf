output "app_url" {
  description = "The app, through Nginx."
  value       = "http://localhost:${var.http_port}"
}

output "grafana_url" {
  description = "Monitoring dashboard. Log in as admin with grafana_admin_password."
  value       = "http://localhost:${var.grafana_port}"
}

output "public_url" {
  description = "How to get the public address, when the tunnel is enabled."
  value       = var.enable_public_tunnel ? "Run ./tunnel-url.sh to print the https://*.trycloudflare.com address" : "Public tunnel is off (enable_public_tunnel = false)"
}

output "app_image_digest" {
  description = "Exact image version that is running."
  value       = data.docker_registry_image.app.sha256_digest
}
