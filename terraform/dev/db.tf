# rds

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
  master_username         = local.db_creds.adm_username
  master_password         = local.db_creds.adm_password
  backup_retention_period = 5
  preferred_backup_window = "07:00-09:00"
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

# create this manually
data "aws_secretsmanager_secret_version" "creds" {
  secret_id = "pdex-db-creds"
}

data "aws_secretsmanager_secret_version" "opensearch_creds" {
  secret_id = "pdex-opensearch-creds"
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
