# security.tf

data "aws_security_group" "web" {
  name = "Web"
}

data "aws_security_group" "app" {
  name = "App"
}

data "aws_security_group" "data" {
  name = "Data"
}

resource "aws_security_group" "allow_nfs" {
  name        = "allow_nfs"
  description = "Allow NFS inbound traffic and all outbound traffic"
  vpc_id      = data.aws_vpc.main.id

  tags = {
    Name = "allow_nfs"
  }
}

resource "aws_vpc_security_group_ingress_rule" "allow_nfs_ipv4" {
  security_group_id = aws_security_group.allow_nfs.id
  #cidr_ipv4         = data.aws_vpc.main.cidr_block

  # NFS: only from App SG (or node SG), not whole VPC
  referenced_security_group_id = data.aws_security_group.app.id
  from_port         = 2049
  ip_protocol       = "tcp"
  to_port           = 2049
  description       = "NFS from app tier only"
}

# resource "aws_vpc_security_group_egress_rule" "allow_all_traffic_ipv4" {
#   security_group_id = aws_security_group.allow_nfs.id
#   cidr_ipv4         = "0.0.0.0/0"
#   ip_protocol       = "-1" # semantically equivalent to all ports
# }

resource "aws_security_group" "allow_postgres" {
  name        = "allow_postgres"
  description = "Allow Postgres inbound traffic and all outbound traffic"
  vpc_id      = data.aws_vpc.main.id

  tags = {
    Name = "allow_postgres"
  }
}

# resource "aws_vpc_security_group_ingress_rule" "allow_postgres_ipv4" {
#   security_group_id = aws_security_group.allow_postgres.id
#   cidr_ipv4         = data.aws_vpc.main.cidr_block
#   from_port         = 5432
#   ip_protocol       = "tcp"
#   to_port           = 5432
# }
# Allow Postgres ONLY from the RDS Proxy SG
resource "aws_vpc_security_group_ingress_rule" "allow_postgres_from_proxy" {
  security_group_id            = aws_security_group.allow_postgres.id
  referenced_security_group_id = aws_security_group.pdex_rds_proxy_sg.id
  from_port                    = 5432
  to_port                      = 5432
  ip_protocol                  = "tcp"
  description                  = "Postgres only from RDS Proxy"
}


# resource "aws_vpc_security_group_egress_rule" "allow_all_traffic_ipv4a" {
#   security_group_id = aws_security_group.allow_postgres.id
#   cidr_ipv4         = "0.0.0.0/0"
#   ip_protocol       = "-1" # semantically equivalent to all ports
# }

resource "aws_security_group" "allow_redis" {
  name        = "allow_redis"
  description = "Allow Redis inbound traffic and all outbound traffic"
  vpc_id      = data.aws_vpc.main.id

  tags = {
    Name = "allow_redis"
  }
}

# resource "aws_vpc_security_group_ingress_rule" "allow_redis_ipv4" {
#   security_group_id = aws_security_group.allow_redis.id
#   cidr_ipv4         = data.aws_vpc.main.cidr_block
#   from_port         = 6379
#   ip_protocol       = "tcp"
#   to_port           = 6379
# }
#Redis: only from App SG (instead of whole VPC)
resource "aws_vpc_security_group_ingress_rule" "allow_redis_from_app" {
  security_group_id            = aws_security_group.allow_redis.id
  referenced_security_group_id = data.aws_security_group.app.id
  from_port                    = 6379
  to_port                      = 6379
  ip_protocol                  = "tcp"
  description                  = "Redis from app tier only"
}

# resource "aws_vpc_security_group_egress_rule" "allow_all_traffic_ipv4b" {
#   security_group_id = aws_security_group.allow_redis.id
#   cidr_ipv4         = "0.0.0.0/0"
#   ip_protocol       = "-1" # semantically equivalent to all ports
# }
resource "aws_vpc_security_group_egress_rule" "allow_postgres_egress_vpc_only" {
  security_group_id = aws_security_group.allow_postgres.id
  cidr_ipv4         = data.aws_vpc.main.cidr_block
  ip_protocol       = "-1"
  description       = "Limit DB egress to VPC only"
}


resource "aws_security_group" "allow_tls" {
  name        = "allow_tls"
  description = "Allow TLS inbound traffic"
  vpc_id      = data.aws_vpc.main.id

  tags = {
    Name = "allow_tls"
  }
}

resource "aws_vpc_security_group_ingress_rule" "allow_tls_ipv4" {
  security_group_id = aws_security_group.allow_tls.id
  cidr_ipv4         = data.aws_vpc.main.cidr_block
  from_port         = 443
  ip_protocol       = "tcp"
  to_port           = 443
}