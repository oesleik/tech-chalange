output "cluster_name" {
  value = aws_eks_cluster.this.name
}

output "cluster_endpoint" {
  value = aws_eks_cluster.this.endpoint
}

output "vpc_id" {
  value = aws_vpc.this.id
}

output "public_subnet_ids" {
  value = aws_subnet.public[*].id
}

output "kubeconfig_note" {
  value = var.use_ministack ? "MiniStack: use make aws-local-kubeconfig (adapter docker exec). Não use aws eks update-kubeconfig." : "AWS: aws eks update-kubeconfig --name ${var.cluster_name} --region ${var.aws_region}"
}

output "ecr_repository_url" {
  value       = var.use_ministack ? null : aws_ecr_repository.php[0].repository_url
  description = "URI da imagem PHP no ECR. Use em charts/tech-challenge/values-aws.yaml (php.image)."
}
