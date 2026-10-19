variable "use_ministack" {
  type        = bool
  description = "Quando true, o provider AWS aponta para o MiniStack (localhost:4566)."
}

variable "ministack_endpoint" {
  type        = string
  default     = "http://localhost:4566"
  description = "Endpoint do emulador. Ignorado se use_ministack = false."
}

variable "aws_region" {
  type    = string
  default = "us-east-1"
}

variable "aws_access_key" {
  type      = string
  default   = "test"
  sensitive = true
}

variable "aws_secret_key" {
  type      = string
  default   = "test"
  sensitive = true
}

variable "cluster_name" {
  type    = string
  default = "tech-challenge"
}

variable "vpc_cidr" {
  type    = string
  default = "10.0.0.0/16"
}

variable "public_subnet_cidrs" {
  type    = list(string)
  default = ["10.0.0.0/24", "10.0.1.0/24"]
}

variable "availability_zones" {
  type    = list(string)
  default = ["us-east-1a", "us-east-1b"]
}

variable "node_instance_types" {
  type        = list(string)
  default     = ["t3.medium"]
  description = "Tipo(s) do node EKS"
}

variable "node_desired_size" {
  type    = number
  default = 1
}

variable "node_min_size" {
  type    = number
  default = 1
}

variable "node_max_size" {
  type    = number
  default = 2
}

variable "private_subnet_cidrs" {
  description = "CIDRs das sub-redes privadas para serviços gerenciados."
  type        = list(string)
  default     = ["10.0.10.0/24", "10.0.11.0/24"]
}

variable "rds_instance_class" {
  description = "Classe da instância Amazon RDS."
  type        = string
  default     = "db.t3.micro"
}

variable "rds_allocated_storage" {
  description = "Armazenamento inicial do RDS em GiB."
  type        = number
  default     = 20
}

variable "rds_db_name" {
  description = "Nome do banco de dados da aplicação."
  type        = string
  default     = "app_db"
}

variable "rds_master_username" {
  description = "Usuário administrativo do RDS."
  type        = string
  default     = "dbadmin"
}

variable "rds_master_password" {
  description = "Senha administrativa do RDS, fornecida por variável de ambiente."
  type        = string
  sensitive   = true
  default     = ""
variable "jwt_secret" {
  type        = string
  default     = null
  sensitive   = true
  description = "Mesmo JWT_SECRET do PHP. Usado pela Lambda de autenticação para assinar o token (via TF_VAR_jwt_secret)."

  validation {
    condition     = var.jwt_secret == null || length(coalesce(var.jwt_secret, "")) >= 32
    error_message = "jwt_secret precisa ter ao menos 32 bytes (exigência do HS256 no lcobucci/jwt)."
  }
}

variable "jwt_issuer" {
  type        = string
  default     = "tech-challenge-api"
  description = "Mesmo JWT_ISSUER do PHP."
}

variable "jwt_ttl" {
  type        = number
  default     = 3600
  description = "Validade do token em segundos (JWT_TTL)."
}
