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
