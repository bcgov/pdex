data "aws_secretsmanager_secret_version" "pdex_rds_master" {
  secret_id = var.pdex_rds_secret_arn
}

data "aws_subnets" "private" {
  filter {
    name   = "vpc-id"
    values = [var.vpc_id]
  }

  filter {
    name   = "tag:Name"
    # use environment_name variable to build the subnet name filter
    values = ["${var.environment_name}-*"]
  }
}

locals {
  db_creds = jsondecode(data.aws_secretsmanager_secret_version.pdex_rds_master.secret_string)
}

resource "aws_db_subnet_group" "data_subnet" {
  name                   = "data-subnet"
  subnet_ids             = data.aws_subnets.data.ids

  tags = var.common_tags
}

resource "aws_rds_cluster" "postgres-pdex" {
  cluster_identifier      = "pdex-postgres-cluster"
  engine                  = "aurora-postgresql"
  engine_version          = "16.8"
  engine_mode             = "provisioned"
  master_username         = local.db_creds.username
  master_password         = local.db_creds.password
  backup_retention_period = 35
  preferred_backup_window = "07:00-09:00"
  preferred_maintenance_window = "sun:06:00-sun:06:30"
  db_subnet_group_name    = aws_db_subnet_group.data_subnet.name
  kms_key_id              = aws_kms_key.pdex-kms-key.arn
  storage_encrypted       = true
  vpc_security_group_ids  = [data.aws_security_group.data.id, aws_security_group.allow_postgres.id]
  skip_final_snapshot     = true
  final_snapshot_identifier = "pdex-finalsnapshot"
  
  serverlessv2_scaling_configuration {
    max_capacity = 2.0
    min_capacity = 1.0
  }

  tags = var.common_tags
}
  
resource "aws_rds_cluster_instance" "postgres-pdex" {
  count = 2
  cluster_identifier = aws_rds_cluster.postgres-pdex.id
  instance_class     = "db.serverless"
  engine             = aws_rds_cluster.postgres-pdex.engine
  engine_version     = aws_rds_cluster.postgres-pdex.engine_version
}

resource "aws_security_group" "pdex_rds_proxy_sg" {
  name        = "pdex-rds-proxy-sg"
  description = "Allow access to RDS proxy"
  vpc_id      = data.aws_vpc.main.id

  egress {
    description = "Allow all outbound traffic"
    from_port   = 5432
    to_port     = 5432
    protocol    = "tcp"
    cidr_blocks = [data.aws_vpc.main.cidr_block]
  }

  tags = var.common_tags
}

# Allow Postgres FROM app SG -> proxy SG
resource "aws_security_group_rule" "proxy_ingress_from_app_sg" {
  type                     = "ingress"
  description              = "Postgres from app tier"
  security_group_id        = aws_security_group.pdex_rds_proxy_sg.id
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = data.aws_security_group.app.id
}

# Allow Postgres FROM EKS nodes SG -> proxy SG (your CLI rule)
resource "aws_security_group_rule" "proxy_ingress_from_eks_nodes" {
  type                     = "ingress"
  description              = "Postgres from EKS nodes"
  security_group_id        = aws_security_group.pdex_rds_proxy_sg.id
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = data.aws_security_group.eks_node_sg.id
}


resource "aws_appautoscaling_target" "rds_cluster_read_replica" {
  max_capacity       = 4
  min_capacity       = 1
  resource_id        = "cluster:${aws_rds_cluster.postgres-pdex.id}"
  scalable_dimension = "rds:cluster:ReadReplicaCount"
  service_namespace  = "rds"
}

# add auto scaling policy, target metric average connections 100 with min 1 and max 4 instances
resource "aws_appautoscaling_policy" "rds_pdex_connections_scaling_policy" {
  name               = "rds-pdex-connections-scaling-policy"
  policy_type       = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.rds_cluster_read_replica.resource_id
  scalable_dimension = aws_appautoscaling_target.rds_cluster_read_replica.scalable_dimension
  service_namespace  = aws_appautoscaling_target.rds_cluster_read_replica.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "RDSReaderAverageDatabaseConnections"
    }
    target_value = 100.0
    scale_in_cooldown  = 300
    scale_out_cooldown = 300
  }
}

# add auto scaling policy, target metric average cpu utilization 60% with min 1 and max 4 instances
resource "aws_appautoscaling_policy" "rds_pdex_cpu_scaling_policy" {
  name               = "rds-pdex-cpu-scaling-policy"
  policy_type       = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.rds_cluster_read_replica.resource_id
  scalable_dimension = aws_appautoscaling_target.rds_cluster_read_replica.scalable_dimension
  service_namespace  = aws_appautoscaling_target.rds_cluster_read_replica.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "RDSReaderAverageCPUUtilization"
    }
    target_value = 60.0
    scale_in_cooldown  = 300
    scale_out_cooldown = 300
  }
}

resource "aws_db_proxy" "pdex" {
  name                   = "pdex-rds-proxy"
  engine_family          = "POSTGRESQL"
  role_arn               = aws_iam_role.pdex_rds_proxy_secrets_role.arn
  vpc_subnet_ids         = data.aws_subnets.private.ids
  vpc_security_group_ids = [aws_security_group.pdex_rds_proxy_sg.id]

  require_tls          = true
  idle_client_timeout  = 1800 # 30 minutes

  auth {
    auth_scheme               = "SECRETS"
    secret_arn                = var.pdex_rds_secret_arn
    iam_auth                  = "DISABLED"
    client_password_auth_type = "POSTGRES_MD5"
  }

  debug_logging = false 
}


# Default Target Group for the Proxy
resource "aws_db_proxy_default_target_group" "pdex" {
  db_proxy_name        = aws_db_proxy.pdex.name

  connection_pool_config {
    max_connections_percent       = 100
    max_idle_connections_percent  = 50
    connection_borrow_timeout     = 120
    session_pinning_filters       = []
  }
}

# Attach an RDS instance to the proxy target group
resource "aws_db_proxy_target" "pdex" {
  db_proxy_name         = aws_db_proxy.pdex.name
  target_group_name     = aws_db_proxy_default_target_group.pdex.name
  db_cluster_identifier = aws_rds_cluster.postgres-pdex.cluster_identifier
}