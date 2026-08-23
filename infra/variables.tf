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
  default     = ["t3.small"]
  description = "Tipo(s) do node EKS. Padrão t3.small (t3.micro não comporta os pods). No CI, o workflow AWS deploy pode sobrescrever (t3.medium) para teste de HPA."
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
