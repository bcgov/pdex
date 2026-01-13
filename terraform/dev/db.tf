# rds
data "aws_secretsmanager_secret" "pdex_rds_proxy_creds" {
  name = "secrets.env-bV4LPz-pdex-rds-creds"
}

data "aws_iam_role" "pdex_rds_proxy_role" {
  name = "pdex-cluster-pdex-secrets"
}

# create this manually
data "aws_secretsmanager_secret_version" "creds" {
  secret_id = "pdex-rds-creds"
}

data "aws_secretsmanager_secret_version" "opensearch_creds" {
  secret_id = "pdex-opensearch-creds"
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


locals {
  db_creds = jsondecode(
    data.aws_secretsmanager_secret_version.creds.secret_string
  )
  opensearch_creds = jsondecode(
    data.aws_secretsmanager_secret_version.opensearch_creds.secret_string
  )

}
  
resource "aws_rds_cluster_instance" "postgres-pdex" {
  count = 2
  cluster_identifier = aws_rds_cluster.postgres-pdex.id
  instance_class     = "db.serverless"
  engine             = aws_rds_cluster.postgres-pdex.engine
  engine_version     = aws_rds_cluster.postgres-pdex.engine_version
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
      predefined_metric_type = "RDSReaderAverageCConnections"
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
  role_arn               = data.aws_iam_role.pdex_rds_proxy_role.arn
  vpc_subnet_ids         = var.private_subnet_ids
  vpc_security_group_ids = [aws_security_group.pdex_rds_proxy_sg.id]

  require_tls          = true
  idle_client_timeout  = 1800 # 30 minutes

  auth {
    auth_scheme               = "SECRETS"
    secret_arn                = data.aws_secretsmanager_secret.pdex_rds_proxy_creds.arn
    iam_auth                  = "DISABLED"
    client_password_auth_type = "POSTGRES_MD5"
  }

  debug_logging = false 
}
