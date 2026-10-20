# Recursos utilizados apenas na AWS real.
# O repositório ECR é mantido para armazenar a imagem da aplicação PHP.

resource "aws_ecr_repository" "php" {
  count                = var.use_ministack ? 0 : 1
  name                 = "tech-challenge-php"
  image_tag_mutability = "MUTABLE"
  force_delete         = true

  image_scanning_configuration {
    scan_on_push = true
  }
}
