data "aws_caller_identity" "current" {
  count = var.use_ministack ? 0 : 1
}

resource "aws_eks_cluster" "this" {
  name     = var.cluster_name
  role_arn = aws_iam_role.eks_cluster.arn

  vpc_config {
    subnet_ids              = aws_subnet.public[*].id
    endpoint_public_access  = true
    endpoint_private_access = false
  }

  dynamic "access_config" {
    for_each = var.use_ministack ? [] : [1]
    content {
      authentication_mode                         = "API_AND_CONFIG_MAP"
      bootstrap_cluster_creator_admin_permissions = true
    }
  }

  depends_on = [
    aws_iam_role_policy_attachment.eks_cluster,
    aws_internet_gateway.this,
  ]
}

# Console AWS (root da conta). O creator do cluster é a role gha-apply, não o root.
resource "aws_eks_access_entry" "account_root" {
  count         = var.use_ministack ? 0 : 1
  cluster_name  = aws_eks_cluster.this.name
  principal_arn = "arn:aws:iam::${data.aws_caller_identity.current[0].account_id}:root"
  type          = "STANDARD"
}

resource "aws_eks_access_policy_association" "account_root_admin" {
  count         = var.use_ministack ? 0 : 1
  cluster_name  = aws_eks_cluster.this.name
  principal_arn = aws_eks_access_entry.account_root[0].principal_arn
  policy_arn    = "arn:aws:eks::aws:cluster-access-policy/AmazonEKSClusterAdminPolicy"

  access_scope {
    type = "cluster"
  }
}

resource "aws_eks_node_group" "this" {
  cluster_name    = aws_eks_cluster.this.name
  node_group_name = "default"
  node_role_arn   = aws_iam_role.eks_nodes.arn
  subnet_ids      = aws_subnet.public[*].id
  instance_types  = var.node_instance_types

  scaling_config {
    desired_size = var.node_desired_size
    min_size     = var.node_min_size
    max_size     = var.node_max_size
  }

  depends_on = [
    aws_iam_role_policy_attachment.eks_worker,
    aws_iam_role_policy_attachment.eks_cni,
    aws_iam_role_policy_attachment.eks_ecr_read,
  ]
}

# Sem NLB (custo fixo). API no NodePort 30080 no IP público do node.
resource "aws_vpc_security_group_ingress_rule" "nodeport_http" {
  count = var.use_ministack ? 0 : 1

  security_group_id = aws_eks_cluster.this.vpc_config[0].cluster_security_group_id
  description       = "NodePort HTTP"
  cidr_ipv4         = "0.0.0.0/0"
  from_port         = 30080
  ip_protocol       = "tcp"
  to_port           = 30080
}
