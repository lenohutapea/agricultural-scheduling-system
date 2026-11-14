variable "app_image" {
  description = "Container image of the app, published by the CI pipeline."
  type        = string
  default     = "ghcr.io/lenohutapea/agricultural-scheduling-system:latest"
}

variable "db_user" {
  description = "Database user the app connects as."
  type        = string
  default     = "pertanian"
}

variable "db_password" {
  description = "Password for db_user."
  type        = string
  sensitive   = true

  validation {
    condition     = length(var.db_password) >= 12
    error_message = "Use at least 12 characters for db_password."
  }
}

variable "db_root_password" {
  description = "MariaDB root password."
  type        = string
  sensitive   = true

  validation {
    condition     = length(var.db_root_password) >= 12
    error_message = "Use at least 12 characters for db_root_password."
  }
}

variable "grafana_admin_password" {
  description = "Password for the Grafana admin user."
  type        = string
  sensitive   = true

  validation {
    condition     = length(var.grafana_admin_password) >= 12
    error_message = "Use at least 12 characters for grafana_admin_password."
  }
}

variable "http_port" {
  description = "Host port where Nginx serves the app."
  type        = number
  default     = 8080
}

variable "grafana_port" {
  description = "Host port for Grafana."
  type        = number
  default     = 3000
}

variable "enable_public_tunnel" {
  description = "Expose the app on a public https://*.trycloudflare.com address. Create your app account first."
  type        = bool
  default     = false
}
