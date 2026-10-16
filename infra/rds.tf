# A configuração é desativada no MiniStack.
resource "aws_db_subnet_group" "this" {
  count = var.use_ministack ? 0 : 1

  name        = "${var.cluster_name}-rds-subnets"
  description = "Sub-redes privadas do RDS do Tech Challenge"
  subnet_ids  = aws_subnet.private[*].id

  tags = {
    Name    = "${var.cluster_name}-rds-subnets"
    Project = "tech-challenge"
  }
}

resource "aws_security_group" "rds" {
  count = var.use_ministack ? 0 : 1

  name        = "${var.cluster_name}-rds"
  description = "Permite MySQL apenas a partir do security group do EKS"
  vpc_id      = aws_vpc.this.id

  ingress {
    description = "MySQL a partir do EKS"
    from_port   = 3306
    to_port     = 3306
    protocol    = "tcp"
    security_groups = [
      aws_eks_cluster.this.vpc_config[0].cluster_security_group_id
    ]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name    = "${var.cluster_name}-rds"
    Project = "tech-challenge"
  }
}

resource "aws_db_instance" "mysql" {
  count = var.use_ministack ? 0 : 1

  identifier     = "${var.cluster_name}-mysql"
  engine         = "mysql"
  instance_class = var.rds_instance_class

  db_name  = var.rds_db_name
  username = var.rds_master_username
  password = var.rds_master_password
  port     = 3306

  allocated_storage = var.rds_allocated_storage
  storage_type      = "gp3"
  storage_encrypted = true

  db_subnet_group_name   = aws_db_subnet_group.this[0].name
  vpc_security_group_ids = [aws_security_group.rds[0].id]
  publicly_accessible    = false

  multi_az                   = false
  backup_retention_period    = 1
  auto_minor_version_upgrade = true
  deletion_protection        = false
  skip_final_snapshot        = true
  delete_automated_backups   = true
  apply_immediately          = true

  lifecycle {
    precondition {
      condition     = length(var.rds_master_password) >= 32
      error_message = "Defina DB_ROOT_PASSWORD com uma senha forte antes de criar o RDS."
    }
  }

  tags = {
    Name    = "${var.cluster_name}-mysql"
    Project = "tech-challenge"
  }
}