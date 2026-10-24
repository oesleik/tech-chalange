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

variable "eks_admin_principal_arns" {
  type        = list(string)
  default     = []
  description = "ARNs de usuários/roles IAM com acesso admin ao cluster EKS (via TF_VAR_eks_admin_principal_arns, JSON)."

  validation {
    condition     = alltrue([for arn in var.eks_admin_principal_arns : can(regex("^arn:aws:iam::[0-9]{12}:(user|role)/.+$", arn))])
    error_message = "Use ARNs de usuário ou role IAM (ex.: arn:aws:iam::123456789012:user/nome). Grupos IAM não são aceitos pelo EKS."
  }
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
  default     = ["t3.small"]
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
}

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

variable "lambda_db_username" {
  type        = string
  default     = "autenticacao"
  description = "Usuário MySQL somente leitura da Lambda de autenticação (criado pelo job rds-user-setup)."
}

variable "newrelic_account_id" {
  type        = string
  default     = null
  description = "Account id do New Relic (via TF_VAR_newrelic_account_id). Vazio desliga a integração AWS do New Relic. Também é o External ID da role de polling."
}

variable "newrelic_api_key" {
  type        = string
  default     = null
  sensitive   = true
  description = "User API key do New Relic (NRAK-..., via TF_VAR_newrelic_api_key). Usada pelo provider para vincular a conta AWS e ligar as integrações."
}

variable "newrelic_region" {
  type        = string
  default     = "US"
  description = "Região da conta New Relic (US ou EU)."
}

variable "newrelic_logs_ingest_key" {
  type        = string
  default     = null
  sensitive   = true
  description = "License key do New Relic usada pelo Firehose para enviar logs (via TF_VAR_newrelic_logs_ingest_key; a mesma NEW_RELIC_LICENSE_KEY do cluster). Fica no state."
}

variable "newrelic_logs_endpoint" {
  type        = string
  default     = "https://aws-api.newrelic.com/firehose/v1"
  description = "Endpoint de logs do Firehose no New Relic. Conta EU: https://aws-api.eu01.nr-data.net/firehose/v1."
}

variable "newrelic_aws_principal_account_id" {
  type        = string
  default     = "754728514883"
  description = "Conta AWS do New Relic que assume a role de polling."
}

variable "newrelic_alert_emails" {
  type        = string
  default     = ""
  description = "E-mails que recebem os alertas do New Relic, separados por vírgula (via TF_VAR_newrelic_alert_emails). Vazio: alertas sem notificação."
}

variable "lambda_db_password" {
  type        = string
  default     = null
  sensitive   = true
  description = "Senha do usuário MySQL da Lambda (via TF_VAR_lambda_db_password). Fica no state e na configuração da função."
}
