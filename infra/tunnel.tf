# Optional public https:// address via a Cloudflare quick tunnel (no account, no card).
# The address is random and changes when the tunnel restarts. Get it with ./tunnel-url.sh
#
# Turn it on only after you have created your own account in the app: the first
# visitor to an empty app is offered the first-account setup.

resource "docker_image" "cloudflared" {
  count        = var.enable_public_tunnel ? 1 : 0
  name         = "cloudflare/cloudflared:2024.10.0"
  keep_locally = true
}

resource "docker_container" "tunnel" {
  count   = var.enable_public_tunnel ? 1 : 0
  name    = "pertanian-tunnel"
  image   = docker_image.cloudflared[0].image_id
  restart = "unless-stopped"
  command = ["tunnel", "--no-autoupdate", "--url", "http://nginx:80"]

  networks_advanced {
    name = docker_network.pertanian.name
  }

  depends_on = [docker_container.nginx]
}
